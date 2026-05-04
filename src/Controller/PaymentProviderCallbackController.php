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
    private const int STATE_MAX_AGE_SECONDS = 1800; // 30 minutes

    public function __construct(
        private PaymentGatewayFactory $gatewayFactory,
        private HostProfileRepository $hostProfileRepository,
        private EntityManagerInterface $em,
        private string $appSecret,
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

        $hostProfileId = $this->verifyAndExtractState($state);

        $hostProfile = $this->hostProfileRepository->find(Uuid::fromString($hostProfileId));
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
        ]);
    }

    private function verifyAndExtractState(string $state): string
    {
        $decoded = base64_decode($state, true);
        if (false === $decoded) {
            throw new BadRequestHttpException('Invalid state parameter');
        }

        $parts = explode('|', $decoded);
        if (3 !== \count($parts)) {
            throw new BadRequestHttpException('Invalid state parameter');
        }

        [$hostProfileId, $timestamp, $signature] = $parts;

        $expectedSignature = hash_hmac('sha256', $hostProfileId.'|'.$timestamp, $this->appSecret);
        if (!hash_equals($expectedSignature, $signature)) {
            throw new BadRequestHttpException('Invalid state signature');
        }

        if (time() - (int) $timestamp > self::STATE_MAX_AGE_SECONDS) {
            throw new BadRequestHttpException('State parameter has expired');
        }

        return $hostProfileId;
    }
}
