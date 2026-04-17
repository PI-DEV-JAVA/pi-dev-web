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

        $stats = $em->getRepository(Activity::class)->getUserPerformanceStats($this->getUser());

        $activities = $em->getRepository(Activity::class)->findBy(
            ['employee' => $this->getUser()],
            ['activityDate' => 'DESC']
        );

        return $this->render('front/account/activities.html.twig', [
            'activities' => $activities,
            'stats' => $stats,
        ]);
    }

    #[Route('/activities/{id}', name: 'app_activity_detail', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function detail(Activity $activity, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Ensure the employee owns this activity
        if ($activity->getEmployee() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Cette activité ne vous appartient pas.");
        }

        if ($request->isMethod('POST')) {
            $report = $request->request->get('employeeReport');
            if ($report !== null) {
                $activity->setEmployeeReport(trim($report));
                // Register exactly when it was submitted
                $activity->setSubmittedAt(new \DateTime());
                // If they update their report after being rejected, reset to pending
                if ($activity->getStatus() === 'REJECTED') {
                    $activity->setStatus('PENDING');
                }
                
                $em->flush();
                $this->addFlash('success', 'Votre rapport a été enregistré/mis à jour.');
                return $this->redirectToRoute('app_activity_detail', ['id' => $activity->getId()]);
            }
        }

        return $this->render('front/account/activity_detail.html.twig', [
            'activity' => $activity,
        ]);
    }

    #[Route('/activities/{id}/timer-save', name: 'app_activity_timer_save', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function timerSave(Activity $activity, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($activity->getEmployee() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $seconds = isset($data['seconds']) ? (int)$data['seconds'] : 0;

        if ($seconds > 0) {
            $activity->setTimeSpent($seconds);
            $em->flush();
        }

        return new JsonResponse(['success' => true, 'total' => $activity->getTimeSpent()]);
    }
}
