<?php

namespace App\Repository;

use App\Entity\DataSet;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method DataSet|null find($id, $lockMode = null, $lockVersion = null)
 * @method DataSet|null findOneBy(array $criteria, array $orderBy = null)
 * @method DataSet[]    findAll()
 * @method DataSet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DataSetRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DataSet::class);
    }

    // /**
    //  * @return DataSet[] Returns an array of DataSet objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DataSet
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
