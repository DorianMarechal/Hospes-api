<?php

namespace App\Service;

use App\Dto\NightPrice;
use App\Dto\QuoteResult;
use App\Entity\Lodging;
use App\Entity\PriceOverride;
use App\Entity\Season;

class PriceCalculator
{
    /**
     * @param Season[]        $seasons
     * @param PriceOverride[] $priceOverrides
     */
    public function calculate(
        Lodging $lodging,
        \DateTimeImmutable $checkin,
        \DateTimeImmutable $checkout,
        int $guestsCount,
        array $seasons,
        array $priceOverrides,
    ): QuoteResult {
        $nights = [];
        $current = $checkin;

        while ($current < $checkout) {
            $nights[] = $this->resolveNightPrice($lodging, $current, $seasons, $priceOverrides);
            $current = $current->modify('+1 day');
        }

        $nightsTotal = array_sum(array_map(fn (NightPrice $n) => $n->price, $nights));
        $numberOfNights = count($nights);
        $cleaningFee = $lodging->getCleaningFee() ?? 0;
        $touristTaxTotal = ($lodging->getTouristTaxPerPerson() ?? 0) * $guestsCount * $numberOfNights;
        $depositAmount = $lodging->getDepositAmount() ?? 0;
        $totalPrice = $nightsTotal + $cleaningFee + $touristTaxTotal;

        return new QuoteResult(
            nights: $nights,
            nightsTotal: $nightsTotal,
            cleaningFee: $cleaningFee,
            touristTaxTotal: $touristTaxTotal,
            depositAmount: $depositAmount,
            totalPrice: $totalPrice,
        );
    }

    /**
     * @param Season[]        $seasons
     * @param PriceOverride[] $priceOverrides
     */
    private function resolveNightPrice(
        Lodging $lodging,
        \DateTimeImmutable $date,
        array $seasons,
        array $priceOverrides,
    ): NightPrice {
        $isWeekend = \in_array((int) $date->format('N'), [5, 6], true);
        $dayType = $isWeekend ? 'week-end' : 'semaine';

        foreach ($priceOverrides as $override) {
            if ($override->getDate()->format('Y-m-d') === $date->format('Y-m-d')) {
                $label = $override->getLabel() ?? 'Override';

                return new NightPrice($date, $override->getPrice(), "$label / $dayType");
            }
        }

        foreach ($seasons as $season) {
            if ($date >= $season->getStartDate() && $date < $season->getEndDate()) {
                $price = $isWeekend ? $season->getPriceWeekend() : $season->getPriceWeek();

                return new NightPrice($date, $price, $season->getName()." / $dayType");
            }
        }

        $price = $isWeekend ? $lodging->getBasePriceWeekend() : $lodging->getBasePriceWeek();

        return new NightPrice($date, $price, "Tarif de base / $dayType");
    }
}
