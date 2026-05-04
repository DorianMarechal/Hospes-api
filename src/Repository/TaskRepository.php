<?php

namespace App\Repository;

use App\Entity\HostProfile;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * @return Task[]
     */
    public function findForUser(User $user, ?string $from = null, ?string $to = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.assignee = :user')
            ->setParameter('user', $user)
            ->orderBy('t.dueDate', 'ASC');

        $this->applyDateFilters($qb, $from, $to);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Task[]
     */
    public function findForHost(HostProfile $hostProfile, ?string $from = null, ?string $to = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.hostProfile = :host')
            ->setParameter('host', $hostProfile)
            ->orderBy('t.dueDate', 'ASC');

        $this->applyDateFilters($qb, $from, $to);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     */
    private function applyDateFilters($qb, ?string $from, ?string $to): void
    {
        if (null !== $from) {
            $fromDate = \DateTimeImmutable::createFromFormat('Y-m-d', $from);
            if (false !== $fromDate) {
                $qb->andWhere('t.dueDate >= :from')
                    ->setParameter('from', $fromDate);
            }
        }

        if (null !== $to) {
            $toDate = \DateTimeImmutable::createFromFormat('Y-m-d', $to);
            if (false !== $toDate) {
                $qb->andWhere('t.dueDate <= :to')
                    ->setParameter('to', $toDate);
            }
        }
    }
}
