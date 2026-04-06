<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Event;
use App\Entity\Formation;
use App\Entity\Interview;
use App\Entity\Offer;
use App\Entity\Sync;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function account(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        // Admin/HR → redirect to back office
        if (in_array($user->getRole(), ['ADMIN', 'HR'])) {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('front/account/dashboard.html.twig');
    }

    // Legacy redirect: old /dashboard route → new location
    #[Route('/dashboard', name: 'app_dashboard')]
    public function legacyDashboard(): Response
    {
        $user = $this->getUser();
        if ($user && in_array($user->getRole(), ['ADMIN', 'HR'])) {
            return $this->redirectToRoute('admin_dashboard');
        }
        return $this->redirectToRoute('app_account');
    }
}
