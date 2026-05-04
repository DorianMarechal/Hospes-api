<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\PromotionCodeRepository;
use Symfony\Bundle\SecurityBundle\Security;

class MyPromotionCodesProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private PromotionCodeRepository $promoRepository,
    ) {
    }

    /**
     * @return \App\Entity\PromotionCode[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getHostProfile()) {
            return [];
        }

        return $this->promoRepository->findByHost($user->getHostProfile());
    }
}
