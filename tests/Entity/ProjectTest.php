<?php

namespace App\Tests\Entity;

use App\Entity\Project;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    private Project $project;

    protected function setUp(): void
    {
        $this->project = new Project();
    }

    public function testDefaultStatus(): void
    {
        $this->assertEquals('PLANNED', $this->project->getStatus());
    }

    public function testSetGetName(): void
    {
        $this->project->setName('Migration Cloud');
        $this->assertEquals('Migration Cloud', $this->project->getName());
    }

    public function testSetGetDescription(): void
    {
        $this->project->setDescription('Migrer infra vers AWS');
        $this->assertEquals('Migrer infra vers AWS', $this->project->getDescription());
    }

    public function testSetStatusInProgress(): void
    {
        $this->project->setStatus('IN_PROGRESS');
        $this->assertEquals('IN_PROGRESS', $this->project->getStatus());
    }

    public function testSetStatusCompleted(): void
    {
        $this->project->setStatus('COMPLETED');
        $this->assertEquals('COMPLETED', $this->project->getStatus());
    }

    public function testSetGetDates(): void
    {
        $start = new \DateTime('2025-01-01');
        $end = new \DateTime('2025-06-30');
        $this->project->setStartDate($start);
        $this->project->setEndDate($end);
        $this->assertSame($start, $this->project->getStartDate());
        $this->assertGreaterThan($this->project->getStartDate(), $this->project->getEndDate());
    }

    public function testSetGetBudget(): void
    {
        $this->project->setBudget('50000 TND');
        $this->assertEquals('50000 TND', $this->project->getBudget());
    }

    public function testSetGetProjectManager(): void
    {
        $user = new User();
        $user->setEmail('pm@talentos.tn');
        $this->project->setProjectManager($user);
        $this->assertSame($user, $this->project->getProjectManager());
    }

    public function testNullBudget(): void
    {
        $this->assertNull($this->project->getBudget());
    }
}
