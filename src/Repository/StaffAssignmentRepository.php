<?php

namespace App\Repository;

use App\Entity\StaffAssignment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StaffAssignment>
 */
class StaffAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StaffAssignment::class);
    }

    /**
     * @return StaffAssignment[]
     */
    public function findByHost(User $host): array
    {
        return $this->findBy(['host' => $host]);
    }

    public function findByInvitationToken(string $token): ?StaffAssignment
    {
        return $this->findOneBy(['invitationToken' => $token]);
    }

    public function findActiveByStaff(User $staff): ?StaffAssignment
    {
        return $this->createQueryBuilder('sa')
            ->where('sa.staff = :staff')
            ->andWhere('sa.isRevoked = false')
            ->setParameter('staff', $staff)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
