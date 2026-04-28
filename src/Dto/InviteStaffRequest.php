<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class InviteStaffRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    /**
     * @var string[]
     */
    #[Assert\NotBlank]
    #[Assert\All([
        new Assert\Choice(choices: [
            'can_view_bookings',
            'can_edit_bookings',
            'can_block_dates',
            'can_view_revenue',
            'can_manage_lodgings',
        ]),
    ])]
    public array $permissions = [];

    /**
     * @var string[]
     */
    public array $lodgingIds = [];
}
