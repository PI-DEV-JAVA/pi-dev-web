<?php

namespace App\Repository;

use App\Entity\EvenementRh;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvenementRh>
 */
class EvenementRhRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvenementRh::class);
    }

    /**
     * @return EvenementRh[] Returns an array of EvenementRh objects
     */
    public function findByFilters(?string $titre, ?string $type, ?string $statut): array
    {
        $qb = $this->createQueryBuilder('e');

        if ($titre) {
            $qb->andWhere('e.titre LIKE :titre')
               ->setParameter('titre', '%' . $titre . '%');
        }

        if ($type) {
            $qb->andWhere('e.typeEvent = :type')
               ->setParameter('type', $type);
        }

        if ($statut) {
            $qb->andWhere('e.statut = :statut')
               ->setParameter('statut', $statut);
        }

        return $qb->orderBy('e.dateEvent', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
