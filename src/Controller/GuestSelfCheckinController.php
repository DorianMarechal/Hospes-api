<?php

namespace App\Controller;

use App\Entity\Guest;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class GuestSelfCheckinController
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/guest-portal/{token}/register-guests', methods: ['POST'])]
    public function registerGuests(string $token, Request $request): JsonResponse
    {
        $booking = $this->bookingRepository->findOneBy(['guestPortalToken' => $token]);
        if (null === $booking) {
            throw new NotFoundHttpException('Booking not found.');
        }

        $payload = json_decode($request->getContent(), true);
        $guestsData = $payload['guests'] ?? [];

        if (!\is_array($guestsData) || empty($guestsData)) {
            throw new HttpException(422, 'At least one guest is required.');
        }

        if (\count($guestsData) > 20) {
            throw new HttpException(422, 'Too many guests (max 20).');
        }

        $now = new \DateTimeImmutable();
        $registered = [];

        foreach ($guestsData as $guestData) {
            if (empty($guestData['firstName']) || empty($guestData['lastName'])) {
                continue;
            }

            $guest = new Guest();
            $guest->setBooking($booking);
            $guest->setFirstName($guestData['firstName']);
            $guest->setLastName($guestData['lastName']);

            if (!empty($guestData['nationality'])) {
                $guest->setNationality($guestData['nationality']);
            }

            if (!empty($guestData['birthDate'])) {
                $birthDate = \DateTimeImmutable::createFromFormat('Y-m-d', $guestData['birthDate']);
                if (false !== $birthDate) {
                    $guest->setBirthDate($birthDate);
                }
            }

            if (!empty($guestData['idType'])) {
                $idType = \App\Enum\IdentityDocumentType::tryFrom($guestData['idType']);
                if (null !== $idType) {
                    $guest->setIdType($idType);
                }
            }

            if (!empty($guestData['idNumber'])) {
                $guest->setIdNumber($guestData['idNumber']);
            }

            if (isset($guestData['gdprConsent']) && true === $guestData['gdprConsent']) {
                $guest->setGdprConsent(true);
            }

            $guest->setCreatedAt($now);

            $this->em->persist($guest);
            $registered[] = [
                'firstName' => $guest->getFirstName(),
                'lastName' => $guest->getLastName(),
                'nationality' => $guest->getNationality(),
            ];
        }

        $this->em->flush();

        return new JsonResponse([
            'registered' => \count($registered),
            'guests' => $registered,
        ], 201);
    }
}
