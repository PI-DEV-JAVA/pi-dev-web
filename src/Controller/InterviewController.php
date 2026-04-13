<?php

namespace App\Controller;

use App\Entity\Interview;
use App\Entity\Meet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InterviewController extends AbstractController
{
    #[Route('/interviews', name: 'app_interviews')]
    public function list(EntityManagerInterface $em, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $qb = $em->getRepository(Interview::class)->createQueryBuilder('i')
            ->join('i.application', 'a')
            ->join('a.user', 'u')
            ->join('a.offer', 'o')
            ->where('a.user = :user')
            ->setParameter('user', $user);

        // Search by offer title
        $search = $request->query->get('q');
        if ($search) {
            $qb->andWhere('o.title LIKE :search OR i.status LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Sort
        $sort = $request->query->get('sort', 'date_desc');
        if ($sort === 'date_asc') {
            $qb->orderBy('i.interviewDate', 'ASC');
        } elseif ($sort === 'date_desc') {
            $qb->orderBy('i.interviewDate', 'DESC');
        }

        $interviews = $qb->getQuery()->getResult();

        return $this->render('front/account/interviews.html.twig', [
            'interviews' => $interviews,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/meet/{roomId}', name: 'app_meet_room')]
    public function room(string $roomId, EntityManagerInterface $em): Response
    {
        $meet = $em->getRepository(Meet::class)->findOneBy(['roomId' => $roomId]);

        if (!$meet) {
            throw $this->createNotFoundException('Salle de réunion introuvable.');
        }

        $userName = null;
        if ($this->getUser()) {
            $userName = $this->getUser()->getProfile() ? $this->getUser()->getProfile()->getFirstName() . ' ' . $this->getUser()->getProfile()->getLastName() : $this->getUser()->getEmail();
        }

        return $this->render('front/meet/room.html.twig', [
            'meet' => $meet,
            'roomId' => $roomId,
            'userName' => $userName,
        ]);
    }
}
