<?php

namespace App\Validator;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidPhoneNumberValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint)
    {
        if($value === null){
            return;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $number = $phoneUtil->parse($value);
            $regionCode = $phoneUtil->getRegionCodeForNumber($number);

            if(!$phoneUtil->isValidNumber($number) || $phoneUtil->getNumberType($number) !== \libphonenumber\PhoneNumberType::MOBILE){
                $example = $phoneUtil->format(
                    $phoneUtil->getExampleNumberForType($regionCode, \libphonenumber\PhoneNumberType::MOBILE),
                    \libphonenumber\PhoneNumberFormat::INTERNATIONAL
                );

                $this->context->buildViolation("Invalid phone number. Expected format for $regionCode: $example")->addViolation();
            }
        } catch (NumberParseException) {
            $this->context->buildViolation('Phone number must start with + followed by country code (e.g. +33612345678)')->addViolation();
        }
    }
}