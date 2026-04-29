<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\PaymentProviderResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentProviderDisconnectProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PaymentProviderResult
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        $hostProfile = $user->getHostProfile();

        if (!$hostProfile) {
            throw new HttpException(403, 'Only hosts can disconnect a payment provider');
        }

        if (null === $hostProfile->getPaymentProvider()) {
            throw new HttpException(422, 'No payment provider connected');
        }

        $hostProfile->setPaymentProvider(null);
        $hostProfile->setPaymentProviderAccountId(null);
        $hostProfile->setPaymentProviderOnboardedAt(null);
        $hostProfile->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $result = new PaymentProviderResult();
        $result->provider = null;
        $result->accountId = null;
        $result->isOnboarded = false;
        $result->onboardedAt = null;
        $result->connectUrl = null;

        return $result;
    }
}
