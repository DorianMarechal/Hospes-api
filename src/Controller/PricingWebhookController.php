<?php

namespace App\Controller;

use App\Entity\PriceOverride;
use App\Repository\LodgingRepository;
use App\Repository\PriceOverrideRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PricingWebhookController
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private PriceOverrideRepository $priceOverrideRepository,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/api/lodgings/{lodgingId}/pricing-webhook', methods: ['POST'])]
    public function __invoke(string $lodgingId, Request $request): Response
    {
        $lodging = $this->lodgingRepository->find($lodgingId);
        if (null === $lodging) {
            return new JsonResponse(['error' => 'Lodging not found'], 404);
        }

        // Authenticate via API key
        $apiKey = $request->headers->get('X-Pricing-Api-Key');
        $expectedKey = $lodging->getPricingWebhookApiKey();

        if (null === $expectedKey || '' === $expectedKey) {
            $this->logger->warning('Pricing webhook received but no API key configured', [
                'lodging' => $lodgingId,
            ]);

            return new JsonResponse(['error' => 'Webhook not configured for this lodging'], 403);
        }

        if (!hash_equals($expectedKey, $apiKey ?? '')) {
            $this->logger->warning('Pricing webhook authentication failed', [
                'lodging' => $lodgingId,
            ]);

            return new JsonResponse(['error' => 'Invalid API key'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON payload'], 400);
        }

        $rates = $payload['rates'] ?? [];
        if (!\is_array($rates) || empty($rates)) {
            return new JsonResponse(['error' => 'Missing or empty "rates" array'], 422);
        }

        $now = new \DateTimeImmutable();
        $processed = 0;

        foreach ($rates as $rate) {
            if (!isset($rate['date'], $rate['price'])) {
                continue;
            }

            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $rate['date']);
            if (false === $date) {
                continue;
            }

            $price = (int) $rate['price'];
            if ($price < 0) {
                continue;
            }

            $label = $rate['label'] ?? 'dynamic_pricing';

            $existing = $this->priceOverrideRepository->findOneBy([
                'lodging' => $lodging,
                'date' => $date,
            ]);

            if (null !== $existing) {
                $existing->setPrice($price);
                $existing->setLabel($label);
                $existing->setUpdatedAt($now);
            } else {
                $override = new PriceOverride();
                $override->setLodging($lodging);
                $override->setDate($date);
                $override->setPrice($price);
                $override->setLabel($label);
                $override->setCreatedAt($now);
                $override->setUpdatedAt($now);
                $this->em->persist($override);
            }

            ++$processed;
        }

        $this->em->flush();

        $this->logger->info('Pricing webhook processed', [
            'lodging' => $lodgingId,
            'rates_processed' => $processed,
        ]);

        return new JsonResponse([
            'status' => 'ok',
            'processed' => $processed,
        ]);
    }
}
