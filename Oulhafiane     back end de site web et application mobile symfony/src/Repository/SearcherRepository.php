<?php

namespace App\Repository;

use App\Entity\Searcher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Searcher|null find($id, $lockMode = null, $lockVersion = null)
 * @method Searcher|null findOneBy(array $criteria, array $orderBy = null)
 * @method Searcher[]    findAll()
 * @method Searcher[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SearcherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Searcher::class);
    }

    // /**
    //  * @return Searcher[] Returns an array of Searcher objects
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
    public function findOneBySomeField($value): ?Searcher
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
