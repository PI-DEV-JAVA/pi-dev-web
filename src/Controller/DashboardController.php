<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Application;
use App\Entity\Event;
use App\Entity\Formation;
use App\Entity\FormationEnrollment;
use App\Entity\Interview;
use App\Entity\Notification;
use App\Entity\Offer;
use App\Entity\ProfileView;
use App\Entity\QuizAttempt;
use App\Entity\Sync;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function account(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        // Admin/HR → redirect to back office
        if (in_array($user->getRole(), ['ADMIN', 'HR'])) {
            return $this->redirectToRoute('admin_dashboard');
        }

        // Candidate dashboard data
        $applications = $em->getRepository(Application::class)->findBy(
            ['user' => $user], ['applicationDate' => 'DESC'], 5
        );
        $activities = $em->getRepository(Activity::class)->findBy(
            ['employee' => $user], ['activityDate' => 'DESC'], 5
        );
        $notifications = $em->getRepository(Notification::class)->findBy(
            ['user' => $user], ['createdAt' => 'DESC'], 5
        );

        // ── Analytics data ──
        $appCount      = $em->getRepository(Application::class)->count(['user' => $user]);
        $acceptedApps  = $em->getRepository(Application::class)->count(['user' => $user, 'status' => 'Acceptée']);
        $rejectedApps  = $em->getRepository(Application::class)->count(['user' => $user, 'status' => 'Refusée']);
        $pendingApps   = $appCount - $acceptedApps - $rejectedApps;
        $actCount      = count($activities);
        $unreadNotifs  = $em->getRepository(Notification::class)->count(['user' => $user, 'isRead' => false]);
        $interviewCount = $em->getRepository(Interview::class)->count(['candidate' => $user]);
        $enrolledCourses = $em->getRepository(FormationEnrollment::class)->count(['user' => $user, 'status' => FormationEnrollment::STATUS_APPROVED]);
        $quizzesPassed = $em->getRepository(QuizAttempt::class)->count(['user' => $user]);

        // Profile views (last 30 days)
        $thirtyDaysAgo = new \DateTime('-30 days');
        $profileViews = $em->createQueryBuilder()
            ->select('COUNT(pv.id)')
            ->from(ProfileView::class, 'pv')
            ->where('pv.viewedUser = :user')
            ->andWhere('pv.viewedAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $thirtyDaysAgo)
            ->getQuery()->getSingleScalarResult();

        // Recent profile viewers (last 5 unique)
        $recentViewers = $em->createQueryBuilder()
            ->select('IDENTITY(pv.viewer) as viewerId, MAX(pv.viewedAt) as lastViewed')
            ->from(ProfileView::class, 'pv')
            ->where('pv.viewedUser = :user')
            ->andWhere('pv.viewer IS NOT NULL')
            ->groupBy('pv.viewer')
            ->orderBy('lastViewed', 'DESC')
            ->setMaxResults(5)
            ->setParameter('user', $user)
            ->getQuery()->getResult();

        $viewerUsers = [];
        foreach ($recentViewers as $rv) {
            $vu = $em->getRepository(\App\Entity\User::class)->find($rv['viewerId']);
            if ($vu) $viewerUsers[] = ['user' => $vu, 'viewedAt' => $rv['lastViewed']];
        }

        return $this->render('front/account/dashboard.html.twig', [
            'applications'    => $applications,
            'activities'      => $activities,
            'notifications'   => $notifications,
            'appCount'        => $appCount,
            'acceptedApps'    => $acceptedApps,
            'rejectedApps'    => $rejectedApps,
            'pendingApps'     => $pendingApps,
            'actCount'        => $actCount,
            'unreadNotifs'    => $unreadNotifs,
            'interviewCount'  => $interviewCount,
            'enrolledCourses' => $enrolledCourses,
            'quizzesPassed'   => $quizzesPassed,
            'profileViews'    => (int)$profileViews,
            'recentViewers'   => $viewerUsers,
        ]);
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
