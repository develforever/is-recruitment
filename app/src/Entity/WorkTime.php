<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: \App\Repository\WorkTimeRepository::class)]
#[ORM\Table(name: 'work_time')]
class WorkTime
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private UuidInterface $id;

    #[ORM\ManyToOne(targetEntity: Employee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Employee $employee;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $startAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $endAt;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $startDay;

    public function __construct(Employee $employee, \DateTimeInterface $startAt, \DateTimeInterface $endAt)
    {
        $this->id = Uuid::uuid4();
        $this->employee = $employee;
        $this->startAt = $startAt;
        $this->endAt = $endAt;
        $this->startDay = new \DateTimeImmutable($startAt->format('Y-m-d'));
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function getStartAt(): \DateTimeInterface
    {
        return $this->startAt;
    }

    public function getEndAt(): \DateTimeInterface
    {
        return $this->endAt;
    }

    public function getStartDay(): \DateTimeInterface
    {
        return $this->startDay;
    }

    public function getDurationSeconds(): int
    {
        return (int) ($this->endAt->getTimestamp() - $this->startAt->getTimestamp());
    }
}
