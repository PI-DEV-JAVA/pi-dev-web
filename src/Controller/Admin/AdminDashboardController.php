<?php

namespace App\Controller\Admin;

use App\Entity\Activity;
use App\Entity\Application;
use App\Entity\Choix;
use App\Entity\Event;
use App\Entity\EventFeedback;
use App\Entity\EventParticipation;
use App\Entity\Formation;
use App\Entity\FormationEnrollment;
use App\Entity\Interview;
use App\Entity\Notification;
use App\Entity\Offer;
use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\QuizAttempt;
use App\Entity\Seance;
use App\Entity\SupportTicket;
use App\Entity\Sync;
use App\Entity\SyncMessage;
use App\Entity\TicketReply;
use App\Entity\User;
use App\Service\CourseMailer;
use App\Service\NotificationService;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminDashboardController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CourseMailer        $courseMailer,
    ) {}

    private function isAdmin(): bool
    {
        return $this->getUser()->getRole() === 'ADMIN';
    }

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  DASHBOARD
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
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

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  OFFERS (HR: own only; ADMIN: all)
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
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
            $offer->setRecruiter($this->getUser());

            // Cover image upload
            $imageFile = $request->files->get('cover_image');
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/offers', $newFilename);
                $offer->setCoverImage('/uploads/offers/' . $newFilename);
            }

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

            // Cover image upload
            $imageFile = $request->files->get('cover_image');
            if ($imageFile) {
                if ($offer->getCoverImage()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $offer->getCoverImage();
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/offers', $newFilename);
                $offer->setCoverImage('/uploads/offers/' . $newFilename);
            }
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

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  EVENTS (HR: own only via organizer; ADMIN: all)
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
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

            // Cover image upload
            $imageFile = $request->files->get('cover_image');
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/events', $newFilename);
                $event->setCoverImage('/uploads/events/' . $newFilename);
            }

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

            // Cover image upload
            $imageFile = $request->files->get('cover_image');
            if ($imageFile) {
                // Delete old image
                if ($event->getCoverImage()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $event->getCoverImage();
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/events', $newFilename);
                $event->setCoverImage('/uploads/events/' . $newFilename);
            }
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

    // â”€â”€ Calendar JSON API â”€â”€
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

    // â”€â”€ Participation Management â”€â”€
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

    // â”€â”€ Presence Dashboard â”€â”€
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

    // â”€â”€ QR Scanner â”€â”€
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

    // â”€â”€ Feedback Admin â”€â”€
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

    // â”€â”€ Statistics â”€â”€
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
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  COURSES (HR: own; ADMIN: all)
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    #[Route('/courses', name: 'admin_courses')]
    public function courses(EntityManagerInterface $em): Response
    {
        if ($this->isAdmin()) {
            $formations = $em->getRepository(Formation::class)->findBy([], ['dateDebut' => 'DESC']);
        } else {
            $formations = $em->getRepository(Formation::class)->findBy(
                ['recruiterId' => $this->getUser()->getId()], ['dateDebut' => 'DESC']
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
            $f->setRecruiterId($this->getUser()->getId());
            $dateStr = $request->request->get('dateDebut');
            if ($dateStr) $f->setDateDebut(new \DateTime($dateStr));

            // Paid/Free
            $isPaid = $request->request->get('is_paid') === '1';
            $f->setIsPaid($isPaid);
            if ($isPaid) {
                $pts = $request->request->get('price_points');
                $f->setPricePoints($pts ? (int)$pts : null);
            }

            // Image upload
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $filename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/formations', $filename);
                $f->setImage($filename);
            }

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
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
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

            // Paid/Free
            $isPaid = $request->request->get('is_paid') === '1';
            $formation->setIsPaid($isPaid);
            if ($isPaid) {
                $pts = $request->request->get('price_points');
                $formation->setPricePoints($pts ? (int)$pts : null);
            } else {
                $formation->setPricePoints(null);
            }

            // Image upload
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                // Delete old image if exists
                $old = $formation->getImage();
                if ($old) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/formations/' . $old;
                    if (file_exists($oldPath)) @unlink($oldPath);
                }
                $filename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/formations', $filename);
                $formation->setImage($filename);
            }

            $em->flush();
            $this->addFlash('success', 'Formation modifiée.');
            return $this->redirectToRoute('admin_course_edit', ['id' => $formation->getId()]);
        }

        $seances = $em->getRepository(Seance::class)->findBy(['formation' => $formation], ['dateDebut' => 'ASC']);
        $quizzes = $em->getRepository(Quiz::class)->findBy(['formation' => $formation]);

        return $this->render('back/courses/form.html.twig', [
            'formation' => $formation,
            'seances'   => $seances,
            'quizzes'   => $quizzes,
        ]);
    }

    #[Route('/courses/{id}/delete', name: 'admin_course_delete', requirements: ['id' => '\d+'])]
    public function courseDelete(Formation $formation, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        $em->remove($formation);
        $em->flush();
        $this->addFlash('success', 'Formation supprimée.');
        return $this->redirectToRoute('admin_courses');
    }

    // â”€â”€ Séances CRUD â”€â”€
    #[Route('/courses/{id}/seance/new', name: 'admin_course_seance_new', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function courseSeanceNew(Formation $formation, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
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
        $seance->setDescription($request->request->get('description'));
        $seance->setType($request->request->get('type', 'PRESENTIEL'));
        $seance->setDateDebut(new \DateTime($dateDebut));
        $seance->setDateFin(new \DateTime($dateFin));
        $seance->setAdresse($request->request->get('adresse'));
        // Save map coordinates if provided
        $lat = $request->request->get('latitude');
        $lng = $request->request->get('longitude');
        if ($lat !== null && $lat !== '') $seance->setLatitude((float)$lat);
        if ($lng !== null && $lng !== '') $seance->setLongitude((float)$lng);
        $videoPath = trim($request->request->get('videoPath', ''));
        if ($videoPath) $seance->setVideoPath($videoPath);
        $dm = $request->request->get('dureeMinutes');
        if ($dm) $seance->setDureeMinutes((int)$dm);

        $em->persist($seance);
        $em->flush();
        // Notify approved enrollees by email
        $this->courseMailer->sendNewSeance($formation, $seance);
        // Notify all approved enrollees
        $ns = new NotificationService($em);
        foreach ($formation->getEnrollments() as $enrollment) {
            if ($enrollment->isApproved()) {
                $ns->notify($enrollment->getUser(), 'COURSE', '📅 Nouvelle séance', 'Séance "' . $seance->getTitre() . '" ajoutée à "' . $formation->getTitre() . '".', '/courses/' . $formation->getId());
            }
        }
        $this->addFlash('success', 'Séance ajoutée.');
        return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $formation->getId()]) . '#seances');
    }

    #[Route('/courses/{id}/seance/{sid}/delete', name: 'admin_course_seance_delete', requirements: ['id' => '\d+', 'sid' => '\d+'])]
    public function courseSeanceDelete(int $id, int $sid, EntityManagerInterface $em): Response
    {
        $formation = $em->getRepository(Formation::class)->find($id);
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        $seance = $em->getRepository(Seance::class)->find($sid);
        if ($seance) { $em->remove($seance); $em->flush(); $this->addFlash('success', 'Séance supprimée.'); }
        return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $id]) . '#seances');
    }

    // â”€â”€ Quiz CRUD â”€â”€
    #[Route('/courses/{id}/quiz/new', name: 'admin_course_quiz_new', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function courseQuizNew(Formation $formation, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
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
        $quiz->setDescription($request->request->get('description'));
        $duree = $request->request->get('duree');
        if ($duree) $quiz->setDuree((int)$duree);

        // Link to seance if provided
        $seanceId = $request->request->get('seance_id');
        if ($seanceId) {
            $seance = $em->getRepository(Seance::class)->find((int)$seanceId);
            if ($seance && $seance->getFormation() === $formation) {
                $quiz->setSeance($seance);
            }
        }

        $em->persist($quiz);
        $em->flush();
        // Notify approved enrollees by email
        $this->courseMailer->sendQuizAvailable($formation, $quiz);
        // Notify all approved enrollees
        $ns = new NotificationService($em);
        foreach ($formation->getEnrollments() as $enrollment) {
            if ($enrollment->isApproved()) {
                $ns->notify($enrollment->getUser(), 'COURSE', '📝 Nouveau quiz', 'Quiz "' . $quiz->getTitre() . '" disponible dans "' . $formation->getTitre() . '".', '/courses/' . $formation->getId());
            }
        }
        $this->addFlash('success', 'Quiz créé.');
        return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $formation->getId()]) . '#quizzes');
    }

    #[Route('/courses/{id}/quiz/{qid}/delete', name: 'admin_course_quiz_delete', requirements: ['id' => '\d+', 'qid' => '\d+'])]
    public function courseQuizDelete(int $id, int $qid, EntityManagerInterface $em): Response
    {
        $formation = $em->getRepository(Formation::class)->find($id);
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
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
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
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

    // â”€â”€ Question + Choix CRUD â”€â”€
    #[Route('/courses/{id}/quiz/{qid}/question/new', name: 'admin_course_question_new', requirements: ['id' => '\d+', 'qid' => '\d+'], methods: ['POST'])]
    public function courseQuestionNew(int $id, int $qid, Request $request, EntityManagerInterface $em): Response
    {
        $formation = $em->getRepository(Formation::class)->find($id);
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
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
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        $question = $em->getRepository(Question::class)->find($questionId);
        if ($question) { $em->remove($question); $em->flush(); $this->addFlash('success', 'Question supprimée.'); }
        return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $id]) . '#quizzes');
    }

    // â”€â”€ Enrollment Management â”€â”€
    #[Route('/courses/{id}/enrollments', name: 'admin_course_enrollments', requirements: ['id' => '\d+'])]
    public function courseEnrollments(Formation $formation, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        $enrollments = $em->getRepository(FormationEnrollment::class)->findBy(
            ['formation' => $formation],
            ['requestedAt' => 'DESC']
        );
        return $this->render('back/courses/enrollments.html.twig', [
            'formation'   => $formation,
            'enrollments' => $enrollments,
        ]);
    }

    #[Route('/courses/{id}/enrollments/{eid}/approve', name: 'admin_course_enrollment_approve', requirements: ['id' => '\d+', 'eid' => '\d+'])]
    public function courseEnrollmentApprove(int $id, int $eid, EntityManagerInterface $em): Response
    {
        $formation  = $em->getRepository(Formation::class)->find($id);
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        $enrollment = $em->getRepository(FormationEnrollment::class)->find($eid);
        if ($enrollment) {
            $enrollment->setStatus(FormationEnrollment::STATUS_APPROVED);
            $enrollment->setRespondedAt(new \DateTime());
            $em->flush();
            $this->courseMailer->sendEnrollmentApproved($enrollment->getUser(), $formation);
            // Notify candidate
            $ns = new NotificationService($em);
            $ns->notify($enrollment->getUser(), 'COURSE', '✅ Inscription approuvée', 'Votre inscription à "' . $formation->getTitre() . '" a été approuvée.', '/courses/' . $formation->getId());
            $this->addFlash('success', 'Inscription approuvée.');
        }
        return $this->redirectToRoute('admin_course_enrollments', ['id' => $id]);
    }

    #[Route('/courses/{id}/enrollments/{eid}/reject', name: 'admin_course_enrollment_reject', requirements: ['id' => '\d+', 'eid' => '\d+'])]
    public function courseEnrollmentReject(int $id, int $eid, EntityManagerInterface $em): Response
    {
        $formation  = $em->getRepository(Formation::class)->find($id);
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        $enrollment = $em->getRepository(FormationEnrollment::class)->find($eid);
        if ($enrollment) {
            $enrollment->setStatus(FormationEnrollment::STATUS_REJECTED);
            $enrollment->setRespondedAt(new \DateTime());
            $em->flush();
            $this->courseMailer->sendEnrollmentRejected($enrollment->getUser(), $formation);
            // Notify candidate
            $ns = new NotificationService($em);
            $ns->notify($enrollment->getUser(), 'COURSE', '❌ Inscription refusée', 'Votre inscription à "' . $formation->getTitre() . '" a été refusée.', '/courses/' . $formation->getId());
            $this->addFlash('warning', 'Inscription refusée.');
        }
        return $this->redirectToRoute('admin_course_enrollments', ['id' => $id]);
    }

    // â”€â”€ Seance Edit â”€â”€
    #[Route('/courses/{id}/seance/{sid}/edit', name: 'admin_course_seance_edit', requirements: ['id' => '\d+', 'sid' => '\d+'], methods: ['POST'])]
    public function courseSeanceEdit(int $id, int $sid, Request $request, EntityManagerInterface $em): Response
    {
        $formation = $em->getRepository(Formation::class)->find($id);
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        $seance = $em->getRepository(Seance::class)->find($sid);
        if (!$seance || $seance->getFormation() !== $formation) {
            throw $this->createNotFoundException();
        }

        $titre = trim($request->request->get('titre', ''));
        if (strlen($titre) >= 2) $seance->setTitre($titre);
        $seance->setDescription($request->request->get('description'));
        $seance->setType($request->request->get('type', 'PRESENTIEL'));
        $dateDebut = $request->request->get('dateDebut');
        $dateFin   = $request->request->get('dateFin');
        if ($dateDebut) $seance->setDateDebut(new \DateTime($dateDebut));
        if ($dateFin)   $seance->setDateFin(new \DateTime($dateFin));
        $seance->setAdresse($request->request->get('adresse'));
        $lat = $request->request->get('latitude');
        $lng = $request->request->get('longitude');
        if ($lat !== null && $lat !== '') $seance->setLatitude((float)$lat);
        if ($lng !== null && $lng !== '') $seance->setLongitude((float)$lng);
        $videoPath = trim($request->request->get('videoPath', ''));
        $seance->setVideoPath($videoPath ?: null);
        $dm = $request->request->get('dureeMinutes');
        $seance->setDureeMinutes($dm ? (int)$dm : null);
        $em->flush();
        $this->addFlash('success', 'Séance modifiée.');
        return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $id]) . '#seances');
    }

    // â”€â”€ Quiz Edit â”€â”€
    #[Route('/courses/{id}/quiz/{qid}/edit', name: 'admin_course_quiz_edit', requirements: ['id' => '\d+', 'qid' => '\d+'], methods: ['POST'])]
    public function courseQuizEdit(int $id, int $qid, Request $request, EntityManagerInterface $em): Response
    {
        $formation = $em->getRepository(Formation::class)->find($id);
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        $quiz = $em->getRepository(Quiz::class)->find($qid);
        if (!$quiz || $quiz->getFormation() !== $formation) {
            throw $this->createNotFoundException();
        }

        $titre = trim($request->request->get('titre', ''));
        if (strlen($titre) >= 2) $quiz->setTitre($titre);
        $quiz->setDescription($request->request->get('description'));
        $duree = $request->request->get('duree');
        $quiz->setDuree($duree ? (int)$duree : null);
        $seanceId = $request->request->get('seance_id');
        if ($seanceId) {
            $seance = $em->getRepository(Seance::class)->find((int)$seanceId);
            if ($seance && $seance->getFormation() === $formation) $quiz->setSeance($seance);
        } else {
            $quiz->setSeance(null);
        }
        $em->flush();
        $this->addFlash('success', 'Quiz modifié.');
        return $this->redirect($this->generateUrl('admin_course_edit', ['id' => $id]) . '#quizzes');
    }

    // â”€â”€ AI Question Generation â”€â”€
    #[Route('/courses/{id}/quiz/{qid}/generate', name: 'admin_course_quiz_generate', requirements: ['id' => '\d+', 'qid' => '\d+'])]
    public function courseQuizGenerate(int $id, int $qid, EntityManagerInterface $em): JsonResponse
    {
        $formation = $em->getRepository(Formation::class)->find($id);
        if (!$this->isAdmin() && $formation->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $quiz   = $em->getRepository(Quiz::class)->find($qid);
        $seance = $quiz?->getSeance();

        $formationTitle = $formation?->getTitre()      ?? 'Formation';
        $seanceTitle    = $seance?->getTitre()          ?? '';
        $seanceDesc     = $seance?->getDescription()    ?? '';
        $quizTitle      = $quiz?->getTitre()            ?? 'Quiz';
        $quizDesc       = $quiz?->getDescription()      ?? '';

        $mainSubject = trim($quizDesc ?: $seanceDesc);
        $sessionCtx  = trim($seanceTitle ?: $quizTitle);
        $fullCtx     = trim($formationTitle . ($sessionCtx ? ' "” ' . $sessionCtx : ''));

        $subjectLine = $mainSubject
            ? "Le sujet exact à évaluer est : \"$mainSubject\"."
            : "Le sujet à évaluer est : \"$fullCtx\".";

        $systemPrompt = <<<EOT
Tu es un expert en création de quiz éducatifs techniques.
Tu génères des questions QCM qui testent la compréhension TECHNIQUE et PRATIQUE du sujet.
Tu ne parles JAMAIS de "l'objectif de la formation" ou de "bonnes pratiques pédagogiques".
Tu génères UNIQUEMENT du JSON valide, sans markdown, sans explication, sans texte autour.
EOT;

        $userPrompt = <<<EOT
$subjectLine
Contexte global : $fullCtx

Génère exactement 5 questions QCM en français sur des CONNAISSANCES TECHNIQUES précises.

Règles :
1. Chaque question porte sur un concept, une syntaxe ou un comportement CONCRET du sujet.
2. 4 choix techniquement plausibles, UN SEUL correct.
3. Niveau intermédiaire/avancé.

Retourne UNIQUEMENT ce tableau JSON (sans aucun texte autour) :
[{"enonce":"...","choix":[{"texte":"...","correct":true},{"texte":"...","correct":false},{"texte":"...","correct":false},{"texte":"...","correct":false}]},...]
EOT;

        // â”€â”€ Provider list: try each in order until one succeeds â”€â”€
        $providers = [
            [
                'name'    => 'groq',
                'key'     => trim($_ENV['GROQ_API_KEY'] ?? $_SERVER['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?? ''),
                'url'     => 'https://api.groq.com/openai/v1/chat/completions',
                'model'   => 'llama-3.3-70b-versatile',
                'system'  => true,
            ],
            [
                'name'    => 'xai',
                'key'     => trim($_ENV['XAI_API_KEY'] ?? $_SERVER['XAI_API_KEY'] ?? getenv('XAI_API_KEY') ?? ''),
                'url'     => 'https://api.x.ai/v1/chat/completions',
                'model'   => 'grok-3-latest',
                'system'  => true,
            ],
        ];

        foreach ($providers as $provider) {
            $apiKey = $provider['key'];
            if (!$apiKey || $apiKey === 'your_groq_api_key_here') {
                continue;
            }

            try {
                $messages = [];
                if ($provider['system']) {
                    $messages[] = ['role' => 'system', 'content' => $systemPrompt];
                }
                $messages[] = ['role' => 'user', 'content' => $userPrompt];

                $resp = $this->httpClient->request('POST', $provider['url'], [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model'       => $provider['model'],
                        'messages'    => $messages,
                        'temperature' => 0.3,
                        'max_tokens'  => 2000,
                    ],
                    'timeout' => 25,
                ]);

                // If HTTP error (e.g. 403 no credits), skip to next provider
                $statusCode = $resp->getStatusCode();
                if ($statusCode !== 200) {
                    continue;
                }

                $data    = $resp->toArray();
                $content = $data['choices'][0]['message']['content'] ?? '';

                // Strip markdown fences
                $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
                $content = preg_replace('/```\s*$/m',          '', $content);

                // Extract first JSON array
                if (preg_match('/\[.*\]/s', $content, $matches)) {
                    $content = $matches[0];
                }

                $questions = json_decode(trim($content), true);
                if (is_array($questions) && count($questions) > 0) {
                    return new JsonResponse([
                        'questions' => $questions,
                        'source'    => 'ai',
                        'provider'  => $provider['name'],
                    ]);
                }
            } catch (\Throwable $e) {
                // Try next provider
                continue;
            }
        }

        // Smart fallback (no AI available)
        $questions = $this->generateSmartFallback($formationTitle, $sessionCtx, $mainSubject);
        return new JsonResponse(['questions' => $questions, 'source' => 'template']);
    }

    private function generateSmartFallback(string $formation, string $session, string $desc): array
    {
        // Use description as the main subject; if empty fall back to session title
        $subject = trim($desc ?: $session ?: $formation);
        $ctx     = trim($formation . ($session ? ' "” ' . $session : ''));

        // Try to detect the domain from keywords for better questions
        $lower = strtolower($subject . ' ' . $ctx);

        // Python 
        if (str_contains($lower, 'python')) {
            if (str_contains($lower, 'structure') || str_contains($lower, 'donné')) {
                return [
                    ['enonce' => 'Quelle structure de données Python est immuable et ordonnée ?',
                     'choix'  => [['texte'=>'tuple','correct'=>true],['texte'=>'list','correct'=>false],['texte'=>'dict','correct'=>false],['texte'=>'set','correct'=>false]]],
                    ['enonce' => 'Quel type Python représente une collection non ordonnée de paires clé-valeur ?',
                     'choix'  => [['texte'=>'dict','correct'=>true],['texte'=>'list','correct'=>false],['texte'=>'tuple','correct'=>false],['texte'=>'str','correct'=>false]]],
                    ['enonce' => 'Quelle méthode permet d\'ajouter un élément à une liste Python ?',
                     'choix'  => [['texte'=>'.append()','correct'=>true],['texte'=>'.add()','correct'=>false],['texte'=>'.insert_end()','correct'=>false],['texte'=>'.push()','correct'=>false]]],
                    ['enonce' => 'Quelle structure Python garantit l\'unicité de ses éléments ?',
                     'choix'  => [['texte'=>'set','correct'=>true],['texte'=>'list','correct'=>false],['texte'=>'tuple','correct'=>false],['texte'=>'dict','correct'=>false]]],
                    ['enonce' => 'Comment accéder à la valeur associée à la clé "age" dans un dictionnaire d\'un Python ?',
                     'choix'  => [['texte'=>'d["age"]','correct'=>true],['texte'=>'d.get_key("age")','correct'=>false],['texte'=>'d->age','correct'=>false],['texte'=>'d.age()','correct'=>false]]],
                ];
            }
            return [
                ['enonce' => 'Quel mot-clé Python définit une fonction ?',
                 'choix'  => [['texte'=>'def','correct'=>true],['texte'=>'func','correct'=>false],['texte'=>'function','correct'=>false],['texte'=>'method','correct'=>false]]],
                ['enonce' => 'Quelle est la sortie de : type(3.14) en Python ?',
                 'choix'  => [['texte'=>"<class 'float'>", 'correct'=>true],['texte'=>"<class 'int'>", 'correct'=>false],['texte'=>"<class 'str'>", 'correct'=>false],['texte'=>"<class 'number'>", 'correct'=>false]]],
                ['enonce' => 'Comment créer une liste vide en Python ?',
                 'choix'  => [['texte'=>'[]','correct'=>true],['texte'=>'{}','correct'=>false],['texte'=>'()','correct'=>false],['texte'=>'list{}','correct'=>false]]],
                ['enonce' => 'Quel opérateur est utilisé pour la division entière en Python ?',
                 'choix'  => [['texte'=>'//','correct'=>true],['texte'=>'/','correct'=>false],['texte'=>'%','correct'=>false],['texte'=>'**','correct'=>false]]],
                ['enonce' => 'Quelle bibliothèque Python est standard pour les calculs numériques ?',
                 'choix'  => [['texte'=>'NumPy','correct'=>true],['texte'=>'Pandas','correct'=>false],['texte'=>'Matplotlib','correct'=>false],['texte'=>'Scikit-learn','correct'=>false]]],
            ];
        }

        // Web / JS / HTML
        if (str_contains($lower, 'javascript') || str_contains($lower, 'html') || str_contains($lower, 'css') || str_contains($lower, 'web')) {
            return [
                ['enonce' => 'Quelle balise HTML définit un lien hypertexte ?',
                 'choix'  => [['texte'=>'<a>','correct'=>true],['texte'=>'<link>','correct'=>false],['texte'=>'<href>','correct'=>false],['texte'=>'<nav>','correct'=>false]]],
                ['enonce' => 'Comment sélectionner un élément par son id en JavaScript ?',
                 'choix'  => [['texte'=>'document.getElementById()','correct'=>true],['texte'=>'document.querySelector()','correct'=>false],['texte'=>'document.getClass()','correct'=>false],['texte'=>'document.findById()','correct'=>false]]],
                ['enonce' => 'Quelle propriété CSS centre un élément horizontalement ?',
                 'choix'  => [['texte'=>'margin: 0 auto','correct'=>true],['texte'=>'text-align: center','correct'=>false],['texte'=>'align: center','correct'=>false],['texte'=>'position: center','correct'=>false]]],
                ['enonce' => 'Que signifie DOM en développement web ?',
                 'choix'  => [['texte'=>'Document Object Model','correct'=>true],['texte'=>'Data Object Method','correct'=>false],['texte'=>'Dynamic Object Module','correct'=>false],['texte'=>'Document Operation Mode','correct'=>false]]],
                ['enonce' => 'Quel attribut HTML rend un champ obligatoire dans un formulaire ?',
                 'choix'  => [['texte'=>'required','correct'=>true],['texte'=>'mandatory','correct'=>false],['texte'=>'needed','correct'=>false],['texte'=>'validate','correct'=>false]]],
            ];
        }

        // Database / SQL
        if (str_contains($lower, 'sql') || str_contains($lower, 'base de donné') || str_contains($lower, 'database')) {
            return [
                ['enonce' => 'Quelle clause SQL filtre les lignes résultantes ?',
                 'choix'  => [['texte'=>'WHERE','correct'=>true],['texte'=>'HAVING','correct'=>false],['texte'=>'GROUP BY','correct'=>false],['texte'=>'ORDER BY','correct'=>false]]],
                ['enonce' => 'Quelle commande SQL crée une nouvelle table ?',
                 'choix'  => [['texte'=>'CREATE TABLE','correct'=>true],['texte'=>'NEW TABLE','correct'=>false],['texte'=>'ADD TABLE','correct'=>false],['texte'=>'MAKE TABLE','correct'=>false]]],
                ['enonce' => 'Quelle jointure SQL retourne toutes les lignes des deux tables ?',
                 'choix'  => [['texte'=>'FULL OUTER JOIN','correct'=>true],['texte'=>'INNER JOIN','correct'=>false],['texte'=>'LEFT JOIN','correct'=>false],['texte'=>'CROSS JOIN','correct'=>false]]],
                ['enonce' => 'Quel type de clé garantit l\'unicité d\'un enregistrement ?',
                 'choix'  => [['texte'=>'Clé primaire','correct'=>true],['texte'=>'Clé étrangère','correct'=>false],['texte'=>'Index','correct'=>false],['texte'=>'Contrainte CHECK','correct'=>false]]],
                ['enonce' => 'Quelle fonction SQL compte le nombre de lignes ?',
                 'choix'  => [['texte'=>'COUNT()','correct'=>true],['texte'=>'SUM()','correct'=>false],['texte'=>'AVG()','correct'=>false],['texte'=>'TOTAL()','correct'=>false]]],
            ];
        }

        // Generic but improved fallback with actual content reference
        $subjectDisplay = $subject ?: $ctx;
        return [
            ['enonce' => "Dans le cadre de \"$subjectDisplay\", quelle affirmation est correcte ?",
             'choix'  => [['texte'=>"Le sujet comporte des concepts théoriques et pratiques", 'correct'=>true],['texte'=>"Il n'existe qu'une seule méthode valable",'correct'=>false],['texte'=>"La mémorisation suffit sans compréhension",'correct'=>false],['texte'=>"Les exemples ne sont pas utiles",'correct'=>false]]],
            ['enonce' => "Quel est l'avantage principal de maîtriser \"$subjectDisplay\" ?",
             'choix'  => [['texte'=>"Résoudre des problèmes concrets plus efficacement",'correct'=>true],['texte'=>"Ne pas avoir besoin de pratiquer",'correct'=>false],['texte'=>"Ignorer la documentation",'correct'=>false],['texte'=>"Travailler uniquement en théorie",'correct'=>false]]],
            ['enonce' => "Pour \"$subjectDisplay\", laquelle de ces approches est recommandée ?",
             'choix'  => [['texte'=>"Pratiquer sur des cas réels",'correct'=>true],['texte'=>"Lire sans expérimenter",'correct'=>false],['texte'=>"Éviter les erreurs à tout prix",'correct'=>false],['texte'=>"Copier sans comprendre",'correct'=>false]]],
            ['enonce' => "Quel élément est central dans \"$subjectDisplay\" ?",
             'choix'  => [['texte'=>"La compréhension des concepts fondamentaux",'correct'=>true],['texte'=>"La mémorisation de formules",'correct'=>false],['texte'=>"L'absence d'exercices",'correct'=>false],['texte'=>"La théorie sans application",'correct'=>false]]],
            ['enonce' => "Comment progresser efficacement sur \"$subjectDisplay\" ?",
             'choix'  => [['texte'=>"Combiner théorie, pratique et révision",'correct'=>true],['texte'=>"Étudier uniquement la nuit",'correct'=>false],['texte'=>"Ne jamais poser de questions",'correct'=>false],['texte'=>"Ignorer les retours",'correct'=>false]]],
        ];
    }

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  PROJECTS (HR: own only; ADMIN: all)
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
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

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  ACTIVITIES (HR assigns to own candidates)
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
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
        $deadlineStr = $request->request->get('expectedDeadline');

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
            
            if ($deadlineStr) {
                $activity->setExpectedDeadline(new \DateTime($deadlineStr));
            }
            
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

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  KANBAN BOARD
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    #[Route('/projects/kanban', name: 'admin_projects_kanban')]
    public function projectsKanban(EntityManagerInterface $em): Response
    {
        if ($this->isAdmin()) {
            $projects = $em->getRepository(Project::class)->findBy([], ['createdAt' => 'DESC']);
        } else {
            $projects = $em->getRepository(Project::class)->findBy(
                ['projectManager' => $this->getUser()], ['createdAt' => 'DESC']
            );
        }

        $columns = ['PLANNED' => [], 'IN_PROGRESS' => [], 'DONE' => [], 'ON_HOLD' => []];
        foreach ($projects as $p) {
            $status = $p->getStatus() ?: 'PLANNED';
            if (isset($columns[$status])) {
                $columns[$status][] = $p;
            }
        }

        return $this->render('back/projects/kanban.html.twig', [
            'columns' => $columns,
            'totalProjects' => count($projects),
        ]);
    }

    #[Route('/projects/{id}/status', name: 'admin_project_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function projectStatus(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $project->getProjectManagerId() !== $this->getUser()->getId()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? $request->request->get('status');

        if (in_array($newStatus, ['PLANNED', 'IN_PROGRESS', 'DONE', 'ON_HOLD'])) {
            $project->setStatus($newStatus);
            $em->flush();
            return new JsonResponse(['success' => true, 'status' => $newStatus]);
        }

        return new JsonResponse(['error' => 'Invalid status'], 400);
    }

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  PROJECT ACTIVITY LOGS
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    #[Route('/projects/{id}/logs', name: 'admin_project_logs', requirements: ['id' => '\d+'])]
    public function projectLogs(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $project->getProjectManagerId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $qb = $em->getRepository(Activity::class)->createQueryBuilder('a')
            ->where('a.project = :project')->setParameter('project', $project)
            ->orderBy('a.activityDate', 'DESC');

        // Optional date filters
        $from = $request->query->get('from');
        $to = $request->query->get('to');
        $employeeFilter = $request->query->get('employee');

        if ($from) {
            $qb->andWhere('a.activityDate >= :from')->setParameter('from', new \DateTime($from));
        }
        if ($to) {
            $qb->andWhere('a.activityDate <= :to')->setParameter('to', new \DateTime($to));
        }
        if ($employeeFilter) {
            $qb->andWhere('a.employee = :emp')->setParameter('emp', $employeeFilter);
        }

        $activities = $qb->getQuery()->getResult();

        // Stats
        $totalHours = 0;
        $contributors = [];
        foreach ($activities as $a) {
            $totalHours += (float)($a->getHoursWorked() ?? 0);
            $empId = $a->getEmployee()?->getId();
            if ($empId && !in_array($empId, $contributors)) {
                $contributors[] = $empId;
            }
        }

        // Get employees for filter dropdown
        $employees = $em->createQueryBuilder()
            ->select('DISTINCT u.id, u.email')
            ->from(Activity::class, 'a')
            ->join('a.employee', 'u')
            ->where('a.project = :project')->setParameter('project', $project)
            ->getQuery()->getResult();

        return $this->render('back/projects/logs.html.twig', [
            'project' => $project,
            'activities' => $activities,
            'totalHours' => round($totalHours, 2),
            'totalActivities' => count($activities),
            'totalContributors' => count($contributors),
            'employees' => $employees,
            'filterFrom' => $from,
            'filterTo' => $to,
            'filterEmployee' => $employeeFilter,
        ]);
    }

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  LEADERBOARD
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    #[Route('/leaderboard', name: 'admin_leaderboard')]
    public function leaderboard(EntityManagerInterface $em, Request $request): Response
    {
        $period = $request->query->get('period', 'all');

        $qb = $em->createQueryBuilder()
            ->select('u.id, u.email, COUNT(a.id) as activityCount, SUM(a.hoursWorked) as totalHours, SUM(a.delayInHours) as totalDelay, SUM(a.revisionCount) as totalRevisions, SUM(CASE WHEN a.reportStatus = \'REVIEWED\' AND a.revisionCount = 0 THEN 1 ELSE 0 END) as firstTimeApprovals')
            ->from(Activity::class, 'a')
            ->join('a.employee', 'u')
            ->groupBy('u.id, u.email');

        if (!$this->isAdmin()) {
            $qb->join('a.project', 'p')
               ->andWhere('p.projectManager = :mgr')->setParameter('mgr', $this->getUser());
        }

        if ($period === 'week') {
            $qb->andWhere('a.activityDate >= :since')
               ->setParameter('since', new \DateTime('-7 days'));
        } elseif ($period === 'month') {
            $qb->andWhere('a.activityDate >= :since')
               ->setParameter('since', new \DateTime('-30 days'));
        }

        $rawRankings = $qb->getQuery()->getResult();
        
        $rankings = [];
        foreach ($rawRankings as $r) {
            $actCount = (int)$r['activityCount'];
            $totalDelay = (int)($r['totalDelay'] ?? 0);
            $totalRevs = (int)($r['totalRevisions'] ?? 0);
            $ftApp = (int)($r['firstTimeApprovals'] ?? 0);
            
            $ftar = $actCount > 0 ? round(($ftApp / $actCount) * 100) : 0;
            
            // Efficiency Score Algorithm
            // Base points: 100 per activity
            // Quality bonus: 20 per FTAR approval
            // Late penalty: -10 per hour delayed
            // Rework penalty: -15 per revision cycle
            $score = ($actCount * 100) + ($ftApp * 20) - ($totalDelay * 10) - ($totalRevs * 15);
            
            $r['efficiencyScore'] = max(0, $score); // ensure score doesn't go below 0 for display
            $r['ftar'] = $ftar;
            $rankings[] = $r;
        }
        
        // Sort by Efficiency Score DESC
        usort($rankings, function($a, $b) {
            return $b['efficiencyScore'] <=> $a['efficiencyScore'];
        });

        // Fetch profile info for top users
        $profileMap = [];
        foreach ($rankings as $r) {
            $user = $em->getRepository(User::class)->find($r['id']);
            $profile = $user?->getProfile();
            $profileMap[$r['id']] = [
                'firstName' => $profile?->getFirstName(),
                'lastName' => $profile?->getLastName(),
                'avatar' => $profile?->getProfilePicturePath(),
            ];
        }

        return $this->render('back/leaderboard/index.html.twig', [
            'rankings' => $rankings,
            'profiles' => $profileMap,
            'period' => $period,
        ]);
    }

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  CALENDAR
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    #[Route('/calendar', name: 'admin_calendar')]
    public function calendar(): Response
    {
        return $this->render('back/calendar/index.html.twig');
    }

    #[Route('/calendar/data', name: 'admin_calendar_data')]
    public function calendarData(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $events = [];

        // Projects as date ranges
        if ($this->isAdmin()) {
            $projects = $em->getRepository(Project::class)->findAll();
        } else {
            $projects = $em->getRepository(Project::class)->findBy(['projectManager' => $user]);
        }

        $statusColors = [
            'PLANNED' => '#f59e0b', 'IN_PROGRESS' => '#4f46e5',
            'DONE' => '#10b981', 'ON_HOLD' => '#ef4444',
        ];

        foreach ($projects as $p) {
            if ($p->getStartDate()) {
                $events[] = [
                    'id' => 'proj_' . $p->getId(),
                    'title' => 'ðŸ“ ' . $p->getName(),
                    'start' => $p->getStartDate()->format('Y-m-d'),
                    'end' => $p->getEndDate() ? $p->getEndDate()->modify('+1 day')->format('Y-m-d') : null,
                    'color' => $statusColors[$p->getStatus()] ?? '#6366f1',
                    'url' => '/admin/projects/' . $p->getId() . '/edit',
                    'extendedProps' => ['type' => 'project', 'status' => $p->getStatus()],
                ];
            }
        }

        // Activities as single-day events
        if ($this->isAdmin()) {
            $activities = $em->getRepository(Activity::class)->findAll();
        } else {
            $activities = $em->getRepository(Activity::class)->createQueryBuilder('a')
                ->join('a.project', 'p')
                ->where('p.projectManager = :uid')->setParameter('uid', $user)
                ->getQuery()->getResult();
        }

        foreach ($activities as $a) {
            if ($a->getActivityDate()) {
                $events[] = [
                    'id' => 'act_' . $a->getId(),
                    'title' => '⚡ ' . ($a->getDescription() ? mb_substr($a->getDescription(), 0, 30) : 'Activité'),
                    'start' => $a->getActivityDate()->format('Y-m-d'),
                    'color' => '#06b6d4',
                    'extendedProps' => [
                        'type' => 'activity',
                        'hours' => $a->getHoursWorked(),
                        'project' => $a->getProject()?->getName(),
                    ],
                ];
            }
        }

        return new JsonResponse($events);
    }

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  EMPLOYEE PDF REPORT
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    #[Route('/reports/employee/{id}', name: 'admin_report_employee', requirements: ['id' => '\d+'])]
    public function reportEmployee(int $id, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin()) {
            // HR can only see reports for employees on their projects
            $hasAccess = $em->createQueryBuilder()
                ->select('COUNT(a.id)')
                ->from(Activity::class, 'a')
                ->join('a.project', 'p')
                ->where('a.employee = :eid AND p.projectManager = :mgr')
                ->setParameter('eid', $id)
                ->setParameter('mgr', $this->getUser())
                ->getQuery()->getSingleScalarResult();
            if ($hasAccess == 0) {
                throw $this->createAccessDeniedException();
            }
        }

        $employee = $em->getRepository(User::class)->find($id);
        if (!$employee) throw $this->createNotFoundException();

        $activities = $em->getRepository(Activity::class)->findBy(
            ['employee' => $employee], ['activityDate' => 'DESC']
        );

        $totalHours = 0;
        $projectNames = [];
        foreach ($activities as $a) {
            $totalHours += (float)($a->getHoursWorked() ?? 0);
            if ($a->getProject() && !in_array($a->getProject()->getName(), $projectNames)) {
                $projectNames[] = $a->getProject()->getName();
            }
        }

        return $this->render('back/reports/employee_pdf.html.twig', [
            'employee' => $employee,
            'activities' => $activities,
            'totalHours' => round($totalHours, 2),
            'totalProjects' => count($projectNames),
            'projectNames' => $projectNames,
        ]);
    }

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  ACTIVITY REPORT REVIEW
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    #[Route('/activities/{id}/review', name: 'admin_activity_review', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function activityReview(Activity $activity, Request $request, EntityManagerInterface $em): Response
    {
        $project = $activity->getProject();
        if (!$this->isAdmin() && ($project && $project->getProjectManagerId() !== $this->getUser()->getId())) {
            throw $this->createAccessDeniedException('Access denied');
        }

        $action = $request->request->get('reviewAction', 'approve');
        $feedback = trim($request->request->get('adminFeedback', ''));

        if ($action === 'revision' || $action === 'reject') {
            if (empty($feedback)) {
                $this->addFlash('danger', 'Le retour (feedback) est obligatoire pour une demande de révision ou un refus.');
                return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('admin_activities'));
            }
            $activity->setAdminFeedback($feedback);
            if ($action === 'revision') {
                $activity->setReportStatus('REVISION_REQUESTED');
                $activity->setRevisionCount($activity->getRevisionCount() + 1);
                $this->addFlash('warning', 'Révision demandée au candidat.');
            } else {
                $activity->setReportStatus('REJECTED');
                $this->addFlash('danger', 'Le rapport a été refusé.');
            }
        } else {
            // approve
            $activity->setReportStatus('REVIEWED');
            $this->addFlash('success', 'Rapport marqué comme révisé et approuvé.');
        }

        $em->flush();

        if ($project) {
            return $this->redirectToRoute('admin_project_logs', ['id' => $project->getId()]);
        }
        return $this->redirectToRoute('admin_activities');
    }

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  SUPPORT (ADMIN only)
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
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

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  USER MANAGEMENT (ADMIN only)
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
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



    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  OFFER APPLICATIONS (view & decision)
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
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
            
            if ($newStatus === 'Entretien') {
                $existingInterview = $em->getRepository(Interview::class)->findOneBy(['application' => $application]);
                if (!$existingInterview) {
                    $interview = new Interview();
                    $interview->setApplication($application);
                    $interview->setInterviewDate(new \DateTime('+1 day'));
                    $interview->setStatus('SCHEDULED');
                    $em->persist($interview);
                }
            }
            
            $em->flush();

            // Notify the candidate
            $candidate = $application->getUser();
            if ($candidate) {
                $ns = new NotificationService($em);
                $statusLabel = match($newStatus) {
                    'Acceptée' => '✅ Candidature acceptée',
                    'Refusée' => 'âŒ Candidature refusée',
                    'Entretien' => 'ðŸ“… Entretien programmé',
                    default => 'ðŸ“‹ Mise à jour candidature',
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

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  NOTIFICATIONS
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
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

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  PROFILE
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
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

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  USER PROFILE (Admin view)
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
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

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  HR â†’ CANDIDATE MESSAGING
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
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

    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    //  WORKFLOW PIPELINE AUTOMATIONS (n8n like)
    // â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
    #[Route('/offers/{id}/workflow', name: 'admin_offer_workflow', requirements: ['id' => '\d+'])]
    public function offerWorkflow(Offer $offer): Response
    {
        if (!$this->isAdmin() && $offer->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
        return $this->render('back/offers/workflow.html.twig', [
            'offer' => $offer,
        ]);
    }

    #[Route('/offers/{id}/workflow/save', name: 'admin_offer_workflow_save', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function offerWorkflowSave(Offer $offer, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $offer->getRecruiterId() !== $this->getUser()->getId()) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['success' => false, 'message' => 'Invalid JSON']);
        }

        $offer->setWorkflow($data);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
