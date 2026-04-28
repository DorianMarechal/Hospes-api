<?php

namespace App\Tests\Unit\Service;

use App\Entity\Lodging;
use App\Entity\PriceOverride;
use App\Entity\Season;
use App\Service\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calculator;
    private Lodging $lodging;

    protected function setUp(): void
    {
        $this->calculator = new PriceCalculator();
        $this->lodging = new Lodging();
        $this->lodging->setBasePriceWeek(10000);
        $this->lodging->setBasePriceWeekend(12000);
        $this->lodging->setCleaningFee(5000);
        $this->lodging->setTouristTaxPerPerson(150);
        $this->lodging->setDepositAmount(30000);
    }

    private function createSeason(
        string $start,
        string $end,
        int $priceWeek,
        int $priceWeekend,
        string $name = 'Haute saison',
    ): Season {
        $season = new Season();
        $season->setStartDate(new \DateTimeImmutable($start));
        $season->setEndDate(new \DateTimeImmutable($end));
        $season->setPriceWeek($priceWeek);
        $season->setPriceWeekend($priceWeekend);
        $season->setName($name);

        return $season;
    }

    private function createPriceOverride(string $date, int $price, ?string $label = null): PriceOverride
    {
        $override = new PriceOverride();
        $override->setDate(new \DateTimeImmutable($date));
        $override->setPrice($price);
        $override->setLabel($label);

        return $override;
    }

    // PC-1 : Tarif de base semaine
    public function testBaseRateWeekday(): void
    {
        // 2026-07-06 = lundi
        $result = $this->calculator->calculate(
            $this->lodging,
            new \DateTimeImmutable('2026-07-06'),
            new \DateTimeImmutable('2026-07-07'),
            2,
            [],
            [],
        );

        $this->assertCount(1, $result->nights);
        $this->assertSame(10000, $result->nights[0]->price);
        $this->assertSame('Tarif de base / semaine', $result->nights[0]->source);
    }

    // PC-2 : Tarif de base week-end (vendredi)
    public function testBaseRateFridayIsWeekend(): void
    {
        // 2026-07-10 = vendredi
        $result = $this->calculator->calculate(
            $this->lodging,
            new \DateTimeImmutable('2026-07-10'),
            new \DateTimeImmutable('2026-07-11'),
            2,
            [],
            [],
        );

        $this->assertSame(12000, $result->nights[0]->price);
        $this->assertSame('Tarif de base / week-end', $result->nights[0]->source);
    }

    // PC-3 : Tarif de base week-end (samedi)
    public function testBaseRateSaturdayIsWeekend(): void
    {
        // 2026-07-11 = samedi
        $result = $this->calculator->calculate(
            $this->lodging,
            new \DateTimeImmutable('2026-07-11'),
            new \DateTimeImmutable('2026-07-12'),
            2,
            [],
            [],
        );

        $this->assertSame(12000, $result->nights[0]->price);
    }

    // PC-4 : Dimanche n'est PAS week-end
    public function testSundayIsNotWeekend(): void
    {
        // 2026-07-12 = dimanche
        $result = $this->calculator->calculate(
            $this->lodging,
            new \DateTimeImmutable('2026-07-12'),
            new \DateTimeImmutable('2026-07-13'),
            2,
            [],
            [],
        );

        $this->assertSame(10000, $result->nights[0]->price);
        $this->assertSame('Tarif de base / semaine', $result->nights[0]->source);
    }

    // PC-5 : Saison surchage le tarif de base
    public function testSeasonOverridesBaseRate(): void
    {
        $season = $this->createSeason('2026-07-01', '2026-07-31', 15000, 18000);

        // 2026-07-06 = lundi, couvert par la saison
        $result = $this->calculator->calculate(
            $this->lodging,
            new \DateTimeImmutable('2026-07-06'),
            new \DateTimeImmutable('2026-07-07'),
            2,
            [$season],
            [],
        );

        $this->assertSame(15000, $result->nights[0]->price);
        $this->assertStringContainsString('Haute saison', $result->nights[0]->source);
    }

    // PC-6 : Saison week-end
    public function testSeasonWeekendRate(): void
    {
        $season = $this->createSeason('2026-07-01', '2026-07-31', 15000, 18000);

        // 2026-07-10 = vendredi
        $result = $this->calculator->calculate(
            $this->lodging,
            new \DateTimeImmutable('2026-07-10'),
            new \DateTimeImmutable('2026-07-11'),
            2,
            [$season],
            [],
        );

        $this->assertSame(18000, $result->nights[0]->price);
        $this->assertStringContainsString('week-end', $result->nights[0]->source);
    }

    // PC-7 : PriceOverride prioritaire sur saison
    public function testPriceOverrideTakesPriorityOverSeason(): void
    {
        $season = $this->createSeason('2026-07-01', '2026-07-31', 15000, 18000);
        $override = $this->createPriceOverride('2026-07-06', 25000, 'Jour férié');

        $result = $this->calculator->calculate(
            $this->lodging,
            new \DateTimeImmutable('2026-07-06'),
            new \DateTimeImmutable('2026-07-07'),
            2,
            [$season],
            [$override],
        );

        $this->assertSame(25000, $result->nights[0]->price);
        $this->assertStringContainsString('Jour férié', $result->nights[0]->source);
    }

    // PC-8 : Séjour chevauchant la frontière d'une saison
    public function testMultiNightSpanningSeasonBoundary(): void
    {
        // Saison du 01 au 15 juillet
        $season = $this->createSeason('2026-07-01', '2026-07-15', 15000, 18000);

        // Séjour du 14 au 16 : nuit 14 en saison, nuit 15 en tarif de base
        // 2026-07-14 = mardi, 2026-07-15 = mercredi
        $result = $this->calculator->calculate(
            $this->lodging,
            new \DateTimeImmutable('2026-07-14'),
            new \DateTimeImmutable('2026-07-16'),
            2,
            [$season],
            [],
        );

        $this->assertCount(2, $result->nights);
        $this->assertSame(15000, $result->nights[0]->price); // en saison
        $this->assertSame(10000, $result->nights[1]->price); // hors saison, tarif de base
    }

    // PC-9 : Calcul complet avec tous les frais
    public function testFullQuoteWithAllFees(): void
    {
        // 3 nuits du lundi au jeudi, 2 hôtes
        $result = $this->calculator->calculate(
            $this->lodging,
            new \DateTimeImmutable('2026-07-06'),
            new \DateTimeImmutable('2026-07-09'),
            2,
            [],
            [],
        );

        $this->assertCount(3, $result->nights);
        $this->assertSame(30000, $result->nightsTotal); // 3 × 10000
        $this->assertSame(5000, $result->cleaningFee);
        $this->assertSame(900, $result->touristTaxTotal); // 150 × 2 × 3
        $this->assertSame(30000, $result->depositAmount);
        $this->assertSame(35900, $result->totalPrice); // 30000 + 5000 + 900
    }

    // PC-10 : Séjour avec nuits semaine + week-end
    public function testMixedWeekdayWeekendPricing(): void
    {
        // Du jeudi 09 au lundi 13 : jeu(semaine), ven(we), sam(we), dim(semaine)
        $result = $this->calculator->calculate(
            $this->lodging,
            new \DateTimeImmutable('2026-07-09'),
            new \DateTimeImmutable('2026-07-13'),
            1,
            [],
            [],
        );

        $this->assertCount(4, $result->nights);
        $this->assertSame(10000, $result->nights[0]->price); // jeudi
        $this->assertSame(12000, $result->nights[1]->price); // vendredi
        $this->assertSame(12000, $result->nights[2]->price); // samedi
        $this->assertSame(10000, $result->nights[3]->price); // dimanche
        $this->assertSame(44000, $result->nightsTotal);
    }

    // PC-11 : Pas de frais optionnels (null)
    public function testNullOptionalFeesDefaultToZero(): void
    {
        $lodging = new Lodging();
        $lodging->setBasePriceWeek(8000);
        $lodging->setBasePriceWeekend(9000);
        $lodging->setCleaningFee(null);
        $lodging->setTouristTaxPerPerson(null);
        $lodging->setDepositAmount(null);

        $result = $this->calculator->calculate(
            $lodging,
            new \DateTimeImmutable('2026-07-06'),
            new \DateTimeImmutable('2026-07-07'),
            2,
            [],
            [],
        );

        $this->assertSame(0, $result->cleaningFee);
        $this->assertSame(0, $result->touristTaxTotal);
        $this->assertSame(0, $result->depositAmount);
        $this->assertSame(8000, $result->totalPrice);
    }

    // PC-12 : Override sans label
    public function testPriceOverrideWithoutLabel(): void
    {
        $override = $this->createPriceOverride('2026-07-06', 20000);

        $result = $this->calculator->calculate(
            $this->lodging,
            new \DateTimeImmutable('2026-07-06'),
            new \DateTimeImmutable('2026-07-07'),
            2,
            [],
            [$override],
        );

        $this->assertSame(20000, $result->nights[0]->price);
        $this->assertStringContainsString('Override', $result->nights[0]->source);
    }
}
