<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\LodgingAmenity;
use App\Entity\User;
use App\Repository\LodgingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LodgingAmenityProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private LodgingRepository $lodgingRepository,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): LodgingAmenity
    {
        assert($data instanceof LodgingAmenity);

        $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        $user = $this->security->getUser();
        \assert($user instanceof User);

        $hostProfile = $user->getHostProfile();
        if (!$hostProfile || !$lodging->getHost()?->getId()?->equals($hostProfile->getId())) {
            throw new AccessDeniedHttpException('You do not own this lodging');
        }

        $data->setLodging($lodging);

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
