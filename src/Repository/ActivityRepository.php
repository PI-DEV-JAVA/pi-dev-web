<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function findPendingActivities(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', 'PENDING')
            ->orderBy('a.activityDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getUserPerformanceStats(User $user): array
    {
        $activities = $this->findBy(['employee' => $user]);
        $total = count($activities);
        if ($total === 0) {
            return ['total' => 0, 'onTime' => 0, 'percentage' => 100, 'approved' => 0];
        }

        $onTimeCount = 0;
        $approvedCount = 0;
        foreach ($activities as $a) {
            if ($a->isOnTime()) {
                $onTimeCount++;
            }
            if ($a->getStatus() === 'APPROVED') {
                $approvedCount++;
            }
        }

        return [
            'total' => $total,
            'onTime' => $onTimeCount,
            'percentage' => round(($onTimeCount / $total) * 100),
            'approved' => $approvedCount,
        ];
    }

    public function getProjectLogs(Project $project): array
    {
        $activities = $this->findBy(['project' => $project]);
        $logs = [];

        foreach ($activities as $a) {
            $empId = $a->getEmployee()->getId();
            if (!isset($logs[$empId])) {
                $logs[$empId] = [
                    'employee' => $a->getEmployee(),
                    'total' => 0,
                    'onTime' => 0,
                    'approved' => 0,
                    'activities' => []
                ];
            }
            $logs[$empId]['total']++;
            if ($a->isOnTime()) {
                $logs[$empId]['onTime']++;
            }
            if ($a->getStatus() === 'APPROVED') {
                $logs[$empId]['approved']++;
            }
            $logs[$empId]['activities'][] = $a;
        }

        foreach ($logs as &$log) {
            $log['percentage'] = round(($log['onTime'] / $log['total']) * 100);
            // sort sub-activities by date descending
            usort($log['activities'], fn($a, $b) => $b->getActivityDate() <=> $a->getActivityDate());
        }

        return $logs;
    }

    public function findRecentActivities(int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.activityDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }
}
