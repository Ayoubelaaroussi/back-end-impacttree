<?php

namespace App\Repository;

use App\Entity\SearcherApplications;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method SearcherApplications|null find($id, $lockMode = null, $lockVersion = null)
 * @method SearcherApplications|null findOneBy(array $criteria, array $orderBy = null)
 * @method SearcherApplications[]    findAll()
 * @method SearcherApplications[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SearcherApplicationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearcherApplications::class);
    }

    // /**
    //  * @return SearcherApplications[] Returns an array of SearcherApplications objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SearcherApplications
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
