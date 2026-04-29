<?php

namespace App\Tests\Unit\Service;

use App\Service\BookingReferenceGenerator;
use PHPUnit\Framework\TestCase;

class BookingReferenceGeneratorTest extends TestCase
{
    private BookingReferenceGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new BookingReferenceGenerator();
    }

    public function testGenerateReturnsCorrectFormat(): void
    {
        $reference = $this->generator->generate();

        $this->assertMatchesRegularExpression('/^HOS-[A-F0-9]{8}-\d{2}$/', $reference);
    }

    public function testGenerateEndsWithCurrentYear(): void
    {
        $reference = $this->generator->generate();
        $expectedSuffix = date('y');

        $this->assertStringEndsWith('-'.$expectedSuffix, $reference);
    }

    public function testGenerateReturnsUniqueValues(): void
    {
        $references = [];
        for ($i = 0; $i < 100; ++$i) {
            $references[] = $this->generator->generate();
        }

        $this->assertCount(100, array_unique($references));
    }

    public function testGenerateHasCorrectLength(): void
    {
        $reference = $this->generator->generate();

        // HOS- (4) + 8 hex chars + - (1) + 2 year digits = 15
        $this->assertSame(15, \strlen($reference));
    }
}
