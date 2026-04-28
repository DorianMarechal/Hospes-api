<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\QuoteProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Quote',
    operations: [
        new Post(
            uriTemplate: '/lodgings/{lodgingId}/quote',
            output: QuoteResult::class,
            processor: QuoteProcessor::class,
        ),
    ],
)]
class QuoteRequest
{
    #[Assert\NotNull]
    #[Assert\Type(\DateTimeImmutable::class)]
    public ?\DateTimeImmutable $checkin = null;

    #[Assert\NotNull]
    #[Assert\Type(\DateTimeImmutable::class)]
    public ?\DateTimeImmutable $checkout = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $guestsCount = null;
}
