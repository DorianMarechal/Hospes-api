<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\PaymentProviderResult;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentProviderStatusProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PaymentProviderResult
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        $hostProfile = $user->getHostProfile();

        if (!$hostProfile) {
            throw new HttpException(403, 'Only hosts can view payment provider status');
        }

        $result = new PaymentProviderResult();
        $result->provider = $hostProfile->getPaymentProvider();
        $result->accountId = $hostProfile->getPaymentProviderAccountId();
        $result->isOnboarded = null !== $hostProfile->getPaymentProviderAccountId();
        $result->onboardedAt = $hostProfile->getPaymentProviderOnboardedAt();
        $result->connectUrl = null;

        return $result;
    }
}
