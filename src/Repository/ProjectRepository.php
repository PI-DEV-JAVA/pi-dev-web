<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findActiveProjects(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isArchived = :archived')
            ->andWhere('p.status != :done_status')
            ->setParameter('archived', false)
            ->setParameter('done_status', 'DONE')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findProjectsCloseToDeadline(int $daysLimit = 7): array
    {
        $limitDate = new \DateTime("+$daysLimit days");
        return $this->createQueryBuilder('p')
            ->andWhere('p.endDate IS NOT NULL')
            ->andWhere('p.endDate <= :limitDate')
            ->andWhere('p.endDate >= :today')
            ->andWhere('p.status != :done')
            ->andWhere('p.isArchived = :archived')
            ->setParameter('limitDate', $limitDate)
            ->setParameter('today', new \DateTime())
            ->setParameter('done', 'DONE')
            ->setParameter('archived', false)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOverdueProjects(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.endDate IS NOT NULL')
            ->andWhere('p.endDate < :today')
            ->andWhere('p.status != :done')
            ->andWhere('p.isArchived = :archived')
            ->setParameter('today', new \DateTime())
            ->setParameter('done', 'DONE')
            ->setParameter('archived', false)
            ->getQuery()
            ->getResult()
        ;
    }
}
