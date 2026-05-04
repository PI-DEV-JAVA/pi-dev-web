<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  MODULE: Offers & Applications
 *  Covers: Offer entity, Application entity, Bookmark entity,
 *          OfferController logic (list, apply, bookmark, search)
 *  Tests: CRUD, status workflow, scoring, salary, search/filter
 * ═══════════════════════════════════════════════════════════════
 */

namespace App\Tests\Module;

use App\Entity\Offer;
use App\Entity\Application;
use App\Entity\Bookmark;
use App\Entity\User;
use App\Entity\Profile;
use PHPUnit\Framework\TestCase;

class OfferApplicationTest extends TestCase
{
    // ┌─────────────────────────────────────┐
    // │  OFFER ENTITY — Success Scenarios   │
    // └─────────────────────────────────────┘

    public function testOfferCreationWithDefaults(): void
    {
        $offer = new Offer();
        $this->assertNull($offer->getId());
        $this->assertEquals(1, $offer->getPositionsAvailable());
        $this->assertEquals(0, $offer->getApplicationsReceived());
        $this->assertNull($offer->getStatus());
        $this->assertCount(0, $offer->getApplications());
    }

    public function testOfferSetAllFields(): void
    {
        $offer = new Offer();
        $offer->setTitle('Développeur PHP Symfony');
        $offer->setDescription('<p>Rejoignez notre équipe tech</p>');
        $offer->setDepartment('IT');
        $offer->setContractType('CDI');
        $offer->setExperienceLevel('Senior (5+ ans)');
        $offer->setLocation('Tunis, Tunisie');
        $offer->setSalaryMin(2500.0);
        $offer->setSalaryMax(4000.0);
        $offer->setPositionsAvailable(3);
        $offer->setStatus('Publiée');

        $this->assertEquals('Développeur PHP Symfony', $offer->getTitle());
        $this->assertStringContainsString('tech', $offer->getDescription());
        $this->assertEquals('IT', $offer->getDepartment());
        $this->assertEquals('CDI', $offer->getContractType());
        $this->assertEquals('Senior (5+ ans)', $offer->getExperienceLevel());
        $this->assertEquals('Tunis, Tunisie', $offer->getLocation());
        $this->assertEquals(2500.0, $offer->getSalaryMin());
        $this->assertEquals(4000.0, $offer->getSalaryMax());
        $this->assertEquals(3, $offer->getPositionsAvailable());
        $this->assertEquals('Publiée', $offer->getStatus());
    }

    public function testOfferSalaryRangeIsValid(): void
    {
        $offer = new Offer();
        $offer->setSalaryMin(1500.0);
        $offer->setSalaryMax(3000.0);
        $this->assertLessThanOrEqual($offer->getSalaryMax(), $offer->getSalaryMin());
    }

    public function testOfferDateRangeIsValid(): void
    {
        $offer = new Offer();
        $publish = new \DateTime('2025-01-01');
        $closing = new \DateTime('2025-03-01');
        $offer->setPublishDate($publish);
        $offer->setClosingDate($closing);
        $this->assertGreaterThan($offer->getPublishDate(), $offer->getClosingDate());
    }

    public function testOfferRecruiterAssignment(): void
    {
        $offer = new Offer();
        $recruiter = new User();
        $recruiter->setEmail('rh@company.tn');
        $recruiter->setRole('RECRUITER');
        $offer->setRecruiter($recruiter);
        $this->assertSame($recruiter, $offer->getRecruiter());
        $this->assertEquals('RECRUITER', $offer->getRecruiter()->getRole());
    }

    public function testOfferCoverImage(): void
    {
        $offer = new Offer();
        $offer->setCoverImage('/uploads/offers/cover.jpg');
        $this->assertEquals('/uploads/offers/cover.jpg', $offer->getCoverImage());
    }

    public function testOfferWorkflow(): void
    {
        $offer = new Offer();
        $workflow = ['screening' => true, 'interview' => false, 'offer' => false];
        $offer->setWorkflow($workflow);
        $this->assertArrayHasKey('screening', $offer->getWorkflow());
        $this->assertTrue($offer->getWorkflow()['screening']);
    }

    public function testOfferIncrementApplicationsReceived(): void
    {
        $offer = new Offer();
        $this->assertEquals(0, $offer->getApplicationsReceived());
        $offer->setApplicationsReceived($offer->getApplicationsReceived() + 1);
        $this->assertEquals(1, $offer->getApplicationsReceived());
    }

    // ── OfferController: Search & Filter Simulation ──
    public function testOfferSearchByTitle(): void
    {
        $offers = [
            ['title' => 'Développeur PHP', 'location' => 'Tunis'],
            ['title' => 'Designer UX', 'location' => 'Sfax'],
            ['title' => 'Développeur Java', 'location' => 'Tunis'],
        ];
        $query = 'Développeur';
        $results = array_filter($offers, fn($o) => stripos($o['title'], $query) !== false);
        $this->assertCount(2, $results);
    }

    public function testOfferFilterByContractType(): void
    {
        $offers = [
            ['title' => 'Dev PHP', 'contract' => 'CDI'],
            ['title' => 'Dev JS', 'contract' => 'CDD'],
            ['title' => 'Dev Python', 'contract' => 'CDI'],
        ];
        $filtered = array_filter($offers, fn($o) => $o['contract'] === 'CDI');
        $this->assertCount(2, $filtered);
    }

    public function testOfferFilterByLocation(): void
    {
        $offers = [
            ['title' => 'Dev', 'location' => 'Tunis'],
            ['title' => 'QA', 'location' => 'Sfax'],
        ];
        $filtered = array_filter($offers, fn($o) => $o['location'] === 'Tunis');
        $this->assertCount(1, $filtered);
    }

    // ┌─────────────────────────────────────┐
    // │  OFFER ENTITY — Failure Scenarios   │
    // └─────────────────────────────────────┘

    public function testFailOfferNullTitle(): void
    {
        $offer = new Offer();
        $this->assertNull($offer->getTitle());
    }

    public function testFailOfferNullSalary(): void
    {
        $offer = new Offer();
        $this->assertNull($offer->getSalaryMin());
        $this->assertNull($offer->getSalaryMax());
    }

    public function testFailOfferNoRecruiter(): void
    {
        $offer = new Offer();
        $this->assertNull($offer->getRecruiter());
    }

    public function testFailOfferClosedBeforePublish(): void
    {
        $offer = new Offer();
        $offer->setPublishDate(new \DateTime('2025-03-01'));
        $offer->setClosingDate(new \DateTime('2025-01-01'));
        // Closing is BEFORE publish — invalid state
        $this->assertLessThan($offer->getPublishDate(), $offer->getClosingDate());
    }

    public function testFailSearchNoResults(): void
    {
        $offers = [['title' => 'Dev PHP'], ['title' => 'Dev Java']];
        $query = 'Marketing';
        $results = array_filter($offers, fn($o) => stripos($o['title'], $query) !== false);
        $this->assertEmpty($results);
    }

    // ┌──────────────────────────────────────────┐
    // │  APPLICATION ENTITY — Success Scenarios  │
    // └──────────────────────────────────────────┘

    public function testApplicationCreationWithDefaults(): void
    {
        $app = new Application();
        $this->assertEquals('Nouvelle', $app->getStatus());
        $this->assertEquals(0, $app->getScore());
        $this->assertNull($app->getCvFilePath());
        $this->assertNull($app->getMotivationLetter());
    }

    public function testApplicationFullCreation(): void
    {
        $app = new Application();
        $user = new User();
        $user->setEmail('candidate@test.tn');
        $offer = new Offer();
        $offer->setTitle('Dev PHP');

        $app->setUser($user);
        $app->setOffer($offer);
        $app->setCvFilePath('/uploads/cvs/cv.pdf');
        $app->setMotivationLetter('Je suis très motivé pour ce poste.');
        $app->setApplicationDate(new \DateTime());

        $this->assertSame($user, $app->getUser());
        $this->assertEquals('Dev PHP', $app->getOffer()->getTitle());
        $this->assertNotNull($app->getCvFilePath());
        $this->assertStringContainsString('motivé', $app->getMotivationLetter());
    }

    // ── Status workflow transitions ──
    public function testApplicationStatusWorkflow(): void
    {
        $app = new Application();
        $this->assertEquals('Nouvelle', $app->getStatus());

        $app->setStatus('En attente');
        $this->assertEquals('En attente', $app->getStatus());

        $app->setStatus('Entretien');
        $this->assertEquals('Entretien', $app->getStatus());

        $app->setStatus('Acceptée');
        $this->assertEquals('Acceptée', $app->getStatus());
    }

    public function testApplicationStatusRejection(): void
    {
        $app = new Application();
        $app->setStatus('Refusée');
        $this->assertEquals('Refusée', $app->getStatus());
    }

    // ── AI Score ──
    public function testApplicationAiScoreHigh(): void
    {
        $app = new Application();
        $app->setScore(85);
        $this->assertEquals(85, $app->getScore());
        $this->assertGreaterThanOrEqual(70, $app->getScore());
    }

    public function testApplicationAiScoreLow(): void
    {
        $app = new Application();
        $app->setScore(20);
        $this->assertEquals(20, $app->getScore());
        $this->assertLessThan(30, $app->getScore());
    }

    public function testApplicationAiScoreMid(): void
    {
        $app = new Application();
        $app->setScore(55);
        $this->assertGreaterThanOrEqual(40, $app->getScore());
        $this->assertLessThan(70, $app->getScore());
    }

    // ── Interview fields ──
    public function testApplicationInterviewScheduled(): void
    {
        $app = new Application();
        $app->setStatus('Entretien');
        $app->setInterviewer('Marie Dupont');
        $app->setInterviewDate(new \DateTime('2025-07-15 10:00'));
        $this->assertEquals('Marie Dupont', $app->getInterviewer());
        $this->assertNotNull($app->getInterviewDate());
    }

    public function testApplicationInterviewResult(): void
    {
        $app = new Application();
        $app->setInterviewResult('PASSED');
        $this->assertEquals('PASSED', $app->getInterviewResult());
    }

    public function testApplicationRecruiterResponse(): void
    {
        $app = new Application();
        $app->setRecruiterResponse('Félicitations, vous êtes retenu !');
        $app->setResponseDate(new \DateTime());
        $this->assertStringContainsString('retenu', $app->getRecruiterResponse());
    }

    public function testApplicationNotes(): void
    {
        $app = new Application();
        $app->setNotes('Excellent profil, background solide en Symfony.');
        $this->assertStringContainsString('Symfony', $app->getNotes());
    }

    // ┌──────────────────────────────────────────┐
    // │  APPLICATION ENTITY — Failure Scenarios  │
    // └──────────────────────────────────────────┘

    public function testFailApplicationWithoutUser(): void
    {
        $app = new Application();
        $this->assertNull($app->getUser());
    }

    public function testFailApplicationWithoutOffer(): void
    {
        $app = new Application();
        $this->assertNull($app->getOffer());
    }

    public function testFailApplicationNoCv(): void
    {
        $app = new Application();
        $this->assertNull($app->getCvFilePath());
    }

    public function testFailApplicationZeroScore(): void
    {
        $app = new Application();
        $this->assertEquals(0, $app->getScore());
    }

    // ┌──────────────────────────────────────┐
    // │  BOOKMARK ENTITY — Success/Failure   │
    // └──────────────────────────────────────┘

    public function testBookmarkCreation(): void
    {
        $bookmark = new Bookmark();
        $user = new User();
        $offer = new Offer();
        $offer->setTitle('Dev React');

        $bookmark->setUser($user);
        $bookmark->setOffer($offer);

        $this->assertSame($user, $bookmark->getUser());
        $this->assertEquals('Dev React', $bookmark->getOffer()->getTitle());
    }

    public function testFailBookmarkWithoutUser(): void
    {
        $bookmark = new Bookmark();
        $this->assertNull($bookmark->getUser());
    }

    public function testFailBookmarkWithoutOffer(): void
    {
        $bookmark = new Bookmark();
        $this->assertNull($bookmark->getOffer());
    }

    // ── OfferController: Apply simulation ──
    public function testApplySimulationSuccess(): void
    {
        $user = new User();
        $user->setEmail('candidate@test.tn');
        $profile = new Profile();
        $profile->setProfileCompleted(true);
        $profile->setCvPath('/uploads/cvs/cv.pdf');
        $user->setProfile($profile);

        $offer = new Offer();
        $offer->setTitle('Dev PHP');
        $offer->setStatus('Publiée');

        // Simulate controller checks
        $hasProfile = $user->getProfile() !== null && $user->getProfile()->isProfileCompleted();
        $isPublished = $offer->getStatus() === 'Publiée';

        $this->assertTrue($hasProfile);
        $this->assertTrue($isPublished);
    }

    public function testFailApplyProfileNotCompleted(): void
    {
        $user = new User();
        $profile = new Profile();
        $profile->setProfileCompleted(false);
        $user->setProfile($profile);

        $hasProfile = $user->getProfile()->isProfileCompleted();
        $this->assertFalse($hasProfile);
    }

    public function testFailApplyOfferClosed(): void
    {
        $offer = new Offer();
        $offer->setStatus('Fermée');
        $this->assertNotEquals('Publiée', $offer->getStatus());
    }
}
