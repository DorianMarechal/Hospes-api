<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\UserRepository;

class AdminUsersProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    /**
     * @return \App\Entity\User[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $filters = $context['filters'] ?? [];
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $qb = $this->userRepository->createQueryBuilder('u');

        if (isset($filters['search']) && '' !== $filters['search']) {
            $search = '%'.strtolower($filters['search']).'%';
            $qb->andWhere('LOWER(u.email) LIKE :search OR LOWER(u.firstName) LIKE :search OR LOWER(u.lastName) LIKE :search')
                ->setParameter('search', $search);
        }

        if (isset($filters['status']) && '' !== $filters['status']) {
            $qb->andWhere('u.isActive = :active')
                ->setParameter('active', 'active' === $filters['status']);
        }

        if (isset($filters['role']) && '' !== $filters['role']) {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%"'.$filters['role'].'"%');
        }

        return $qb->orderBy('u.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
