<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\FormationEnrollment;
use App\Entity\QuizAttempt;
use App\Entity\Seance;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class CertificateController extends AbstractController
{
    public function __construct(private readonly Environment $twig) {}

    #[Route('/courses/{id}/certificate', name: 'app_certificate', requirements: ['id' => '\d+'])]
    public function download(Formation $formation, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Must be logged in as candidate
        if (!$user || $user->getRole() !== 'CANDIDATE') {
            return $this->redirectToRoute('app_login');
        }

        // Must have APPROVED enrollment
        $enrollment = $em->getRepository(FormationEnrollment::class)->findOneBy([
            'user'      => $user,
            'formation' => $formation,
            'status'    => FormationEnrollment::STATUS_APPROVED,
        ]);

        if (!$enrollment) {
            $this->addFlash('danger', 'Vous n\'êtes pas inscrit(e) à cette formation.');
            return $this->redirectToRoute('app_course_detail', ['id' => $formation->getId()]);
        }

        // Get all seances + their quiz attempts for this user
        $seances = $em->getRepository(Seance::class)->findBy(['formation' => $formation]);

        $attempts = [];
        $totalScore = 0;
        $attemptCount = 0;

        foreach ($seances as $seance) {
            if ($seance->getQuiz()) {
                $attempt = $em->getRepository(QuizAttempt::class)->findOneBy([
                    'user' => $user,
                    'quiz' => $seance->getQuiz(),
                ]);
                if ($attempt) {
                    $attempts[] = ['seance' => $seance, 'attempt' => $attempt];
                    $totalScore += $attempt->getScore();
                    $attemptCount++;
                }
            }
        }

        if ($attemptCount === 0) {
            $this->addFlash('danger', 'Vous devez compléter au moins un quiz pour obtenir votre certificat.');
            return $this->redirectToRoute('app_course_detail', ['id' => $formation->getId()]);
        }

        $avgScore = round($totalScore / $attemptCount, 1);

        // Render the certificate HTML
        $html = $this->twig->render('certificate/course_certificate.html.twig', [
            'user'       => $user,
            'formation'  => $formation,
            'enrollment' => $enrollment,
            'attempts'   => $attempts,
            'avgScore'   => $avgScore,
            'date'       => new \DateTime(),
        ]);

        // Generate PDF with dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = sprintf('certificat-%s-%d.pdf',
            preg_replace('/[^a-z0-9]+/i', '-', $formation->getTitre()),
            $user->getId()
        );

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]
        );
    }
}
