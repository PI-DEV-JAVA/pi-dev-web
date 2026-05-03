<?php

namespace App\Tests\Entity;

use App\Entity\Offer;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Offer entity.
 * Covers: CRUD fields, recruiter relationship, status management, salary range.
 */
class OfferTest extends TestCase
{
    private Offer $offer;

    protected function setUp(): void
    {
        $this->offer = new Offer();
    }

    // ═══════════════════════════════════════════
    //  1. CRÉATION D'OFFRE — Champs de base
    // ═══════════════════════════════════════════

    public function testNewOfferHasDefaultValues(): void
    {
        $this->assertNull($this->offer->getId());
        $this->assertNull($this->offer->getTitle());
        $this->assertNull($this->offer->getDescription());
        $this->assertEquals(1, $this->offer->getPositionsAvailable());
        $this->assertEquals(0, $this->offer->getApplicationsReceived());
    }

    public function testSetAndGetTitle(): void
    {
        $this->offer->setTitle('Développeur Symfony Senior');
        $this->assertEquals('Développeur Symfony Senior', $this->offer->getTitle());
    }

    public function testSetAndGetDescription(): void
    {
        $this->offer->setDescription('Nous recherchons un dev Symfony avec 3 ans d\'expérience.');
        $this->assertStringContainsString('Symfony', $this->offer->getDescription());
    }

    public function testSetAndGetDepartment(): void
    {
        $this->offer->setDepartment('Informatique');
        $this->assertEquals('Informatique', $this->offer->getDepartment());
    }

    // ═══════════════════════════════════════════
    //  2. TYPE DE CONTRAT & EXPÉRIENCE
    // ═══════════════════════════════════════════

    public function testSetContractTypeCDI(): void
    {
        $this->offer->setContractType('CDI');
        $this->assertEquals('CDI', $this->offer->getContractType());
    }

    public function testSetContractTypeCDD(): void
    {
        $this->offer->setContractType('CDD');
        $this->assertEquals('CDD', $this->offer->getContractType());
    }

    public function testSetExperienceLevelJunior(): void
    {
        $this->offer->setExperienceLevel('Junior');
        $this->assertEquals('Junior', $this->offer->getExperienceLevel());
    }

    public function testSetExperienceLevelSenior(): void
    {
        $this->offer->setExperienceLevel('Senior');
        $this->assertEquals('Senior', $this->offer->getExperienceLevel());
    }

    // ═══════════════════════════════════════════
    //  3. SALAIRE — Plage min/max
    // ═══════════════════════════════════════════

    public function testSetSalaryRange(): void
    {
        $this->offer->setSalaryMin(2500.00);
        $this->offer->setSalaryMax(4000.00);
        $this->assertEquals(2500.00, $this->offer->getSalaryMin());
        $this->assertEquals(4000.00, $this->offer->getSalaryMax());
    }

    public function testSalaryMinIsLessThanMax(): void
    {
        $this->offer->setSalaryMin(1500.00);
        $this->offer->setSalaryMax(3000.00);
        $this->assertLessThan($this->offer->getSalaryMax(), $this->offer->getSalaryMin());
    }

    public function testSalaryCanBeNull(): void
    {
        $this->assertNull($this->offer->getSalaryMin());
        $this->assertNull($this->offer->getSalaryMax());
    }

    // ═══════════════════════════════════════════
    //  4. LOCALISATION & STATUT
    // ═══════════════════════════════════════════

    public function testSetLocation(): void
    {
        $this->offer->setLocation('Tunis, Tunisie');
        $this->assertEquals('Tunis, Tunisie', $this->offer->getLocation());
    }

    public function testSetStatusOuverte(): void
    {
        $this->offer->setStatus('Ouverte');
        $this->assertEquals('Ouverte', $this->offer->getStatus());
    }

    public function testSetStatusFermee(): void
    {
        $this->offer->setStatus('Fermée');
        $this->assertEquals('Fermée', $this->offer->getStatus());
    }

    // ═══════════════════════════════════════════
    //  5. DATES — Publication & Clôture
    // ═══════════════════════════════════════════

    public function testSetPublishDate(): void
    {
        $date = new \DateTime('2026-01-15');
        $this->offer->setPublishDate($date);
        $this->assertEquals($date, $this->offer->getPublishDate());
    }

    public function testSetClosingDate(): void
    {
        $date = new \DateTime('2026-03-15');
        $this->offer->setClosingDate($date);
        $this->assertEquals($date, $this->offer->getClosingDate());
    }

    public function testClosingDateIsAfterPublishDate(): void
    {
        $publish = new \DateTime('2026-01-01');
        $closing = new \DateTime('2026-02-01');
        $this->offer->setPublishDate($publish);
        $this->offer->setClosingDate($closing);
        $this->assertGreaterThan($this->offer->getPublishDate(), $this->offer->getClosingDate());
    }

    // ═══════════════════════════════════════════
    //  6. RECRUTEUR — Relation ManyToOne avec User
    // ═══════════════════════════════════════════

    public function testNewOfferHasNoRecruiter(): void
    {
        $this->assertNull($this->offer->getRecruiter());
    }

    public function testSetRecruiterAsUserEntity(): void
    {
        $hr = new User();
        $hr->setEmail('rh@talentos.tn');
        $hr->setRole('HR');

        $this->offer->setRecruiter($hr);
        $this->assertSame($hr, $this->offer->getRecruiter());
        $this->assertEquals('rh@talentos.tn', $this->offer->getRecruiter()->getEmail());
    }

    public function testGetRecruiterIdReturnsNullWhenNoRecruiter(): void
    {
        $this->assertNull($this->offer->getRecruiterId());
    }

    public function testRecruiterCanBeRemoved(): void
    {
        $hr = new User();
        $this->offer->setRecruiter($hr);
        $this->offer->setRecruiter(null);
        $this->assertNull($this->offer->getRecruiter());
    }

    // ═══════════════════════════════════════════
    //  7. CANDIDATURES — Compteurs
    // ═══════════════════════════════════════════

    public function testPositionsAvailableDefaultIsOne(): void
    {
        $this->assertEquals(1, $this->offer->getPositionsAvailable());
    }

    public function testSetPositionsAvailable(): void
    {
        $this->offer->setPositionsAvailable(5);
        $this->assertEquals(5, $this->offer->getPositionsAvailable());
    }

    public function testApplicationsReceivedDefaultIsZero(): void
    {
        $this->assertEquals(0, $this->offer->getApplicationsReceived());
    }

    public function testIncrementApplicationsReceived(): void
    {
        $this->offer->setApplicationsReceived(3);
        $this->assertEquals(3, $this->offer->getApplicationsReceived());
    }

    public function testApplicationsCollectionIsInitialized(): void
    {
        $this->assertCount(0, $this->offer->getApplications());
    }

    // ═══════════════════════════════════════════
    //  8. FLUENT API — Chainage de méthodes
    // ═══════════════════════════════════════════

    public function testFluentSetters(): void
    {
        $result = $this->offer
            ->setTitle('Dev PHP')
            ->setDepartment('IT')
            ->setContractType('CDI')
            ->setLocation('Tunis')
            ->setStatus('Ouverte');

        $this->assertInstanceOf(Offer::class, $result);
        $this->assertEquals('Dev PHP', $result->getTitle());
    }
}
