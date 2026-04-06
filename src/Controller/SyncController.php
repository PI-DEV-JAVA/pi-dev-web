<?php

namespace App\Controller;

use App\Entity\Sync;
use App\Entity\SyncMessage;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SyncController extends AbstractController
{
    #[Route('/my-circle', name: 'app_my_circle')]
    public function circle(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $syncs = $em->getRepository(Sync::class)->createQueryBuilder('s')
            ->where('s.sender = :user OR s.receiver = :user')
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()->getResult();

        return $this->render('front/account/circle.html.twig', [
            'syncs' => $syncs,
        ]);
    }

    #[Route('/chat/{id}', name: 'app_chat', requirements: ['id' => '\d+'])]
    public function chat(Sync $sync, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($sync->getSender() !== $user && $sync->getReceiver() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $messages = $em->getRepository(SyncMessage::class)->findBy(
            ['sync' => $sync],
            ['createdAt' => 'ASC']
        );

        $otherUser = $sync->getSender() === $user ? $sync->getReceiver() : $sync->getSender();

        return $this->render('front/account/chat.html.twig', [
            'sync' => $sync,
            'messages' => $messages,
            'otherUser' => $otherUser,
        ]);
    }

    #[Route('/chat/{id}/send', name: 'app_chat_send', methods: ['POST'])]
    public function sendMessage(Sync $sync, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($sync->getSender() !== $user && $sync->getReceiver() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $content = trim($request->request->get('message', ''));
        if ($content) {
            $msg = new SyncMessage();
            $msg->setSync($sync);
            $msg->setSender($user);
            $msg->setMessage($content);
            $em->persist($msg);
            $em->flush();
        }

        return $this->redirectToRoute('app_chat', ['id' => $sync->getId()]);
    }
}
