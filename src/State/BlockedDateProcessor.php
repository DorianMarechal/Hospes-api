<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\BlockedDate;
use App\Repository\BlockedDateRepository;
use App\Repository\BookingRepository;
use App\Repository\LodgingRepository;
use App\Service\AvailabilityResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlockedDateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private LodgingRepository $lodgingRepository,
        private BookingRepository $bookingRepository,
        private BlockedDateRepository $blockedDateRepository,
        private AvailabilityResolver $availabilityResolver,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        assert($data instanceof BlockedDate);

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        if (null === $lodging->getHost() || !$lodging->getHost()->getId()?->equals($user->getHostProfile()?->getId())) {
            throw new HttpException(403, 'You do not own this lodging');
        }

        $existingBookings = $this->bookingRepository->findByLodging($lodging);
        $existingBlocked = $this->blockedDateRepository->findByLodging($lodging);

        if (!$this->availabilityResolver->isAvailable(
            $lodging,
            $data->getStartDate(),
            $data->getEndDate(),
            $existingBookings,
            $existingBlocked,
            null,
        )) {
            throw new HttpException(409, 'These dates conflict with an existing booking or blocked period');
        }

        $data->setLodging($lodging);
        $data->setSource('manual');
        $data->setCreatedAt(new \DateTimeImmutable());
        $data->setUpdatedAt(new \DateTimeImmutable());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
