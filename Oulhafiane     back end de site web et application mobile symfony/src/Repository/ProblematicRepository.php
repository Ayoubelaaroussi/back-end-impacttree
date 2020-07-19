<?php

namespace App\Repository;

use App\Entity\Problematic;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Problematic|null find($id, $lockMode = null, $lockVersion = null)
 * @method Problematic|null findOneBy(array $criteria, array $orderBy = null)
 * @method Problematic[]    findAll()
 * @method Problematic[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProblematicRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Problematic::class);
    }

    // /**
    //  * @return Problematic[] Returns an array of Problematic objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Problematic
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
