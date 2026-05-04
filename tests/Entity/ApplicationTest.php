<?php

namespace App\Tests\Entity;

use App\Entity\Application;
use App\Entity\Offer;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application();
    }

    // ── Defaults ──
    public function testDefaultStatus(): void
    {
        $this->assertEquals('Nouvelle', $this->app->getStatus());
    }

    public function testDefaultScore(): void
    {
        $this->assertEquals(0, $this->app->getScore());
    }

    // ── Relations ──
    public function testSetGetUser(): void
    {
        $user = new User();
        $user->setEmail('candidate@test.tn');
        $this->app->setUser($user);
        $this->assertSame($user, $this->app->getUser());
    }

    public function testSetGetOffer(): void
    {
        $offer = new Offer();
        $offer->setTitle('Dev Java');
        $this->app->setOffer($offer);
        $this->assertSame($offer, $this->app->getOffer());
    }

    // ── Status transitions ──
    public function testSetStatusAccepted(): void
    {
        $this->app->setStatus('Acceptée');
        $this->assertEquals('Acceptée', $this->app->getStatus());
    }

    public function testSetStatusRejected(): void
    {
        $this->app->setStatus('Refusée');
        $this->assertEquals('Refusée', $this->app->getStatus());
    }

    public function testSetStatusInterview(): void
    {
        $this->app->setStatus('Entretien');
        $this->assertEquals('Entretien', $this->app->getStatus());
    }

    public function testSetStatusPending(): void
    {
        $this->app->setStatus('En attente');
        $this->assertEquals('En attente', $this->app->getStatus());
    }

    // ── CV ──
    public function testSetGetCvFilePath(): void
    {
        $this->app->setCvFilePath('/uploads/cvs/cv-test.pdf');
        $this->assertEquals('/uploads/cvs/cv-test.pdf', $this->app->getCvFilePath());
    }

    public function testNullCvFilePath(): void
    {
        $this->assertNull($this->app->getCvFilePath());
    }

    // ── Motivation letter ──
    public function testSetGetMotivationLetter(): void
    {
        $this->app->setMotivationLetter('Je suis motivé pour ce poste.');
        $this->assertEquals('Je suis motivé pour ce poste.', $this->app->getMotivationLetter());
    }

    // ── Score ──
    public function testSetGetScore(): void
    {
        $this->app->setScore(85.5);
        $this->assertEquals(85.5, $this->app->getScore());
    }

    public function testScoreZero(): void
    {
        $this->app->setScore(0);
        $this->assertEquals(0, $this->app->getScore());
    }

    public function testScoreMax(): void
    {
        $this->app->setScore(100);
        $this->assertEquals(100, $this->app->getScore());
    }

    // ── Date ──
    public function testSetGetApplicationDate(): void
    {
        $date = new \DateTime('2025-06-15');
        $this->app->setApplicationDate($date);
        $this->assertSame($date, $this->app->getApplicationDate());
    }

    // ── Notes ──
    public function testSetGetNotes(): void
    {
        $this->app->setNotes('Bon candidat, à recontacter.');
        $this->assertEquals('Bon candidat, à recontacter.', $this->app->getNotes());
    }

    // ── Interview fields ──
    public function testSetGetInterviewer(): void
    {
        $this->app->setInterviewer('Marie Dupont');
        $this->assertEquals('Marie Dupont', $this->app->getInterviewer());
    }

    public function testSetGetInterviewDate(): void
    {
        $date = new \DateTime('2025-07-01 10:00');
        $this->app->setInterviewDate($date);
        $this->assertSame($date, $this->app->getInterviewDate());
    }

    public function testSetGetInterviewResult(): void
    {
        $this->app->setInterviewResult('PASSED');
        $this->assertEquals('PASSED', $this->app->getInterviewResult());
    }

    // ── Recruiter response ──
    public function testSetGetRecruiterResponse(): void
    {
        $this->app->setRecruiterResponse('Votre candidature a retenu notre attention.');
        $this->assertStringContainsString('retenu', $this->app->getRecruiterResponse());
    }

    // ── Workflow ──
    public function testSetGetWorkflow(): void
    {
        $wf = ['step' => 'review', 'completed' => true];
        $this->app->setWorkflow($wf);
        $this->assertEquals($wf, $this->app->getWorkflow());
    }
}
