<?php

declare(strict_types=1);

namespace App\Tests\Unit\Business;

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires — Règle métier : Éligibilité au Certificat
 *
 * Règle : Un candidat obtient le certificat si sa moyenne sur l'ensemble
 *         des quiz de la formation est >= 70 % (soit >= 14/20).
 *
 * Source : CertificateController::download() & CourseController::detail()
 */
class CertificateEligibilityTest extends TestCase
{
    // ─────────────────────────────────────────────
    //  Helper — reproduit la logique du contrôleur
    // ─────────────────────────────────────────────

    /**
     * Calcule la moyenne et retourne si le candidat est éligible au certificat.
     *
     * @param float[] $scores  Scores /20 de chaque quiz
     */
    private function isEligible(array $scores): bool
    {
        $attemptCount = count($scores);
        if ($attemptCount === 0) {
            return false;
        }

        $totalScore = array_sum($scores);
        $avgScore   = round($totalScore / $attemptCount, 1);
        $avgPercent = ($avgScore / 20) * 100;

        return $avgPercent >= 70;
    }

    /** Retourne le pourcentage arrondi (pour les assertions explicites). */
    private function avgPercent(array $scores): float
    {
        $count = count($scores);
        if ($count === 0) return 0.0;
        $avg = round(array_sum($scores) / $count, 1);
        return round(($avg / 20) * 100, 1);
    }

    // ─────────────────────────────────────────────
    //  Cas d'éligibilité (≥ 70%)
    // ─────────────────────────────────────────────

    /**
     * @testdox Score exact 14/20 → 70 % → éligible (seuil minimum)
     */
    public function testEligibleAtExact70Percent(): void
    {
        // 14/20 = 70 %
        $this->assertTrue($this->isEligible([14.0]));
        $this->assertSame(70.0, $this->avgPercent([14.0]));
    }

    /**
     * @testdox Score 16/20 → 80 % → éligible
     */
    public function testEligibleAt80Percent(): void
    {
        $this->assertTrue($this->isEligible([16.0]));
        $this->assertSame(80.0, $this->avgPercent([16.0]));
    }

    /**
     * @testdox Score parfait 20/20 → 100 % → éligible
     */
    public function testEligibleAtPerfectScore(): void
    {
        $this->assertTrue($this->isEligible([20.0]));
        $this->assertSame(100.0, $this->avgPercent([20.0]));
    }

    /**
     * @testdox Moyenne de 3 quiz : [16, 14, 18] → 16/20 → 80 % → éligible
     */
    public function testEligibleWithMultipleQuizAverage(): void
    {
        $this->assertTrue($this->isEligible([16.0, 14.0, 18.0]));
        $this->assertSame(80.0, $this->avgPercent([16.0, 14.0, 18.0]));
    }

    /**
     * @testdox Moyenne exactement au seuil avec 2 quiz : [14, 14] → 14/20 → éligible
     */
    public function testEligibleWithTwoQuizzesAtThreshold(): void
    {
        $this->assertTrue($this->isEligible([14.0, 14.0]));
    }

    // ─────────────────────────────────────────────
    //  Cas de non-éligibilité (< 70%)
    // ─────────────────────────────────────────────

    /**
     * @testdox Score 13/20 → 65 % → non éligible (sous le seuil)
     */
    public function testNotEligibleAt65Percent(): void
    {
        $this->assertFalse($this->isEligible([13.0]));
        $this->assertSame(65.0, $this->avgPercent([13.0]));
    }

    /**
     * @testdox Score 10/20 → 50 % → non éligible
     */
    public function testNotEligibleAt50Percent(): void
    {
        $this->assertFalse($this->isEligible([10.0]));
        $this->assertSame(50.0, $this->avgPercent([10.0]));
    }

    /**
     * @testdox Score 0/20 (tentative de triche) → 0 % → non éligible
     */
    public function testNotEligibleWhenCheated(): void
    {
        // Un candidat tricheur reçoit 0 → jamais éligible
        $this->assertFalse($this->isEligible([0.0]));
        $this->assertSame(0.0, $this->avgPercent([0.0]));
    }

    /**
     * @testdox Aucun quiz complété → non éligible (pas de tentative)
     */
    public function testNotEligibleWithNoAttempts(): void
    {
        $this->assertFalse($this->isEligible([]));
    }

    /**
     * @testdox Moyenne [20, 0] (1 triche + 1 parfait) → 10/20 = 50 % → non éligible
     */
    public function testNotEligibleWhenCheatDragsAverageBelow70(): void
    {
        // Un quiz parfait + un quiz frauduleux (score 0) → moyenne 10/20 → 50 % → refusé
        $this->assertFalse($this->isEligible([20.0, 0.0]));
        $this->assertSame(50.0, $this->avgPercent([20.0, 0.0]));
    }

    /**
     * @testdox Moyenne de 3 quiz : [10, 12, 13.5] → ~11.8/20 = 59 % → non éligible
     */
    public function testNotEligibleWithLowMultipleQuizzes(): void
    {
        $this->assertFalse($this->isEligible([10.0, 12.0, 13.5]));
        $pct = $this->avgPercent([10.0, 12.0, 13.5]);
        $this->assertLessThan(70.0, $pct);
    }

    // ─────────────────────────────────────────────
    //  Cas limites (edge cases)
    // ─────────────────────────────────────────────

    /**
     * @testdox Score juste en dessous du seuil : 13.9/20 → 69.5 % → non éligible
     */
    public function testJustBelowThreshold(): void
    {
        $this->assertFalse($this->isEligible([13.9]));
    }

    /**
     * @testdox Score juste au-dessus du seuil : 14.1/20 → 70.5 % → éligible
     */
    public function testJustAboveThreshold(): void
    {
        $this->assertTrue($this->isEligible([14.1]));
    }

    /**
     * @testdox La règle des points bonus (score > 15/20) est indépendante de l'éligibilité
     */
    public function testPointsRewardThresholdIsIndependentOfCertificate(): void
    {
        // Éligible certificat à 14/20 (70 %)
        $this->assertTrue($this->isEligible([14.0]));

        // Mais points bonus seulement si score > 15/20
        $scoreForPoints = 14.0;
        $earnedPoints   = $scoreForPoints > 15 ? (int) round($scoreForPoints) : 0;
        $this->assertSame(0, $earnedPoints); // pas de points bonus à 14/20

        // À 16/20 : éligible certificat ET points bonus
        $this->assertTrue($this->isEligible([16.0]));
        $scoreForPoints16 = 16.0;
        $earnedPoints16   = $scoreForPoints16 > 15 ? (int) round($scoreForPoints16) : 0;
        $this->assertSame(16, $earnedPoints16);
    }
}
