<?php

namespace App\Tests\Entity;

use App\Entity\Offer;
use App\Entity\Application;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class OfferTest extends TestCase
{
    private Offer $offer;

    protected function setUp(): void
    {
        $this->offer = new Offer();
    }

    // ── Defaults ──
    public function testDefaultPositionsAvailable(): void
    {
        $this->assertEquals(1, $this->offer->getPositionsAvailable());
    }

    public function testDefaultApplicationsReceived(): void
    {
        $this->assertEquals(0, $this->offer->getApplicationsReceived());
    }

    // ── Basic fields ──
    public function testSetGetTitle(): void
    {
        $this->offer->setTitle('Développeur PHP Senior');
        $this->assertEquals('Développeur PHP Senior', $this->offer->getTitle());
    }

    public function testSetGetDescription(): void
    {
        $this->offer->setDescription('<p>Rejoignez notre équipe</p>');
        $this->assertEquals('<p>Rejoignez notre équipe</p>', $this->offer->getDescription());
    }

    public function testSetGetDepartment(): void
    {
        $this->offer->setDepartment('Informatique');
        $this->assertEquals('Informatique', $this->offer->getDepartment());
    }

    public function testSetGetContractType(): void
    {
        $this->offer->setContractType('CDI');
        $this->assertEquals('CDI', $this->offer->getContractType());
    }

    public function testSetGetExperienceLevel(): void
    {
        $this->offer->setExperienceLevel('Senior (5+ ans)');
        $this->assertEquals('Senior (5+ ans)', $this->offer->getExperienceLevel());
    }

    public function testSetGetLocation(): void
    {
        $this->offer->setLocation('Tunis');
        $this->assertEquals('Tunis', $this->offer->getLocation());
    }

    public function testSetGetStatus(): void
    {
        $this->offer->setStatus('Publiée');
        $this->assertEquals('Publiée', $this->offer->getStatus());
    }

    // ── Salary ──
    public function testSetGetSalaryMin(): void
    {
        $this->offer->setSalaryMin(2000.0);
        $this->assertEquals(2000.0, $this->offer->getSalaryMin());
    }

    public function testSetGetSalaryMax(): void
    {
        $this->offer->setSalaryMax(4000.0);
        $this->assertEquals(4000.0, $this->offer->getSalaryMax());
    }

    public function testSalaryRangeValid(): void
    {
        $this->offer->setSalaryMin(1500.0);
        $this->offer->setSalaryMax(3000.0);
        $this->assertLessThanOrEqual($this->offer->getSalaryMax(), $this->offer->getSalaryMin());
    }

    public function testNullSalary(): void
    {
        $this->assertNull($this->offer->getSalaryMin());
        $this->assertNull($this->offer->getSalaryMax());
    }

    // ── Dates ──
    public function testSetGetPublishDate(): void
    {
        $date = new \DateTime('2025-01-15');
        $this->offer->setPublishDate($date);
        $this->assertSame($date, $this->offer->getPublishDate());
    }

    public function testSetGetClosingDate(): void
    {
        $date = new \DateTime('2025-03-01');
        $this->offer->setClosingDate($date);
        $this->assertSame($date, $this->offer->getClosingDate());
    }

    public function testClosingDateAfterPublishDate(): void
    {
        $publish = new \DateTime('2025-01-01');
        $closing = new \DateTime('2025-02-01');
        $this->offer->setPublishDate($publish);
        $this->offer->setClosingDate($closing);
        $this->assertGreaterThan($this->offer->getPublishDate(), $this->offer->getClosingDate());
    }

    // ── Positions ──
    public function testSetGetPositionsAvailable(): void
    {
        $this->offer->setPositionsAvailable(5);
        $this->assertEquals(5, $this->offer->getPositionsAvailable());
    }

    public function testSetGetApplicationsReceived(): void
    {
        $this->offer->setApplicationsReceived(12);
        $this->assertEquals(12, $this->offer->getApplicationsReceived());
    }

    // ── Recruiter ──
    public function testSetGetRecruiter(): void
    {
        $user = new User();
        $user->setEmail('rh@talentos.tn');
        $this->offer->setRecruiter($user);
        $this->assertSame($user, $this->offer->getRecruiter());
        $this->assertEquals('rh@talentos.tn', $this->offer->getRecruiter()->getEmail());
    }

    public function testNullRecruiter(): void
    {
        $this->assertNull($this->offer->getRecruiter());
    }

    // ── Cover Image ──
    public function testSetGetCoverImage(): void
    {
        $this->offer->setCoverImage('/uploads/offers/cover.jpg');
        $this->assertEquals('/uploads/offers/cover.jpg', $this->offer->getCoverImage());
    }

    // ── Workflow ──
    public function testSetGetWorkflow(): void
    {
        $wf = ['step1' => 'Screening', 'step2' => 'Interview'];
        $this->offer->setWorkflow($wf);
        $this->assertEquals($wf, $this->offer->getWorkflow());
    }

    // ── Applications collection ──
    public function testApplicationsCollectionInitialized(): void
    {
        $this->assertCount(0, $this->offer->getApplications());
    }
}
