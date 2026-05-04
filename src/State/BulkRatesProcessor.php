<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\BulkRatesRequest;
use App\Entity\PriceOverride;
use App\Entity\User;
use App\Repository\LodgingRepository;
use App\Repository\PriceOverrideRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BulkRatesProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private LodgingRepository $lodgingRepository,
        private PriceOverrideRepository $priceOverrideRepository,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @return PriceOverride[]
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        if (!$data instanceof BulkRatesRequest) {
            throw new \InvalidArgumentException('Expected '.BulkRatesRequest::class);
        }

        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('Expected authenticated user');
        }

        $hostProfile = $user->getHostProfile();
        if (!$hostProfile || !$lodging->getHost()?->getId()?->equals($hostProfile->getId())) {
            throw new AccessDeniedHttpException('You do not own this lodging');
        }

        $startDate = \DateTimeImmutable::createFromFormat('Y-m-d', $data->startDate);
        $endDate = \DateTimeImmutable::createFromFormat('Y-m-d', $data->endDate);

        if (false === $startDate || false === $endDate) {
            throw new HttpException(422, 'Invalid date format, expected Y-m-d');
        }

        if ($startDate > $endDate) {
            throw new HttpException(422, 'Start date must be before or equal to end date');
        }

        if ($startDate->diff($endDate)->days > 366) {
            throw new HttpException(422, 'Date range cannot exceed 366 days');
        }

        $now = new \DateTimeImmutable();
        $result = [];
        $current = $startDate;

        while ($current <= $endDate) {
            $existing = $this->priceOverrideRepository->findOneBy([
                'lodging' => $lodging,
                'date' => $current,
            ]);

            if (null !== $existing) {
                $existing->setPrice($data->price);
                if (null !== $data->label) {
                    $existing->setLabel($data->label);
                }
                $existing->setUpdatedAt($now);
                $result[] = $existing;
            } else {
                $override = new PriceOverride();
                $override->setLodging($lodging);
                $override->setDate($current);
                $override->setPrice($data->price);
                $override->setLabel($data->label);
                $override->setCreatedAt($now);
                $override->setUpdatedAt($now);
                $this->em->persist($override);
                $result[] = $override;
            }

            $current = $current->modify('+1 day');
        }

        $this->em->flush();

        return $result;
    }
}
