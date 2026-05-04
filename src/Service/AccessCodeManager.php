<?php

namespace App\Service;

use App\Entity\AccessCode;
use App\Entity\Booking;
use App\Repository\AccessCodeRepository;
use Doctrine\ORM\EntityManagerInterface;

class AccessCodeManager
{
    public function __construct(
        private AccessCodeRepository $accessCodeRepository,
        private EntityManagerInterface $em,
    ) {
    }

    public function generateForBooking(Booking $booking): AccessCode
    {
        $existing = $this->accessCodeRepository->findByBooking($booking);
        if (null !== $existing) {
            return $existing;
        }

        $checkin = $booking->getCheckin();
        $checkout = $booking->getCheckout();
        $now = new \DateTimeImmutable();

        $accessCode = new AccessCode();
        $accessCode->setBooking($booking);
        $accessCode->setCode($this->generateRandomCode());
        $accessCode->setValidFrom($checkin ?? $now);
        $accessCode->setValidTo($checkout ?? $now->modify('+1 day'));
        $accessCode->setCreatedAt($now);

        $this->em->persist($accessCode);

        return $accessCode;
    }

    public function revokeForBooking(Booking $booking): void
    {
        $accessCode = $this->accessCodeRepository->findByBooking($booking);
        if (null === $accessCode || $accessCode->isRevoked()) {
            return;
        }

        $accessCode->setRevoked(true);
    }

    private function generateRandomCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}
