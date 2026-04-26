<?php

namespace App\Controller\Admin;

use App\Entity\Activity;
use App\Entity\Application;
use App\Entity\Choix;
use App\Entity\Event;
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
use App\Entity\TicketReply;
use App\Entity\User;
use App\Service\CourseMailer;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
            $myOffers = $em->getRepository(Offer::class)->findBy(['recruiterId' => $user->getId()]);
            $myProjects = $em->getRepository(Project::class)->findBy(['projectManagerId' => $user->getId()]);

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
                ['recruiterId' => $user->getId()], ['publishDate' => 'DESC'], 5
            );
            $recentApplications = $em->getRepository(Application::class)->createQueryBuilder('a')
                ->join('a.offer', 'o')
                ->where('o.recruiterId = :uid')->setParameter('uid', $user->getId())
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
                ['recruiterId' => $this->getUser()->getId()], ['publishDate' => 'DESC']
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
            $offer->setApplicationsReceived(0);
            $offer->setRecruiterId($this->getUser()->getId());

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
        if (!$this->isAdmin() && $offer->getRecruiterId() !== $this->getUser()->getId()) {
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
        if (!$this->isAdmin() && $offer->getRecruiterId() !== $this->getUser()->getId()) {
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
    public function events(EntityManagerInterface $em): Response
    {
        if ($this->isAdmin()) {
            $events = $em->getRepository(Event::class)->findBy([], ['eventDate' => 'DESC']);
        } else {
            $events = $em->getRepository(Event::class)->findBy(
                ['organizer' => $this->getUser()], ['eventDate' => 'DESC']
            );
        }
        return $this->render('back/events/list.html.twig', ['events' => $events]);
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

    // ── Séances CRUD ──
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

    // ── Quiz CRUD ──
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

    // ── Question + Choix CRUD ──
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

    // ── Enrollment Management ──
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
            $this->addFlash('warning', 'Inscription refusée.');
        }
        return $this->redirectToRoute('admin_course_enrollments', ['id' => $id]);
    }

    // ── Seance Edit ──
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

    // ── Quiz Edit ──
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

    // ── AI Question Generation ──
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
        $fullCtx     = trim($formationTitle . ($sessionCtx ? ' — ' . $sessionCtx : ''));

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

        // ── Provider list: try each in order until one succeeds ──
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
        $ctx     = trim($formation . ($session ? ' — ' . $session : ''));

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
                    ['enonce' => 'Quelle méthode permet d’ajouter un élément à une liste Python ?',
                     'choix'  => [['texte'=>'.append()','correct'=>true],['texte'=>'.add()','correct'=>false],['texte'=>'.insert_end()','correct'=>false],['texte'=>'.push()','correct'=>false]]],
                    ['enonce' => 'Quelle structure Python garantit l’unicité de ses éléments ?',
                     'choix'  => [['texte'=>'set','correct'=>true],['texte'=>'list','correct'=>false],['texte'=>'tuple','correct'=>false],['texte'=>'dict','correct'=>false]]],
                    ['enonce' => 'Comment accéder à la valeur associée à la clé "age" dans un dictionnaire d Python ?',
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
                ['enonce' => 'Quel type de clé garantit l’unicité d’un enregistrement ?',
                 'choix'  => [['texte'=>'Clé primaire','correct'=>true],['texte'=>'Clé étrangère','correct'=>false],['texte'=>'Index','correct'=>false],['texte'=>'Contrainte CHECK','correct'=>false]]],
                ['enonce' => 'Quelle fonction SQL compte le nombre de lignes ?',
                 'choix'  => [['texte'=>'COUNT()','correct'=>true],['texte'=>'SUM()','correct'=>false],['texte'=>'AVG()','correct'=>false],['texte'=>'TOTAL()','correct'=>false]]],
            ];
        }

        // Generic but improved fallback with actual content reference
        $subjectDisplay = $subject ?: $ctx;
        return [
            ['enonce' => "Dans le cadre de \"$subjectDisplay\", quelle affirmation est correcte ?",
             'choix'  => [['texte'=>"Le sujet comporte des concepts théoriques et pratiques", 'correct'=>true],['texte'=>"Il n’existe qu’une seule méthode valable",'correct'=>false],['texte'=>"La mémorisation suffit sans compréhension",'correct'=>false],['texte'=>"Les exemples ne sont pas utiles",'correct'=>false]]],
            ['enonce' => "Quel est l’avantage principal de maîtriser \"$subjectDisplay\" ?",
             'choix'  => [['texte'=>"Résoudre des problèmes concrets plus efficacement",'correct'=>true],['texte'=>"Ne pas avoir besoin de pratiquer",'correct'=>false],['texte'=>"Ignorer la documentation",'correct'=>false],['texte'=>"Travailler uniquement en théorie",'correct'=>false]]],
            ['enonce' => "Pour \"$subjectDisplay\", laquelle de ces approches est recommandée ?",
             'choix'  => [['texte'=>"Pratiquer sur des cas réels",'correct'=>true],['texte'=>"Lire sans expérimenter",'correct'=>false],['texte'=>"Éviter les erreurs à tout prix",'correct'=>false],['texte'=>"Copier sans comprendre",'correct'=>false]]],
            ['enonce' => "Quel élément est central dans \"$subjectDisplay\" ?",
             'choix'  => [['texte'=>"La compréhension des concepts fondamentaux",'correct'=>true],['texte'=>"La mémorisation de formules",'correct'=>false],['texte'=>"L’absence d’exercices",'correct'=>false],['texte'=>"La théorie sans application",'correct'=>false]]],
            ['enonce' => "Comment progresser efficacement sur \"$subjectDisplay\" ?",
             'choix'  => [['texte'=>"Combiner théorie, pratique et révision",'correct'=>true],['texte'=>"Étudier uniquement la nuit",'correct'=>false],['texte'=>"Ne jamais poser de questions",'correct'=>false],['texte'=>"Ignorer les retours",'correct'=>false]]],
        ];
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
                ['projectManagerId' => $this->getUser()->getId()], ['createdAt' => 'DESC']
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
            if ($budget) $p->setBudget($budget);
            $p->setProjectManagerId($this->getUser()->getId());

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
                ->where('p.projectManagerId = :uid')->setParameter('uid', $user->getId())
                ->orderBy('a.activityDate', 'DESC')
                ->getQuery()->getResult();

            // HR's candidates = users who applied to their offers
            $candidates = $em->createQueryBuilder()
                ->select('DISTINCT u')
                ->from(User::class, 'u')
                ->join(Application::class, 'app', 'WITH', 'app.user = u')
                ->join('app.offer', 'o')
                ->where('o.recruiterId = :uid')->setParameter('uid', $user->getId())
                ->getQuery()->getResult();
        }

        // Load HR's own projects for the assignment form
        if ($this->isAdmin()) {
            $projects = $em->getRepository(Project::class)->findBy([], ['createdAt' => 'DESC']);
        } else {
            $projects = $em->getRepository(Project::class)->findBy(['projectManagerId' => $user->getId()]);
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
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('admin_profile');
        }

        return $this->render('back/profile/index.html.twig', [
            'profile' => $profile,
        ]);
    }
}
