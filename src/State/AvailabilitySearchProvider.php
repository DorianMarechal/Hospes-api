<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\AvailabilitySearchResult;
use App\Enum\LodgingType;
use App\Repository\BlockedDateRepository;
use App\Repository\BookingRepository;
use App\Repository\LodgingRepository;
use App\Service\AvailabilityResolver;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AvailabilitySearchProvider implements ProviderInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private BookingRepository $bookingRepository,
        private BlockedDateRepository $blockedDateRepository,
        private AvailabilityResolver $availabilityResolver,
    ) {
    }

    /**
     * @return AvailabilitySearchResult[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $filters = $context['filters'] ?? [];

        if (empty($filters['checkin']) || empty($filters['checkout'])) {
            throw new BadRequestHttpException('Query parameters "checkin" and "checkout" are required (format: YYYY-MM-DD)');
        }

        $checkin = \DateTimeImmutable::createFromFormat('Y-m-d', $filters['checkin']);
        $checkout = \DateTimeImmutable::createFromFormat('Y-m-d', $filters['checkout']);

        if (!$checkin || !$checkout) {
            throw new BadRequestHttpException('Invalid date format. Use YYYY-MM-DD');
        }

        $checkin = $checkin->setTime(0, 0);
        $checkout = $checkout->setTime(0, 0);

        if ($checkout <= $checkin) {
            throw new BadRequestHttpException('Checkout must be after checkin');
        }

        $criteria = [];

        if (!empty($filters['type'])) {
            $type = LodgingType::tryFrom($filters['type']);
            if (!$type) {
                throw new BadRequestHttpException('Invalid lodging type');
            }
            $criteria['type'] = $type;
        }

        if (!empty($filters['city'])) {
            $criteria['city'] = $filters['city'];
        }

        if (!empty($filters['capacity'])) {
            $criteria['capacity'] = (int) $filters['capacity'];
        }

        if (!empty($filters['price_min'])) {
            $criteria['priceMin'] = (int) $filters['price_min'];
        }

        if (!empty($filters['price_max'])) {
            $criteria['priceMax'] = (int) $filters['price_max'];
        }

        if (!empty($filters['rating_min'])) {
            $criteria['ratingMin'] = (float) $filters['rating_min'];
        }

        if (!empty($filters['amenities'])) {
            $criteria['amenities'] = explode(',', $filters['amenities']);
        }

        if (!empty($filters['cursor'])) {
            $criteria['cursor'] = $filters['cursor'];
        }

        $limit = min((int) ($filters['limit'] ?? 20), 50);
        $criteria['limit'] = $limit;

        // Géospatial : intégré dans advancedSearch si lat/lng fournis
        $hasGeo = !empty($filters['latitude']) && !empty($filters['longitude']);

        if ($hasGeo) {
            $criteria['latitude'] = (float) $filters['latitude'];
            $criteria['longitude'] = (float) $filters['longitude'];
            $criteria['radiusKm'] = (float) ($filters['radius'] ?? 30);
        }

        $lodgings = $this->lodgingRepository->advancedSearch($criteria);

        $results = [];
        $searchLat = $hasGeo ? (float) $filters['latitude'] : null;
        $searchLng = $hasGeo ? (float) $filters['longitude'] : null;

        // Batch: fetch all overlapping bookings/blocked dates for candidate lodgings
        $bookingsByLodging = $this->bookingRepository->findActiveOverlappingForLodgings($lodgings, $checkin, $checkout);
        $blockedByLodging = $this->blockedDateRepository->findOverlappingForLodgings($lodgings, $checkin, $checkout);

        foreach ($lodgings as $lodging) {
            $lodgingId = $lodging->getId()?->toRfc4122() ?? '';
            $bookings = $bookingsByLodging[$lodgingId] ?? [];
            $blockedDates = $blockedByLodging[$lodgingId] ?? [];

            $available = $this->availabilityResolver->isAvailable(
                $lodging,
                $checkin,
                $checkout,
                $bookings,
                $blockedDates,
                null,
            );

            if (!$available) {
                continue;
            }

            $distanceKm = null;
            if (null !== $searchLat && null !== $searchLng
                && null !== $lodging->getLatitude() && null !== $lodging->getLongitude()) {
                $distanceKm = $this->haversine(
                    $searchLat, $searchLng,
                    (float) $lodging->getLatitude(), (float) $lodging->getLongitude(),
                );
            }

            $results[] = new AvailabilitySearchResult(
                lodgingId: $lodging->getId(),
                name: $lodging->getName(),
                type: $lodging->getType()->value,
                city: $lodging->getCity(),
                region: $lodging->getRegion(),
                country: $lodging->getCountry(),
                capacity: $lodging->getCapacity(),
                basePriceWeek: $lodging->getBasePriceWeek(),
                basePriceWeekend: $lodging->getBasePriceWeekend(),
                averageRating: $lodging->getAverageRating(),
                reviewCount: $lodging->getReviewCount(),
                latitude: $lodging->getLatitude(),
                longitude: $lodging->getLongitude(),
                distanceKm: $distanceKm,
            );
        }

        // Tri par pertinence : distance + note + prix
        usort($results, function (AvailabilitySearchResult $a, AvailabilitySearchResult $b) use ($hasGeo): int {
            $scoreA = $this->relevanceScore($a, $hasGeo);
            $scoreB = $this->relevanceScore($b, $hasGeo);

            return $scoreB <=> $scoreA;
        });

        return $results;
    }

    private function relevanceScore(AvailabilitySearchResult $r, bool $hasGeo): float
    {
        $score = 0.0;

        // Note : 0-5 → 0-40 points
        $rating = (float) ($r->averageRating ?? 0);
        $score += $rating * 8;

        // Nombre d'avis : log scale, max ~20 points
        $reviews = $r->reviewCount ?? 0;
        if ($reviews > 0) {
            $score += min(log($reviews + 1, 2) * 4, 20);
        }

        // Distance : plus c'est proche, plus le score est haut (max 40 points)
        if ($hasGeo && null !== $r->distanceKm) {
            $score += max(0, 40 - $r->distanceKm);
        }

        return $score;
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
