<?php

namespace App\Controller;

use App\Entity\WebhookEvent;
use App\Enum\BookingStatus;
use App\Enum\PaymentProvider;
use App\Enum\PaymentStatus;
use App\Payment\PaymentGatewayFactory;
use App\Repository\PaymentRepository;
use App\Repository\WebhookEventRepository;
use App\Service\NotificationDispatcher;
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
        private WebhookEventRepository $webhookEventRepository,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private NotificationDispatcher $notificationDispatcher,
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

        // Deduplication: skip if already processed
        if ($this->webhookEventRepository->hasBeenProcessed($provider->value, $event['eventId'])) {
            $this->logger->info('Webhook event already processed, skipping', [
                'provider' => $provider->value,
                'eventId' => $event['eventId'],
            ]);

            return new JsonResponse(['status' => 'duplicate']);
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
            'payment.succeeded' => $this->handlePaymentSucceeded($payment),
            'payment.failed' => $payment->setStatus(PaymentStatus::FAILED),
            default => $this->logger->warning('Unhandled webhook event type', [
                'provider' => $provider->value,
                'type' => $event['type'],
                'eventId' => $event['eventId'],
            ]),
        };

        $payment->setUpdatedAt($now);

        // Record event for deduplication
        $webhookEvent = new WebhookEvent();
        $webhookEvent->setProvider($provider->value);
        $webhookEvent->setProviderEventId($event['eventId']);
        $webhookEvent->setEventType($event['type']);
        $webhookEvent->setProcessedAt($now);
        $this->em->persist($webhookEvent);

        $this->em->flush();
        $this->notificationDispatcher->publishPendingNotifications();

        $this->logger->info('Webhook processed', [
            'provider' => $provider->value,
            'type' => $event['type'],
            'eventId' => $event['eventId'],
            'paymentId' => $payment->getId()?->toRfc4122(),
        ]);

        return new JsonResponse(['status' => 'processed']);
    }

    private function handlePaymentSucceeded(\App\Entity\Payment $payment): void
    {
        $payment->setStatus(PaymentStatus::SUCCEEDED);

        $booking = $payment->getBooking();
        if (null !== $booking && BookingStatus::PENDING === $booking->getStatus()) {
            $booking->setStatus(BookingStatus::CONFIRMED);
            $booking->setExpiresAt(null);
            $booking->setUpdatedAt(new \DateTimeImmutable());
            $this->notificationDispatcher->bookingConfirmed($booking);
        }
    }
}
