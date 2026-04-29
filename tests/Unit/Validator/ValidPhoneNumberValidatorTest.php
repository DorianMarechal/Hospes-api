<?php

namespace App\Tests\Unit\Validator;

use App\Validator\ValidPhoneNumber;
use App\Validator\ValidPhoneNumberValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ValidPhoneNumberValidator>
 */
class ValidPhoneNumberValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ValidPhoneNumberValidator();
    }

    public function test_null_value_is_valid(): void
    {
        $this->validator->validate(null, new ValidPhoneNumber());

        $this->assertNoViolation();
    }

    public function test_valid_french_mobile(): void
    {
        $this->validator->validate('+33612345678', new ValidPhoneNumber());

        $this->assertNoViolation();
    }

    public function test_valid_german_mobile(): void
    {
        $this->validator->validate('+4915112345678', new ValidPhoneNumber());

        $this->assertNoViolation();
    }

    public function test_invalid_number_without_plus(): void
    {
        $this->validator->validate('0612345678', new ValidPhoneNumber());

        $this->buildViolation('Phone number must start with + followed by country code (e.g. +33612345678)')
            ->assertRaised();
    }

    public function test_landline_is_rejected(): void
    {
        // French landline
        $this->validator->validate('+33112345678', new ValidPhoneNumber());

        $violations = $this->context->getViolations();
        $this->assertGreaterThan(0, $violations->count());
    }

    public function test_random_string_is_rejected(): void
    {
        $this->validator->validate('not-a-number', new ValidPhoneNumber());

        $violations = $this->context->getViolations();
        $this->assertGreaterThan(0, $violations->count());
    }
}
