<?php

namespace App\Tests\Unit\Service;

use App\Service\LegalIdentifierValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LegalIdentifierValidatorTest extends TestCase
{
    private LegalIdentifierValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new LegalIdentifierValidator();
    }

    #[DataProvider('validIdentifiersProvider')]
    public function test_validate_accepts_valid_identifiers(string $type, string $value, string $country): void
    {
        $this->assertTrue($this->validator->validate($type, $value, $country));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function validIdentifiersProvider(): iterable
    {
        yield 'FR siret' => ['siret', '12345678901234', 'FR'];
        yield 'FR siren' => ['siren', '123456789', 'FR'];
        yield 'FR vat' => ['vat', 'FR12345678901', 'FR'];
        yield 'DE handelsregister' => ['handelsregister', '123456', 'DE'];
        yield 'DE vat' => ['vat', 'DE123456789', 'DE'];
        yield 'GB companyhouse' => ['companyhouse', '12345678', 'GB'];
        yield 'GB utr' => ['utr', '1234567890', 'GB'];
        yield 'GB vat 9' => ['vat', 'GB123456789', 'GB'];
        yield 'GB vat 12' => ['vat', 'GB123456789012', 'GB'];
        yield 'ES cif letter' => ['cif', 'A1234567B', 'ES'];
        yield 'ES nif' => ['nif', '12345678A', 'ES'];
        yield 'ES vat' => ['vat', 'ESA12345678', 'ES'];
        yield 'IT piva' => ['piva', '12345678901', 'IT'];
        yield 'IT vat' => ['vat', 'IT12345678901', 'IT'];
        yield 'PT nif' => ['nif', '123456789', 'PT'];
        yield 'PT vat' => ['vat', 'PT123456789', 'PT'];
        yield 'BE company_number' => ['company_number', '1234567890', 'BE'];
        yield 'BE vat' => ['vat', 'BE1234567890', 'BE'];
        yield 'NL kvk' => ['kvk', '12345678', 'NL'];
        yield 'NL vat' => ['vat', 'NL123456789B12', 'NL'];
        yield 'CH uid' => ['uid', 'CHE123456789', 'CH'];
        yield 'CH vat MWST' => ['vat', 'CHE123456789MWST', 'CH'];
        yield 'CH vat TVA' => ['vat', 'CHE123456789TVA', 'CH'];
        yield 'AT uid' => ['uid', 'U12345678', 'AT'];
        yield 'AT vat' => ['vat', 'ATU12345678', 'AT'];
        yield 'LU rcs' => ['rcs', 'A123', 'LU'];
        yield 'LU vat' => ['vat', 'LU12345678', 'LU'];
        yield 'IE company_house' => ['company_house', '123456', 'IE'];
        yield 'IE vat 9' => ['vat', 'IE123456789', 'IE'];
    }

    #[DataProvider('invalidIdentifiersProvider')]
    public function test_validate_rejects_invalid_identifiers(string $type, string $value, string $country): void
    {
        $this->assertFalse($this->validator->validate($type, $value, $country));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function invalidIdentifiersProvider(): iterable
    {
        yield 'FR siret too short' => ['siret', '1234567890', 'FR'];
        yield 'FR siret with letters' => ['siret', '1234567890ABCD', 'FR'];
        yield 'FR siren too long' => ['siren', '1234567890', 'FR'];
        yield 'DE vat wrong prefix' => ['vat', 'FR123456789', 'DE'];
        yield 'GB companyhouse too short' => ['companyhouse', '1234', 'GB'];
        yield 'IT piva too short' => ['piva', '123456', 'IT'];
        yield 'NL vat wrong format' => ['vat', 'NL123456789', 'NL'];
        yield 'AT uid missing U' => ['uid', '12345678', 'AT'];
    }

    public function test_validate_throws_for_unsupported_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->validator->validate('unknown_type', '123', 'FR');
    }

    public function test_validate_throws_for_unsupported_country(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->validator->validate('siret', '12345678901234', 'US');
    }

    public function test_get_expected_format_returns_format(): void
    {
        $format = $this->validator->getExpectedFormat('siret', 'FR');

        $this->assertSame('14 digits (e.g. 12345678901234)', $format);
    }

    public function test_get_expected_format_returns_null_for_unknown(): void
    {
        $format = $this->validator->getExpectedFormat('unknown', 'FR');

        $this->assertNull($format);
    }
}
