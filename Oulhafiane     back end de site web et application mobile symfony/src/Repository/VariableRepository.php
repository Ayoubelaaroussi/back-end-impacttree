<?php

namespace App\Repository;

use App\Entity\Variable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Variable|null find($id, $lockMode = null, $lockVersion = null)
 * @method Variable|null findOneBy(array $criteria, array $orderBy = null)
 * @method Variable[]    findAll()
 * @method Variable[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VariableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Variable::class);
    }

    public function getVariablesOfDataset($dataset)
    {
        return $this->createQueryBuilder('v')
            ->innerJoin('v.part','p')
            ->andWhere('p.dataSet = :dataset')
            ->setParameter('dataset', $dataset)
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Variable[] Returns an array of Variable objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Variable
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
