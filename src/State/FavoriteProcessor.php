<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Favorite;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\LodgingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FavoriteProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private LodgingRepository $lodgingRepository,
        private FavoriteRepository $favoriteRepository,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Favorite
    {
        assert($data instanceof Favorite);

        /** @var User $user */
        $user = $this->security->getUser();

        $lodging = $this->lodgingRepository->find($data->getLodgingId());
        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        $existing = $this->favoriteRepository->findOneBy(['user' => $user, 'lodging' => $lodging]);
        if ($existing) {
            throw new BadRequestHttpException('This lodging is already in your favorites');
        }

        $data->setUser($user);
        $data->setLodging($lodging);
        $data->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }
}
