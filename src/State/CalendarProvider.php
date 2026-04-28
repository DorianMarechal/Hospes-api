<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\CalendarDay;
use App\Entity\BlockedDate;
use App\Entity\Booking;
use App\Enum\BookingStatus;
use App\Repository\BlockedDateRepository;
use App\Repository\BookingRepository;
use App\Repository\LodgingRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CalendarProvider implements ProviderInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private BookingRepository $bookingRepository,
        private BlockedDateRepository $blockedDateRepository,
    ) {
    }

    /**
     * @return CalendarDay[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        $filters = $context['filters'] ?? [];
        if (empty($filters['month'])) {
            throw new BadRequestHttpException('Query parameter "month" is required (format: YYYY-MM)');
        }

        $month = $filters['month'];
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new BadRequestHttpException('Invalid month format. Use YYYY-MM');
        }

        $firstDay = new \DateTimeImmutable($month.'-01');
        $lastDay = $firstDay->modify('last day of this month');
        $monthEnd = $lastDay->modify('+1 day');

        $bookings = $this->bookingRepository->findByLodging($lodging);
        $blockedDates = $this->blockedDateRepository->findByLodging($lodging);

        $calendar = [];
        $current = $firstDay;

        while ($current < $monthEnd) {
            $dateStr = $current->format('Y-m-d');
            $nextDay = $current->modify('+1 day');

            $day = $this->resolveDayStatus($current, $nextDay, $bookings, $blockedDates);
            $calendar[] = $day;

            $current = $nextDay;
        }

        return $calendar;
    }

    /**
     * @param Booking[]     $bookings
     * @param BlockedDate[] $blockedDates
     */
    private function resolveDayStatus(
        \DateTimeImmutable $date,
        \DateTimeImmutable $nextDay,
        array $bookings,
        array $blockedDates,
    ): CalendarDay {
        $dateStr = $date->format('Y-m-d');

        foreach ($bookings as $booking) {
            if (BookingStatus::CANCELLED === $booking->getStatus()) {
                continue;
            }

            if (BookingStatus::PENDING === $booking->getStatus() && $booking->getExpiresAt() < new \DateTimeImmutable()) {
                continue;
            }

            if ($date >= $booking->getCheckin() && $date < $booking->getCheckout()) {
                return new CalendarDay(
                    date: $dateStr,
                    status: 'booked',
                    bookingId: (string) $booking->getId(),
                    bookingReference: $booking->getReference(),
                );
            }
        }

        foreach ($blockedDates as $blockedDate) {
            if ($date >= $blockedDate->getStartDate() && $date < $blockedDate->getEndDate()) {
                return new CalendarDay(
                    date: $dateStr,
                    status: 'blocked',
                    blockedDateId: (string) $blockedDate->getId(),
                    reason: $blockedDate->getReason(),
                );
            }
        }

        return new CalendarDay(
            date: $dateStr,
            status: 'available',
        );
    }
}
