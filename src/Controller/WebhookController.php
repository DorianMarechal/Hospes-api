<?php

namespace App\Controller;

use App\Enum\PaymentProvider;
use App\Enum\PaymentStatus;
use App\Payment\PaymentGatewayFactory;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WebhookController
{
    public function __construct(
        private PaymentGatewayFactory $gatewayFactory,
        private PaymentRepository $paymentRepository,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/api/webhooks/stripe', methods: ['POST'])]
    public function stripe(Request $request): Response
    {
        return $this->handleWebhook(
            $request,
            PaymentProvider::STRIPE,
            $request->headers->get('Stripe-Signature', ''),
        );
    }

    #[Route('/api/webhooks/paypal', methods: ['POST'])]
    public function paypal(Request $request): Response
    {
        return $this->handleWebhook(
            $request,
            PaymentProvider::PAYPAL,
            $request->headers->get('Paypal-Transmission-Sig', ''),
        );
    }

    private function handleWebhook(Request $request, PaymentProvider $provider, string $signature): Response
    {
        $gateway = $this->gatewayFactory->get($provider);
        $payload = $request->getContent();

        try {
            $event = $gateway->verifyWebhook($payload, $signature);
        } catch (\RuntimeException $e) {
            $this->logger->warning('Webhook signature verification failed', [
                'provider' => $provider->value,
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse(['error' => 'Invalid signature'], 400);
        }

        $payment = $this->paymentRepository->findOneBy(['providerTransactionId' => $event['transactionId']]);
        if (!$payment) {
            $this->logger->info('Webhook received for unknown transaction', [
                'provider' => $provider->value,
                'transactionId' => $event['transactionId'],
            ]);

            return new JsonResponse(['status' => 'ignored']);
        }

        $now = new \DateTimeImmutable();

        match ($event['type']) {
            'payment.succeeded' => $payment->setStatus(PaymentStatus::SUCCEEDED),
            'payment.failed' => $payment->setStatus(PaymentStatus::FAILED),
            default => null,
        };

        $payment->setUpdatedAt($now);
        $this->em->flush();

        $this->logger->info('Webhook processed', [
            'provider' => $provider->value,
            'type' => $event['type'],
            'paymentId' => $payment->getId()?->toRfc4122(),
        ]);

        return new JsonResponse(['status' => 'processed']);
    }
}
