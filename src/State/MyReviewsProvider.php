<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\ReviewRepository;
use Symfony\Bundle\SecurityBundle\Security;

class MyReviewsProvider implements ProviderInterface
{
    public function __construct(
        private ReviewRepository $reviewRepository,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return $this->reviewRepository->findByCustomer($user);
    }
}
