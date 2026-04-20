<?php

namespace App\Controller;

use App\Entity\Choix;
use App\Entity\Formation;
use App\Entity\FormationEnrollment;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\QuizAttempt;
use App\Entity\Seance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CourseController extends AbstractController
{
    // ─────────────────────────────────────────────
    //  LIST formations (public)
    // ─────────────────────────────────────────────
    #[Route('/courses', name: 'app_courses')]
    public function list(EntityManagerInterface $em): Response
    {
        $formations = $em->getRepository(Formation::class)->findBy([], ['dateDebut' => 'DESC']);

        // For each formation, get current user enrollment status
        $enrollments = [];
        $user = $this->getUser();
        if ($user && $user->getRole() === 'CANDIDATE') {
            $myEnrollments = $em->getRepository(FormationEnrollment::class)->findBy(['user' => $user]);
            foreach ($myEnrollments as $e) {
                $enrollments[$e->getFormation()->getId()] = $e->getStatus();
            }
        }

        return $this->render('front/courses/list.html.twig', [
            'formations'  => $formations,
            'enrollments' => $enrollments,
        ]);
    }

    // ─────────────────────────────────────────────
    //  MY FORMATIONS (candidate: enrolled formations filtered by status)
    // ─────────────────────────────────────────────
    #[Route('/my-formations', name: 'app_my_formations')]
    public function myFormations(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'CANDIDATE') {
            return $this->redirectToRoute('app_courses');
        }

        $statusFilter = $request->query->get('status', ''); // APPROVED | PENDING | REJECTED | ''

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
    //  DETAIL (public, but seances/quizzes locked for non-enrolled)
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
        if ($user && $user->getRole() === 'CANDIDATE') {
            $enrollment = $em->getRepository(FormationEnrollment::class)->findOneBy([
                'user'      => $user,
                'formation' => $formation,
            ]);
        }

        // Check if user already attempted each quiz (to show result)
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

        return $this->render('front/courses/detail.html.twig', [
            'formation'       => $formation,
            'seances'         => $seances,
            'enrollment'      => $enrollment,
            'attemptedQuizIds'=> $attemptedQuizIds,
        ]);
    }

    // ─────────────────────────────────────────────
    //  APPLY to formation (candidate only)
    // ─────────────────────────────────────────────
    #[Route('/courses/{id}/apply', name: 'app_course_apply', methods: ['POST'])]
    public function apply(Formation $formation, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'CANDIDATE') {
            return $this->redirectToRoute('app_login');
        }

        $existing = $em->getRepository(FormationEnrollment::class)->findOneBy([
            'user'      => $user,
            'formation' => $formation,
        ]);

        if (!$existing) {
            $enrollment = new FormationEnrollment();
            $enrollment->setUser($user);
            $enrollment->setFormation($formation);
            $em->persist($enrollment);
            $em->flush();
            $this->addFlash('success', 'Votre demande d\'inscription a été envoyée à l\'administrateur.');
        } else {
            $this->addFlash('info', 'Vous avez déjà une demande en cours pour cette formation.');
        }

        return $this->redirectToRoute('app_course_detail', ['id' => $formation->getId()]);
    }

    // ─────────────────────────────────────────────
    //  QUIZ — display (enrolled & approved + seance ended + within 24h)
    // ─────────────────────────────────────────────
    #[Route('/quiz/{id}', name: 'app_quiz', requirements: ['id' => '\d+'])]
    public function quiz(Quiz $quiz, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Must be logged in as candidate
        if (!$user || $user->getRole() !== 'CANDIDATE') {
            $this->addFlash('danger', 'Vous devez être connecté en tant que candidat pour passer ce quiz.');
            return $this->redirectToRoute('app_login');
        }

        $seance = $quiz->getSeance();
        $formation = $seance ? $seance->getFormation() : $quiz->getFormation();

        // Check enrollment
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

        // Check quiz window (seance must be finished, within 24h)
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

        // Check if already attempted
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
    //  QUIZ — result page for already-attempted
    // ─────────────────────────────────────────────
    #[Route('/quiz/{id}/result', name: 'app_quiz_result', requirements: ['id' => '\d+'])]
    public function quizResult(Quiz $quiz, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

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
            'quiz'      => $quiz,
            'attempt'   => $attempt,
            'formation' => $formation,
            'results'   => null,
            'score'     => $attempt->getScore(),
            'cheated'   => $attempt->isCheated(),
        ]);
    }

    // ─────────────────────────────────────────────
    //  QUIZ — submit answers
    // ─────────────────────────────────────────────
    #[Route('/quiz/{id}/submit', name: 'app_quiz_submit', methods: ['POST'])]
    public function submitQuiz(Quiz $quiz, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'CANDIDATE') {
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

        $cheated       = (bool)$request->request->get('cheated', 0);
        $tabSwitches   = (int)$request->request->get('tab_switches', 0);

        $attempt = new QuizAttempt();
        $attempt->setUser($user);
        $attempt->setQuiz($quiz);
        $attempt->setCheated($cheated);
        $attempt->setTabSwitchCount($tabSwitches);

        if ($cheated) {
            $attempt->setScore(0);
            $em->persist($attempt);
            $em->flush();

            $seance = $quiz->getSeance();
            $formation = $seance ? $seance->getFormation() : $quiz->getFormation();

            return $this->render('front/courses/quiz_result.html.twig', [
                'quiz'      => $quiz,
                'attempt'   => $attempt,
                'formation' => $formation,
                'results'   => null,
                'score'     => 0,
                'cheated'   => true,
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
                if ($c->isCorrect())           $correctAnswer = $c;
                if ($c->getId() == $answer)    $userAnswer    = $c;
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
        $em->flush();

        $seance    = $quiz->getSeance();
        $formation = $seance ? $seance->getFormation() : $quiz->getFormation();

        return $this->render('front/courses/quiz_result.html.twig', [
            'quiz'      => $quiz,
            'attempt'   => $attempt,
            'formation' => $formation,
            'results'   => $results,
            'score'     => $scoreOn20,
            'cheated'   => false,
        ]);
    }
}
