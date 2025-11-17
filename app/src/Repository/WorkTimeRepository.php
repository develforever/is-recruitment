<?php

namespace App\Repository;

use App\Entity\Employee;
use App\Entity\WorkTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

class WorkTimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkTime::class);
    }

    /**
     * @return WorkTime[]
     */
    public function findByEmployeeAndDay(Employee $employee, \DateTimeInterface $day): array
    {
        // @param $day - DateTimeImmutable reprezentujący dzień (Y-m-d)
        return $this->createQueryBuilder('w')
            ->andWhere('w.employee = :emp')
            ->andWhere('w.startDay = :d')
            ->setParameter('emp', $employee)
            ->setParameter('d', $day, Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return WorkTime[]
     */
    public function findByEmployeeAndMonth(Employee $employee, string $yearMonth): array
    {
        // $yearMonth expected like '2025-11'
        $start = new \DateTimeImmutable($yearMonth . '-01');
        $end = $start->modify('last day of this month');

        return $this->createQueryBuilder('w')
            ->andWhere('w.employee = :emp')
            ->andWhere('w.startDay BETWEEN :start AND :end')
            ->setParameter('emp', $employee)
            ->setParameter('start', $start, Types::DATE_IMMUTABLE)
            ->setParameter('end', $end, Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getResult();
    }
}
