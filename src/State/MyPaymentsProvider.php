<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\PaymentRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MyPaymentsProvider implements ProviderInterface
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private Security $security,
    ) {
    }

    /**
     * @return \App\Entity\Payment[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        $hostProfile = $user->getHostProfile();
        if (!$hostProfile) {
            throw new HttpException(403, 'Only hosts can list received payments');
        }

        $lodgingIds = [];
        foreach ($hostProfile->getLodgings() as $lodging) {
            $lodgingIds[] = $lodging->getId();
        }

        return $this->paymentRepository->findReceivedByHost($lodgingIds);
    }
}
