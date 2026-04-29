<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ConnectPaymentProviderRequest;
use App\Dto\PaymentProviderResult;
use App\Enum\PaymentProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentProviderConnectProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PaymentProviderResult
    {
        assert($data instanceof ConnectPaymentProviderRequest);

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        $hostProfile = $user->getHostProfile();

        if (!$hostProfile) {
            throw new HttpException(403, 'Only hosts can connect a payment provider');
        }

        if (null !== $hostProfile->getPaymentProviderAccountId()) {
            throw new HttpException(422, 'A payment provider is already connected. Disconnect first.');
        }

        $hostProfile->setPaymentProvider($data->provider);
        $hostProfile->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        // Build the OAuth connect URL (placeholder — real implementation uses Stripe/PayPal SDK)
        $connectUrl = $this->buildConnectUrl($data->provider, $hostProfile->getId()?->toRfc4122());

        $result = new PaymentProviderResult();
        $result->provider = $data->provider;
        $result->accountId = null;
        $result->isOnboarded = false;
        $result->onboardedAt = null;
        $result->connectUrl = $connectUrl;

        return $result;
    }

    private function buildConnectUrl(PaymentProvider $provider, ?string $hostProfileId): string
    {
        // Placeholder URLs — will be replaced by real OAuth URLs when SDK is integrated
        return match ($provider) {
            PaymentProvider::STRIPE => '/api/me/payment-provider/oauth/stripe?state='.$hostProfileId,
            PaymentProvider::PAYPAL => '/api/me/payment-provider/oauth/paypal?state='.$hostProfileId,
        };
    }
}
