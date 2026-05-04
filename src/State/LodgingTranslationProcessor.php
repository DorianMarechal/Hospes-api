<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\LodgingTranslation;
use App\Entity\User;
use App\Repository\LodgingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LodgingTranslationProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private LodgingRepository $lodgingRepository,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): LodgingTranslation
    {
        if (!$data instanceof LodgingTranslation) {
            throw new \InvalidArgumentException('Expected '.LodgingTranslation::class);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('Expected authenticated user');
        }

        $hostProfile = $user->getHostProfile();
        if (null === $hostProfile) {
            throw new AccessDeniedHttpException('Host profile required.');
        }

        if ($operation instanceof Post) {
            $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);
            if (null === $lodging) {
                throw new NotFoundHttpException('Lodging not found.');
            }

            if (!$lodging->getHost()?->getId()?->equals($hostProfile->getId())) {
                throw new AccessDeniedHttpException('You do not own this lodging.');
            }

            $data->setLodging($lodging);
        } else {
            $lodging = $data->getLodging();
            if (null === $lodging || !$lodging->getHost()?->getId()?->equals($hostProfile->getId())) {
                throw new AccessDeniedHttpException('You do not own this lodging.');
            }
        }

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
