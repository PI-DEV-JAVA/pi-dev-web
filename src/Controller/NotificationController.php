<?php

namespace App\Controller;

use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'app_notifications')]
    public function list(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $notifications = $em->getRepository(Notification::class)->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC'],
            50
        );

        return $this->render('front/account/notifications.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notifications/count', name: 'app_notifications_count')]
    public function count(EntityManagerInterface $em): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['count' => 0]);
        }
        $count = $em->getRepository(Notification::class)->count([
            'user' => $this->getUser(),
            'isRead' => false,
        ]);
        return $this->json(['count' => $count]);
    }

    #[Route('/notifications/{id}/read', name: 'app_notification_read', requirements: ['id' => '\d+'])]
    public function markRead(Notification $notification, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $notification->setIsRead(true);
        $em->flush();

        // Redirect to the notification link if exists
        if ($notification->getLink()) {
            return $this->redirect($notification->getLink());
        }
        return $this->redirectToRoute('app_notifications');
    }

    #[Route('/notifications/read-all', name: 'app_notifications_read_all')]
    public function markAllRead(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $notifications = $em->getRepository(Notification::class)->findBy([
            'user' => $this->getUser(),
            'isRead' => false,
        ]);
        foreach ($notifications as $n) {
            $n->setIsRead(true);
        }
        $em->flush();
        return $this->redirectToRoute('app_notifications');
    }
}
