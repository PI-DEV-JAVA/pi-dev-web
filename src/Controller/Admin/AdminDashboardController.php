<?php

namespace App\Controller\Admin;

use App\Entity\Activity;
use App\Entity\Application;
use App\Entity\Choix;
use App\Entity\Event;
use App\Entity\EventFeedback;
use App\Entity\EventParticipation;
use App\Entity\Formation;
use App\Entity\Interview;
use App\Entity\Notification;
use App\Entity\Offer;
use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\Seance;
use App\Entity\SupportTicket;
use App\Entity\Sync;
use App\Entity\SyncMessage;
use App\Entity\TicketReply;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminDashboardController extends AbstractController
{
    private function isAdmin(): bool
    {
        return $this->getUser()->getRole() === 'ADMIN';
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  DASHBOARD
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('', name: 'admin_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array($user->getRole(), ['ADMIN', 'HR'])) {
            return $this->redirectToRoute('app_home');
        }

        if ($this->isAdmin()) {
            // Admin sees total stats
            $stats = [
                'users'        => $em->getRepository(User::class)->count([]),
                'offers'       => $em->getRepository(Offer::class)->count([]),
                'events'       => $em->getRepository(Event::class)->count([]),
                'courses'      => $em->getRepository(Formation::class)->count([]),
                'applications' => $em->getRepository(Application::class)->count([]),
                'projects'     => $em->getRepository(Project::class)->count([]),
                'openTickets'  => $em->getRepository(SupportTicket::class)->count(['status' => 'OPEN']),
            ];
            $recentOffers = $em->getRepository(Offer::class)->findBy([], ['publishDate' => 'DESC'], 5);
            $recentApplications = $em->getRepository(Application::class)->findBy([], ['applicationDate' => 'DESC'], 5);
        } else {
            // HR sees only their own stats
            $myOffers = $em->getRepository(Offer::class)->findBy(['recruiter' => $user]);
            $myProjects = $em->getRepository(Project::class)->findBy(['projectManager' => $user]);

            // Count applications on own offers
            $myAppCount = 0;
            foreach ($myOffers as $o) {
                $myAppCount += $em->getRepository(Application::class)->count(['offer' => $o]);
            }

            $myEvents = $em->getRepository(Event::class)->findBy(['organizer' => $user]);

            $stats = [
                'offers'       => count($myOffers),
                'events'       => count($myEvents),
                'projects'     => count($myProjects),
                'applications' => $myAppCount,
            ];
            $recentOffers = $em->getRepository(Offer::class)->findBy(
                ['recruiter' => $user], ['publishDate' => 'DESC'], 5
            );
            $recentApplications = $em->getRepository(Application::class)->createQueryBuilder('a')
                ->join('a.offer', 'o')
                ->where('o.recruiter = :uid')->setParameter('uid', $user)
                ->orderBy('a.applicationDate', 'DESC')
                ->setMaxResults(5)
                ->getQuery()->getResult();
        }

        return $this->render('back/dashboard.html.twig', [
            'stats'              => $stats,
            'recentOffers'       => $recentOffers,
            'recentApplications' => $recentApplications,
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  OFFERS (HR: own only; ADMIN: all)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/offers', name: 'admin_offers')]
    public function offers(EntityManagerInterface $em): Response
    {
        if ($this->isAdmin()) {
            $offers = $em->getRepository(Offer::class)->findBy([], ['publishDate' => 'DESC']);
        } else {
            $offers = $em->getRepository(Offer::class)->findBy(
                ['recruiter' => $this->getUser()], ['publishDate' => 'DESC']
            );
        }
        return $this->render('back/offers/list.html.twig', ['offers' => $offers]);
    }

    #[Route('/offers/new', name: 'admin_offer_new', methods: ['GET', 'POST'])]
    public function offerNew(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $title = trim($request->request->get('title', ''));
            $description = trim($request->request->get('description', ''));
            $location = trim($request->request->get('location', ''));
            $positions = (int)$request->request->get('positionsAvailable', 1);
            $salaryMin = $request->request->get('salaryMin');
            $salaryMax = $request->request->get('salaryMax');

            // Server-side validation
            if (strlen($title) < 3) {
                $this->addFlash('danger', 'Le titre doit contenir au moins 3 caractères.');
                return $this->render('back/offers/form.html.twig', ['offer' => null]);
            }
            if (strlen($description) < 10) {
                $this->addFlash('danger', 'La description doit contenir au moins 10 caractères.');
                return $this->render('back/offers/form.html.twig', ['offer' => null]);
            }
            if (empty($location)) {
                $this->addFlash('danger', 'La localisation est requise.');
                return $this->render('back/offers/form.html.twig', ['offer' => null]);
            }
            if ($positions < 1) {
                $this->addFlash('danger', 'Au moins 1 poste doit être disponible.');
                return $this->render('back/offers/form.html.twig', ['offer' => null]);
            }
            if ($salaryMin && $salaryMax && (float)$salaryMax < (float)$salaryMin) {
                $this->addFlash('danger', 'Le salaire max doit être supérieur ou égal au salaire min.');
                return $this->render('back/offers/form.html.twig', ['offer' => null]);
            }

            $offer = new Offer();
            $offer->setTitle($title);
            $offer->setDescription($description);
            $offer->setContractType($request->request->get('contractType'));
            $offer->setLocation($location);
            $offer->setDepartment($request->request->get('department'));
            $offer->setExperienceLevel($request->request->get('experienceLevel'));
            $offer->setPositionsAvailable($positions);
            if ($salaryMin) $offer->setSalaryMin((float)$salaryMin);
            if ($salaryMax) $offer->setSalaryMax((float)$salaryMax);
            $offer->setStatus('Active');
            $offer->setPublishDate(new \DateTime());
            $offer->setStatus('Active');
            $offer->setRecruiter($this->getUser());
            
            $em->persist($offer);
            $em->flush();
            $this->addFlash('success', 'Offre créée avec succès.');
            return $this->redirectToRoute('admin_offers');
        }
        return $this->render('back/offers/form.html.twig', ['offer' => null]);
    }

    #[Route('/offers/{id}/edit', name: 'admin_offer_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function offerEdit(Offer $offer, Request $request, EntityManagerInterface $em): Response
    {
        // HR can only edit own offers
        if (!$this->isAdmin() && $offer->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $title = trim($request->request->get('title', ''));
            $description = trim($request->request->get('description', ''));
            $location = trim($request->request->get('location', ''));
            $positions = (int)$request->request->get('positionsAvailable', 1);
            $salaryMin = $request->request->get('salaryMin');
            $salaryMax = $request->request->get('salaryMax');

            // Server-side validation
            if (strlen($title) < 3) {
                $this->addFlash('danger', 'Le titre doit contenir au moins 3 caractères.');
                return $this->render('back/offers/form.html.twig', ['offer' => $offer]);
            }
            if (strlen($description) < 10) {
                $this->addFlash('danger', 'La description doit contenir au moins 10 caractères.');
                return $this->render('back/offers/form.html.twig', ['offer' => $offer]);
            }
            if (empty($location)) {
                $this->addFlash('danger', 'La localisation est requise.');
                return $this->render('back/offers/form.html.twig', ['offer' => $offer]);
            }
            if ($positions < 1) {
                $this->addFlash('danger', 'Au moins 1 poste doit être disponible.');
                return $this->render('back/offers/form.html.twig', ['offer' => $offer]);
            }
            if ($salaryMin && $salaryMax && (float)$salaryMax < (float)$salaryMin) {
                $this->addFlash('danger', 'Le salaire max doit être supérieur ou égal au salaire min.');
                return $this->render('back/offers/form.html.twig', ['offer' => $offer]);
            }

            $offer->setTitle($title);
            $offer->setDescription($description);
            $offer->setContractType($request->request->get('contractType'));
            $offer->setLocation($location);
            $offer->setDepartment($request->request->get('department'));
            $offer->setExperienceLevel($request->request->get('experienceLevel'));
            $offer->setPositionsAvailable($positions);
            $offer->setSalaryMin($salaryMin ? (float)$salaryMin : null);
            $offer->setSalaryMax($salaryMax ? (float)$salaryMax : null);
            $em->flush();
            $this->addFlash('success', 'Offre modifiée.');
            return $this->redirectToRoute('admin_offers');
        }
        return $this->render('back/offers/form.html.twig', ['offer' => $offer]);
    }

    #[Route('/offers/{id}/delete', name: 'admin_offer_delete', requirements: ['id' => '\d+'])]
    public function offerDelete(Offer $offer, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $offer->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $em->remove($offer);
        $em->flush();
        $this->addFlash('success', 'Offre supprimée.');
        return $this->redirectToRoute('admin_offers');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  EVENTS (HR: own only via organizer; ADMIN: all)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/events', name: 'admin_events')]
    public function events(EntityManagerInterface $em, Request $request): Response
    {
        $qb = $em->getRepository(Event::class)->createQueryBuilder('e');
        if (!$this->isAdmin()) {
            $qb->andWhere('e.organizer = :org')->setParameter('org', $this->getUser());
        }
        $type = $request->query->get('type');
        if ($type) $qb->andWhere('e.eventType = :t')->setParameter('t', $type);
        $status = $request->query->get('status');
        if ($status) $qb->andWhere('e.status = :s')->setParameter('s', $status);
        $qb->orderBy('e.eventDate', 'DESC');
        $events = $qb->getQuery()->getResult();

        // KPIs
        $totalParticipants = $em->getRepository(EventParticipation::class)->count([]);
        $totalAttended = $em->getRepository(EventParticipation::class)->count(['status' => 'ATTENDED']);
        $presenceRate = $totalParticipants > 0 ? round(($totalAttended / $totalParticipants) * 100) : 0;
        $avgRating = $em->createQueryBuilder()->select('AVG(f.rating)')->from(EventFeedback::class, 'f')->getQuery()->getSingleScalarResult();

        return $this->render('back/events/list.html.twig', [
            'events' => $events,
            'totalEvents' => count($events),
            'totalParticipants' => $totalParticipants,
            'presenceRate' => $presenceRate,
            'avgRating' => $avgRating ? round($avgRating, 1) : 0,
            'typeFilter' => $type,
            'statusFilter' => $status,
        ]);
    }

    #[Route('/events/new', name: 'admin_event_new', methods: ['GET', 'POST'])]
    public function eventNew(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $title = trim($request->request->get('title', ''));
            $description = trim($request->request->get('description', ''));
            $location = trim($request->request->get('location', ''));
            $dateStr = $request->request->get('eventDate');

            if (strlen($title) < 3) {
                $this->addFlash('danger', 'Le titre doit contenir au moins 3 caractères.');
                return $this->render('back/events/form.html.twig', ['event' => null]);
            }
            if (strlen($description) < 10) {
                $this->addFlash('danger', 'La description doit contenir au moins 10 caractères.');
                return $this->render('back/events/form.html.twig', ['event' => null]);
            }
            if (empty($location)) {
                $this->addFlash('danger', 'Le lieu est requis.');
                return $this->render('back/events/form.html.twig', ['event' => null]);
            }
            if (empty($dateStr)) {
                $this->addFlash('danger', 'La date est requise.');
                return $this->render('back/events/form.html.twig', ['event' => null]);
            }

            $event = new Event();
            $event->setTitle($title);
            $event->setDescription($description);
            $event->setEventType($request->request->get('eventType'));
            $event->setLocation($location);
            $event->setEventDate(new \DateTime($dateStr));
            $mc = $request->request->get('maxCapacity');
            if ($mc) $event->setMaxCapacity((int)$mc);
            $event->setStatus('UPCOMING');
            $event->setOrganizer($this->getUser());

            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'Évènement créé.');
            return $this->redirectToRoute('admin_events');
        }
        return $this->render('back/events/form.html.twig', ['event' => null]);
    }

    #[Route('/events/{id}/edit', name: 'admin_event_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function eventEdit(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        if ($request->isMethod('POST')) {
            $title = trim($request->request->get('title', ''));
            $description = trim($request->request->get('description', ''));
            $location = trim($request->request->get('location', ''));
            $dateStr = $request->request->get('eventDate');

            if (strlen($title) < 3) {
                $this->addFlash('danger', 'Le titre doit contenir au moins 3 caractères.');
                return $this->render('back/events/form.html.twig', ['event' => $event]);
            }
            if (strlen($description) < 10) {
                $this->addFlash('danger', 'La description doit contenir au moins 10 caractères.');
                return $this->render('back/events/form.html.twig', ['event' => $event]);
            }
            if (empty($location)) {
                $this->addFlash('danger', 'Le lieu est requis.');
                return $this->render('back/events/form.html.twig', ['event' => $event]);
            }

            $event->setTitle($title);
            $event->setDescription($description);
            $event->setEventType($request->request->get('eventType'));
            $event->setLocation($location);
            if ($dateStr) $event->setEventDate(new \DateTime($dateStr));
            $mc = $request->request->get('maxCapacity');
            if ($mc) $event->setMaxCapacity((int)$mc);
            $em->flush();
            $this->addFlash('success', 'Évènement modifié.');
            return $this->redirectToRoute('admin_events');
        }
        return $this->render('back/events/form.html.twig', ['event' => $event]);
    }

    #[Route('/events/{id}/delete', name: 'admin_event_delete', requirements: ['id' => '\d+'])]
    public function eventDelete(Event $event, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $em->remove($event);
        $em->flush();
        $this->addFlash('success', 'Évènement supprimé.');
        return $this->redirectToRoute('admin_events');
    }

    // ── Calendar JSON API ──
    #[Route('/events/calendar-data', name: 'admin_events_calendar_data')]
    public function eventsCalendarData(EntityManagerInterface $em): Response
    {
        $qb = $em->getRepository(Event::class)->createQueryBuilder('e');
        if (!$this->isAdmin()) {
            $qb->andWhere('e.organizer = :org')->setParameter('org', $this->getUser());
        }
        $events = $qb->getQuery()->getResult();
        $data = [];
        $colors = ['UPCOMING' => '#4f46e5', 'ONGOING' => '#f59e0b', 'COMPLETED' => '#6b7280', 'CANCELLED' => '#ef4444'];
        foreach ($events as $e) {
            $data[] = [
                'id' => $e->getId(),
                'title' => $e->getTitle(),
                'start' => $e->getEventDate()->format('Y-m-d\TH:i:s'),
                'end' => $e->getEndDate() ? $e->getEndDate()->format('Y-m-d\TH:i:s') : null,
                'color' => $colors[$e->getStatus()] ?? '#4f46e5',
                'extendedProps' => [
                    'type' => $e->getEventType(),
                    'location' => $e->getLocation(),
                    'status' => $e->getStatus(),
                    'participants' => $e->getParticipations()->count(),
                ],
            ];
        }
        return new JsonResponse($data);
    }

    // ── Participation Management ──
    #[Route('/events/{id}/participations', name: 'admin_event_participations', requirements: ['id' => '\d+'])]
    public function eventParticipations(Event $event, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $participations = $em->getRepository(EventParticipation::class)->findBy(
            ['event' => $event], ['registeredAt' => 'DESC']
        );
        return $this->render('back/events/participations.html.twig', [
            'event' => $event,
            'participations' => $participations,
        ]);
    }

    #[Route('/events/participation/{id}/status', name: 'admin_participation_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function participationStatus(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $p = $em->getRepository(EventParticipation::class)->find($id);
        if (!$p) throw $this->createNotFoundException();
        $newStatus = $request->request->get('status');
        if (in_array($newStatus, ['PENDING', 'CONFIRMED', 'CANCELLED', 'ATTENDED'])) {
            $p->setStatus($newStatus);
            $em->flush();
            $this->addFlash('success', 'Statut mis à jour: ' . $newStatus);
        }
        return $this->redirectToRoute('admin_event_participations', ['id' => $p->getEvent()->getId()]);
    }

    // ── Presence Dashboard ──
    #[Route('/events/presence', name: 'admin_event_presence')]
    public function eventPresence(EntityManagerInterface $em, Request $request): Response
    {
        $qb = $em->getRepository(EventParticipation::class)->createQueryBuilder('p')
            ->join('p.event', 'e')->join('p.user', 'u')
            ->addSelect('e', 'u')
            ->orderBy('p.registeredAt', 'DESC');

        if (!$this->isAdmin()) {
            $qb->andWhere('e.organizer = :org')->setParameter('org', $this->getUser());
        }
        $eventId = $request->query->get('event');
        if ($eventId) $qb->andWhere('e.id = :eid')->setParameter('eid', $eventId);
        $statusFilter = $request->query->get('presence');
        if ($statusFilter === 'present') $qb->andWhere('p.status = :att')->setParameter('att', 'ATTENDED');
        elseif ($statusFilter === 'absent') $qb->andWhere('p.status != :att')->setParameter('att', 'ATTENDED');

        $participations = $qb->getQuery()->getResult();

        // Events for filter dropdown
        $evQb = $em->getRepository(Event::class)->createQueryBuilder('ev');
        if (!$this->isAdmin()) $evQb->andWhere('ev.organizer = :org')->setParameter('org', $this->getUser());
        $allEvents = $evQb->orderBy('ev.eventDate', 'DESC')->getQuery()->getResult();

        return $this->render('back/events/presence.html.twig', [
            'participations' => $participations,
            'events' => $allEvents,
            'eventFilter' => $eventId,
            'presenceFilter' => $statusFilter,
        ]);
    }

    #[Route('/events/presence/{id}/toggle', name: 'admin_presence_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function presenceToggle(int $id, EntityManagerInterface $em): Response
    {
        $p = $em->getRepository(EventParticipation::class)->find($id);
        if (!$p) throw $this->createNotFoundException();
        $p->setStatus($p->getStatus() === 'ATTENDED' ? 'CONFIRMED' : 'ATTENDED');
        $em->flush();
        return $this->redirectToRoute('admin_event_presence');
    }

    #[Route('/events/presence/{id}/qr', name: 'admin_presence_qr', requirements: ['id' => '\d+'])]
    public function presenceQr(int $id, EntityManagerInterface $em): Response
    {
        $p = $em->getRepository(EventParticipation::class)->find($id);
        if (!$p) throw $this->createNotFoundException();
        // Auto-generate QR code if missing
        if (!$p->getQrCode()) {
            $code = 'EVT_' . $p->getEvent()->getId() . '_USR_' . $p->getUser()->getId() . '_' . strtoupper(substr(md5(random_bytes(8)), 0, 8));
            $p->setQrCode($code);
            $em->flush();
        }
        return $this->render('back/events/qr_show.html.twig', ['participation' => $p]);
    }

    // ── QR Scanner ──
    #[Route('/events/scan', name: 'admin_event_scan')]
    public function eventScan(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $code = trim($request->request->get('code', ''));
            if (!$code && $request->request->get('qr_data')) {
                $code = trim($request->request->get('qr_data', ''));
            }
            $p = $em->getRepository(EventParticipation::class)->findOneBy(['qrCode' => $code]);
            if (!$p) {
                return new JsonResponse(['success' => false, 'message' => 'Code QR non reconnu.']);
            }
            if ($p->getStatus() === 'ATTENDED') {
                return new JsonResponse(['success' => false, 'message' => 'Déjà scanné ! Présence déjà validée.']);
            }
            $p->setStatus('ATTENDED');
            $em->flush();
            $userName = $p->getUser()->getProfile() && $p->getUser()->getProfile()->getFirstName()
                ? $p->getUser()->getProfile()->getFirstName() . ' ' . $p->getUser()->getProfile()->getLastName()
                : $p->getUser()->getEmail();
            return new JsonResponse([
                'success' => true,
                'message' => '✅ Présence validée',
                'name' => $userName,
                'event' => $p->getEvent()->getTitle(),
            ]);
        }
        return $this->render('back/events/scan.html.twig');
    }

    // ── Feedback Admin ──
    #[Route('/events/feedback', name: 'admin_event_feedback')]
    public function eventFeedback(EntityManagerInterface $em, Request $request): Response
    {
        $qb = $em->createQueryBuilder()
            ->select('f', 'p', 'e', 'u')
            ->from(EventFeedback::class, 'f')
            ->join('f.participation', 'p')
            ->join('p.event', 'e')
            ->join('p.user', 'u')
            ->orderBy('f.createdAt', 'DESC');

        if (!$this->isAdmin()) {
            $qb->andWhere('e.organizer = :org')->setParameter('org', $this->getUser());
        }
        $eventId = $request->query->get('event');
        if ($eventId) $qb->andWhere('e.id = :eid')->setParameter('eid', $eventId);
        $ratingFilter = $request->query->get('rating');
        if ($ratingFilter) $qb->andWhere('f.rating = :r')->setParameter('r', $ratingFilter);

        $feedbacks = $qb->getQuery()->getResult();

        $evQb = $em->getRepository(Event::class)->createQueryBuilder('ev');
        if (!$this->isAdmin()) $evQb->andWhere('ev.organizer = :org')->setParameter('org', $this->getUser());
        $allEvents = $evQb->orderBy('ev.eventDate', 'DESC')->getQuery()->getResult();

        return $this->render('back/events/feedback.html.twig', [
            'feedbacks' => $feedbacks,
            'events' => $allEvents,
            'eventFilter' => $eventId,
            'ratingFilter' => $ratingFilter,
        ]);
    }

    #[Route('/events/feedback/{id}/delete', name: 'admin_feedback_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function feedbackDelete(int $id, EntityManagerInterface $em): Response
    {
        $f = $em->getRepository(EventFeedback::class)->find($id);
        if ($f) { $em->remove($f); $em->flush(); $this->addFlash('success', 'Feedback supprimé.'); }
        return $this->redirectToRoute('admin_event_feedback');
    }

    // ── Statistics ──
    #[Route('/events/stats', name: 'admin_event_stats')]
    public function eventStats(EntityManagerInterface $em): Response
    {
        // Per-event stats
        $qb = $em->createQueryBuilder()
            ->select('e.id', 'e.title', 'e.eventType',
                'COUNT(f.id) as feedbackCount',
                'AVG(f.rating) as avgRating',
                'SUM(CASE WHEN f.wouldRecommend = true THEN 1 ELSE 0 END) as recommends',
                'COUNT(DISTINCT p.id) as participantCount')
            ->from(Event::class, 'e')
            ->leftJoin('e.participations', 'p')
            ->leftJoin(EventFeedback::class, 'f', 'WITH', 'f.participation = p')
            ->groupBy('e.id')
            ->orderBy('e.eventDate', 'DESC');
        if (!$this->isAdmin()) {
            $qb->andWhere('e.organizer = :org')->setParameter('org', $this->getUser());
        }
        $stats = $qb->getQuery()->getResult();

        // Event type distribution
        $tqb = $em->createQueryBuilder()
            ->select('e.eventType as type', 'COUNT(e.id) as cnt')
            ->from(Event::class, 'e')
            ->groupBy('e.eventType');
        if (!$this->isAdmin()) {
            $tqb->andWhere('e.organizer = :org')->setParameter('org', $this->getUser());
        }
        $typeDistrib = $tqb->getQuery()->getResult();

        return $this->render('back/events/stats.html.twig', [
            'stats' => $stats,
            'typeDistrib' => $typeDistrib,
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  COURSES (HR: own; ADMIN: all)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/courses', name: 'admin_courses')]
    public function courses(EntityManagerInterface $em): Response
    {
        if ($this->isAdmin()) {
            $formations = $em->getRepository(Formation::class)->findBy([], ['dateDebut' => 'DESC']);
        } else {
            $formations = $em->getRepository(Formation::class)->findBy(
                ['recruiter' => $this->getUser()], ['dateDebut' => 'DESC']
            );
        }
        return $this->render('back/courses/list.html.twig', ['formations' => $formations]);
    }

    #[Route('/courses/new', name: 'admin_course_new', methods: ['GET', 'POST'])]
    public function courseNew(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $titre = trim($request->request->get('titre', ''));
            $description = trim($request->request->get('description', ''));
            $duree = (int)$request->request->get('duree', 1);

            if (strlen($titre) < 3) {
                $this->addFlash('danger', 'Le titre doit contenir au moins 3 caractères.');
                return $this->render('back/courses/form.html.twig', ['formation' => null]);
            }
            if (strlen($description) < 10) {
                $this->addFlash('danger', 'La description doit contenir au moins 10 caractères.');
                return $this->render('back/courses/form.html.twig', ['formation' => null]);
            }

            $f = new Formation();
            $f->setTitre($titre);
            $f->setDescription($description);
            $f->setDuree($duree);
            $f->setNiveau($request->request->get('niveau'));
            $f->setRecruiter($this->getUser());
            $dateStr = $request->request->get('dateDebut');
            if ($dateStr) $f->setDateDebut(new \DateTime($dateStr));

            $em->persist($f);
            $em->flush();
            $this->addFlash('success', 'Formation créée.');
            return $this->redirectToRoute('admin_course_edit', ['id' => $f->getId()]);
        }
        return $this->render('back/courses/form.html.twig', ['formation' => null]);
    }

    #[Route('/courses/{id}/edit', name: 'admin_course_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function courseEdit(Formation $formation, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $formation->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $titre = trim($request->request->get('titre', ''));
            $description = trim($request->request->get('description', ''));

            if (strlen($titre) < 3) {
                $this->addFlash('danger', 'Le titre doit contenir au moins 3 caractères.');
                return $this->redirectToRoute('admin_course_edit', ['id' => $formation->getId()]);
            }
            if (strlen($description) < 10) {
                $this->addFlash('danger', 'La description doit contenir au moins 10 caractères.');
                return $this->redirectToRoute('admin_course_edit', ['id' => $formation->getId()]);
            }

            $formation->setTitre($titre);
            $formation->setDescription($description);
            $formation->setDuree((int)$request->request->get('duree', 1));
            $formation->setNiveau($request->request->get('niveau'));
            $dateStr = $request->request->get('dateDebut');
            if ($dateStr) $formation->setDateDebut(new \DateTime($dateStr));
            $em->flush();
            $this->addFlash('success', 'Formation modifiée.');
            return $this->redirectToRoute('admin_course_edit', ['id' => $formation->getId()]);
        }

        $seances = $em->getRepository(Seance::class)->findBy(['formation' => $formation], ['dateDebut' => 'ASC']);
        $quizzes = $em->getRepository(Quiz::class)->findBy(['formation' => $formation]);

        return $this->render('back/courses/form.html.twig', [
            'formation' => $formation,
            'seances' => $seances,
            'quizzes' => $quizzes,
        ]);
    }

    #[Route('/courses/{id}/delete', name: 'admin_course_delete', requirements: ['id' => '\d+'])]
    public function courseDelete(Formation $formation, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $formation->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $em->remove($formation);
        $em->flush();
        $this->addFlash('success', 'Formation supprimée.');
        return $this->redirectToRoute('admin_courses');
    }

    // ── Séances CRUD ──
    #[Route('/courses/{id}/seance/new', name: 'admin_course_seance_new', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function courseSeanceNew(Formation $formation, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $formation->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $titre = trim($request->request->get('titre', ''));
        $dateDebut = $request->request->get('dateDebut');
        $dateFin = $request->request->get('dateFin');

        if (strlen($titre) < 2) {
            $this->addFlash('danger', 'Le titre de la séance est requis.');
            return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $formation->getId()]) . '#seances');
        }
        if (!$dateDebut || !$dateFin) {
            $this->addFlash('danger', 'Les dates de début et fin sont requises.');
            return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $formation->getId()]) . '#seances');
        }

        $seance = new Seance();
        $seance->setFormation($formation);
        $seance->setTitre($titre);
        $seance->setType($request->request->get('type', 'PRESENTIEL'));
        $seance->setDateDebut(new \DateTime($dateDebut));
        $seance->setDateFin(new \DateTime($dateFin));
        $seance->setAdresse($request->request->get('adresse'));
        $videoPath = trim($request->request->get('videoPath', ''));
        if ($videoPath) $seance->setVideoPath($videoPath);
        $dm = $request->request->get('dureeMinutes');
        if ($dm) $seance->setDureeMinutes((int)$dm);

        $em->persist($seance);
        $em->flush();
        $this->addFlash('success', 'Séance ajoutée.');
        return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $formation->getId()]) . '#seances');
    }

    #[Route('/courses/{id}/seance/{sid}/delete', name: 'admin_course_seance_delete', requirements: ['id' => '\d+', 'sid' => '\d+'])]
    public function courseSeanceDelete(int $id, int $sid, EntityManagerInterface $em): Response
    {
        $formation = $em->getRepository(Formation::class)->find($id);
        if (!$this->isAdmin() && $formation->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $seance = $em->getRepository(Seance::class)->find($sid);
        if ($seance) { $em->remove($seance); $em->flush(); $this->addFlash('success', 'Séance supprimée.'); }
        return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $id]) . '#seances');
    }

    // ── Quiz CRUD ──
    #[Route('/courses/{id}/quiz/new', name: 'admin_course_quiz_new', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function courseQuizNew(Formation $formation, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $formation->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $titre = trim($request->request->get('titre', ''));
        if (strlen($titre) < 2) {
            $this->addFlash('danger', 'Le titre du quiz est requis.');
            return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $formation->getId()]) . '#quizzes');
        }

        $quiz = new Quiz();
        $quiz->setFormation($formation);
        $quiz->setTitre($titre);
        $duree = $request->request->get('duree');
        if ($duree) $quiz->setDuree((int)$duree);

        $em->persist($quiz);
        $em->flush();
        $this->addFlash('success', 'Quiz créé.');
        return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $formation->getId()]) . '#quizzes');
    }

    #[Route('/courses/{id}/quiz/{qid}/delete', name: 'admin_course_quiz_delete', requirements: ['id' => '\d+', 'qid' => '\d+'])]
    public function courseQuizDelete(int $id, int $qid, EntityManagerInterface $em): Response
    {
        $formation = $em->getRepository(Formation::class)->find($id);
        if (!$this->isAdmin() && $formation->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $quiz = $em->getRepository(Quiz::class)->find($qid);
        if ($quiz) { $em->remove($quiz); $em->flush(); $this->addFlash('success', 'Quiz supprimé.'); }
        return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $id]) . '#quizzes');
    }

    #[Route('/courses/{id}/quiz/{qid}/manage', name: 'admin_course_quiz_manage', requirements: ['id' => '\d+', 'qid' => '\d+'])]
    public function courseQuizManage(int $id, int $qid, EntityManagerInterface $em): Response
    {
        $formation = $em->getRepository(Formation::class)->find($id);
        if (!$this->isAdmin() && $formation->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $quiz = $em->getRepository(Quiz::class)->find($qid);
        $questions = $em->getRepository(Question::class)->findBy(['quiz' => $quiz]);

        return $this->render('back/courses/quiz_manage.html.twig', [
            'formation' => $formation,
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    // ── Question + Choix CRUD ──
    #[Route('/courses/{id}/quiz/{qid}/question/new', name: 'admin_course_question_new', requirements: ['id' => '\d+', 'qid' => '\d+'], methods: ['POST'])]
    public function courseQuestionNew(int $id, int $qid, Request $request, EntityManagerInterface $em): Response
    {
        $formation = $em->getRepository(Formation::class)->find($id);
        if (!$this->isAdmin() && $formation->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $quiz = $em->getRepository(Quiz::class)->find($qid);
        $enonce = trim($request->request->get('enonce', ''));
        $choixTexts = $request->request->all('choix');
        $correctIndex = (int)$request->request->get('correct', 0);

        if (strlen($enonce) < 5) {
            $this->addFlash('danger', 'L\'\u00e9nonc\u00e9 doit contenir au moins 5 caract\u00e8res.');
            return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $id]) . '#quizzes');
        }
        if (count($choixTexts) < 2) {
            $this->addFlash('danger', 'Au moins 2 choix sont requis.');
            return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $id]) . '#quizzes');
        }

        $question = new Question();
        $question->setQuiz($quiz);
        $question->setEnonce($enonce);
        $em->persist($question);

        foreach ($choixTexts as $i => $texte) {
            $texte = trim($texte);
            if (!$texte) continue;
            $choix = new Choix();
            $choix->setQuestion($question);
            $choix->setTexte($texte);
            $choix->setIsCorrect($i === $correctIndex);
            $em->persist($choix);
        }

        $em->flush();
        $this->addFlash('success', 'Question ajoutée.');
        return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $id]) . '#quizzes');
    }

    #[Route('/courses/{id}/quiz/{qid}/question/{questionId}/delete', name: 'admin_course_question_delete', requirements: ['id' => '\d+', 'qid' => '\d+', 'questionId' => '\d+'])]
    public function courseQuestionDelete(int $id, int $qid, int $questionId, EntityManagerInterface $em): Response
    {
        $formation = $em->getRepository(Formation::class)->find($id);
        if (!$this->isAdmin() && $formation->getRecruiter() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $question = $em->getRepository(Question::class)->find($questionId);
        if ($question) { $em->remove($question); $em->flush(); $this->addFlash('success', 'Question supprimée.'); }
        return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $id]) . '#quizzes');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  PROJECTS (HR: own only; ADMIN: all)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projects', name: 'admin_projects')]
    public function projects(EntityManagerInterface $em): Response
    {
        if ($this->isAdmin()) {
            $projects = $em->getRepository(Project::class)->findBy([], ['createdAt' => 'DESC']);
        } else {
            $projects = $em->getRepository(Project::class)->findBy(
                ['projectManager' => $this->getUser()], ['createdAt' => 'DESC']
            );
        }
        return $this->render('back/projects/list.html.twig', ['projects' => $projects]);
    }

    #[Route('/projects/new', name: 'admin_project_new', methods: ['GET', 'POST'])]
    public function projectNew(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name', ''));
            $startDate = $request->request->get('startDate');
            $endDate = $request->request->get('endDate');
            $budget = $request->request->get('budget');

            if (strlen($name) < 2) {
                $this->addFlash('danger', 'Le nom doit contenir au moins 2 caractères.');
                return $this->render('back/projects/form.html.twig', ['project' => null]);
            }
            if ($startDate && $endDate && $endDate < $startDate) {
                $this->addFlash('danger', 'La date fin doit être après la date début.');
                return $this->render('back/projects/form.html.twig', ['project' => null]);
            }
            if ($budget && (float)$budget < 0) {
                $this->addFlash('danger', 'Le budget ne peut pas être négatif.');
                return $this->render('back/projects/form.html.twig', ['project' => null]);
            }

            $p = new Project();
            $p->setName($name);
            $p->setDescription($request->request->get('description'));
            $p->setStatus($request->request->get('status', 'PLANNED'));
            if ($startDate) $p->setStartDate(new \DateTime($startDate));
            if ($endDate) $p->setEndDate(new \DateTime($endDate));
            $p->setBudget($budget);
            $p->setProjectManager($this->getUser());

            $em->persist($p);
            $em->flush();
            $this->addFlash('success', 'Projet créé.');
            return $this->redirectToRoute('admin_projects');
        }
        return $this->render('back/projects/form.html.twig', ['project' => null]);
    }

    #[Route('/projects/{id}/edit', name: 'admin_project_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function projectEdit(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $project->getProjectManagerId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name', ''));
            $startDate = $request->request->get('startDate');
            $endDate = $request->request->get('endDate');
            $budget = $request->request->get('budget');

            if (strlen($name) < 2) {
                $this->addFlash('danger', 'Le nom doit contenir au moins 2 caractères.');
                return $this->render('back/projects/form.html.twig', ['project' => $project, 'activities' => $em->getRepository(Activity::class)->findBy(['project' => $project])]);
            }
            if ($startDate && $endDate && $endDate < $startDate) {
                $this->addFlash('danger', 'La date fin doit être après la date début.');
                return $this->render('back/projects/form.html.twig', ['project' => $project, 'activities' => $em->getRepository(Activity::class)->findBy(['project' => $project])]);
            }

            $project->setName($name);
            $project->setDescription($request->request->get('description'));
            $project->setStatus($request->request->get('status', 'PLANNED'));
            if ($startDate) $project->setStartDate(new \DateTime($startDate));
            if ($endDate) $project->setEndDate(new \DateTime($endDate));
            $project->setBudget($budget ?: null);
            $em->flush();
            $this->addFlash('success', 'Projet modifié.');
            return $this->redirectToRoute('admin_projects');
        }

        // Load activities for this project
        $activities = $em->getRepository(Activity::class)->findBy(['project' => $project], ['activityDate' => 'DESC']);

        return $this->render('back/projects/form.html.twig', [
            'project' => $project,
            'activities' => $activities,
        ]);
    }

    #[Route('/projects/{id}/delete', name: 'admin_project_delete', requirements: ['id' => '\d+'])]
    public function projectDelete(Project $project, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $project->getProjectManagerId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        $em->remove($project);
        $em->flush();
        $this->addFlash('success', 'Projet supprimé.');
        return $this->redirectToRoute('admin_projects');
    }

    #[Route('/projects/{id}/activity/new', name: 'admin_project_activity_new', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function projectActivityNew(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $project->getProjectManagerId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $desc = trim($request->request->get('description', ''));
        $hours = $request->request->get('hoursWorked');
        $dateStr = $request->request->get('activityDate');

        if ($desc) {
            $activity = new Activity();
            $activity->setEmployee($this->getUser());
            $activity->setProject($project);
            $activity->setDescription($desc);
            if ($hours) $activity->setHoursWorked($hours);
            $activity->setActivityDate($dateStr ? new \DateTime($dateStr) : new \DateTime());
            $em->persist($activity);
            $em->flush();
            $this->addFlash('success', 'Activité ajoutée.');
        }

        return $this->redirectToRoute('admin_project_edit', ['id' => $project->getId()]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  ACTIVITIES (HR assigns to own candidates)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/activities', name: 'admin_activities')]
    public function activities(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($this->isAdmin()) {
            $activities = $em->getRepository(Activity::class)->findBy([], ['activityDate' => 'DESC']);
            $candidates = $em->getRepository(User::class)->findBy(['role' => 'CANDIDATE']);
        } else {
            // HR: activities assigned by this HR to their candidates
            $activities = $em->getRepository(Activity::class)->createQueryBuilder('a')
                ->join('a.project', 'p')
                ->where('p.projectManager = :uid')->setParameter('uid', $user)
                ->orderBy('a.activityDate', 'DESC')
                ->getQuery()->getResult();

            // HR's candidates = users who applied to their offers
            $candidates = $em->createQueryBuilder()
                ->select('DISTINCT u')
                ->from(User::class, 'u')
                ->join(Application::class, 'app', 'WITH', 'app.user = u')
                ->join('app.offer', 'o')
                ->where('o.recruiter = :uid')->setParameter('uid', $user)
                ->getQuery()->getResult();
        }

        // Load HR's own projects for the assignment form
        if ($this->isAdmin()) {
            $projects = $em->getRepository(Project::class)->findBy([], ['createdAt' => 'DESC']);
        } else {
            $projects = $em->getRepository(Project::class)->findBy(['projectManager' => $user]);
        }

        return $this->render('back/activities/list.html.twig', [
            'activities' => $activities,
            'candidates' => $candidates,
            'projects'   => $projects,
        ]);
    }

    #[Route('/activities/new', name: 'admin_activity_new', methods: ['POST'])]
    public function activityNew(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $candidateId = (int)$request->request->get('candidateId');
        $projectId = $request->request->get('projectId');
        $desc = trim($request->request->get('description', ''));
        $hours = $request->request->get('hoursWorked');
        $dateStr = $request->request->get('activityDate');

        $candidate = $em->getRepository(User::class)->find($candidateId);
        $project = $projectId ? $em->getRepository(Project::class)->find($projectId) : null;

        // Verify ownership: HR can only assign to own candidates
        if (!$this->isAdmin() && $project && $project->getProjectManagerId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($candidate && $desc) {
            $activity = new Activity();
            $activity->setEmployee($candidate);
            $activity->setProject($project);
            $activity->setDescription($desc);
            if ($hours) $activity->setHoursWorked($hours);
            $activity->setActivityDate($dateStr ? new \DateTime($dateStr) : new \DateTime());
            $em->persist($activity);
            $em->flush();

            // Notify candidate
            $ns = new NotificationService($em);
            $pName = $project ? ' (' . $project->getName() . ')' : '';
            $ns->notify($candidate, 'ACTIVITY', 'Nouvelle activité assignée', $desc . $pName, '/activities');

            $this->addFlash('success', 'Activité assignée à ' . $candidate->getEmail());
        }

        return $this->redirectToRoute('admin_activities');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  SUPPORT (ADMIN only)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/support', name: 'admin_support')]
    public function support(EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin()) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }
        $tickets = $em->getRepository(SupportTicket::class)->findBy([], ['createdAt' => 'DESC']);
        return $this->render('back/support/list.html.twig', ['tickets' => $tickets]);
    }

    #[Route('/support/{id}', name: 'admin_support_detail', requirements: ['id' => '\d+'])]
    public function supportDetail(SupportTicket $ticket, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin()) {
            throw $this->createAccessDeniedException();
        }
        $replies = $em->getRepository(TicketReply::class)->findBy(
            ['ticket' => $ticket], ['createdAt' => 'ASC']
        );
        return $this->render('back/support/detail.html.twig', [
            'ticket' => $ticket,
            'replies' => $replies,
        ]);
    }

    #[Route('/support/{id}/reply', name: 'admin_support_reply', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supportReply(SupportTicket $ticket, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin()) {
            throw $this->createAccessDeniedException();
        }
        $content = trim($request->request->get('content', ''));
        if ($content) {
            $reply = new TicketReply();
            $reply->setTicket($ticket);
            $reply->setUser($this->getUser());
            $reply->setMessage($content);
            $em->persist($reply);
            $em->flush();

            // Notify ticket owner
            if ($ticket->getUser()) {
                $ns = new NotificationService($em);
                $ns->notify($ticket->getUser(), 'REPLY', 'Réponse à votre ticket', 'Ticket: ' . $ticket->getSubject(), '/support/' . $ticket->getId());
            }
        }
        return $this->redirectToRoute('admin_support_detail', ['id' => $ticket->getId()]);
    }

    #[Route('/support/{id}/close', name: 'admin_support_close', requirements: ['id' => '\d+'])]
    public function supportClose(SupportTicket $ticket, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin()) {
            throw $this->createAccessDeniedException();
        }
        $ticket->setStatus('CLOSED');
        $em->flush();

        // Notify ticket owner
        if ($ticket->getUser()) {
            $ns = new NotificationService($em);
            $ns->notify($ticket->getUser(), 'REPLY', 'Ticket fermé', 'Votre ticket "' . $ticket->getSubject() . '" a été fermé.', '/support/' . $ticket->getId());
        }

        $this->addFlash('success', 'Ticket fermé.');
        return $this->redirectToRoute('admin_support');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  USER MANAGEMENT (ADMIN only)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/users', name: 'admin_users')]
    public function users(EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin()) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }
        $users = $em->getRepository(User::class)->findBy([], ['createdAt' => 'DESC']);
        return $this->render('back/users/list.html.twig', ['users' => $users]);
    }

    #[Route('/users/{id}/toggle', name: 'admin_user_toggle', requirements: ['id' => '\d+'])]
    public function userToggle(User $user, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin()) {
            throw $this->createAccessDeniedException();
        }
        $user->setActive(!$user->isActive());
        $em->flush();
        $this->addFlash('success', 'Statut modifié pour ' . $user->getEmail());
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/role', name: 'admin_user_role', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function userRole(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin()) {
            throw $this->createAccessDeniedException();
        }
        $newRole = $request->request->get('role');
        if (in_array($newRole, ['CANDIDATE', 'HR', 'ADMIN'])) {
            $user->setRole($newRole);
            $em->flush();
            $this->addFlash('success', 'Rôle modifié pour ' . $user->getEmail());
        }
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/delete', name: 'admin_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function userDelete(User $user, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin()) {
            throw $this->createAccessDeniedException();
        }

        // Prevent admin from deleting themselves
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_users');
        }

        $email = $user->getEmail();

        // Remove profile first if exists
        $profile = $user->getProfile();
        if ($profile) {
            $em->remove($profile);
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur ' . $email . ' supprimé avec succès.');
        return $this->redirectToRoute('admin_users');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  OFFER APPLICATIONS (view & decision)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/offers/{id}/applications', name: 'admin_offer_applications', requirements: ['id' => '\d+'])]
    public function offerApplications(Offer $offer, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $offer->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        $applications = $em->getRepository(Application::class)->findBy(
            ['offer' => $offer], ['applicationDate' => 'DESC']
        );
        return $this->render('back/offers/applications.html.twig', [
            'offer' => $offer,
            'applications' => $applications,
        ]);
    }

    #[Route('/applications/{id}/status', name: 'admin_application_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function applicationStatus(Application $application, Request $request, EntityManagerInterface $em): Response
    {
        $offer = $application->getOffer();
        if (!$this->isAdmin() && $offer->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $newStatus = $request->request->get('status');
        if (in_array($newStatus, ['Acceptée', 'Refusée', 'Entretien', 'En attente'])) {
            $application->setStatus($newStatus);
            $em->flush();

            // Notify the candidate
            $candidate = $application->getUser();
            if ($candidate) {
                $ns = new NotificationService($em);
                $statusLabel = match($newStatus) {
                    'Acceptée' => '✅ Candidature acceptée',
                    'Refusée' => '❌ Candidature refusée',
                    'Entretien' => '📅 Entretien programmé',
                    default => '📋 Mise à jour candidature',
                };
                $ns->notify(
                    $candidate,
                    'OFFER_DECISION',
                    $statusLabel,
                    'Votre candidature pour "' . $offer->getTitle() . '" a été mise à jour: ' . $newStatus,
                    '/account/applications'
                );
            }

            $this->addFlash('success', 'Statut mis à jour: ' . $newStatus);
        }

        return $this->redirectToRoute('admin_offer_applications', ['id' => $offer->getId()]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  NOTIFICATIONS
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/notifications', name: 'admin_notifications')]
    public function notifications(EntityManagerInterface $em): Response
    {
        $notifications = $em->getRepository(Notification::class)->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC'],
            50
        );
        return $this->render('back/notifications/list.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notification/{id}/read', name: 'admin_notification_read', requirements: ['id' => '\d+'])]
    public function notificationRead(int $id, EntityManagerInterface $em): Response
    {
        $notif = $em->getRepository(Notification::class)->find($id);
        if ($notif && $notif->getUser() === $this->getUser()) {
            $notif->setIsRead(true);
            $em->flush();
            if ($notif->getLink()) {
                return $this->redirect($notif->getLink());
            }
        }
        return $this->redirectToRoute('admin_notifications');
    }

    #[Route('/notifications/read-all', name: 'admin_notifications_read_all')]
    public function notificationsReadAll(EntityManagerInterface $em): Response
    {
        $unread = $em->getRepository(Notification::class)->findBy(
            ['user' => $this->getUser(), 'isRead' => false]
        );
        foreach ($unread as $n) {
            $n->setIsRead(true);
        }
        $em->flush();
        $this->addFlash('success', 'Toutes les notifications marquées comme lues.');
        return $this->redirectToRoute('admin_notifications');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  PROFILE
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/profile', name: 'admin_profile')]
    public function profile(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $profile = $user->getProfile();
        if (!$profile) {
            $profile = new Profile();
            $profile->setUser($user);
            $em->persist($profile);
            $em->flush();
        }

        if ($request->isMethod('POST')) {
            $profile->setFirstName(trim($request->request->get('firstName', '')));
            $profile->setLastName(trim($request->request->get('lastName', '')));
            $profile->setProfessionalTitle($request->request->get('professionalTitle'));
            $profile->setPhoneNumber($request->request->get('phoneNumber'));
            $profile->setLocation($request->request->get('location'));
            $profile->setSummary($request->request->get('summary'));
            $yoe = $request->request->get('yearsOfExperience');
            if ($yoe !== null && $yoe !== '') $profile->setYearsOfExperience((int)$yoe);

            // Handle cropped avatar (base64 from Cropper.js)
            $croppedData = $request->request->get('croppedAvatar', '');
            if ($croppedData && str_starts_with($croppedData, 'data:image/')) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0775, true); }
                // Decode base64
                $parts = explode(',', $croppedData, 2);
                $imageData = base64_decode($parts[1]);
                if ($imageData && strlen($imageData) <= 5 * 1024 * 1024) {
                    $filename = 'avatar_' . $user->getId() . '_' . time() . '.jpg';
                    file_put_contents($uploadDir . '/' . $filename, $imageData);
                    // Delete old avatar
                    $oldPath = $profile->getProfilePicturePath();
                    if ($oldPath) {
                        $oldFile = $this->getParameter('kernel.project_dir') . '/public' . $oldPath;
                        if (file_exists($oldFile)) { unlink($oldFile); }
                    }
                    $profile->setProfilePicturePath('/uploads/avatars/' . $filename);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('admin_profile');
        }

        return $this->render('back/profile/index.html.twig', [
            'profile' => $profile,
        ]);
    }

    #[Route('/profile/remove-avatar', name: 'admin_profile_remove_avatar')]
    public function removeAvatar(EntityManagerInterface $em): Response
    {
        $profile = $this->getUser()->getProfile();
        if ($profile && $profile->getProfilePicturePath()) {
            $file = $this->getParameter('kernel.project_dir') . '/public' . $profile->getProfilePicturePath();
            if (file_exists($file)) { unlink($file); }
            $profile->setProfilePicturePath(null);
            $em->flush();
            $this->addFlash('success', 'Photo de profil supprimée.');
        }
        return $this->redirectToRoute('admin_profile');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  USER PROFILE (Admin view)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/user-profile/{id}', name: 'admin_user_profile', requirements: ['id' => '\d+'])]
    public function userProfile(int $id, EntityManagerInterface $em): Response
    {
        $target = $em->getRepository(User::class)->find($id);
        if (!$target) { throw $this->createNotFoundException(); }

        // Get their applications
        $applications = $em->getRepository(Application::class)->findBy(
            ['user' => $target], ['applicationDate' => 'DESC']
        );

        return $this->render('back/user_profile.html.twig', [
            'targetUser' => $target,
            'applications' => $applications,
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  HR → CANDIDATE MESSAGING
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/message-candidate/{id}', name: 'admin_message_candidate', requirements: ['id' => '\d+'])]
    public function messageCandidate(int $id, EntityManagerInterface $em): Response
    {
        $hr = $this->getUser();
        $candidate = $em->getRepository(User::class)->find($id);
        if (!$candidate) { throw $this->createNotFoundException(); }

        // Check if HR has access to this candidate (they applied to one of HR's offers)
        if (!$this->isAdmin()) {
            $hasAccess = $em->createQueryBuilder()
                ->select('COUNT(a.id)')
                ->from(Application::class, 'a')
                ->join('a.offer', 'o')
                ->where('a.user = :candidate AND o.recruiter = :hrId')
                ->setParameter('candidate', $candidate)
                ->setParameter('hrId', $hr)
                ->getQuery()->getSingleScalarResult();
            if ($hasAccess == 0) {
                throw $this->createAccessDeniedException('Ce candidat n\'a pas postulé à vos offres.');
            }
        }

        // Find or create a sync between HR and candidate
        $sync = $em->getRepository(Sync::class)->createQueryBuilder('s')
            ->where('(s.sender = :a AND s.receiver = :b) OR (s.sender = :b AND s.receiver = :a)')
            ->setParameter('a', $hr)->setParameter('b', $candidate)
            ->getQuery()->getOneOrNullResult();

        if (!$sync) {
            $sync = new Sync();
            $sync->setSender($hr);
            $sync->setReceiver($candidate);
            $sync->setReason('HIRE');
            $sync->setStatus('ACCEPTED');
            $sync->setAcceptedAt(new \DateTime());
            $em->persist($sync);
            $em->flush();
        } elseif ($sync->getStatus() !== 'ACCEPTED') {
            $sync->setStatus('ACCEPTED');
            $sync->setAcceptedAt(new \DateTime());
            $em->flush();
        }

        // Load messages and render back-office chat
        $messages = $em->getRepository(SyncMessage::class)->findBy(
            ['sync' => $sync], ['createdAt' => 'ASC']
        );

        // Mark incoming messages as read
        $em->createQueryBuilder()
            ->update(SyncMessage::class, 'sm')
            ->set('sm.isRead', 'true')
            ->where('sm.sync = :sync AND sm.sender != :me AND sm.isRead = false')
            ->setParameter('sync', $sync)
            ->setParameter('me', $hr)
            ->getQuery()->execute();

        return $this->render('back/hr_chat.html.twig', [
            'sync' => $sync,
            'otherUser' => $candidate,
            'messages' => $messages,
        ]);
    }

    #[Route('/my-candidates/messages', name: 'admin_hr_messages')]
    public function hrMessages(EntityManagerInterface $em): Response
    {
        $hr = $this->getUser();

        // Get all candidates who applied to HR's offers
        if ($this->isAdmin()) {
            $candidateIds = $em->createQueryBuilder()
                ->select('DISTINCT IDENTITY(a.user)')
                ->from(Application::class, 'a')
                ->getQuery()->getSingleColumnResult();
        } else {
            $candidateIds = $em->createQueryBuilder()
                ->select('DISTINCT IDENTITY(a.user)')
                ->from(Application::class, 'a')
                ->join('a.offer', 'o')
                ->where('o.recruiter = :hrId')
                ->setParameter('hrId', $hr)
                ->getQuery()->getSingleColumnResult();
        }

        // Get syncs with these candidates
        $conversations = [];
        if ($candidateIds) {
            $syncs = $em->getRepository(Sync::class)->createQueryBuilder('s')
                ->where('(s.sender = :hr AND s.receiver IN (:ids)) OR (s.receiver = :hr AND s.sender IN (:ids))')
                ->andWhere('s.status = :accepted')
                ->setParameter('hr', $hr)
                ->setParameter('ids', $candidateIds)
                ->setParameter('accepted', 'ACCEPTED')
                ->getQuery()->getResult();

            foreach ($syncs as $sync) {
                $other = $sync->getOtherUser($hr);
                if (!$other) continue;
                $lastMsg = $em->getRepository(SyncMessage::class)->findOneBy(
                    ['sync' => $sync], ['createdAt' => 'DESC']
                );
                $unread = $em->getRepository(SyncMessage::class)->count([
                    'sync' => $sync, 'sender' => $other, 'isRead' => false
                ]);
                $conversations[] = [
                    'sync' => $sync,
                    'user' => $other,
                    'lastMessage' => $lastMsg,
                    'unread' => $unread,
                ];
            }
        }

        // Candidates without a conversation yet
        $existingSyncCandidateIds = array_map(fn($c) => $c['user']->getId(), $conversations);
        $newCandidates = [];
        if ($candidateIds) {
            $remaining = array_diff($candidateIds, $existingSyncCandidateIds);
            if ($remaining) {
                $newCandidates = $em->getRepository(User::class)->findBy(['id' => $remaining]);
            }
        }

        return $this->render('back/hr_messages.html.twig', [
            'conversations' => $conversations,
            'newCandidates' => $newCandidates,
        ]);
    }
}
