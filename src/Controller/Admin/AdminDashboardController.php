<?php

namespace App\Controller\Admin;

use App\Entity\Activity;
use App\Entity\Application;
use App\Entity\Event;
use App\Entity\Formation;
use App\Entity\Interview;
use App\Entity\Offer;
use App\Entity\Project;
use App\Entity\SupportTicket;
use App\Entity\TicketReply;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

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
            $offer = new Offer();
            $offer->setTitle($request->request->get('title'));
            $offer->setDescription($request->request->get('description'));
            $offer->setContractType($request->request->get('contractType'));
            $offer->setLocation($request->request->get('location'));
            $offer->setDepartment($request->request->get('department'));
            $offer->setExperienceLevel($request->request->get('experienceLevel'));
            $offer->setPositionsAvailable((int)$request->request->get('positionsAvailable', 1));
            $salaryMin = $request->request->get('salaryMin');
            $salaryMax = $request->request->get('salaryMax');
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
            $offer->setTitle($request->request->get('title'));
            $offer->setDescription($request->request->get('description'));
            $offer->setContractType($request->request->get('contractType'));
            $offer->setLocation($request->request->get('location'));
            $offer->setDepartment($request->request->get('department'));
            $offer->setExperienceLevel($request->request->get('experienceLevel'));
            $offer->setPositionsAvailable((int)$request->request->get('positionsAvailable', 1));
            $salaryMin = $request->request->get('salaryMin');
            $salaryMax = $request->request->get('salaryMax');
            if ($salaryMin) $offer->setSalaryMin((float)$salaryMin);
            if ($salaryMax) $offer->setSalaryMax((float)$salaryMax);
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
            $event = new Event();
            $event->setTitle($request->request->get('title'));
            $event->setDescription($request->request->get('description'));
            $event->setEventType($request->request->get('eventType'));
            $event->setLocation($request->request->get('location'));
            $dateStr = $request->request->get('eventDate');
            if ($dateStr) $event->setEventDate(new \DateTime($dateStr));
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
            $event->setTitle($request->request->get('title'));
            $event->setDescription($request->request->get('description'));
            $event->setEventType($request->request->get('eventType'));
            $event->setLocation($request->request->get('location'));
            $dateStr = $request->request->get('eventDate');
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
    //  COURSES (ADMIN only)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/courses', name: 'admin_courses')]
    public function courses(EntityManagerInterface $em): Response
    {
        $formations = $em->getRepository(Formation::class)->findBy([], ['dateDebut' => 'DESC']);
        return $this->render('back/courses/list.html.twig', ['formations' => $formations]);
    }

    #[Route('/courses/new', name: 'admin_course_new', methods: ['GET', 'POST'])]
    public function courseNew(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $f = new Formation();
            $f->setTitre($request->request->get('titre'));
            $f->setDescription($request->request->get('description'));
            $f->setDuree((int)$request->request->get('duree', 1));
            $f->setNiveau($request->request->get('niveau'));
            $dateStr = $request->request->get('dateDebut');
            if ($dateStr) $f->setDateDebut(new \DateTime($dateStr));

            $em->persist($f);
            $em->flush();
            $this->addFlash('success', 'Formation créée.');
            return $this->redirectToRoute('admin_courses');
        }
        return $this->render('back/courses/form.html.twig', ['formation' => null]);
    }

    #[Route('/courses/{id}/edit', name: 'admin_course_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function courseEdit(Formation $formation, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $formation->setTitre($request->request->get('titre'));
            $formation->setDescription($request->request->get('description'));
            $formation->setDuree((int)$request->request->get('duree', 1));
            $formation->setNiveau($request->request->get('niveau'));
            $dateStr = $request->request->get('dateDebut');
            if ($dateStr) $formation->setDateDebut(new \DateTime($dateStr));
            $em->flush();
            $this->addFlash('success', 'Formation modifiée.');
            return $this->redirectToRoute('admin_courses');
        }
        return $this->render('back/courses/form.html.twig', ['formation' => $formation]);
    }

    #[Route('/courses/{id}/delete', name: 'admin_course_delete', requirements: ['id' => '\d+'])]
    public function courseDelete(Formation $formation, EntityManagerInterface $em): Response
    {
        $em->remove($formation);
        $em->flush();
        $this->addFlash('success', 'Formation supprimée.');
        return $this->redirectToRoute('admin_courses');
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
            $p = new Project();
            $p->setName($request->request->get('name'));
            $p->setDescription($request->request->get('description'));
            $p->setStatus($request->request->get('status', 'PLANNED'));
            $startDate = $request->request->get('startDate');
            $endDate = $request->request->get('endDate');
            if ($startDate) $p->setStartDate(new \DateTime($startDate));
            if ($endDate) $p->setEndDate(new \DateTime($endDate));
            $budget = $request->request->get('budget');
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
            $project->setName($request->request->get('name'));
            $project->setDescription($request->request->get('description'));
            $project->setStatus($request->request->get('status', 'PLANNED'));
            $startDate = $request->request->get('startDate');
            $endDate = $request->request->get('endDate');
            if ($startDate) $project->setStartDate(new \DateTime($startDate));
            if ($endDate) $project->setEndDate(new \DateTime($endDate));
            $budget = $request->request->get('budget');
            if ($budget) $project->setBudget($budget);
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

    #[Route('/projects/{id}/logs', name: 'admin_project_logs', requirements: ['id' => '\d+'])]
    public function projectLogs(Project $project, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $project->getProjectManagerId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $logs = $em->getRepository(Activity::class)->getProjectLogs($project);

        return $this->render('back/projects/logs.html.twig', [
            'project' => $project,
            'logs' => $logs,
        ]);
    }

    #[Route('/projects/{id}/kanban', name: 'admin_project_kanban', requirements: ['id' => '\d+'])]
    public function projectKanban(Project $project, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $project->getProjectManagerId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $activities = $em->getRepository(Activity::class)->findBy(['project' => $project], ['activityDate' => 'DESC']);

        return $this->render('back/projects/kanban.html.twig', [
            'project' => $project,
            'activities' => $activities,
        ]);
    }

    #[Route('/activities/{id}/kanban-update', name: 'admin_activity_kanban_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function activityKanbanUpdate(Activity $activity, Request $request, EntityManagerInterface $em): Response
    {
        $project = $activity->getProject();
        if ($project && !$this->isAdmin() && $project->getProjectManagerId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'Access Denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['status'])) {
            $activity->setStatus($data['status']);
            $em->flush();
            return $this->json(['success' => true]);
        }

        return $this->json(['error' => 'Invalid data'], 400);
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
    public function projectActivityNew(Project $project, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if (!$this->isAdmin() && $project->getProjectManagerId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $desc = trim($request->request->get('description', ''));
        $hours = $request->request->get('hoursWorked');
        $dateStr = $request->request->get('activityDate');
        $deadlineStr = $request->request->get('deadline');

        if ($desc) {
            $activity = new Activity();
            $activity->setEmployee($this->getUser());
            $activity->setProject($project);
            $activity->setDescription($desc);
            if ($hours) $activity->setHoursWorked($hours);
            $activity->setActivityDate($dateStr ? new \DateTime($dateStr) : new \DateTime());
            if ($deadlineStr) $activity->setDeadline(new \DateTime($deadlineStr));
            $em->persist($activity);
            $em->flush();

            // Send Email Notification
            try {
                $emailMsg = (new Email())
                    ->from('talentos.pidev@gmail.com')
                    ->to($this->getUser()->getEmail())
                    ->subject('Talentos — Nouvelle activité enregistrée')
                    ->html(
                        '<div style="font-family: \'Segoe UI\', Arial, sans-serif; max-width: 480px; margin: 0 auto;'
                        . 'padding: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);'
                        . 'border-radius: 16px;">'
                        . '<div style="background: white; border-radius: 12px; padding: 32px; text-align: center;">'
                        . '<h1 style="color: #111827; font-size: 24px; margin: 0 0 8px;">Talentos</h1>'
                        . '<p style="color: #6b7280; font-size: 14px; margin: 0 0 24px;">Nouvelle activité enregistrée</p>'
                        . '<div style="background: #f3f4f6; border-radius: 12px; padding: 20px; margin: 0 0 24px; text-align: left;">'
                        . '<p style="color: #111827; font-weight: 600; font-size: 14px; margin: 0 0 8px;">Détails :</p>'
                        . '<p style="color: #6b7280; font-size: 14px; margin: 0 0 8px;">' . htmlspecialchars($desc) . '</p>'
                        . '<p style="color: #6366f1; font-weight: bold; font-size: 14px; margin: 0;">Projet : ' . htmlspecialchars($project->getName()) . '</p>'
                        . '</div>'
                        . '<p style="color: #9ca3af; font-size: 12px; margin: 0;">Connectez-vous à votre tableau de bord pour plus de détails.</p>'
                        . '</div></div>'
                    );
                $mailer->send($emailMsg);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'L\'activité a été assignée, mais l\'email a échoué: ' . $e->getMessage());
            }

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
    public function activityNew(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        $candidateId = (int)$request->request->get('candidateId');
        $projectId = $request->request->get('projectId');
        $desc = trim($request->request->get('description', ''));
        $hours = $request->request->get('hoursWorked');
        $dateStr = $request->request->get('activityDate');
        $deadlineStr = $request->request->get('deadline');

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
            if ($deadlineStr) $activity->setDeadline(new \DateTime($deadlineStr));
            $em->persist($activity);
            $em->flush();

            // Notify candidate
            $ns = new NotificationService($em);
            $pName = $project ? ' (' . $project->getName() . ')' : '';
            $ns->notify($candidate, 'ACTIVITY', 'Nouvelle activité assignée', $desc . $pName, '/activities');

            // Send Email
            try {
                $emailMsg = (new Email())
                    ->from('talentos.pidev@gmail.com')
                    ->to($candidate->getEmail())
                    ->subject('Talentos — Nouvelle activité assignée')
                    ->html(
                        '<div style="font-family: \'Segoe UI\', Arial, sans-serif; max-width: 480px; margin: 0 auto;'
                        . 'padding: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);'
                        . 'border-radius: 16px;">'
                        . '<div style="background: white; border-radius: 12px; padding: 32px; text-align: center;">'
                        . '<h1 style="color: #111827; font-size: 24px; margin: 0 0 8px;">Talentos</h1>'
                        . '<p style="color: #6b7280; font-size: 14px; margin: 0 0 24px;">Nouvelle activité assignée</p>'
                        . '<div style="background: #f3f4f6; border-radius: 12px; padding: 20px; margin: 0 0 24px; text-align: left;">'
                        . '<p style="color: #111827; font-weight: 600; font-size: 14px; margin: 0 0 8px;">Description de la tâche :</p>'
                        . '<p style="color: #6b7280; font-size: 14px; margin: 0 0 8px;">' . htmlspecialchars($desc) . '</p>'
                        . '<p style="color: #6366f1; font-weight: bold; font-size: 14px; margin: 0;">' . ($project ? 'Projet : ' . htmlspecialchars($project->getName()) : 'Aucun projet spécifique') . '</p>'
                        . '</div>'
                        . '<p style="color: #9ca3af; font-size: 12px; margin: 0;">Connectez-vous à votre espace candidat pour soumettre votre rapport.</p>'
                        . '</div></div>'
                    );
                $mailer->send($emailMsg);
            } catch (\Exception $e) {
                $this->addFlash('danger', 'L\'activité a été assignée, mais l\'email a échoué: ' . $e->getMessage());
            }

            $this->addFlash('success', 'Activité assignée à ' . $candidate->getEmail());
        }

        return $this->redirectToRoute('admin_activities');
    }

    #[Route('/activities/{id}/review', name: 'admin_activity_review', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function activityReview(Activity $activity, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $project = $activity->getProject();
        
        if (!$this->isAdmin() && $project && $project->getProjectManagerId() !== $user->getId()) {
             throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $status = $request->request->get('status');
            $responseTxt = $request->request->get('adminResponse');
            
            if (in_array($status, ['APPROVED', 'REJECTED', 'PENDING'])) {
                $activity->setStatus($status);
            }
            if ($responseTxt !== null) {
                $activity->setAdminResponse(trim($responseTxt));
            }
            $em->flush();
            $this->addFlash('success', 'Activité mise à jour: ' . $activity->getStatus());
            return $this->redirectToRoute('admin_activity_review', ['id' => $activity->getId()]);
        }

        return $this->render('back/activities/review.html.twig', [
            'activity' => $activity,
        ]);
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
}
