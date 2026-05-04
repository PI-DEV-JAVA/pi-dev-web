<?php

namespace App\Controller;

use App\Entity\Choix;
use App\Entity\Formation;
use App\Entity\FormationEnrollment;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\QuizAttempt;
use App\Entity\Seance;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CourseController extends AbstractController
{
    // ─────────────────────────────────────────────
    //  LIST formations (public) — with sorting
    // ─────────────────────────────────────────────
    #[Route('/courses', name: 'app_courses')]
    public function list(Request $request, EntityManagerInterface $em): Response
    {
        $sort = $request->query->get('sort', 'date'); // date | name | enrollments | price
        $type = $request->query->get('type', '');     // '' | free | paid

        // Base query
        $qb = $em->getRepository(Formation::class)->createQueryBuilder('f')
            ->leftJoin('f.enrollments', 'e');

        // Filter by type
        if ($type === 'free') {
            $qb->where('f.isPaid = false');
        } elseif ($type === 'paid') {
            $qb->where('f.isPaid = true');
        }

        // Sorting
        switch ($sort) {
            case 'name':
                $qb->orderBy('f.titre', 'ASC');
                break;
            case 'enrollments':
                $qb->groupBy('f.id')->orderBy('COUNT(e.id)', 'DESC');
                break;
            case 'price':
                $qb->orderBy('f.isPaid', 'ASC')->addOrderBy('f.pricePoints', 'ASC');
                break;
            default: // date
                $qb->orderBy('f.dateDebut', 'DESC');
        }

        $formations = $qb->getQuery()->getResult();

        // For each formation, count total enrollments
        $enrollmentCounts = [];
        foreach ($formations as $f) {
            $enrollmentCounts[$f->getId()] = $em->getRepository(FormationEnrollment::class)
                ->count(['formation' => $f, 'status' => FormationEnrollment::STATUS_APPROVED]);
        }

        // For current user enrollment status
        $enrollments = [];
        $user = $this->getUser();
        $pointsBalance = 0;
        if ($user instanceof User && $user->getRole() === 'CANDIDATE') {
            $myEnrollments = $em->getRepository(FormationEnrollment::class)->findBy(['user' => $user]);
            foreach ($myEnrollments as $e) {
                $enrollments[$e->getFormation()->getId()] = $e->getStatus();
            }
            $pointsBalance = $user->getPointsBalance();
        }

        return $this->render('front/courses/list.html.twig', [
            'formations'       => $formations,
            'enrollments'      => $enrollments,
            'enrollmentCounts' => $enrollmentCounts,
            'sort'             => $sort,
            'type'             => $type,
            'pointsBalance'    => $pointsBalance,
        ]);
    }

    // ─────────────────────────────────────────────
    //  MY FORMATIONS (candidate)
    // ─────────────────────────────────────────────
    #[Route('/my-formations', name: 'app_my_formations')]
    public function myFormations(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRole() !== 'CANDIDATE') {
            return $this->redirectToRoute('app_courses');
        }

        $statusFilter = $request->query->get('status', '');

        $criteria = ['user' => $user];
        if (in_array($statusFilter, ['APPROVED', 'PENDING', 'REJECTED'])) {
            $criteria['status'] = $statusFilter;
        }

        $enrollments = $em->getRepository(FormationEnrollment::class)->findBy(
            $criteria,
            ['requestedAt' => 'DESC']
        );

        return $this->render('front/courses/my_formations.html.twig', [
            'enrollments'  => $enrollments,
            'statusFilter' => $statusFilter,
        ]);
    }

    // ─────────────────────────────────────────────
    //  DETAIL
    // ─────────────────────────────────────────────
    #[Route('/courses/{id}', name: 'app_course_detail', requirements: ['id' => '\d+'])]
    public function detail(Formation $formation, EntityManagerInterface $em): Response
    {
        $seances = $em->getRepository(Seance::class)->findBy(
            ['formation' => $formation],
            ['dateDebut' => 'ASC']
        );

        $user = $this->getUser();
        $enrollment = null;
        if ($user instanceof User && $user->getRole() === 'CANDIDATE') {
            $enrollment = $em->getRepository(FormationEnrollment::class)->findOneBy([
                'user'      => $user,
                'formation' => $formation,
            ]);
        }

        // Check if user already attempted each quiz
        $attemptedQuizIds = [];
        if ($user) {
            foreach ($seances as $s) {
                if ($s->getQuiz()) {
                    $attempt = $em->getRepository(QuizAttempt::class)->findOneBy([
                        'user' => $user,
                        'quiz' => $s->getQuiz(),
                    ]);
                    if ($attempt) {
                        $attemptedQuizIds[$s->getQuiz()->getId()] = $attempt;
                    }
                }
            }
        }

        // Compute average score for certificate eligibility
        $avgScore = null;
        $canGetCertificate = false;
        if ($user && $enrollment && $enrollment->isApproved()) {
            $totalScore = 0;
            $attemptCount = 0;
            $totalQuizzes = 0;
            foreach ($seances as $seance) {
                if ($seance->getQuiz()) {
                    $totalQuizzes++;
                    $attempt = $em->getRepository(QuizAttempt::class)->findOneBy([
                        'user' => $user,
                        'quiz' => $seance->getQuiz(),
                    ]);
                    if ($attempt) {
                        $totalScore += $attempt->getScore();
                        $attemptCount++;
                    }
                }
            }
            if ($attemptCount > 0) {
                $avgScore = round($totalScore / $attemptCount, 1);
                // 70% of 20 = 14
                $canGetCertificate = ($avgScore / 20 * 100) >= 70;
            }
        }

        return $this->render('front/courses/detail.html.twig', [
            'formation'         => $formation,
            'seances'           => $seances,
            'enrollment'        => $enrollment,
            'attemptedQuizIds'  => $attemptedQuizIds,
            'avgScore'          => $avgScore,
            'canGetCertificate' => $canGetCertificate,
        ]);
    }

    // ─────────────────────────────────────────────
    //  APPLY to formation (candidate only)
    // ─────────────────────────────────────────────
    #[Route('/courses/{id}/apply', name: 'app_course_apply', methods: ['POST'])]
    public function apply(Formation $formation, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRole() !== 'CANDIDATE') {
            return $this->redirectToRoute('app_login');
        }

        $existing = $em->getRepository(FormationEnrollment::class)->findOneBy([
            'user'      => $user,
            'formation' => $formation,
        ]);

        if ($existing) {
            $this->addFlash('info', 'Vous avez déjà une demande en cours pour cette formation.');
            return $this->redirectToRoute('app_course_detail', ['id' => $formation->getId()]);
        }

        // If paid formation — check points balance
        if ($formation->isPaid()) {
            $cost = $formation->getPricePoints() ?? 0;
            if ($user->getPointsBalance() < $cost) {
                $this->addFlash('danger', sprintf(
                    'Solde insuffisant ! Cette formation coûte %d pts. Votre solde : %d pts.',
                    $cost,
                    $user->getPointsBalance()
                ));
                return $this->redirectToRoute('app_course_detail', ['id' => $formation->getId()]);
            }
            // Deduct points
            $user->deductPoints($cost);
        }

        $enrollment = new FormationEnrollment();
        $enrollment->setUser($user);
        $enrollment->setFormation($formation);
        // Paid formations: auto-approve after payment; free: wait for admin
        if ($formation->isPaid()) {
            $enrollment->setStatus(FormationEnrollment::STATUS_APPROVED);
            $enrollment->setRespondedAt(new \DateTime());
            $this->addFlash('success', sprintf(
                'Inscription confirmée ! %d pts ont été déduits de votre solde.',
                $formation->getPricePoints() ?? 0
            ));
        } else {
            $this->addFlash('success', 'Votre demande d\'inscription a été envoyée à l\'administrateur.');
        }

        $em->persist($enrollment);
        $em->flush();

        // ── Notify all admins of the new enrollment ──
        try {
            $profile = $user->getProfile();
            $candidateName = ($profile && $profile->getFirstName())
                ? trim($profile->getFirstName() . ' ' . ($profile->getLastName() ?? ''))
                : $user->getEmail();

            $enrollStatus = $formation->isPaid()
                ? 'inscription confirmée (formation payante)'
                : 'demande en attente d\'approbation';

            $ns     = new NotificationService($em);
            $admins = $em->getRepository(User::class)->findBy(['role' => 'ADMIN']);
            foreach ($admins as $admin) {
                $ns->notify(
                    $admin,
                    'ENROLLMENT',
                    '📚 Nouvelle inscription — ' . $formation->getTitre(),
                    sprintf('%s a rejoint la formation « %s » (%s).', $candidateName, $formation->getTitre(), $enrollStatus),
                    '/admin/courses/' . $formation->getId() . '/edit'
                );
            }
        } catch (\Throwable) {
            // Never block enrollment if notification fails
        }

        return $this->redirectToRoute('app_course_detail', ['id' => $formation->getId()]);
    }

    // ─────────────────────────────────────────────
    //  BUY POINTS — display packs page
    // ─────────────────────────────────────────────
    #[Route('/points/buy', name: 'app_points_buy', methods: ['GET'])]
    public function buyPoints(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRole() !== 'CANDIDATE') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/courses/buy_points.html.twig', [
            'user'  => $user,
            'packs' => \App\Service\StripeService::PACKS,
        ]);
    }

    // ─────────────────────────────────────────────
    //  STRIPE — create checkout session
    // ─────────────────────────────────────────────
    #[Route('/points/checkout', name: 'app_points_checkout', methods: ['POST'])]
    public function stripeCheckout(Request $request, \App\Service\StripeService $stripe): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRole() !== 'CANDIDATE') {
            return $this->redirectToRoute('app_login');
        }

        $points = (int)$request->request->get('points', 0);
        if (!isset(\App\Service\StripeService::PACKS[$points])) {
            $this->addFlash('danger', 'Pack de points invalide.');
            return $this->redirectToRoute('app_points_buy');
        }

        try {
            $successUrl = $this->generateUrl('app_points_success', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl  = $this->generateUrl('app_points_cancel', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

            $checkoutUrl = $stripe->createCheckoutSession(
                $user->getId(),
                $points,
                $successUrl,
                $cancelUrl
            );

            return $this->redirect($checkoutUrl);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur de paiement : ' . $e->getMessage());
            return $this->redirectToRoute('app_points_buy');
        }
    }

    // ─────────────────────────────────────────────
    //  STRIPE — success callback
    // ─────────────────────────────────────────────
    #[Route('/points/success', name: 'app_points_success', methods: ['GET'])]
    public function stripeSuccess(Request $request, \App\Service\StripeService $stripe, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $sessionId = $request->query->get('session_id');
        if (!$sessionId) {
            return $this->redirectToRoute('app_points_buy');
        }

        $session = $stripe->retrieveSession($sessionId);
        if (!$session) {
            $this->addFlash('danger', 'Le paiement n\'a pas pu être vérifié. Contactez le support si vous avez été débité.');
            return $this->redirectToRoute('app_points_buy');
        }

        $points = (int)($session->metadata->points ?? 0);
        if ($points > 0) {
            $user->addPoints($points);
            $em->flush();
        }

        return $this->render('front/courses/payment_success.html.twig', [
            'points'  => $points,
            'balance' => $user->getPointsBalance(),
        ]);
    }

    // ─────────────────────────────────────────────
    //  STRIPE — cancel callback
    // ─────────────────────────────────────────────
    #[Route('/points/cancel', name: 'app_points_cancel', methods: ['GET'])]
    public function stripeCancel(): Response
    {
        $this->addFlash('info', 'Le paiement a été annulé. Aucun montant n\'a été débité.');
        return $this->redirectToRoute('app_points_buy');
    }

    // ─────────────────────────────────────────────
    //  QUIZ — display
    // ─────────────────────────────────────────────
    #[Route('/quiz/{id}', name: 'app_quiz', requirements: ['id' => '\d+'])]
    public function quiz(Quiz $quiz, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User || $user->getRole() !== 'CANDIDATE') {
            $this->addFlash('danger', 'Vous devez être connecté en tant que candidat pour passer ce quiz.');
            return $this->redirectToRoute('app_login');
        }

        $seance = $quiz->getSeance();
        $formation = $seance ? $seance->getFormation() : $quiz->getFormation();

        if ($formation) {
            $enrollment = $em->getRepository(FormationEnrollment::class)->findOneBy([
                'user'      => $user,
                'formation' => $formation,
            ]);
            if (!$enrollment || !$enrollment->isApproved()) {
                $this->addFlash('danger', 'Vous n\'êtes pas inscrit à cette formation ou votre demande n\'a pas encore été approuvée.');
                return $this->redirectToRoute('app_courses');
            }
        }

        if ($seance) {
            if (!$seance->isTerminee()) {
                $this->addFlash('warning', 'Le quiz sera disponible à la fin de la séance "' . $seance->getTitre() . '".');
                return $this->redirectToRoute('app_course_detail', ['id' => $formation->getId()]);
            }
            if (!$seance->isQuizUnlocked()) {
                $this->addFlash('warning', 'Le délai de 24h pour passer ce quiz est expiré.');
                return $this->redirectToRoute('app_course_detail', ['id' => $formation->getId()]);
            }
        }

        $existing = $em->getRepository(QuizAttempt::class)->findOneBy([
            'user' => $user,
            'quiz' => $quiz,
        ]);
        if ($existing) {
            return $this->redirectToRoute('app_quiz_result', ['id' => $quiz->getId()]);
        }

        $questions = $em->getRepository(Question::class)->findBy(['quiz' => $quiz]);

        return $this->render('front/courses/quiz.html.twig', [
            'quiz'      => $quiz,
            'questions' => $questions,
            'seance'    => $seance,
        ]);
    }

    // ─────────────────────────────────────────────
    //  QUIZ — result page
    // ─────────────────────────────────────────────
    #[Route('/quiz/{id}/result', name: 'app_quiz_result', requirements: ['id' => '\d+'])]
    public function quizResult(Quiz $quiz, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) return $this->redirectToRoute('app_login');

        $attempt = $em->getRepository(QuizAttempt::class)->findOneBy([
            'user' => $user,
            'quiz' => $quiz,
        ]);

        if (!$attempt) {
            return $this->redirectToRoute('app_quiz', ['id' => $quiz->getId()]);
        }

        $seance = $quiz->getSeance();
        $formation = $seance ? $seance->getFormation() : $quiz->getFormation();

        return $this->render('front/courses/quiz_result.html.twig', [
            'quiz'        => $quiz,
            'attempt'     => $attempt,
            'formation'   => $formation,
            'results'     => null,
            'score'       => $attempt->getScore(),
            'cheated'     => $attempt->isCheated(),
            'pointsEarned'=> 0,
        ]);
    }

    // ─────────────────────────────────────────────
    //  QUIZ — submit answers
    // ─────────────────────────────────────────────
    #[Route('/quiz/{id}/submit', name: 'app_quiz_submit', methods: ['POST'])]
    public function submitQuiz(Quiz $quiz, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRole() !== 'CANDIDATE') {
            return $this->redirectToRoute('app_login');
        }

        // Prevent double submission
        $existing = $em->getRepository(QuizAttempt::class)->findOneBy([
            'user' => $user,
            'quiz' => $quiz,
        ]);
        if ($existing) {
            return $this->redirectToRoute('app_quiz_result', ['id' => $quiz->getId()]);
        }

        $cheated     = (bool)$request->request->get('cheated', 0);
        $tabSwitches = (int)$request->request->get('tab_switches', 0);

        $attempt = new QuizAttempt();
        $attempt->setUser($user);
        $attempt->setQuiz($quiz);
        $attempt->setCheated($cheated);
        $attempt->setTabSwitchCount($tabSwitches);

        $seance    = $quiz->getSeance();
        $formation = $seance ? $seance->getFormation() : $quiz->getFormation();

        if ($cheated) {
            $attempt->setScore(0);
            $em->persist($attempt);
            $em->flush();

            return $this->render('front/courses/quiz_result.html.twig', [
                'quiz'         => $quiz,
                'attempt'      => $attempt,
                'formation'    => $formation,
                'results'      => null,
                'score'        => 0,
                'cheated'      => true,
                'pointsEarned' => 0,
            ]);
        }

        $questions = $em->getRepository(Question::class)->findBy(['quiz' => $quiz]);
        $correct   = 0;
        $total     = count($questions);
        $results   = [];

        foreach ($questions as $question) {
            $answer = $request->request->get('q_' . $question->getId());
            $choix  = $em->getRepository(Choix::class)->findBy(['question' => $question]);

            $correctAnswer = null;
            $userAnswer    = null;
            foreach ($choix as $c) {
                if ($c->isCorrect())          $correctAnswer = $c;
                if ($c->getId() == $answer)   $userAnswer    = $c;
            }

            $isCorrect = $userAnswer && $correctAnswer && $userAnswer->getId() === $correctAnswer->getId();
            if ($isCorrect) $correct++;

            $results[] = [
                'question'      => $question,
                'userAnswer'    => $userAnswer,
                'correctAnswer' => $correctAnswer,
                'isCorrect'     => $isCorrect,
            ];
        }

        // Score out of 20
        $scoreOn20 = $total > 0 ? round(($correct / $total) * 20, 2) : 0;

        $attempt->setScore($scoreOn20);
        $em->persist($attempt);

        // ── Points reward: score > 15/20 → add score as points ──
        $pointsEarned = 0;
        if ($scoreOn20 > 15) {
            $pointsEarned = (int)round($scoreOn20);
            $user->addPoints($pointsEarned);
        }

        $em->flush();

        return $this->render('front/courses/quiz_result.html.twig', [
            'quiz'         => $quiz,
            'attempt'      => $attempt,
            'formation'    => $formation,
            'results'      => $results,
            'score'        => $scoreOn20,
            'cheated'      => false,
            'pointsEarned' => $pointsEarned,
        ]);
    }
}
