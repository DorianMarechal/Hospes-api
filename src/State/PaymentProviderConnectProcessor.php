<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ConnectPaymentProviderRequest;
use App\Dto\PaymentProviderResult;
use App\Entity\User;
use App\Payment\PaymentGatewayFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentProviderConnectProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private PaymentGatewayFactory $gatewayFactory,
        private string $appSecret,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PaymentProviderResult
    {
        if (!$data instanceof ConnectPaymentProviderRequest) {
            throw new \InvalidArgumentException('Expected '.ConnectPaymentProviderRequest::class);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new HttpException(401, 'Authentication required');
        }
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

        $hostProfileId = $hostProfile->getId()?->toRfc4122() ?? '';
        $timestamp = time();
        $payload = $hostProfileId.'|'.$timestamp;
        $signature = hash_hmac('sha256', $payload, $this->appSecret);
        $state = base64_encode($payload.'|'.$signature);

        $gateway = $this->gatewayFactory->get($data->provider);
        $connectUrl = $gateway->buildOnboardingUrl($state);

        $result = new PaymentProviderResult();
        $result->provider = $data->provider;
        $result->accountId = null;
        $result->isOnboarded = false;
        $result->onboardedAt = null;
        $result->connectUrl = $connectUrl;

        return $result;
    }
}
