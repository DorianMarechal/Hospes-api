<?php

namespace App\Service;

class LegalIdentifierValidator
{
    private const PATTERNS = [
        'FR' => [
            'siret' => '/^\d{14}$/',
            'siren' => '/^\d{9}$/',
            'vat' => '/^FR\d{2}\d{9}$/',
        ],
        'DE' => [
            'handelsregister' => '/^\d{1,10}$/',
            'vat' => '/^DE\d{9}$/',
        ],
        'GB' => [
            'companyhouse' => '/^\d{8}$/',
            'utr' => '/^\d{10}$/',
            'vat' => '/^GB\d{9}$|^GB\d{12}$/',
        ],
        'ES' => [
            'cif' => '/^[A-Z]\d{7}[0-9A-Z]$|^\d{8}[A-Z]$/',
            'nif' => '/^\d{8}[A-Z]$/',
            'vat' => '/^ES[A-Z0-9]\d{7}[A-Z0-9]$/',
        ],
        'IT' => [
            'piva' => '/^\d{11}$/',
            'vat' => '/^IT\d{11}$/',
        ],
        'PT' => [
            'nif' => '/^\d{9}$/',
            'vat' => '/^PT\d{9}$/',
        ],
        'BE' => [
            'company_number' => '/^\d{10}$/',
            'vat' => '/^BE\d{10}$/',
        ],
        'NL' => [
            'kvk' => '/^\d{8}$/',
            'vat' => '/^NL\d{9}B\d{2}$/',
        ],
        'CH' => [
            'uid' => '/^CHE\d{9}(MWST|TVA|IVA)?$/',
            'vat' => '/^CHE\d{9}(MWST|TVA|IVA)$/',
        ],
        'AT' => [
            'uid' => '/^U\d{8}$/',
            'vat' => '/^ATU\d{8}$/',
        ],
        'LU' => [
            'rcs' => '/^[A-Z0-9]{1,13}$/',
            'vat' => '/^LU\d{8}$/',
        ],
        'IE' => [
            'company_house' => '/^\d{6}$/',
            'vat' => '/^IE\d{9}$|^IE\d{7}[A-Z]{1,2}$/',
        ],
    ];

    private const FORMATS = [
        'FR' => [
            'siret' => '14 digits (e.g. 12345678901234)',
            'siren' => '9 digits (e.g. 123456789)',
            'vat' => 'FR + 11 digits (e.g. FR12345678901)',
        ],
        'DE' => [
            'handelsregister' => '1-10 digits (e.g. 123456)',
            'vat' => 'DE + 9 digits (e.g. DE123456789)',
        ],
        'GB' => [
            'companyhouse' => '8 digits (e.g. 12345678)',
            'utr' => '10 digits (e.g. 1234567890)',
            'vat' => 'GB + 9 or 12 digits (e.g. GB123456789)',
        ],
        'ES' => [
            'cif' => 'Letter + 7 digits + alphanumeric (e.g. A12345678)',
            'nif' => '8 digits + letter (e.g. 12345678A)',
            'vat' => 'ES + alphanumeric + 7 digits + alphanumeric (e.g. ESA12345678)',
        ],
        'IT' => [
            'piva' => '11 digits (e.g. 12345678901)',
            'vat' => 'IT + 11 digits (e.g. IT12345678901)',
        ],
        'PT' => [
            'nif' => '9 digits (e.g. 123456789)',
            'vat' => 'PT + 9 digits (e.g. PT123456789)',
        ],
        'BE' => [
            'company_number' => '10 digits (e.g. 1234567890)',
            'vat' => 'BE + 10 digits (e.g. BE1234567890)',
        ],
        'NL' => [
            'kvk' => '8 digits (e.g. 12345678)',
            'vat' => 'NL + 9 digits + B + 2 digits (e.g. NL123456789B12)',
        ],
        'CH' => [
            'uid' => 'CHE + 9 digits (e.g. CHE123456789)',
            'vat' => 'CHE + 9 digits + MWST/TVA/IVA (e.g. CHE123456789MWST)',
        ],
        'AT' => [
            'uid' => 'U + 8 digits (e.g. U12345678)',
            'vat' => 'ATU + 8 digits (e.g. ATU12345678)',
        ],
        'LU' => [
            'rcs' => '1-13 alphanumeric characters (e.g. A123)',
            'vat' => 'LU + 8 digits (e.g. LU12345678)',
        ],
        'IE' => [
            'company_house' => '6 digits (e.g. 123456)',
            'vat' => 'IE + 9 digits or 7 digits + letters (e.g. IE123456789)',
        ],
    ];

    public function validate(string $type, string $value, string $country): bool
    {
        $patterns = self::PATTERNS[$country][$type] ?? null;

        if (null === $patterns) {
            throw new \InvalidArgumentException("Unsupported identifier type '$type' for country '$country'");
        }

        return (bool) preg_match($patterns, $value);
    }

    public function getExpectedFormat(string $type, string $country): ?string
    {
        return self::FORMATS[$country][$type] ?? null;
    }
}
