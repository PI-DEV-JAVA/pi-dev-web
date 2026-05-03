<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Formation;
use App\Entity\Offer;
use App\Entity\Sync;
use App\Entity\User;
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

        // Suggested users for Connect section
        $suggestedUsers = [];
        if ($user) {
            // Get IDs of users already connected/pending
            $syncedIds = [];
            $syncs = $em->getRepository(Sync::class)->createQueryBuilder('s')
                ->where('s.sender = :u OR s.receiver = :u')
                ->setParameter('u', $user)
                ->getQuery()->getResult();
            foreach ($syncs as $s) {
                $other = $s->getOtherUser($user);
                if ($other) $syncedIds[] = $other->getId();
            }
            $excludeIds = array_merge($syncedIds, [$user->getId()]);

            $suggestedUsers = $em->createQueryBuilder()
                ->select('u', 'p')
                ->from(User::class, 'u')
                ->join('u.profile', 'p')
                ->where('u.id NOT IN (:exclude)')
                ->andWhere('p.profileCompleted = true')
                ->setParameter('exclude', $excludeIds ?: [0])
                ->setMaxResults(6)
                ->getQuery()->getResult();
        }

        return $this->render('front/home.html.twig', [
            'offersCount' => $offersCount,
            'eventsCount' => $eventsCount,
            'coursesCount' => $coursesCount,
            'recentOffers' => $recentOffers,
            'upcomingEvents' => $upcomingEvents,
            'featuredOffer' => $featuredOffer,
            'featuredEvent' => $featuredEvent,
            'suggestedUsers' => $suggestedUsers,
        ]);
    }
}
