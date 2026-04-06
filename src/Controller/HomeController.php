<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Formation;
use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $em): Response
    {
        // If admin/HR, redirect to back office
        $user = $this->getUser();
        if ($user && in_array($user->getRole(), ['ADMIN', 'HR'])) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $offersCount = $em->getRepository(Offer::class)->count([]);
        $eventsCount = $em->getRepository(Event::class)->count([]);
        $coursesCount = $em->getRepository(Formation::class)->count([]);

        $recentOffers = $em->getRepository(Offer::class)->findBy([], ['publishDate' => 'DESC'], 6);
        $upcomingEvents = $em->getRepository(Event::class)->createQueryBuilder('e')
            ->where('e.eventDate >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.eventDate', 'ASC')
            ->setMaxResults(3)
            ->getQuery()->getResult();

        $featuredOffer = $recentOffers[0] ?? null;
        $featuredEvent = $upcomingEvents[0] ?? null;

        return $this->render('front/home.html.twig', [
            'offersCount' => $offersCount,
            'eventsCount' => $eventsCount,
            'coursesCount' => $coursesCount,
            'recentOffers' => $recentOffers,
            'upcomingEvents' => $upcomingEvents,
            'featuredOffer' => $featuredOffer,
            'featuredEvent' => $featuredEvent,
        ]);
    }
}
