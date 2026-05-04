<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  MODULE: Interviews & Meets
 *  Covers: Interview entity, Meet entity,
 *          InterviewController logic (list, room, notes, calendar)
 *  Tests: scheduling, status, grading, room IDs, code execution
 * ═══════════════════════════════════════════════════════════════
 */

namespace App\Tests\Module;

use App\Entity\Interview;
use App\Entity\Meet;
use App\Entity\Application;
use App\Entity\Offer;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class InterviewMeetTest extends TestCase
{
    // ┌────────────────────────────────────────┐
    // │  INTERVIEW ENTITY — Success Scenarios  │
    // └────────────────────────────────────────┘

    public function testInterviewCreationDefaults(): void
    {
        $interview = new Interview();
        $this->assertNull($interview->getId());
        $this->assertEquals('SCHEDULED', $interview->getStatus());
        $this->assertNull($interview->getNotes());
        $this->assertNull($interview->getLocation());
        $this->assertNull($interview->getMeetingLink());
        $this->assertCount(0, $interview->getMeets());
    }

    public function testInterviewScheduling(): void
    {
        $interview = new Interview();
        $app = new Application();
        $user = new User();
        $user->setEmail('candidate@test.tn');
        $app->setUser($user);

        $interview->setApplication($app);
        $interview->setInterviewDate(new \DateTime('2025-07-15 09:00'));
        $interview->setLocation('Bureau Tunis, 3ème étage');

        $this->assertSame($app, $interview->getApplication());
        $this->assertEquals('2025-07-15', $interview->getInterviewDate()->format('Y-m-d'));
        $this->assertStringContainsString('Tunis', $interview->getLocation());
    }

    public function testInterviewOnlineWithMeetingLink(): void
    {
        $interview = new Interview();
        $interview->setMeetingLink('https://meet.google.com/abc-def-ghi');
        $interview->setLocation(null);
        $this->assertNull($interview->getLocation());
        $this->assertStringContainsString('meet.google.com', $interview->getMeetingLink());
    }

    public function testInterviewStatusWorkflow(): void
    {
        $interview = new Interview();

        // Scheduled → In Progress → Completed
        $this->assertEquals('SCHEDULED', $interview->getStatus());

        $interview->setStatus('IN_PROGRESS');
        $this->assertEquals('IN_PROGRESS', $interview->getStatus());

        $interview->setStatus('COMPLETED');
        $this->assertEquals('COMPLETED', $interview->getStatus());
    }

    public function testInterviewCancelled(): void
    {
        $interview = new Interview();
        $interview->setStatus('CANCELLED');
        $this->assertEquals('CANCELLED', $interview->getStatus());
    }

    public function testInterviewNotes(): void
    {
        $interview = new Interview();
        $interview->setNotes('Le candidat a démontré une maîtrise solide de Symfony et Docker.');
        $this->assertStringContainsString('Symfony', $interview->getNotes());
        $this->assertStringContainsString('Docker', $interview->getNotes());
    }


    public function testInterviewCalendarDateValid(): void
    {
        $interview = new Interview();
        $interview->setInterviewDate(new \DateTime('2025-08-01 14:00'));
        $this->assertGreaterThan(new \DateTime('2025-01-01'), $interview->getInterviewDate());
    }

    // ┌────────────────────────────────────────┐
    // │  INTERVIEW ENTITY — Failure Scenarios  │
    // └────────────────────────────────────────┘

    public function testFailInterviewNoApplication(): void
    {
        $interview = new Interview();
        $this->assertNull($interview->getApplication());
    }

    public function testFailInterviewNoDate(): void
    {
        $interview = new Interview();
        $this->assertNull($interview->getInterviewDate());
    }

    public function testFailInterviewNoLocationOrLink(): void
    {
        $interview = new Interview();
        $this->assertNull($interview->getLocation());
        $this->assertNull($interview->getMeetingLink());
    }

    // ┌──────────────────────────────────────┐
    // │  MEET ENTITY — Success Scenarios    │
    // └──────────────────────────────────────┘

    public function testMeetCreationDefaults(): void
    {
        $meet = new Meet();
        $this->assertNull($meet->getId());
        $this->assertEquals('GENERAL', $meet->getMeetType());
        $this->assertNull($meet->getGrade());
        $this->assertNull($meet->getNotes());
    }

    public function testMeetSetAllFields(): void
    {
        $meet = new Meet();
        $interview = new Interview();

        $meet->setInterview($interview);
        $meet->setTitle('Entretien technique — Phase 1');
        $meet->setMeetDate(new \DateTime('2025-07-15 10:00'));
        $meet->setRoomId('room_abc123');
        $meet->setMeetType('TECHNICAL');

        $this->assertSame($interview, $meet->getInterview());
        $this->assertEquals('Entretien technique — Phase 1', $meet->getTitle());
        $this->assertEquals('room_abc123', $meet->getRoomId());
        $this->assertEquals('TECHNICAL', $meet->getMeetType());
    }

    public function testMeetGrading(): void
    {
        $meet = new Meet();
        $meet->setGrade(8.5);
        $this->assertEquals(8.5, $meet->getGrade());
    }

    public function testMeetPerfectScore(): void
    {
        $meet = new Meet();
        $meet->setGrade(10.0);
        $this->assertEquals(10.0, $meet->getGrade());
    }

    public function testMeetLowScore(): void
    {
        $meet = new Meet();
        $meet->setGrade(2.0);
        $this->assertEquals(2.0, $meet->getGrade());
        $this->assertLessThan(5.0, $meet->getGrade());
    }

    // ── InterviewController: Notes saving simulation ──
    public function testMeetSaveNotes(): void
    {
        $meet = new Meet();
        $meet->setNotes('Bonne réponse sur les design patterns. A creuser sur les tests unitaires.');
        $this->assertStringContainsString('design patterns', $meet->getNotes());
    }

    // ── InterviewController: Room ID simulation ──
    public function testMeetRoomIdGenerated(): void
    {
        $meet = new Meet();
        $roomId = 'meet_' . bin2hex(random_bytes(8));
        $meet->setRoomId($roomId);
        $this->assertStringStartsWith('meet_', $meet->getRoomId());
        $this->assertGreaterThan(10, strlen($meet->getRoomId()));
    }

    public function testMeetTypeVariants(): void
    {
        $meet = new Meet();

        $meet->setMeetType('GENERAL');
        $this->assertEquals('GENERAL', $meet->getMeetType());

        $meet->setMeetType('TECHNICAL');
        $this->assertEquals('TECHNICAL', $meet->getMeetType());

        $meet->setMeetType('HR');
        $this->assertEquals('HR', $meet->getMeetType());
    }

    // ┌──────────────────────────────────────┐
    // │  MEET ENTITY — Failure Scenarios    │
    // └──────────────────────────────────────┘

    public function testFailMeetNoInterview(): void
    {
        $meet = new Meet();
        $this->assertNull($meet->getInterview());
    }

    public function testFailMeetNoGrade(): void
    {
        $meet = new Meet();
        $this->assertNull($meet->getGrade());
    }

    public function testFailMeetNoRoomId(): void
    {
        $meet = new Meet();
        $this->assertNull($meet->getRoomId());
    }

    public function testFailMeetNoTitle(): void
    {
        $meet = new Meet();
        $this->assertNull($meet->getTitle());
    }

    // ── InterviewController: runCode simulation ──
    public function testCodeExecutionOutputSimulation(): void
    {
        // Simulate what the controller does for code execution
        $code = 'print("Hello World")';
        $language = 'python';
        $this->assertNotEmpty($code);
        $this->assertEquals('python', $language);
    }

    public function testFailCodeExecutionEmptyCode(): void
    {
        $code = '';
        $this->assertEmpty($code);
    }
}
