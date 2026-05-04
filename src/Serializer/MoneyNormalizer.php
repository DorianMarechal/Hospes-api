<?php

namespace App\Serializer;

use App\Entity\Booking;
use App\Entity\BookingNight;
use App\Entity\Deposit;
use App\Entity\Payment;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsDecorator(decorates: 'api_platform.jsonld.normalizer.item')]
class MoneyNormalizer implements NormalizerInterface
{
    private const string DEFAULT_CURRENCY = 'EUR';

    private const array MONEY_FIELDS = [
        Booking::class => ['nightsTotal', 'cleaningFee', 'touristTaxTotal', 'depositAmount', 'totalPrice'],
        BookingNight::class => ['price'],
        Payment::class => ['amount'],
        Deposit::class => ['amount', 'retainedAmount'],
    ];

    public function __construct(
        private NormalizerInterface $inner,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $result = $this->inner->normalize($data, $format, $context);

        if (!\is_array($result) || !\is_object($data)) {
            return $result;
        }

        $class = $data::class;
        if (!isset(self::MONEY_FIELDS[$class])) {
            return $result;
        }

        $currency = method_exists($data, 'getCurrency') ? $data->getCurrency() : self::DEFAULT_CURRENCY;

        foreach (self::MONEY_FIELDS[$class] as $field) {
            if (\array_key_exists($field, $result) && \is_int($result[$field])) {
                $result[$field] = [
                    'amount' => $result[$field],
                    'currency' => $currency,
                ];
            }
        }

        return $result;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->inner->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->inner->getSupportedTypes($format);
    }
}
