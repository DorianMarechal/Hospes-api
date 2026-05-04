<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\BookingRepository;
use App\Repository\FavoriteRepository;
use App\Repository\NotificationRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\SecurityBundle\Security;

class DataExportProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private BookingRepository $bookingRepository,
        private ReviewRepository $reviewRepository,
        private FavoriteRepository $favoriteRepository,
        private NotificationRepository $notificationRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        $bookings = $this->bookingRepository->findByCustomerWithLodging($user);
        $reviews = $this->reviewRepository->findBy(['customer' => $user]);
        $favorites = $this->favoriteRepository->findBy(['user' => $user]);
        $notifications = $this->notificationRepository->findBy(['user' => $user]);

        return [
            'user' => [
                'id' => $user->getId()?->toRfc4122(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'roles' => $user->getRoles(),
                'consentedAt' => $user->getConsentedAt()?->format(\DateTimeInterface::ATOM),
                'createdAt' => $user->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ],
            'bookings' => array_map(fn ($b) => [
                'id' => $b->getId()?->toRfc4122(),
                'reference' => $b->getReference(),
                'lodging' => $b->getLodging()?->getName(),
                'checkin' => $b->getCheckin()?->format('Y-m-d'),
                'checkout' => $b->getCheckout()?->format('Y-m-d'),
                'guestsCount' => $b->getGuestsCount(),
                'totalPrice' => $b->getTotalPrice(),
                'status' => $b->getStatus()?->value,
                'createdAt' => $b->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ], $bookings),
            'reviews' => array_map(fn ($r) => [
                'id' => $r->getId()?->toRfc4122(),
                'lodging' => $r->getLodging()?->getName(),
                'rating' => $r->getRating(),
                'comment' => $r->getComment(),
                'createdAt' => $r->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ], $reviews),
            'favorites' => array_map(fn ($f) => [
                'lodging' => $f->getLodging()?->getName(),
                'createdAt' => $f->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ], $favorites),
            'notifications' => array_map(fn ($n) => [
                'type' => $n->getType(),
                'title' => $n->getTitle(),
                'content' => $n->getContent(),
                'createdAt' => $n->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ], $notifications),
            'exportedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }
}
