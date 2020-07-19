<?php

namespace App\Repository;

use App\Entity\FileExcel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method FileExcel|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileExcel|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileExcel[]    findAll()
 * @method FileExcel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileExcelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileExcel::class);
    }

    // /**
    //  * @return FileExcel[] Returns an array of FileExcel objects
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
    public function findOneBySomeField($value): ?FileExcel
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
