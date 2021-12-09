<?php

namespace App\Repository;

use App\Entity\Actors;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Actors|null find($id, $lockMode = null, $lockVersion = null)
 * @method Actors|null findOneBy(array $criteria, array $orderBy = null)
 * @method Actors[]    findAll()
 * @method Actors[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActorsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Actors::class);
    }


    public function findByResearch($research)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.firstname LIKE :research')
            ->setParameter('research', '%'.$research.'%')
            ->orWhere('a.lastname LIKE :research')
            ->setParameter('research', '%'.$research.'%')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }


    /*
    public function findOneBySomeField($value): ?Actors
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
