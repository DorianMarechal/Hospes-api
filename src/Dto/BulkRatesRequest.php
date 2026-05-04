<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class BulkRatesRequest
{
    #[Assert\NotBlank]
    public ?string $startDate = null;

    #[Assert\NotBlank]
    public ?string $endDate = null;

    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    public ?int $price = null;

    #[Assert\Length(max: 100)]
    public ?string $label = null;
}
