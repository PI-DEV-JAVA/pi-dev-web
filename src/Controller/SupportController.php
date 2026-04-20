<?php

namespace App\Controller;

use App\Entity\SupportTicket;
use App\Entity\TicketReply;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SupportController extends AbstractController
{
    #[Route('/support', name: 'app_support')]
    public function list(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($user->getRole() === 'ADMIN') {
            $tickets = $em->getRepository(SupportTicket::class)->findBy([], ['createdAt' => 'DESC']);
        } else {
            $tickets = $em->getRepository(SupportTicket::class)->findBy(
                ['user' => $user],
                ['createdAt' => 'DESC']
            );
        }

        return $this->render('front/account/support.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/support/new', name: 'app_support_new', methods: ['POST'])]
    public function newTicket(Request $request, EntityManagerInterface $em): Response
    {
        $subject = trim($request->request->get('subject', ''));
        $description = trim($request->request->get('message', ''));

        if ($subject && $description) {
            $ticket = new SupportTicket();
            $ticket->setUser($this->getUser());
            $ticket->setSubject($subject);
            $ticket->setDescription($description);
            $ticket->setStatus('OPEN');
            $em->persist($ticket);
            $em->flush();
            $this->addFlash('success', 'Ticket créé avec succès !');
        }

        return $this->redirectToRoute('app_support');
    }

    #[Route('/support/{id}', name: 'app_support_detail', requirements: ['id' => '\d+'])]
    public function detail(SupportTicket $ticket, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user->getRole() !== 'ADMIN' && $ticket->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $replies = $em->getRepository(TicketReply::class)->findBy(
            ['ticket' => $ticket],
            ['createdAt' => 'ASC']
        );

        return $this->render('front/account/support_detail.html.twig', [
            'ticket' => $ticket,
            'replies' => $replies,
        ]);
    }

    #[Route('/support/{id}/reply', name: 'app_support_reply', methods: ['POST'])]
    public function reply(SupportTicket $ticket, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user->getRole() !== 'ADMIN' && $ticket->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $content = trim($request->request->get('content', ''));
        if ($content) {
            $reply = new TicketReply();
            $reply->setTicket($ticket);
            $reply->setUser($user);
            $reply->setMessage($content);
            $em->persist($reply);
            $em->flush();
        }

        return $this->redirectToRoute('app_support_detail', ['id' => $ticket->getId()]);
    }
}
