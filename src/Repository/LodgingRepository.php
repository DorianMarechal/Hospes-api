<?php

namespace App\Repository;

use App\Entity\Lodging;
use App\Enum\LodgingType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lodging>
 */
class LodgingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lodging::class);
    }

    public function findWithPricing(mixed $id): ?Lodging
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.seasons', 's')->addSelect('s')
            ->leftJoin('l.priceOverrides', 'po')->addSelect('po')
            ->andWhere('l.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array{
     *     type?: LodgingType,
     *     city?: string,
     *     capacity?: int,
     *     priceMin?: int,
     *     priceMax?: int,
     *     ratingMin?: float,
     *     latitude?: float,
     *     longitude?: float,
     *     radiusKm?: float,
     *     amenities?: string[],
     *     cursor?: string,
     *     limit?: int,
     * } $criteria
     *
     * @return Lodging[]
     */
    public function advancedSearch(array $criteria): array
    {
        $qb = $this->createQueryBuilder('l')
            ->andWhere('l.isActive = true');

        if (isset($criteria['type'])) {
            $qb->andWhere('l.type = :type')
                ->setParameter('type', $criteria['type']);
        }

        if (isset($criteria['city'])) {
            $qb->andWhere('LOWER(l.city) = LOWER(:city)')
                ->setParameter('city', $criteria['city']);
        }

        if (isset($criteria['capacity'])) {
            $qb->andWhere('l.capacity >= :capacity')
                ->setParameter('capacity', $criteria['capacity']);
        }

        if (isset($criteria['priceMin'])) {
            $qb->andWhere('l.basePriceWeek >= :priceMin')
                ->setParameter('priceMin', $criteria['priceMin']);
        }

        if (isset($criteria['priceMax'])) {
            $qb->andWhere('l.basePriceWeek <= :priceMax')
                ->setParameter('priceMax', $criteria['priceMax']);
        }

        if (isset($criteria['ratingMin'])) {
            $qb->andWhere('l.averageRating >= :ratingMin')
                ->setParameter('ratingMin', $criteria['ratingMin']);
        }

        if (isset($criteria['amenities']) && \count($criteria['amenities']) > 0) {
            $qb->andWhere(
                'l.id IN (
                    SELECT IDENTITY(la.lodging) FROM App\Entity\LodgingAmenity la
                    JOIN la.amenity a
                    WHERE a.name IN (:amenities)
                    GROUP BY la.lodging
                    HAVING COUNT(DISTINCT a.id) = :amenityCount
                )',
            )
                ->setParameter('amenities', $criteria['amenities'])
                ->setParameter('amenityCount', \count($criteria['amenities']));
        }

        if (isset($criteria['latitude'], $criteria['longitude'], $criteria['radiusKm'])) {
            $geoIds = $this->findIdsWithinRadius([
                'latitude' => $criteria['latitude'],
                'longitude' => $criteria['longitude'],
                'radiusKm' => $criteria['radiusKm'],
            ]);

            if ([] === $geoIds) {
                return [];
            }

            $qb->andWhere('l.id IN (:geoIds)')
                ->setParameter('geoIds', $geoIds);
        }

        if (isset($criteria['cursor'])) {
            $qb->andWhere('l.id > :cursor')
                ->setParameter('cursor', $criteria['cursor']);
        }

        $qb->orderBy('l.id', 'ASC')
            ->setMaxResults($criteria['limit'] ?? 20);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array{
     *     latitude: float,
     *     longitude: float,
     *     radiusKm: float,
     * } $geo
     *
     * @return string[] UUIDs of lodgings within radius
     */
    public function findIdsWithinRadius(array $geo): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT id::text FROM lodging
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL
            AND ST_DWithin(
                ST_MakePoint(longitude::float, latitude::float)::geography,
                ST_MakePoint(:lng, :lat)::geography,
                :radius
            )
        SQL;

        $result = $conn->executeQuery($sql, [
            'lat' => $geo['latitude'],
            'lng' => $geo['longitude'],
            'radius' => $geo['radiusKm'] * 1000,
        ]);

        return $result->fetchFirstColumn();
    }
}
