<?php

namespace App\Tests\Entity;

use App\Entity\Event;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    private Event $event;

    protected function setUp(): void
    {
        $this->event = new Event();
    }

    // ── Defaults ──
    public function testDefaultEventType(): void
    {
        $this->assertEquals('MEETUP', $this->event->getEventType());
    }

    public function testDefaultStatus(): void
    {
        $this->assertEquals('UPCOMING', $this->event->getStatus());
    }

    public function testDefaultIsOnline(): void
    {
        $this->assertFalse($this->event->isOnline());
    }

    public function testDefaultMaxCapacity(): void
    {
        $this->assertEquals(0, $this->event->getMaxCapacity());
    }

    // ── Basic fields ──
    public function testSetGetTitle(): void
    {
        $this->event->setTitle('Workshop Symfony');
        $this->assertEquals('Workshop Symfony', $this->event->getTitle());
    }

    public function testSetGetDescription(): void
    {
        $this->event->setDescription('Apprenez Symfony en 3h');
        $this->assertEquals('Apprenez Symfony en 3h', $this->event->getDescription());
    }

    public function testSetGetEventType(): void
    {
        $this->event->setEventType('CONFERENCE');
        $this->assertEquals('CONFERENCE', $this->event->getEventType());
    }

    public function testSetGetLocation(): void
    {
        $this->event->setLocation('Technopôle El Ghazala');
        $this->assertEquals('Technopôle El Ghazala', $this->event->getLocation());
    }

    // ── Online event ──
    public function testOnlineEvent(): void
    {
        $this->event->setIsOnline(true);
        $this->event->setOnlineLink('https://meet.google.com/abc');
        $this->assertTrue($this->event->isOnline());
        $this->assertEquals('https://meet.google.com/abc', $this->event->getOnlineLink());
    }

    // ── Dates ──
    public function testSetGetEventDate(): void
    {
        $date = new \DateTime('2025-06-20 14:00');
        $this->event->setEventDate($date);
        $this->assertSame($date, $this->event->getEventDate());
    }

    public function testSetGetEndDate(): void
    {
        $date = new \DateTime('2025-06-20 17:00');
        $this->event->setEndDate($date);
        $this->assertSame($date, $this->event->getEndDate());
    }

    public function testEndDateAfterStartDate(): void
    {
        $start = new \DateTime('2025-06-20 14:00');
        $end = new \DateTime('2025-06-20 17:00');
        $this->event->setEventDate($start);
        $this->event->setEndDate($end);
        $this->assertGreaterThan($this->event->getEventDate(), $this->event->getEndDate());
    }

    // ── Capacity ──
    public function testSetGetMaxCapacity(): void
    {
        $this->event->setMaxCapacity(100);
        $this->assertEquals(100, $this->event->getMaxCapacity());
    }

    // ── Geolocation ──
    public function testSetGetLatitude(): void
    {
        $this->event->setLatitude(36.8065);
        $this->assertEquals(36.8065, $this->event->getLatitude());
    }

    public function testSetGetLongitude(): void
    {
        $this->event->setLongitude(10.1815);
        $this->assertEquals(10.1815, $this->event->getLongitude());
    }

    // ── Organizer ──
    public function testSetGetOrganizer(): void
    {
        $user = new User();
        $this->event->setOrganizer($user);
        $this->assertSame($user, $this->event->getOrganizer());
    }

    // ── Cover Image ──
    public function testSetGetCoverImage(): void
    {
        $this->event->setCoverImage('/uploads/events/cover.jpg');
        $this->assertEquals('/uploads/events/cover.jpg', $this->event->getCoverImage());
    }

    // ── Status transitions ──
    public function testStatusCompleted(): void
    {
        $this->event->setStatus('COMPLETED');
        $this->assertEquals('COMPLETED', $this->event->getStatus());
    }

    public function testStatusCancelled(): void
    {
        $this->event->setStatus('CANCELLED');
        $this->assertEquals('CANCELLED', $this->event->getStatus());
    }

    // ── Collections ──
    public function testParticipationsInitialized(): void
    {
        $this->assertCount(0, $this->event->getParticipations());
    }

    public function testLikesInitialized(): void
    {
        $this->assertCount(0, $this->event->getLikes());
    }

    public function testCommentsInitialized(): void
    {
        $this->assertCount(0, $this->event->getComments());
    }
}
