<?php

namespace App\Controller;

use App\Entity\Activity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
}
