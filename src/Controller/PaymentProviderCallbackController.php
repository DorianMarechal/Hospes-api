<?php

namespace App\Controller;

use App\Enum\PaymentProvider;
use App\Payment\PaymentGatewayFactory;
use App\Repository\HostProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class PaymentProviderCallbackController
{
    public function __construct(
        private PaymentGatewayFactory $gatewayFactory,
        private HostProfileRepository $hostProfileRepository,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/payment-provider/stripe/callback', methods: ['GET'])]
    public function stripeCallback(Request $request): JsonResponse
    {
        return $this->handleCallback($request, PaymentProvider::STRIPE, 'code');
    }

    #[Route('/api/payment-provider/paypal/callback', methods: ['GET'])]
    public function paypalCallback(Request $request): JsonResponse
    {
        return $this->handleCallback($request, PaymentProvider::PAYPAL, 'merchantIdInPayPal');
    }

    private function handleCallback(Request $request, PaymentProvider $provider, string $codeParam): JsonResponse
    {
        $state = $request->query->getString('state');
        $code = $request->query->getString($codeParam);

        if (!$state || !$code) {
            throw new BadRequestHttpException('Missing required parameters');
        }

        $hostProfile = $this->hostProfileRepository->find(Uuid::fromString($state));
        if (!$hostProfile) {
            throw new BadRequestHttpException('Invalid state parameter');
        }

        $gateway = $this->gatewayFactory->get($provider);
        $accountId = $gateway->completeOnboarding($code);

        $hostProfile->setPaymentProvider($provider);
        $hostProfile->setPaymentProviderAccountId($accountId);
        $hostProfile->setPaymentProviderOnboardedAt(new \DateTimeImmutable());
        $this->em->flush();

        return new JsonResponse([
            'status' => 'connected',
            'provider' => $provider->value,
            'accountId' => $accountId,
        ]);
    }
}
