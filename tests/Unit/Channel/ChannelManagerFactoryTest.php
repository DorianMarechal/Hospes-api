<?php

namespace App\Tests\Unit\Channel;

use App\Channel\AirbnbChannel;
use App\Channel\BookingComChannel;
use App\Channel\ChannelManagerFactory;
use App\Enum\Channel;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ChannelManagerFactoryTest extends TestCase
{
    private ChannelManagerFactory $factory;
    private AirbnbChannel $airbnbChannel;
    private BookingComChannel $bookingComChannel;

    protected function setUp(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->airbnbChannel = new AirbnbChannel($logger);
        $this->bookingComChannel = new BookingComChannel($logger);
        $this->factory = new ChannelManagerFactory($this->airbnbChannel, $this->bookingComChannel);
    }

    public function testGetAirbnbReturnsAirbnbChannelInstance(): void
    {
        $result = $this->factory->get(Channel::AIRBNB);

        $this->assertInstanceOf(AirbnbChannel::class, $result);
        $this->assertSame($this->airbnbChannel, $result);
    }

    public function testGetBookingComReturnsBookingComChannelInstance(): void
    {
        $result = $this->factory->get(Channel::BOOKING_COM);

        $this->assertInstanceOf(BookingComChannel::class, $result);
        $this->assertSame($this->bookingComChannel, $result);
    }
}
