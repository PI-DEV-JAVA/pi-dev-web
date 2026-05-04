<?php

namespace App\Tests\Entity;

use App\Entity\Formation;
use PHPUnit\Framework\TestCase;

class FormationTest extends TestCase
{
    private Formation $formation;

    protected function setUp(): void
    {
        $this->formation = new Formation();
    }

    // ── Defaults ──
    public function testDefaultIsPaid(): void
    {
        $this->assertFalse($this->formation->isPaid());
    }

    // ── Basic fields ──
    public function testSetGetTitre(): void
    {
        $this->formation->setTitre('Formation Symfony Avancé');
        $this->assertEquals('Formation Symfony Avancé', $this->formation->getTitre());
    }

    public function testSetGetDescription(): void
    {
        $this->formation->setDescription('Apprenez Symfony de A à Z');
        $this->assertEquals('Apprenez Symfony de A à Z', $this->formation->getDescription());
    }

    public function testSetGetNiveau(): void
    {
        $this->formation->setNiveau('Avancé');
        $this->assertEquals('Avancé', $this->formation->getNiveau());
    }

    public function testSetGetDuree(): void
    {
        $this->formation->setDuree(40);
        $this->assertEquals(40, $this->formation->getDuree());
    }

    // ── Paid course ──
    public function testSetIsPaid(): void
    {
        $this->formation->setIsPaid(true);
        $this->assertTrue($this->formation->isPaid());
    }

    public function testSetGetPricePoints(): void
    {
        $this->formation->setPricePoints(500);
        $this->assertEquals(500, $this->formation->getPricePoints());
    }

    public function testFreeCoursePriceNull(): void
    {
        $this->assertNull($this->formation->getPricePoints());
    }

    // ── Dates ──
    public function testSetGetDateDebut(): void
    {
        $date = new \DateTime('2025-09-01');
        $this->formation->setDateDebut($date);
        $this->assertSame($date, $this->formation->getDateDebut());
    }

    public function testSetGetDateFin(): void
    {
        $date = new \DateTime('2025-12-01');
        $this->formation->setDateFin($date);
        $this->assertSame($date, $this->formation->getDateFin());
    }

    public function testDateFinAfterDateDebut(): void
    {
        $start = new \DateTime('2025-09-01');
        $end = new \DateTime('2025-12-01');
        $this->formation->setDateDebut($start);
        $this->formation->setDateFin($end);
        $this->assertGreaterThan($this->formation->getDateDebut(), $this->formation->getDateFin());
    }

    // ── Image ──
    public function testSetGetImage(): void
    {
        $this->formation->setImage('/uploads/formations/img.jpg');
        $this->assertEquals('/uploads/formations/img.jpg', $this->formation->getImage());
    }

    // ── Recruiter ──
    public function testSetGetRecruiterId(): void
    {
        $this->formation->setRecruiterId(42);
        $this->assertEquals(42, $this->formation->getRecruiterId());
    }

    // ── Collections ──
    public function testSeancesInitialized(): void
    {
        $this->assertCount(0, $this->formation->getSeances());
    }

    public function testQuizzesInitialized(): void
    {
        $this->assertCount(0, $this->formation->getQuizzes());
    }

    public function testEnrollmentsInitialized(): void
    {
        $this->assertCount(0, $this->formation->getEnrollments());
    }

    // ── Edge cases ──
    public function testZeroDuration(): void
    {
        $this->formation->setDuree(0);
        $this->assertEquals(0, $this->formation->getDuree());
    }

    public function testNullNiveau(): void
    {
        $this->assertNull($this->formation->getNiveau());
    }
}
