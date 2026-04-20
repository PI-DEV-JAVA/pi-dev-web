<?php

namespace App\Controller;

use App\Entity\Interview;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InterviewController extends AbstractController
{
    #[Route('/interviews', name: 'app_interviews')]
    public function list(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $interviews = $em->getRepository(Interview::class)->createQueryBuilder('i')
            ->join('i.application', 'a')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('i.interviewDate', 'DESC')
            ->getQuery()->getResult();

        return $this->render('front/account/interviews.html.twig', [
            'interviews' => $interviews,
        ]);
    }
}
