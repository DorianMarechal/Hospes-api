<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Enum\BookingStatus;
use App\Repository\BookingRepository;

class AdminBookingsProvider implements ProviderInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
    ) {
    }

    /**
     * @return \App\Entity\Booking[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $filters = $context['filters'] ?? [];
        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $qb = $this->bookingRepository->createQueryBuilder('b');

        if (isset($filters['status']) && '' !== $filters['status']) {
            $status = BookingStatus::tryFrom($filters['status']);
            if (null !== $status) {
                $qb->andWhere('b.status = :status')
                    ->setParameter('status', $status);
            }
        }

        if (isset($filters['from']) && '' !== $filters['from']) {
            $qb->andWhere('b.checkin >= :from')
                ->setParameter('from', new \DateTimeImmutable($filters['from']));
        }

        if (isset($filters['to']) && '' !== $filters['to']) {
            $qb->andWhere('b.checkout <= :to')
                ->setParameter('to', new \DateTimeImmutable($filters['to']));
        }

        if (isset($filters['lodging']) && '' !== $filters['lodging']) {
            $qb->andWhere('b.lodging = :lodging')
                ->setParameter('lodging', $filters['lodging']);
        }

        return $qb->orderBy('b.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
