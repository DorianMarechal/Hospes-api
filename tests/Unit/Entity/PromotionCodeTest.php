<?php

namespace App\Tests\Unit\Entity;

use App\Entity\PromotionCode;
use App\Enum\PromotionType;
use PHPUnit\Framework\TestCase;

class PromotionCodeTest extends TestCase
{
    // --- isUsable ---

    public function testIsUsableReturnsTrueWhenNoRestrictions(): void
    {
        $promo = new PromotionCode();
        $promo->setType(PromotionType::FIXED);
        $promo->setValue(500);
        // maxUses, validFrom, validTo all null

        $this->assertTrue($promo->isUsable());
    }

    public function testIsUsableReturnsFalseWhenMaxUsesReached(): void
    {
        $promo = new PromotionCode();
        $promo->setType(PromotionType::FIXED);
        $promo->setValue(500);
        $promo->setMaxUses(3);
        $promo->incrementUsesCount();
        $promo->incrementUsesCount();
        $promo->incrementUsesCount();

        $this->assertFalse($promo->isUsable());
    }

    public function testIsUsableReturnsFalseWhenMaxUsesExceeded(): void
    {
        $promo = new PromotionCode();
        $promo->setType(PromotionType::FIXED);
        $promo->setValue(500);
        $promo->setMaxUses(2);
        $promo->incrementUsesCount();
        $promo->incrementUsesCount();
        $promo->incrementUsesCount();

        $this->assertFalse($promo->isUsable());
    }

    public function testIsUsableReturnsTrueWhenUsesCountBelowMaxUses(): void
    {
        $promo = new PromotionCode();
        $promo->setType(PromotionType::FIXED);
        $promo->setValue(500);
        $promo->setMaxUses(5);
        $promo->incrementUsesCount();

        $this->assertTrue($promo->isUsable());
    }

    public function testIsUsableReturnsFalseWhenValidToIsInThePast(): void
    {
        $promo = new PromotionCode();
        $promo->setType(PromotionType::FIXED);
        $promo->setValue(500);
        $promo->setValidTo(new \DateTimeImmutable('-1 day'));

        $this->assertFalse($promo->isUsable());
    }

    public function testIsUsableReturnsFalseWhenValidFromIsInTheFuture(): void
    {
        $promo = new PromotionCode();
        $promo->setType(PromotionType::FIXED);
        $promo->setValue(500);
        $promo->setValidFrom(new \DateTimeImmutable('+1 day'));

        $this->assertFalse($promo->isUsable());
    }

    public function testIsUsableReturnsTrueWhenWithinValidDateRange(): void
    {
        $promo = new PromotionCode();
        $promo->setType(PromotionType::FIXED);
        $promo->setValue(500);
        $promo->setValidFrom(new \DateTimeImmutable('-1 day'));
        $promo->setValidTo(new \DateTimeImmutable('+1 day'));

        $this->assertTrue($promo->isUsable());
    }

    // --- calculateDiscount ---

    public function testCalculateDiscountPercentType(): void
    {
        $promo = new PromotionCode();
        $promo->setType(PromotionType::PERCENT);
        $promo->setValue(15); // 15%

        // 10000 * 15% = 1500
        $this->assertSame(1500, $promo->calculateDiscount(10000));
    }

    public function testCalculateDiscountFixedTypeWhenFixedIsBelowTotal(): void
    {
        $promo = new PromotionCode();
        $promo->setType(PromotionType::FIXED);
        $promo->setValue(2000);

        // min(2000, 10000) = 2000
        $this->assertSame(2000, $promo->calculateDiscount(10000));
    }

    public function testCalculateDiscountFixedTypeCapsAtTotalPrice(): void
    {
        $promo = new PromotionCode();
        $promo->setType(PromotionType::FIXED);
        $promo->setValue(20000);

        // min(20000, 5000) = 5000 — discount cannot exceed the total
        $this->assertSame(5000, $promo->calculateDiscount(5000));
    }

    public function testCalculateDiscountReturnsZeroWhenTypeIsNull(): void
    {
        $promo = new PromotionCode();
        $promo->setValue(500);
        // type not set

        $this->assertSame(0, $promo->calculateDiscount(10000));
    }

    public function testCalculateDiscountReturnsZeroWhenValueIsNull(): void
    {
        $promo = new PromotionCode();
        $promo->setType(PromotionType::PERCENT);
        // value not set

        $this->assertSame(0, $promo->calculateDiscount(10000));
    }

    public function testCalculateDiscountPercentRoundsCorrectly(): void
    {
        $promo = new PromotionCode();
        $promo->setType(PromotionType::PERCENT);
        $promo->setValue(33); // 33%

        // 1000 * 33 / 100 = 330.0 — exact
        $this->assertSame(330, $promo->calculateDiscount(1000));
    }

    // --- incrementUsesCount ---

    public function testIncrementUsesCountIncrementsFromZero(): void
    {
        $promo = new PromotionCode();

        $this->assertSame(0, $promo->getUsesCount());

        $promo->incrementUsesCount();

        $this->assertSame(1, $promo->getUsesCount());
    }

    public function testIncrementUsesCountIncrementsMultipleTimes(): void
    {
        $promo = new PromotionCode();

        $promo->incrementUsesCount();
        $promo->incrementUsesCount();
        $promo->incrementUsesCount();

        $this->assertSame(3, $promo->getUsesCount());
    }

    public function testIncrementUsesCountReturnsFluentInterface(): void
    {
        $promo = new PromotionCode();

        $result = $promo->incrementUsesCount();

        $this->assertSame($promo, $result);
    }

    // --- setCode ---

    public function testSetCodeUppercasesLowercaseInput(): void
    {
        $promo = new PromotionCode();
        $promo->setCode('summer20');

        $this->assertSame('SUMMER20', $promo->getCode());
    }

    public function testSetCodeUppercasesMixedCaseInput(): void
    {
        $promo = new PromotionCode();
        $promo->setCode('SuMmEr20');

        $this->assertSame('SUMMER20', $promo->getCode());
    }

    public function testSetCodeKeepsAlreadyUppercaseInput(): void
    {
        $promo = new PromotionCode();
        $promo->setCode('PROMO2026');

        $this->assertSame('PROMO2026', $promo->getCode());
    }

    public function testSetCodeReturnsFluentInterface(): void
    {
        $promo = new PromotionCode();

        $result = $promo->setCode('test');

        $this->assertSame($promo, $result);
    }
}
