<?php

namespace App\Repository;

use App\Entity\MsgContactUs;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method MsgContactUs|null find($id, $lockMode = null, $lockVersion = null)
 * @method MsgContactUs|null findOneBy(array $criteria, array $orderBy = null)
 * @method MsgContactUs[]    findAll()
 * @method MsgContactUs[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MsgContactUsRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MsgContactUs::class);
    }

    // /**
    //  * @return MsgContactUs[] Returns an array of MsgContactUs objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MsgContactUs
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
