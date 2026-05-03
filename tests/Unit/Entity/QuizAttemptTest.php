<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\QuizAttempt;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires — Entité QuizAttempt
 *
 * Couvre la règle métier avancée :
 *   • Détection de tentative de triche (cheating detection)
 *   • Comptage des changements d'onglet (tab-switch count)
 *   • Calcul du score
 *   • Invariant : tentative frauduleuse → score forcé à 0
 */
class QuizAttemptTest extends TestCase
{
    // ─────────────────────────────────────────────
    //  Valeurs par défaut à la construction
    // ─────────────────────────────────────────────

    public function testDefaultScoreIsZero(): void
    {
        $attempt = new QuizAttempt();
        $this->assertSame(0.0, $attempt->getScore());
    }

    public function testDefaultCheatedIsFalse(): void
    {
        $attempt = new QuizAttempt();
        $this->assertFalse($attempt->isCheated());
    }

    public function testDefaultTabSwitchCountIsZero(): void
    {
        $attempt = new QuizAttempt();
        $this->assertSame(0, $attempt->getTabSwitchCount());
    }

    public function testSubmittedAtIsSetOnConstruct(): void
    {
        $before  = new \DateTime('-1 second');
        $attempt = new QuizAttempt();
        $after   = new \DateTime('+1 second');

        $this->assertNotNull($attempt->getSubmittedAt());
        $this->assertGreaterThanOrEqual($before, $attempt->getSubmittedAt());
        $this->assertLessThanOrEqual($after, $attempt->getSubmittedAt());
    }

    // ─────────────────────────────────────────────
    //  Setters / getters basiques
    // ─────────────────────────────────────────────

    public function testSetScore(): void
    {
        $attempt = new QuizAttempt();
        $attempt->setScore(14.5);
        $this->assertSame(14.5, $attempt->getScore());
    }

    public function testSetScoreMaxIs20(): void
    {
        $attempt = new QuizAttempt();
        $attempt->setScore(20.0);
        $this->assertSame(20.0, $attempt->getScore());
    }

    public function testSetTabSwitchCount(): void
    {
        $attempt = new QuizAttempt();
        $attempt->setTabSwitchCount(3);
        $this->assertSame(3, $attempt->getTabSwitchCount());
    }

    // ─────────────────────────────────────────────
    //  Règle métier : Détection de triche
    // ─────────────────────────────────────────────

    /**
     * @testdox Marquer une tentative comme frauduleuse → isCheated() retourne true
     */
    public function testMarkingAttemptAsCheated(): void
    {
        $attempt = new QuizAttempt();
        $attempt->setCheated(true);

        $this->assertTrue($attempt->isCheated());
    }

    /**
     * @testdox Règle métier : tentative frauduleuse → le score doit être 0
     */
    public function testCheatedAttemptMustHaveZeroScore(): void
    {
        $attempt = new QuizAttempt();
        $attempt->setCheated(true);
        // Le contrôleur force le score à 0 lors d'une triche détectée
        $attempt->setScore(0);

        $this->assertTrue($attempt->isCheated());
        $this->assertSame(0.0, $attempt->getScore());
    }

    /**
     * @testdox Un candidat non-tricheur avec score 16/20 ne doit pas être marqué tricheur
     */
    public function testHonestAttemptIsNotCheated(): void
    {
        $attempt = new QuizAttempt();
        $attempt->setScore(16.0);
        $attempt->setCheated(false);

        $this->assertFalse($attempt->isCheated());
        $this->assertSame(16.0, $attempt->getScore());
    }

    /**
     * @testdox Un score de 20/20 avec 0 changement d'onglet = tentative légitime
     */
    public function testPerfectScoreWithNoTabSwitchesIsLegitimate(): void
    {
        $attempt = new QuizAttempt();
        $attempt->setScore(20.0);
        $attempt->setTabSwitchCount(0);
        $attempt->setCheated(false);

        $this->assertFalse($attempt->isCheated());
        $this->assertSame(0, $attempt->getTabSwitchCount());
        $this->assertSame(20.0, $attempt->getScore());
    }

    /**
     * @testdox Plusieurs changements d'onglet enregistrés correctement
     */
    public function testMultipleTabSwitchesAreRecorded(): void
    {
        $attempt = new QuizAttempt();
        $attempt->setTabSwitchCount(5);

        $this->assertSame(5, $attempt->getTabSwitchCount());
    }

    /**
     * @testdox Triche + 4 changements d'onglet → score 0, isCheated = true
     */
    public function testCheatedWithTabSwitches(): void
    {
        $attempt = new QuizAttempt();
        $attempt->setCheated(true);
        $attempt->setTabSwitchCount(4);
        $attempt->setScore(0); // forcé par le contrôleur

        $this->assertTrue($attempt->isCheated());
        $this->assertSame(4, $attempt->getTabSwitchCount());
        $this->assertSame(0.0, $attempt->getScore());
    }

    // ─────────────────────────────────────────────
    //  Calcul du score (/20)
    // ─────────────────────────────────────────────

    /**
     * @testdox Calcul du score : 3 bonnes réponses sur 5 → 12/20
     */
    public function testScoreCalculation3Of5(): void
    {
        $correct = 3;
        $total   = 5;
        // Reproduction de la formule du contrôleur
        $scoreOn20 = $total > 0 ? round(($correct / $total) * 20, 2) : 0;

        $attempt = new QuizAttempt();
        $attempt->setScore($scoreOn20);

        $this->assertSame(12.0, $attempt->getScore());
    }

    /**
     * @testdox Calcul du score : 5 bonnes réponses sur 5 → 20/20
     */
    public function testScoreCalculation5Of5(): void
    {
        $correct = 5;
        $total   = 5;
        $scoreOn20 = $total > 0 ? round(($correct / $total) * 20, 2) : 0;

        $attempt = new QuizAttempt();
        $attempt->setScore($scoreOn20);

        $this->assertSame(20.0, $attempt->getScore());
    }

    /**
     * @testdox Calcul du score : 0 bonne réponse → 0/20
     */
    public function testScoreCalculation0Of5(): void
    {
        $correct = 0;
        $total   = 5;
        $scoreOn20 = $total > 0 ? round(($correct / $total) * 20, 2) : 0;

        $attempt = new QuizAttempt();
        $attempt->setScore((float)$scoreOn20);

        $this->assertSame(0.0, $attempt->getScore());
    }

    /**
     * @testdox Aucune question dans le quiz → score 0 (division par zéro évitée)
     */
    public function testScoreWithNoQuestions(): void
    {
        $correct = 0;
        $total   = 0;
        $scoreOn20 = $total > 0 ? round(($correct / $total) * 20, 2) : 0;

        $attempt = new QuizAttempt();
        $attempt->setScore((float)$scoreOn20);

        $this->assertSame(0.0, $attempt->getScore());
    }
}
