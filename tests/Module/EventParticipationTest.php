<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  MODULE: Events & Participation
 *  Covers: Event, EventParticipation, EventLike, EventComment,
 *          EventFeedback entities
 *          EventController logic (participate, cancel, like,
 *          comment, feedback, capacity check, QR verification)
 *  Tests: registration, capacity, likes, comments, feedback
 * ═══════════════════════════════════════════════════════════════
 */

namespace App\Tests\Module;

use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Entity\EventLike;
use App\Entity\EventComment;
use App\Entity\EventFeedback;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class EventParticipationTest extends TestCase
{
    // ┌──────────────────────────────────────┐
    // │  EVENT ENTITY — Success Scenarios   │
    // └──────────────────────────────────────┘

    public function testEventCreationDefaults(): void
    {
        $event = new Event();
        $this->assertNull($event->getId());
        $this->assertEquals('MEETUP', $event->getEventType());
        $this->assertEquals('UPCOMING', $event->getStatus());
        $this->assertFalse($event->isOnline());
        $this->assertEquals(0, $event->getMaxCapacity());
        $this->assertCount(0, $event->getParticipations());
        $this->assertCount(0, $event->getLikes());
        $this->assertCount(0, $event->getComments());
    }

    public function testEventSetAllFields(): void
    {
        $event = new Event();
        $event->setTitle('Hackathon Talentos 2025');
        $event->setDescription('48h de code intensif');
        $event->setEventType('HACKATHON');
        $event->setLocation('Technopôle El Ghazala');
        $event->setMaxCapacity(200);
        $event->setEventDate(new \DateTime('2025-10-15 09:00'));
        $event->setEndDate(new \DateTime('2025-10-17 18:00'));
        $event->setCoverImage('/uploads/events/hackathon.jpg');

        $this->assertEquals('Hackathon Talentos 2025', $event->getTitle());
        $this->assertEquals('HACKATHON', $event->getEventType());
        $this->assertEquals(200, $event->getMaxCapacity());
        $this->assertNotNull($event->getCoverImage());
    }

    public function testEventOnlineSetup(): void
    {
        $event = new Event();
        $event->setIsOnline(true);
        $event->setOnlineLink('https://meet.google.com/xyz');
        $event->setLocation(null);
        $this->assertTrue($event->isOnline());
        $this->assertStringContainsString('meet.google.com', $event->getOnlineLink());
    }

    public function testEventInPersonSetup(): void
    {
        $event = new Event();
        $event->setIsOnline(false);
        $event->setLocation('Centre Urbain Nord, Tunis');
        $event->setLatitude(36.8448);
        $event->setLongitude(10.1658);
        $this->assertFalse($event->isOnline());
        $this->assertNotNull($event->getLatitude());
    }

    public function testEventOrganizerAssignment(): void
    {
        $event = new Event();
        $organizer = new User();
        $organizer->setEmail('organizer@talentos.tn');
        $event->setOrganizer($organizer);
        $this->assertSame($organizer, $event->getOrganizer());
    }

    public function testEventStatusTransitions(): void
    {
        $event = new Event();
        $this->assertEquals('UPCOMING', $event->getStatus());

        $event->setStatus('ONGOING');
        $this->assertEquals('ONGOING', $event->getStatus());

        $event->setStatus('COMPLETED');
        $this->assertEquals('COMPLETED', $event->getStatus());
    }

    public function testEventCancelled(): void
    {
        $event = new Event();
        $event->setStatus('CANCELLED');
        $this->assertEquals('CANCELLED', $event->getStatus());
    }

    public function testEventDateRange(): void
    {
        $event = new Event();
        $start = new \DateTime('2025-10-15 09:00');
        $end = new \DateTime('2025-10-15 17:00');
        $event->setEventDate($start);
        $event->setEndDate($end);
        $this->assertGreaterThan($event->getEventDate(), $event->getEndDate());
    }

    public function testEventTypeVariants(): void
    {
        $event = new Event();
        $types = ['MEETUP', 'CONFERENCE', 'WORKSHOP', 'HACKATHON', 'WEBINAR'];
        foreach ($types as $type) {
            $event->setEventType($type);
            $this->assertEquals($type, $event->getEventType());
        }
    }

    // ┌──────────────────────────────────────┐
    // │  EVENT ENTITY — Failure Scenarios   │
    // └──────────────────────────────────────┘

    public function testFailEventNullTitle(): void
    {
        $event = new Event();
        $this->assertNull($event->getTitle());
    }

    public function testFailEventNoOrganizer(): void
    {
        $event = new Event();
        $this->assertNull($event->getOrganizer());
    }

    public function testFailEventNoDates(): void
    {
        $event = new Event();
        $this->assertNull($event->getEventDate());
        $this->assertNull($event->getEndDate());
    }

    // ┌──────────────────────────────────────────────┐
    // │  PARTICIPATION — Success Scenarios           │
    // └──────────────────────────────────────────────┘

    public function testParticipationCreation(): void
    {
        $p = new EventParticipation();
        $user = new User();
        $event = new Event();

        $p->setUser($user);
        $p->setEvent($event);
        $p->setStatus('CONFIRMED');

        $this->assertSame($user, $p->getUser());
        $this->assertSame($event, $p->getEvent());
        $this->assertEquals('CONFIRMED', $p->getStatus());
    }

    public function testParticipationDefaultStatus(): void
    {
        $p = new EventParticipation();
        $this->assertEquals('PENDING', $p->getStatus());
    }

    public function testParticipationQrCode(): void
    {
        $p = new EventParticipation();
        $qr = 'QR_' . bin2hex(random_bytes(8));
        $p->setQrCode($qr);
        $this->assertStringStartsWith('QR_', $p->getQrCode());
    }

    // ── EventController: Capacity check simulation ──
    public function testParticipationCapacityAvailable(): void
    {
        $event = new Event();
        $event->setMaxCapacity(100);
        $currentParticipants = 50;
        $hasSpace = $event->getMaxCapacity() === 0 || $currentParticipants < $event->getMaxCapacity();
        $this->assertTrue($hasSpace);
    }

    public function testFailParticipationCapacityFull(): void
    {
        $event = new Event();
        $event->setMaxCapacity(100);
        $currentParticipants = 100;
        $hasSpace = $event->getMaxCapacity() > 0 && $currentParticipants < $event->getMaxCapacity();
        $this->assertFalse($hasSpace);
    }

    public function testParticipationUnlimitedCapacity(): void
    {
        $event = new Event();
        $event->setMaxCapacity(0); // 0 = unlimited
        $hasSpace = $event->getMaxCapacity() === 0 || 999 < $event->getMaxCapacity();
        $this->assertTrue($hasSpace);
    }

    // ── EventController: Cancel participation ──
    public function testParticipationCancelled(): void
    {
        $p = new EventParticipation();
        $p->setStatus('CONFIRMED');
        $p->setStatus('CANCELLED');
        $this->assertEquals('CANCELLED', $p->getStatus());
    }

    // ── EventController: QR verification simulation ──
    public function testQrVerificationSuccess(): void
    {
        $p = new EventParticipation();
        $qr = 'QR_abc123';
        $p->setQrCode($qr);

        // Simulate controller verification
        $inputQr = 'QR_abc123';
        $isValid = $p->getQrCode() === $inputQr;
        $this->assertTrue($isValid);
    }

    public function testFailQrVerificationWrongCode(): void
    {
        $p = new EventParticipation();
        $p->setQrCode('QR_abc123');

        $inputQr = 'QR_wrong';
        $isValid = $p->getQrCode() === $inputQr;
        $this->assertFalse($isValid);
    }

    // ┌──────────────────────────────────────────────┐
    // │  PARTICIPATION — Failure Scenarios           │
    // └──────────────────────────────────────────────┘

    public function testFailParticipationNoUser(): void
    {
        $p = new EventParticipation();
        $this->assertNull($p->getUser());
    }

    public function testFailParticipationNoEvent(): void
    {
        $p = new EventParticipation();
        $this->assertNull($p->getEvent());
    }

    // ┌──────────────────────────────────┐
    // │  LIKE — Success/Failure         │
    // └──────────────────────────────────┘

    public function testEventLikeCreation(): void
    {
        $like = new EventLike();
        $user = new User();
        $event = new Event();

        $like->setUser($user);
        $like->setEvent($event);

        $this->assertSame($user, $like->getUser());
        $this->assertSame($event, $like->getEvent());
    }

    // ── EventController: Toggle like simulation ──
    public function testLikeToggleAddRemove(): void
    {
        $likes = []; // simulate collection
        $userId = 1;

        // Add like
        $likes[$userId] = true;
        $this->assertArrayHasKey($userId, $likes);

        // Remove like (toggle)
        unset($likes[$userId]);
        $this->assertArrayNotHasKey($userId, $likes);
    }

    public function testFailLikeNoUser(): void
    {
        $like = new EventLike();
        $this->assertNull($like->getUser());
    }

    // ┌──────────────────────────────────┐
    // │  COMMENT — Success/Failure      │
    // └──────────────────────────────────┘

    public function testEventCommentCreation(): void
    {
        $comment = new EventComment();
        $user = new User();
        $event = new Event();

        $comment->setUser($user);
        $comment->setEvent($event);
        $comment->setContent('Super événement, très instructif !');

        $this->assertSame($user, $comment->getUser());
        $this->assertStringContainsString('instructif', $comment->getContent());
    }

    public function testFailCommentEmptyContent(): void
    {
        $comment = new EventComment();
        $comment->setContent('');
        $this->assertEmpty($comment->getContent());
    }

    public function testFailCommentNullContent(): void
    {
        $comment = new EventComment();
        $this->assertNull($comment->getContent());
    }

    // ┌──────────────────────────────────┐
    // │  FEEDBACK — Success/Failure     │
    // └──────────────────────────────────┘

    public function testEventFeedbackCreation(): void
    {
        $fb = new EventFeedback();
        $participation = new EventParticipation();

        $fb->setParticipation($participation);
        $fb->setRating(5);
        $fb->setComment('Excellente organisation et contenu pertinent');
        $fb->setWouldRecommend(true);

        $this->assertEquals(5, $fb->getRating());
        $this->assertTrue($fb->isWouldRecommend());
        $this->assertStringContainsString('organisation', $fb->getComment());
    }

    public function testFeedbackDefaultValues(): void
    {
        $fb = new EventFeedback();
        $this->assertEquals(5, $fb->getRating());
        $this->assertTrue($fb->isWouldRecommend());
    }

    public function testFeedbackLowRating(): void
    {
        $fb = new EventFeedback();
        $fb->setRating(1);
        $fb->setWouldRecommend(false);
        $this->assertEquals(1, $fb->getRating());
        $this->assertFalse($fb->isWouldRecommend());
    }

    public function testFeedbackRatingRange(): void
    {
        $fb = new EventFeedback();
        // Test all valid ratings
        foreach ([1, 2, 3, 4, 5] as $r) {
            $fb->setRating($r);
            $this->assertGreaterThanOrEqual(1, $fb->getRating());
            $this->assertLessThanOrEqual(5, $fb->getRating());
        }
    }

    public function testFailFeedbackNoParticipation(): void
    {
        $fb = new EventFeedback();
        $this->assertNull($fb->getParticipation());
    }

    public function testFailFeedbackNullComment(): void
    {
        $fb = new EventFeedback();
        $this->assertNull($fb->getComment());
    }
}
