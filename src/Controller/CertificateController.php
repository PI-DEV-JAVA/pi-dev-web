<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\FormationEnrollment;
use App\Entity\QuizAttempt;
use App\Entity\Seance;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class CertificateController extends AbstractController
{
    public function __construct(private readonly Environment $twig) {}

    // ─────────────────────────────────────────────
    //  DOWNLOAD CERTIFICATE PDF
    // ─────────────────────────────────────────────
    #[Route('/courses/{id}/certificate', name: 'app_certificate', requirements: ['id' => '\d+'])]
    public function download(Formation $formation, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User || $user->getRole() !== 'CANDIDATE') {
            return $this->redirectToRoute('app_login');
        }

        $enrollment = $em->getRepository(FormationEnrollment::class)->findOneBy([
            'user'      => $user,
            'formation' => $formation,
            'status'    => FormationEnrollment::STATUS_APPROVED,
        ]);

        if (!$enrollment) {
            $this->addFlash('danger', 'Vous n\'êtes pas inscrit(e) à cette formation.');
            return $this->redirectToRoute('app_course_detail', ['id' => $formation->getId()]);
        }

        // Get all quiz attempts for this user in this formation
        $seances = $em->getRepository(Seance::class)->findBy(['formation' => $formation]);

        $attempts     = [];
        $totalScore   = 0;
        $attemptCount = 0;

        foreach ($seances as $seance) {
            if ($seance->getQuiz()) {
                $attempt = $em->getRepository(QuizAttempt::class)->findOneBy([
                    'user' => $user,
                    'quiz' => $seance->getQuiz(),
                ]);
                if ($attempt) {
                    $attempts[]  = ['seance' => $seance, 'attempt' => $attempt];
                    $totalScore  += $attempt->getScore();
                    $attemptCount++;
                }
            }
        }

        if ($attemptCount === 0) {
            $this->addFlash('danger', 'Vous devez compléter au moins un quiz pour obtenir votre certificat.');
            return $this->redirectToRoute('app_course_detail', ['id' => $formation->getId()]);
        }

        $avgScore  = round($totalScore / $attemptCount, 1);
        $avgPercent = round($avgScore / 20 * 100, 1);

        // ── 70% threshold check ──
        if ($avgPercent < 70) {
            $this->addFlash('danger', sprintf(
                'Votre moyenne est de %.1f%% (%.1f/20). Vous devez atteindre au moins 70%% pour obtenir le certificat.',
                $avgPercent,
                $avgScore
            ));
            return $this->redirectToRoute('app_course_detail', ['id' => $formation->getId()]);
        }

        // ── Generate QR Code (absolute URL to the verify page) ──
        $verifyUrl = $this->generateUrl(
            'app_certificate_verify',
            ['userId' => $user->getId(), 'formationId' => $formation->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $qrResult = Builder::create()
            ->writer(new SvgWriter())
            ->data($verifyUrl)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(90)
            ->margin(0)
            ->build();

        // Clean SVG for Dompdf:
        // 1. Strip the XML declaration (confuses Dompdf inline SVG parser)
        // 2. Force explicit width/height attributes
        // 3. Ensure foreground is black (some SVG writers use fill="currentColor")
        $qrSvg = $qrResult->getString();
        $qrSvg = preg_replace('/^<\?xml[^?]*\?>\s*/i', '', trim($qrSvg));
        $qrSvg = preg_replace('/<svg\b/', '<svg width="90" height="90"', $qrSvg, 1);
        $qrSvg = str_replace('fill="currentColor"', 'fill="#000000"', $qrSvg);

        // ── Render certificate HTML ──
        $html = $this->twig->render('certificate/course_certificate.html.twig', [
            'user'       => $user,
            'formation'  => $formation,
            'enrollment' => $enrollment,
            'attempts'   => $attempts,
            'avgScore'   => $avgScore,
            'avgPercent' => $avgPercent,
            'date'       => new \DateTime(),
            'qrSvg'      => $qrSvg,
            'verifyUrl'  => $verifyUrl,
        ]);

        // ── Generate PDF ──
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);

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

    // ─────────────────────────────────────────────
    //  VERIFY CERTIFICATE (public page, scannable)
    // ─────────────────────────────────────────────
    #[Route('/certificate/verify/{userId}/{formationId}', name: 'app_certificate_verify', requirements: ['userId' => '\d+', 'formationId' => '\d+'])]
    public function verify(int $userId, int $formationId, EntityManagerInterface $em): Response
    {
        $user      = $em->getRepository(User::class)->find($userId);
        $formation = $em->getRepository(Formation::class)->find($formationId);

        if (!$user || !$formation) {
            return $this->render('certificate/verify.html.twig', [
                'valid'     => false,
                'user'      => null,
                'formation' => null,
                'avgScore'  => null,
                'date'      => null,
            ]);
        }

        $enrollment = $em->getRepository(FormationEnrollment::class)->findOneBy([
            'user'      => $user,
            'formation' => $formation,
            'status'    => FormationEnrollment::STATUS_APPROVED,
        ]);

        if (!$enrollment) {
            return $this->render('certificate/verify.html.twig', [
                'valid'     => false,
                'user'      => $user,
                'formation' => $formation,
                'avgScore'  => null,
                'date'      => null,
            ]);
        }

        $seances      = $em->getRepository(Seance::class)->findBy(['formation' => $formation]);
        $totalScore   = 0;
        $attemptCount = 0;

        foreach ($seances as $seance) {
            if ($seance->getQuiz()) {
                $attempt = $em->getRepository(QuizAttempt::class)->findOneBy([
                    'user' => $user,
                    'quiz' => $seance->getQuiz(),
                ]);
                if ($attempt) {
                    $totalScore  += $attempt->getScore();
                    $attemptCount++;
                }
            }
        }

        $avgScore   = $attemptCount > 0 ? round($totalScore / $attemptCount, 1) : 0;
        $avgPercent = round($avgScore / 20 * 100, 1);
        $valid      = $avgPercent >= 70;

        return $this->render('certificate/verify.html.twig', [
            'valid'      => $valid,
            'user'       => $user,
            'formation'  => $formation,
            'avgScore'   => $avgScore,
            'avgPercent' => $avgPercent,
            'date'       => $enrollment->getRespondedAt() ?? $enrollment->getRequestedAt(),
        ]);
    }
}
