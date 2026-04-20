<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\FormationEnrollment;
use App\Entity\QuizAttempt;
use App\Entity\Seance;
use App\Entity\Quiz;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnalyticsController extends AbstractController
{
    // ─── Candidate: My Analytics ─────────────────────────────────────────
    #[Route('/analytics', name: 'app_analytics')]
    public function candidateAnalytics(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'CANDIDATE') {
            return $this->redirectToRoute('app_courses');
        }

        // All approved enrollments
        $enrollments = $em->getRepository(FormationEnrollment::class)->findBy([
            'user'   => $user,
            'status' => FormationEnrollment::STATUS_APPROVED,
        ]);

        // All quiz attempts
        $attempts = $em->getRepository(QuizAttempt::class)->findBy(['user' => $user]);

        // Map quizId → attempt
        $attemptMap = [];
        foreach ($attempts as $a) {
            $attemptMap[$a->getQuiz()->getId()] = $a;
        }

        // Per-formation stats
        $formationStats = [];
        $totalScore = 0;
        $totalAttempts = 0;
        $totalQuizzes = 0;

        foreach ($enrollments as $enrollment) {
            $formation = $enrollment->getFormation();
            $seances   = $em->getRepository(Seance::class)->findBy(['formation' => $formation]);

            $formationQuizzes  = 0;
            $formationDone     = 0;
            $formationScore    = 0;

            foreach ($seances as $s) {
                if ($s->getQuiz()) {
                    $formationQuizzes++;
                    $totalQuizzes++;
                    if (isset($attemptMap[$s->getQuiz()->getId()])) {
                        $formationDone++;
                        $totalAttempts++;
                        $score = $attemptMap[$s->getQuiz()->getId()]->getScore();
                        $formationScore += $score;
                        $totalScore    += $score;
                    }
                }
            }

            $formationStats[] = [
                'formation'    => $formation,
                'quizzes'      => $formationQuizzes,
                'done'         => $formationDone,
                'avgScore'     => $formationDone > 0 ? round($formationScore / $formationDone, 1) : null,
                'completion'   => $formationQuizzes > 0 ? round($formationDone / $formationQuizzes * 100) : 0,
            ];
        }

        // Chart data: bar chart — avg score per formation
        $chartLabels = array_map(fn($s) => $s['formation']->getTitre(), $formationStats);
        $chartScores = array_map(fn($s) => $s['avgScore'] ?? 0, $formationStats);
        $chartCompletion = array_map(fn($s) => $s['completion'], $formationStats);

        // Recent attempts (last 10)
        $recentAttempts = $em->getRepository(QuizAttempt::class)->findBy(
            ['user' => $user],
            ['submittedAt' => 'DESC'],
            10
        );

        $globalAvg = $totalAttempts > 0 ? round($totalScore / $totalAttempts, 1) : 0;
        $globalCompletion = $totalQuizzes > 0 ? round($totalAttempts / $totalQuizzes * 100) : 0;

        return $this->render('front/analytics.html.twig', [
            'formationStats'    => $formationStats,
            'chartLabels'       => $chartLabels,
            'chartScores'       => $chartScores,
            'chartCompletion'   => $chartCompletion,
            'recentAttempts'    => $recentAttempts,
            'globalAvg'         => $globalAvg,
            'globalCompletion'  => $globalCompletion,
            'totalAttempts'     => $totalAttempts,
            'totalEnrollments'  => count($enrollments),
        ]);
    }

    // ─── Admin: Course Analytics ──────────────────────────────────────────
    #[Route('/admin/analytics', name: 'admin_analytics')]
    public function adminAnalytics(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array($user->getRole(), ['ADMIN', 'HR'])) {
            return $this->redirectToRoute('app_home');
        }

        $formations = $em->getRepository(Formation::class)->findBy([], ['dateDebut' => 'DESC']);

        $stats = [];
        $totalEnrollments = 0;
        $totalApproved    = 0;
        $totalAttempts    = 0;
        $totalScore       = 0;

        foreach ($formations as $formation) {
            $allEnroll     = $em->getRepository(FormationEnrollment::class)->findBy(['formation' => $formation]);
            $approvedCount = count(array_filter($allEnroll, fn($e) => $e->getStatus() === FormationEnrollment::STATUS_APPROVED));
            $pendingCount  = count(array_filter($allEnroll, fn($e) => $e->getStatus() === FormationEnrollment::STATUS_PENDING));
            $rejectedCount = count(array_filter($allEnroll, fn($e) => $e->getStatus() === FormationEnrollment::STATUS_REJECTED));

            $seances = $em->getRepository(Seance::class)->findBy(['formation' => $formation]);
            $quizIds = [];
            foreach ($seances as $s) {
                if ($s->getQuiz()) $quizIds[] = $s->getQuiz()->getId();
            }

            $formationAttempts = 0;
            $formationScore    = 0;
            if ($quizIds) {
                $attempts = $em->getRepository(QuizAttempt::class)->createQueryBuilder('qa')
                    ->join('qa.quiz', 'q')
                    ->where('q.id IN (:ids)')
                    ->setParameter('ids', $quizIds)
                    ->getQuery()->getResult();
                $formationAttempts = count($attempts);
                $formationScore    = array_sum(array_map(fn($a) => $a->getScore(), $attempts));
            }

            $totalEnrollments += count($allEnroll);
            $totalApproved    += $approvedCount;
            $totalAttempts    += $formationAttempts;
            $totalScore       += $formationScore;

            $stats[] = [
                'formation'  => $formation,
                'total'      => count($allEnroll),
                'approved'   => $approvedCount,
                'pending'    => $pendingCount,
                'rejected'   => $rejectedCount,
                'attempts'   => $formationAttempts,
                'avgScore'   => $formationAttempts > 0 ? round($formationScore / $formationAttempts, 1) : null,
                'successRate'=> $formationAttempts > 0
                    ? round(count(array_filter(
                        $em->getRepository(QuizAttempt::class)->createQueryBuilder('qa')
                            ->join('qa.quiz', 'q')
                            ->where('q.id IN (:ids) AND qa.score >= 10')
                            ->setParameter('ids', $quizIds ?: [0])
                            ->getQuery()->getResult()
                    )) / $formationAttempts * 100)
                    : null,
            ];
        }

        $chartLabels  = array_map(fn($s) => mb_substr($s['formation']->getTitre(), 0, 20), $stats);
        $chartEnroll  = array_map(fn($s) => $s['approved'], $stats);
        $chartScores  = array_map(fn($s) => $s['avgScore'] ?? 0, $stats);

        return $this->render('back/analytics.html.twig', [
            'stats'            => $stats,
            'chartLabels'      => $chartLabels,
            'chartEnroll'      => $chartEnroll,
            'chartScores'      => $chartScores,
            'totalEnrollments' => $totalEnrollments,
            'totalApproved'    => $totalApproved,
            'totalAttempts'    => $totalAttempts,
            'globalAvgScore'   => $totalAttempts > 0 ? round($totalScore / $totalAttempts, 1) : 0,
        ]);
    }
}
