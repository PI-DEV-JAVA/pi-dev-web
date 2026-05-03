<?php

namespace App\Controller;

use App\Entity\Activity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ActivityController extends AbstractController
{
    #[Route('/activities', name: 'app_activities')]
    public function list(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $activities = $em->getRepository(Activity::class)->findBy(
            ['employee' => $this->getUser()],
            ['activityDate' => 'DESC']
        );

        return $this->render('front/account/activities.html.twig', [
            'activities' => $activities,
        ]);
    }

    #[Route('/activities/{id}', name: 'app_activity_detail', requirements: ['id' => '\d+'])]
    public function detail(int $id, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $activity = $em->getRepository(Activity::class)->find($id);
        if (!$activity || $activity->getEmployee() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cette activité ne vous appartient pas.');
        }

        return $this->render('front/account/activity_detail.html.twig', [
            'activity' => $activity,
        ]);
    }

    #[Route('/activities/{id}/timer-save', name: 'app_activity_timer_save', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function timerSave(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $activity = $em->getRepository(Activity::class)->find($id);
        if (!$activity || $activity->getEmployee() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $seconds = (int)($data['seconds'] ?? 0);

        if ($seconds > 0) {
            $addedHours = round($seconds / 3600, 2);
            $currentHours = (float)($activity->getHoursWorked() ?? 0);
            $activity->setHoursWorked((string)($currentHours + $addedHours));
            $em->flush();
            return new JsonResponse(['success' => true, 'totalHours' => $activity->getHoursWorked()]);
        }

        return new JsonResponse(['success' => false, 'error' => 'No time added'], 400);
    }

    #[Route('/activities/{id}/submit-report', name: 'app_activity_submit_report', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function submitReport(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $activity = $em->getRepository(Activity::class)->find($id);
        if (!$activity || $activity->getEmployee() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cette activité ne vous appartient pas.');
        }

        $reportContent = trim($request->request->get('userReport', ''));
        if ($reportContent) {
            $activity->setUserReport($reportContent);
            $activity->setReportStatus('SUBMITTED');

            $deadline = $activity->getExpectedDeadline();
            if ($deadline) {
                $now = new \DateTime();
                if ($now > $deadline) {
                    $activity->setIsLateSubmission(true);
                    $diff = $now->diff($deadline);
                    $hours = ($diff->days * 24) + $diff->h;
                    $activity->setDelayInHours($hours);
                } else {
                    $activity->setIsLateSubmission(false);
                    $activity->setDelayInHours(0);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Votre rapport a été soumis avec succès.');
        } else {
            $this->addFlash('danger', 'Le rapport ne peut pas être vide.');
        }

        return $this->redirectToRoute('app_activity_detail', ['id' => $id]);
    }
}

