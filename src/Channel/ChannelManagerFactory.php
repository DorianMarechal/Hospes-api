<?php

namespace App\Channel;

use App\Enum\Channel;

class ChannelManagerFactory
{
    public function __construct(
        private AirbnbChannel $airbnbChannel,
        private BookingComChannel $bookingComChannel,
    ) {
    }

    public function get(Channel $channel): ChannelManagerInterface
    {
        return match ($channel) {
            Channel::AIRBNB => $this->airbnbChannel,
            Channel::BOOKING_COM => $this->bookingComChannel,
        };
    }
}
