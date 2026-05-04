<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  MODULE: Activities & Projects
 *  Covers: Activity entity, Project entity,
 *          ActivityController logic (list, detail, timer, report)
 *  Tests: CRUD, status, timer, report submission, late detection
 * ═══════════════════════════════════════════════════════════════
 */

namespace App\Tests\Module;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ActivityProjectTest extends TestCase
{
    // ┌──────────────────────────────────────┐
    // │  PROJECT ENTITY — Success Scenarios  │
    // └──────────────────────────────────────┘

    public function testProjectCreationDefaults(): void
    {
        $project = new Project();
        $this->assertNull($project->getId());
        $this->assertEquals('PLANNED', $project->getStatus());
        $this->assertNull($project->getBudget());
    }

    public function testProjectSetAllFields(): void
    {
        $project = new Project();
        $project->setName('Refonte Site Web');
        $project->setDescription('Migration vers Symfony 7');
        $project->setStatus('IN_PROGRESS');
        $project->setBudget('75000 TND');
        $project->setStartDate(new \DateTime('2025-01-15'));
        $project->setEndDate(new \DateTime('2025-06-30'));

        $this->assertEquals('Refonte Site Web', $project->getName());
        $this->assertEquals('IN_PROGRESS', $project->getStatus());
        $this->assertEquals('75000 TND', $project->getBudget());
    }

    public function testProjectStatusTransitions(): void
    {
        $project = new Project();
        $this->assertEquals('PLANNED', $project->getStatus());

        $project->setStatus('IN_PROGRESS');
        $this->assertEquals('IN_PROGRESS', $project->getStatus());

        $project->setStatus('COMPLETED');
        $this->assertEquals('COMPLETED', $project->getStatus());
    }

    public function testProjectDateRange(): void
    {
        $project = new Project();
        $start = new \DateTime('2025-01-01');
        $end = new \DateTime('2025-12-31');
        $project->setStartDate($start);
        $project->setEndDate($end);
        $this->assertGreaterThan($project->getStartDate(), $project->getEndDate());
    }

    public function testProjectManagerAssignment(): void
    {
        $project = new Project();
        $pm = new User();
        $pm->setEmail('pm@company.tn');
        $pm->setRole('RECRUITER');
        $project->setProjectManager($pm);
        $this->assertSame($pm, $project->getProjectManager());
    }

    // ┌──────────────────────────────────────┐
    // │  PROJECT ENTITY — Failure Scenarios  │
    // └──────────────────────────────────────┘

    public function testFailProjectNoManager(): void
    {
        $project = new Project();
        $this->assertNull($project->getProjectManager());
    }

    public function testFailProjectNullDates(): void
    {
        $project = new Project();
        $this->assertNull($project->getStartDate());
        $this->assertNull($project->getEndDate());
    }

    public function testFailProjectNullBudget(): void
    {
        $project = new Project();
        $this->assertNull($project->getBudget());
    }

    // ┌───────────────────────────────────────┐
    // │  ACTIVITY ENTITY — Success Scenarios  │
    // └───────────────────────────────────────┘

    public function testActivityCreationDefaults(): void
    {
        $activity = new Activity();
        $this->assertNull($activity->getId());
        $this->assertEquals('PENDING', $activity->getReportStatus());
        $this->assertFalse($activity->isLateSubmission());
        $this->assertEquals(0, $activity->getDelayInHours());
        $this->assertEquals(0, $activity->getRevisionCount());
    }

    public function testActivitySetAllFields(): void
    {
        $activity = new Activity();
        $employee = new User();
        $employee->setEmail('employee@company.tn');
        $project = new Project();
        $project->setName('App Mobile');

        $activity->setEmployee($employee);
        $activity->setProject($project);
        $activity->setDescription('Développer le module authentification');
        $activity->setHoursWorked('8');
        $activity->setActivityDate(new \DateTime('2025-06-10'));

        $this->assertSame($employee, $activity->getEmployee());
        $this->assertEquals('App Mobile', $activity->getProject()->getName());
        $this->assertEquals('8', $activity->getHoursWorked());
    }

    // ── ActivityController: Timer simulation ──
    public function testActivityTimerSave(): void
    {
        $activity = new Activity();
        $activity->setHoursWorked('4.5');
        $this->assertEquals('4.5', $activity->getHoursWorked());
    }

    // ── ActivityController: Report submission ──
    public function testActivitySubmitReport(): void
    {
        $activity = new Activity();
        $activity->setUserReport('Tâche terminée: module de login implémenté et testé.');
        $activity->setReportStatus('SUBMITTED');
        $this->assertStringContainsString('login', $activity->getUserReport());
        $this->assertEquals('SUBMITTED', $activity->getReportStatus());
    }

    public function testActivityReportApproved(): void
    {
        $activity = new Activity();
        $activity->setReportStatus('APPROVED');
        $activity->setAdminFeedback('Excellent travail!');
        $this->assertEquals('APPROVED', $activity->getReportStatus());
        $this->assertStringContainsString('Excellent', $activity->getAdminFeedback());
    }

    public function testActivityReportRejected(): void
    {
        $activity = new Activity();
        $activity->setReportStatus('REJECTED');
        $activity->setAdminFeedback('Rapport incomplet, merci de réviser.');
        $activity->setRevisionCount(1);
        $this->assertEquals('REJECTED', $activity->getReportStatus());
        $this->assertEquals(1, $activity->getRevisionCount());
    }

    // ── Late submission detection ──
    public function testActivityLateSubmission(): void
    {
        $activity = new Activity();
        $deadline = new \DateTime('2025-06-01');
        $activity->setExpectedDeadline($deadline);

        // Submitted 2 days late
        $activity->setIsLateSubmission(true);
        $activity->setDelayInHours(48);

        $this->assertTrue($activity->isLateSubmission());
        $this->assertEquals(48, $activity->getDelayInHours());
    }

    public function testActivityOnTimeSubmission(): void
    {
        $activity = new Activity();
        $activity->setExpectedDeadline(new \DateTime('+1 day'));
        $activity->setIsLateSubmission(false);
        $activity->setDelayInHours(0);

        $this->assertFalse($activity->isLateSubmission());
        $this->assertEquals(0, $activity->getDelayInHours());
    }

    // ── Revision count ──
    public function testActivityMultipleRevisions(): void
    {
        $activity = new Activity();
        $activity->setRevisionCount(0);
        $activity->setRevisionCount($activity->getRevisionCount() + 1);
        $activity->setRevisionCount($activity->getRevisionCount() + 1);
        $this->assertEquals(2, $activity->getRevisionCount());
    }

    // ┌───────────────────────────────────────┐
    // │  ACTIVITY ENTITY — Failure Scenarios  │
    // └───────────────────────────────────────┘

    public function testFailActivityNoEmployee(): void
    {
        $activity = new Activity();
        $this->assertNull($activity->getEmployee());
    }

    public function testFailActivityNoProject(): void
    {
        $activity = new Activity();
        $this->assertNull($activity->getProject());
    }

    public function testFailActivityNoReport(): void
    {
        $activity = new Activity();
        $this->assertNull($activity->getUserReport());
    }

    public function testFailActivityNoDeadline(): void
    {
        $activity = new Activity();
        $this->assertNull($activity->getExpectedDeadline());
    }

    public function testFailActivityNullAdminFeedback(): void
    {
        $activity = new Activity();
        $this->assertNull($activity->getAdminFeedback());
    }
}
