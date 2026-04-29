<?php

namespace App\Validator;

use App\Entity\Season;
use App\Repository\SeasonRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NoSeasonOverlapValidator extends ConstraintValidator
{
    public function __construct(
        private SeasonRepository $seasonRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoSeasonOverlap) {
            throw new UnexpectedTypeException($constraint, NoSeasonOverlap::class);
        }

        if (!$value instanceof Season) {
            throw new UnexpectedValueException($value, Season::class);
        }

        $lodging = $value->getLodging();
        $startDate = $value->getStartDate();
        $endDate = $value->getEndDate();

        if (null === $lodging || null === $startDate || null === $endDate) {
            return;
        }

        $existingSeasons = $this->seasonRepository->findBy(['lodging' => $lodging]);

        foreach ($existingSeasons as $existing) {
            if (null !== $value->getId() && $existing->getId()?->equals($value->getId())) {
                continue;
            }

            if ($startDate < $existing->getEndDate() && $endDate > $existing->getStartDate()) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ name }}', $existing->getName() ?? '')
                    ->setParameter('{{ start }}', $existing->getStartDate()->format('Y-m-d'))
                    ->setParameter('{{ end }}', $existing->getEndDate()->format('Y-m-d'))
                    ->addViolation();

                return;
            }
        }
    }
}
