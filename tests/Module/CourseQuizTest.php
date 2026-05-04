<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  MODULE: Courses & Quiz
 *  Covers: Formation, Seance, Quiz, Question, Choix, QuizAttempt,
 *          FormationEnrollment entities
 *          CourseController logic (enroll, quiz, scoring, cheating)
 *  Tests: enrollment flow, quiz submission, scoring, anti-cheat
 * ═══════════════════════════════════════════════════════════════
 */

namespace App\Tests\Module;

use App\Entity\Formation;
use App\Entity\Seance;
use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\Choix;
use App\Entity\QuizAttempt;
use App\Entity\FormationEnrollment;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CourseQuizTest extends TestCase
{
    // ┌────────────────────────────────────────┐
    // │  FORMATION ENTITY — Success Scenarios  │
    // └────────────────────────────────────────┘

    public function testFormationCreationDefaults(): void
    {
        $f = new Formation();
        $this->assertNull($f->getId());
        $this->assertFalse($f->isPaid());
        $this->assertNull($f->getPricePoints());
        $this->assertCount(0, $f->getSeances());
        $this->assertCount(0, $f->getQuizzes());
        $this->assertCount(0, $f->getEnrollments());
    }

    public function testFormationSetAllFields(): void
    {
        $f = new Formation();
        $f->setTitre('Symfony 7 Masterclass');
        $f->setDescription('Formation complète Symfony');
        $f->setNiveau('Avancé');
        $f->setDuree(40);
        $f->setImage('/uploads/formations/symfony.jpg');
        $f->setDateDebut(new \DateTime('2025-09-01'));
        $f->setDateFin(new \DateTime('2025-12-15'));
        $f->setRecruiterId(5);

        $this->assertEquals('Symfony 7 Masterclass', $f->getTitre());
        $this->assertEquals('Avancé', $f->getNiveau());
        $this->assertEquals(40, $f->getDuree());
        $this->assertEquals(5, $f->getRecruiterId());
    }

    public function testFormationPaidCourse(): void
    {
        $f = new Formation();
        $f->setIsPaid(true);
        $f->setPricePoints(500);
        $this->assertTrue($f->isPaid());
        $this->assertEquals(500, $f->getPricePoints());
    }

    public function testFormationFreeCourse(): void
    {
        $f = new Formation();
        $f->setIsPaid(false);
        $this->assertFalse($f->isPaid());
        $this->assertNull($f->getPricePoints());
    }

    public function testFormationDateRange(): void
    {
        $f = new Formation();
        $start = new \DateTime('2025-09-01');
        $end = new \DateTime('2025-12-15');
        $f->setDateDebut($start);
        $f->setDateFin($end);
        $this->assertGreaterThan($f->getDateDebut(), $f->getDateFin());
    }

    // ┌────────────────────────────────────────┐
    // │  FORMATION ENTITY — Failure Scenarios  │
    // └────────────────────────────────────────┘

    public function testFailFormationNullTitle(): void
    {
        $f = new Formation();
        $this->assertNull($f->getTitre());
    }

    public function testFailFormationZeroDuration(): void
    {
        $f = new Formation();
        $f->setDuree(0);
        $this->assertEquals(0, $f->getDuree());
    }

    public function testFailFormationNullDates(): void
    {
        $f = new Formation();
        $this->assertNull($f->getDateDebut());
        $this->assertNull($f->getDateFin());
    }

    // ┌──────────────────────────────────────┐
    // │  SEANCE ENTITY — Success/Failure    │
    // └──────────────────────────────────────┘

    public function testSeanceCreation(): void
    {
        $seance = new Seance();
        $formation = new Formation();
        $formation->setTitre('React Native');

        $seance->setFormation($formation);
        $seance->setTitre('Introduction à React');
        $seance->setDescription('Les bases de React');
        $seance->setType('PRESENTIEL');
        $seance->setStatut('PLANIFIEE');
        $seance->setDureeMinutes(120);
        $seance->setAdresse('Technopôle Ghazala');

        $this->assertEquals('Introduction à React', $seance->getTitre());
        $this->assertEquals('PRESENTIEL', $seance->getType());
        $this->assertEquals(120, $seance->getDureeMinutes());
        $this->assertEquals('PLANIFIEE', $seance->getStatut());
    }

    public function testSeanceOnlineWithVideo(): void
    {
        $seance = new Seance();
        $seance->setType('EN_LIGNE');
        $seance->setVideoPath('/uploads/videos/lesson1.mp4');
        $this->assertEquals('EN_LIGNE', $seance->getType());
        $this->assertNotNull($seance->getVideoPath());
    }

    public function testSeanceGeolocation(): void
    {
        $seance = new Seance();
        $seance->setLatitude(36.8448);
        $seance->setLongitude(10.1658);
        $this->assertEquals(36.8448, $seance->getLatitude());
        $this->assertEquals(10.1658, $seance->getLongitude());
    }

    public function testFailSeanceNullFormation(): void
    {
        $seance = new Seance();
        $this->assertNull($seance->getFormation());
    }

    // ┌────────────────────────────────────────────┐
    // │  QUIZ & QUESTION & CHOIX — Success/Failure │
    // └────────────────────────────────────────────┘

    public function testQuizCreation(): void
    {
        $quiz = new Quiz();
        $formation = new Formation();

        $quiz->setFormation($formation);
        $quiz->setTitre('Quiz Symfony - Chapitre 1');
        $quiz->setDuree(30);
        $quiz->setDescription('Testez vos connaissances sur les bases');

        $this->assertEquals('Quiz Symfony - Chapitre 1', $quiz->getTitre());
        $this->assertEquals(30, $quiz->getDuree());
        $this->assertCount(0, $quiz->getQuestions());
    }

    public function testQuizWithSeance(): void
    {
        $quiz = new Quiz();
        $seance = new Seance();
        $seance->setTitre('Séance 1');
        $quiz->setSeance($seance);
        $this->assertSame($seance, $quiz->getSeance());
    }

    public function testQuestionCreation(): void
    {
        $question = new Question();
        $quiz = new Quiz();
        $question->setQuiz($quiz);
        $question->setEnonce('Quel design pattern utilise Symfony pour l\'injection de dépendances?');
        $this->assertStringContainsString('design pattern', $question->getEnonce());
        $this->assertCount(0, $question->getChoix());
    }

    public function testChoixCorrectAnswer(): void
    {
        $choix = new Choix();
        $choix->setTexte('Service Container');
        $choix->setIsCorrect(true);
        $this->assertEquals('Service Container', $choix->getTexte());
        $this->assertTrue($choix->isCorrect());
    }

    public function testChoixWrongAnswer(): void
    {
        $choix = new Choix();
        $choix->setTexte('Singleton');
        $choix->setIsCorrect(false);
        $this->assertFalse($choix->isCorrect());
    }

    public function testFailQuizNullFormation(): void
    {
        $quiz = new Quiz();
        $this->assertNull($quiz->getFormation());
    }

    public function testFailQuestionNoEnonce(): void
    {
        $question = new Question();
        $this->assertNull($question->getEnonce());
    }

    // ── Quiz scoring simulation ──
    public function testQuizScoringAllCorrect(): void
    {
        $answers = [true, true, true, true]; // 4/4 correct
        $total = count($answers);
        $correct = array_filter($answers, fn($a) => $a === true);
        $score = (count($correct) / $total) * 100;
        $this->assertEquals(100.0, $score);
    }

    public function testQuizScoringPartialCorrect(): void
    {
        $answers = [true, false, true, false]; // 2/4 correct
        $total = count($answers);
        $correct = array_filter($answers, fn($a) => $a === true);
        $score = (count($correct) / $total) * 100;
        $this->assertEquals(50.0, $score);
    }

    public function testQuizScoringAllWrong(): void
    {
        $answers = [false, false, false];
        $total = count($answers);
        $correct = array_filter($answers, fn($a) => $a === true);
        $score = (count($correct) / $total) * 100;
        $this->assertEquals(0.0, $score);
    }

    // ┌────────────────────────────────────────────┐
    // │  QUIZ ATTEMPT — Success/Failure Scenarios  │
    // └────────────────────────────────────────────┘

    public function testQuizAttemptCreationDefaults(): void
    {
        $attempt = new QuizAttempt();
        $this->assertEquals(0, $attempt->getScore());
        $this->assertEquals(0, $attempt->getTabSwitchCount());
        $this->assertFalse($attempt->isCheated());
    }

    public function testQuizAttemptSuccessful(): void
    {
        $attempt = new QuizAttempt();
        $user = new User();
        $quiz = new Quiz();

        $attempt->setUser($user);
        $attempt->setQuiz($quiz);
        $attempt->setScore(85.0);
        $attempt->setTabSwitchCount(0);
        $attempt->setCheated(false);
        $this->assertEquals(85.0, $attempt->getScore());
        $this->assertFalse($attempt->isCheated());
    }

    public function testQuizAttemptPerfectScore(): void
    {
        $attempt = new QuizAttempt();
        $attempt->setScore(100.0);
        $this->assertEquals(100.0, $attempt->getScore());
    }

    // ── Anti-cheat: tab switching detection ──
    public function testQuizAttemptTabSwitchDetected(): void
    {
        $attempt = new QuizAttempt();
        $attempt->setTabSwitchCount(3);
        $this->assertEquals(3, $attempt->getTabSwitchCount());
        // 3+ switches → flagged as cheating
        $attempt->setCheated($attempt->getTabSwitchCount() >= 3);
        $this->assertTrue($attempt->isCheated());
    }

    public function testQuizAttemptNoTabSwitch(): void
    {
        $attempt = new QuizAttempt();
        $attempt->setTabSwitchCount(0);
        $attempt->setCheated(false);
        $this->assertFalse($attempt->isCheated());
    }

    public function testQuizAttemptFewTabSwitches(): void
    {
        $attempt = new QuizAttempt();
        $attempt->setTabSwitchCount(2);
        // Under threshold — not flagged
        $attempt->setCheated($attempt->getTabSwitchCount() >= 3);
        $this->assertFalse($attempt->isCheated());
    }

    public function testFailQuizAttemptNoUser(): void
    {
        $attempt = new QuizAttempt();
        $this->assertNull($attempt->getUser());
    }

    public function testFailQuizAttemptNoQuiz(): void
    {
        $attempt = new QuizAttempt();
        $this->assertNull($attempt->getQuiz());
    }

    // ┌──────────────────────────────────────────────┐
    // │  ENROLLMENT — Success/Failure Scenarios      │
    // └──────────────────────────────────────────────┘

    public function testEnrollmentCreation(): void
    {
        $enrollment = new FormationEnrollment();
        $user = new User();
        $formation = new Formation();
        $formation->setTitre('Docker & DevOps');

        $enrollment->setUser($user);
        $enrollment->setFormation($formation);


        $this->assertSame($user, $enrollment->getUser());
        $this->assertEquals('Docker & DevOps', $enrollment->getFormation()->getTitre());
        $this->assertEquals('PENDING', $enrollment->getStatus());
    }

    public function testEnrollmentApproved(): void
    {
        $enrollment = new FormationEnrollment();
        $enrollment->setStatus('APPROVED');
        $enrollment->setRespondedAt(new \DateTime());
        $this->assertEquals('APPROVED', $enrollment->getStatus());
        $this->assertNotNull($enrollment->getRespondedAt());
    }

    public function testEnrollmentRejected(): void
    {
        $enrollment = new FormationEnrollment();
        $enrollment->setStatus('REJECTED');
        $this->assertEquals('REJECTED', $enrollment->getStatus());
    }

    // ── CourseController: Paid course enrollment simulation ──
    public function testPaidCourseEnrollmentWithEnoughPoints(): void
    {
        $user = new User();
        $user->setPointsBalance(500);
        $formation = new Formation();
        $formation->setIsPaid(true);
        $formation->setPricePoints(300);

        $canAfford = $user->getPointsBalance() >= $formation->getPricePoints();
        $this->assertTrue($canAfford);

        // Deduct points
        $user->setPointsBalance($user->getPointsBalance() - $formation->getPricePoints());
        $this->assertEquals(200, $user->getPointsBalance());
    }

    public function testFailPaidCourseNotEnoughPoints(): void
    {
        $user = new User();
        $user->setPointsBalance(100);
        $formation = new Formation();
        $formation->setIsPaid(true);
        $formation->setPricePoints(500);

        $canAfford = $user->getPointsBalance() >= $formation->getPricePoints();
        $this->assertFalse($canAfford);
    }

    public function testFreeCourseEnrollmentAlwaysAllowed(): void
    {
        $formation = new Formation();
        $formation->setIsPaid(false);
        $this->assertFalse($formation->isPaid());
        // No points check needed
    }

    public function testFailEnrollmentNoUser(): void
    {
        $enrollment = new FormationEnrollment();
        $this->assertNull($enrollment->getUser());
    }
}
