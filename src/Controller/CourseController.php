<?php

namespace App\Controller;

use App\Entity\Choix;
use App\Entity\Formation;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\Seance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CourseController extends AbstractController
{
    #[Route('/courses', name: 'app_courses')]
    public function list(EntityManagerInterface $em): Response
    {
        $formations = $em->getRepository(Formation::class)->findBy([], ['dateDebut' => 'DESC']);

        return $this->render('front/courses/list.html.twig', [
            'formations' => $formations,
        ]);
    }

    #[Route('/courses/{id}', name: 'app_course_detail', requirements: ['id' => '\d+'])]
    public function detail(Formation $formation, EntityManagerInterface $em): Response
    {
        $seances = $em->getRepository(Seance::class)->findBy(
            ['formation' => $formation],
            ['dateDebut' => 'ASC']
        );

        $quizzes = $em->getRepository(Quiz::class)->findBy(['formation' => $formation]);

        return $this->render('front/courses/detail.html.twig', [
            'formation' => $formation,
            'seances' => $seances,
            'quizzes' => $quizzes,
        ]);
    }

    #[Route('/quiz/{id}', name: 'app_quiz', requirements: ['id' => '\d+'])]
    public function quiz(Quiz $quiz, EntityManagerInterface $em): Response
    {
        $questions = $em->getRepository(Question::class)->findBy(['quiz' => $quiz]);

        // Load choices for each question
        foreach ($questions as $question) {
            $choix = $em->getRepository(Choix::class)->findBy(['question' => $question]);
            $question->loadedChoices = $choix;
        }

        return $this->render('front/courses/quiz.html.twig', [
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    #[Route('/quiz/{id}/submit', name: 'app_quiz_submit', methods: ['POST'])]
    public function submitQuiz(Quiz $quiz, Request $request, EntityManagerInterface $em): Response
    {
        $questions = $em->getRepository(Question::class)->findBy(['quiz' => $quiz]);
        $correct = 0;
        $total = count($questions);
        $results = [];

        foreach ($questions as $question) {
            $answer = $request->request->get('q_' . $question->getId());
            $choix = $em->getRepository(Choix::class)->findBy(['question' => $question]);

            $correctAnswer = null;
            $userAnswer = null;
            foreach ($choix as $c) {
                if ($c->isCorrect()) {
                    $correctAnswer = $c;
                }
                if ($c->getId() == $answer) {
                    $userAnswer = $c;
                }
            }

            $isCorrect = $userAnswer && $correctAnswer && $userAnswer->getId() === $correctAnswer->getId();
            if ($isCorrect)
                $correct++;

            $results[] = [
                'question' => $question,
                'userAnswer' => $userAnswer,
                'correctAnswer' => $correctAnswer,
                'isCorrect' => $isCorrect,
            ];
        }

        $score = $total > 0 ? round(($correct / $total) * 100) : 0;

        return $this->render('front/courses/quiz_result.html.twig', [
            'quiz' => $quiz,
            'results' => $results,
            'score' => $score,
            'correct' => $correct,
            'total' => $total,
        ]);
    }
}
