<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventComment;
use App\Entity\EventFeedback;
use App\Entity\EventLike;
use App\Entity\EventParticipation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

        // Feedback: check if user can leave feedback + get existing feedbacks
        $canFeedback = false;
        $myFeedback = null;
        if ($participation) {
            $myFeedback = $em->getRepository(EventFeedback::class)->findOneBy(['participation' => $participation]);
            if (!$myFeedback && $event->getEventDate() < new \DateTime() && in_array($participation->getStatus(), ['CONFIRMED', 'ATTENDED'])) {
                $canFeedback = true;
            }
        }

        // All feedbacks for this event
        $feedbacks = $em->createQueryBuilder()
            ->select('f', 'p', 'u')
            ->from(EventFeedback::class, 'f')
            ->join('f.participation', 'p')
            ->join('p.user', 'u')
            ->where('p.event = :ev')->setParameter('ev', $event)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()->getResult();

        return $this->render('front/events/detail.html.twig', [
            'event' => $event,
            'participation' => $participation,
            'liked' => $liked !== null,
            'comments' => $comments,
            'likesCount' => $likesCount,
            'canFeedback' => $canFeedback,
            'myFeedback' => $myFeedback,
            'feedbacks' => $feedbacks,
        ]);
    }

    #[Route('/events/{id}/participate', name: 'app_event_participate', methods: ['POST'])]
    public function participate(Event $event, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['success' => false, 'message' => 'Connexion requise.'], 401);

        $existing = $em->getRepository(EventParticipation::class)->findOneBy([
            'event' => $event, 'user' => $user,
        ]);

        if ($existing) {
            return new JsonResponse(['success' => false, 'message' => 'Vous êtes déjà inscrit.', 'status' => $existing->getStatus()]);
        }

        // Generate 6-char challenge code
        $challengeCode = strtoupper(substr(md5(random_bytes(16)), 0, 6));
        $qrFullCode = 'EVT_' . $event->getId() . '_USR_' . $user->getId() . '_' . $challengeCode;

        $p = new EventParticipation();
        $p->setEvent($event);
        $p->setUser($user);
        $p->setStatus('PENDING');
        $p->setQrCode($qrFullCode);
        $em->persist($p);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'challengeCode' => $challengeCode,
            'qrData' => $qrFullCode,
            'participationId' => $p->getId(),
        ]);
    }

    #[Route('/events/{id}/verify-participation', name: 'app_event_verify_participation', methods: ['POST'])]
    public function verifyParticipation(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['success' => false, 'message' => 'Connexion requise.'], 401);

        $code = strtoupper(trim($request->request->get('code', '')));
        $participation = $em->getRepository(EventParticipation::class)->findOneBy([
            'event' => $event, 'user' => $user,
        ]);

        if (!$participation) {
            return new JsonResponse(['success' => false, 'message' => 'Aucune inscription trouvée.']);
        }
        if ($participation->getStatus() === 'CONFIRMED') {
            return new JsonResponse(['success' => true, 'message' => 'Déjà confirmé !', 'already' => true]);
        }

        // Extract the 6-char code from the stored qrCode
        $storedQr = $participation->getQrCode();
        $parts = explode('_', $storedQr);
        $expectedCode = end($parts);

        if ($code === $expectedCode) {
            $participation->setStatus('CONFIRMED');
            $em->flush();
            return new JsonResponse(['success' => true, 'message' => 'Participation confirmée ! Bienvenue 🎉']);
        }

        return new JsonResponse(['success' => false, 'message' => 'Code incorrect. Vérifiez et réessayez.']);
    }

    #[Route('/events/{id}/cancel-participation', name: 'app_event_cancel_participation', methods: ['POST'])]
    public function cancelParticipation(Event $event, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $p = $em->getRepository(EventParticipation::class)->findOneBy(['event' => $event, 'user' => $user]);
        if ($p) {
            $em->remove($p);
            $em->flush();
        }
        return new JsonResponse(['success' => true]);
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

    #[Route('/events/{id}/feedback', name: 'app_event_feedback', methods: ['POST'])]
    public function feedback(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $participation = $em->getRepository(EventParticipation::class)->findOneBy([
            'event' => $event, 'user' => $user,
        ]);

        if (!$participation) {
            $this->addFlash('danger', 'Vous devez participer à cet événement.');
            return $this->redirectToRoute('app_event_detail', ['id' => $event->getId()]);
        }

        // Check no existing feedback
        $existing = $em->getRepository(EventFeedback::class)->findOneBy(['participation' => $participation]);
        if ($existing) {
            $this->addFlash('info', 'Vous avez déjà donné votre avis.');
            return $this->redirectToRoute('app_event_detail', ['id' => $event->getId()]);
        }

        $rating = (int) $request->request->get('rating', 5);
        if ($rating < 1 || $rating > 5) $rating = 5;

        $feedback = new EventFeedback();
        $feedback->setParticipation($participation);
        $feedback->setRating($rating);
        $feedback->setComment(trim($request->request->get('comment', '')) ?: null);
        $feedback->setWouldRecommend($request->request->get('recommend', '1') === '1');
        $em->persist($feedback);
        $em->flush();

        $this->addFlash('success', 'Merci pour votre avis !');
        return $this->redirectToRoute('app_event_detail', ['id' => $event->getId()]);
    }
}
