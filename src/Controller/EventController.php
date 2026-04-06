<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventComment;
use App\Entity\EventLike;
use App\Entity\EventParticipation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/events', name: 'app_events')]
    public function list(EntityManagerInterface $em, Request $request): Response
    {
        $qb = $em->getRepository(Event::class)->createQueryBuilder('e')
            ->orderBy('e.eventDate', 'DESC');

        $type = $request->query->get('type');
        if ($type) {
            $qb->andWhere('e.eventType = :type')->setParameter('type', $type);
        }

        $status = $request->query->get('status');
        if ($status) {
            $qb->andWhere('e.status = :status')->setParameter('status', $status);
        }

        $events = $qb->getQuery()->getResult();

        return $this->render('front/events/list.html.twig', [
            'events' => $events,
            'typeFilter' => $type,
            'statusFilter' => $status,
        ]);
    }

    #[Route('/events/{id}', name: 'app_event_detail', requirements: ['id' => '\d+'])]
    public function detail(Event $event, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $participation = $em->getRepository(EventParticipation::class)->findOneBy([
            'event' => $event,
            'user' => $user,
        ]);

        $liked = $em->getRepository(EventLike::class)->findOneBy([
            'event' => $event,
            'user' => $user,
        ]);

        $comments = $em->getRepository(EventComment::class)->findBy(
            ['event' => $event],
            ['createdAt' => 'DESC']
        );

        $likesCount = $em->getRepository(EventLike::class)->count(['event' => $event]);

        return $this->render('front/events/detail.html.twig', [
            'event' => $event,
            'participation' => $participation,
            'liked' => $liked !== null,
            'comments' => $comments,
            'likesCount' => $likesCount,
        ]);
    }

    #[Route('/events/{id}/participate', name: 'app_event_participate')]
    public function participate(Event $event, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $existing = $em->getRepository(EventParticipation::class)->findOneBy([
            'event' => $event,
            'user' => $user,
        ]);

        if ($existing) {
            $em->remove($existing);
            $this->addFlash('info', 'Participation annulée.');
        } else {
            $p = new EventParticipation();
            $p->setEvent($event);
            $p->setUser($user);
            $p->setStatus('CONFIRMED');
            $em->persist($p);
            $this->addFlash('success', 'Vous êtes inscrit à cet évènement !');
        }
        $em->flush();

        return $this->redirectToRoute('app_event_detail', ['id' => $event->getId()]);
    }

    #[Route('/events/{id}/like', name: 'app_event_like')]
    public function like(Event $event, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $existing = $em->getRepository(EventLike::class)->findOneBy([
            'event' => $event,
            'user' => $user,
        ]);

        if ($existing) {
            $em->remove($existing);
        } else {
            $like = new EventLike();
            $like->setEvent($event);
            $like->setUser($user);
            $em->persist($like);
        }
        $em->flush();

        return $this->redirectToRoute('app_event_detail', ['id' => $event->getId()]);
    }

    #[Route('/events/{id}/comment', name: 'app_event_comment', methods: ['POST'])]
    public function comment(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $content = trim($request->request->get('content', ''));
        if ($content) {
            $comment = new EventComment();
            $comment->setEvent($event);
            $comment->setUser($this->getUser());
            $comment->setContent($content);
            $em->persist($comment);
            $em->flush();
            $this->addFlash('success', 'Commentaire ajouté !');
        }

        return $this->redirectToRoute('app_event_detail', ['id' => $event->getId()]);
    }
}
