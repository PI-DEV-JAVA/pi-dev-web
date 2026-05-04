<?php

namespace App\Tests\Entity;

use App\Entity\Interview;
use App\Entity\Application;
use PHPUnit\Framework\TestCase;

class InterviewTest extends TestCase
{
    private Interview $interview;

    protected function setUp(): void
    {
        $this->interview = new Interview();
    }

    public function testDefaultStatus(): void
    {
        $this->assertEquals('SCHEDULED', $this->interview->getStatus());
    }

    public function testSetGetApplication(): void
    {
        $app = new Application();
        $this->interview->setApplication($app);
        $this->assertSame($app, $this->interview->getApplication());
    }

    public function testSetGetInterviewDate(): void
    {
        $date = new \DateTime('2025-07-10 09:00');
        $this->interview->setInterviewDate($date);
        $this->assertSame($date, $this->interview->getInterviewDate());
    }

    public function testSetStatusCompleted(): void
    {
        $this->interview->setStatus('COMPLETED');
        $this->assertEquals('COMPLETED', $this->interview->getStatus());
    }

    public function testSetStatusCancelled(): void
    {
        $this->interview->setStatus('CANCELLED');
        $this->assertEquals('CANCELLED', $this->interview->getStatus());
    }

    public function testSetGetNotes(): void
    {
        $this->interview->setNotes('Candidat très compétent');
        $this->assertEquals('Candidat très compétent', $this->interview->getNotes());
    }

    public function testSetGetLocation(): void
    {
        $this->interview->setLocation('Bureau Tunis Centre');
        $this->assertEquals('Bureau Tunis Centre', $this->interview->getLocation());
    }

    public function testSetGetMeetingLink(): void
    {
        $this->interview->setMeetingLink('https://meet.google.com/xyz');
        $this->assertEquals('https://meet.google.com/xyz', $this->interview->getMeetingLink());
    }

    public function testNullLocationForOnline(): void
    {
        $this->interview->setLocation(null);
        $this->interview->setMeetingLink('https://zoom.us/j/123');
        $this->assertNull($this->interview->getLocation());
        $this->assertNotNull($this->interview->getMeetingLink());
    }

    public function testMeetsCollectionInitialized(): void
    {
        $this->assertCount(0, $this->interview->getMeets());
    }
}
