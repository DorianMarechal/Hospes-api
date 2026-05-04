<?php

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Entity\BookingModificationRequest;
use App\Entity\Conversation;
use App\Entity\HostProfile;
use App\Entity\Lodging;
use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\User;
use App\Service\MercurePublisher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

class MercurePublisherTest extends TestCase
{
    private HubInterface $hub;
    private SerializerInterface $serializer;
    private MercurePublisher $publisher;

    protected function setUp(): void
    {
        $this->hub = $this->createMock(HubInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->publisher = new MercurePublisher($this->hub, $this->serializer);
    }

    private function setId(object $entity, ?Uuid $id = null): Uuid
    {
        $uuid = $id ?? Uuid::v7();
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $uuid);

        return $uuid;
    }

    // --- publishNotification ---

    public function testPublishNotificationWithValidUserPublishesUpdate(): void
    {
        $user = new User();
        $userId = $this->setId($user);

        $notification = new Notification();
        $notification->setUser($user);

        $this->serializer
            ->expects($this->once())
            ->method('serialize')
            ->with($notification, 'json', ['groups' => ['notification:read']])
            ->willReturn('{"type":"booking_confirmed"}');

        $capturedUpdate = null;
        $this->hub
            ->expects($this->once())
            ->method('publish')
            ->willReturnCallback(function (Update $update) use (&$capturedUpdate) {
                $capturedUpdate = $update;

                return 'id';
            });

        $this->publisher->publishNotification($notification);

        $this->assertNotNull($capturedUpdate);
        $this->assertSame('/users/'.$userId.'/notifications', $capturedUpdate->getTopics()[0]);
        $this->assertSame('{"type":"booking_confirmed"}', $capturedUpdate->getData());
        $this->assertTrue($capturedUpdate->isPrivate());
    }

    public function testPublishNotificationWithNullUserDoesNotPublish(): void
    {
        $notification = new Notification();
        // user is null — getId() on null triggers the null coalescing guard

        $this->hub->expects($this->never())->method('publish');
        $this->serializer->expects($this->never())->method('serialize');

        $this->publisher->publishNotification($notification);
    }

    public function testPublishNotificationTopicUrlFormat(): void
    {
        $user = new User();
        $userId = $this->setId($user);

        $notification = new Notification();
        $notification->setUser($user);

        $this->serializer->method('serialize')->willReturn('{}');

        $capturedUpdate = null;
        $this->hub->method('publish')->willReturnCallback(function (Update $update) use (&$capturedUpdate) {
            $capturedUpdate = $update;

            return 'id';
        });

        $this->publisher->publishNotification($notification);

        $expectedTopic = '/users/'.$userId->toRfc4122().'/notifications';
        $this->assertSame($expectedTopic, $capturedUpdate->getTopics()[0]);
    }

    // --- publishMessage ---

    public function testPublishMessageWithNullConversationDoesNotPublish(): void
    {
        $message = new Message();
        // conversation is null by default

        $this->hub->expects($this->never())->method('publish');
        $this->serializer->expects($this->never())->method('serialize');

        $this->publisher->publishMessage($message);
    }

    public function testPublishMessageWithValidConversationPublishesUpdate(): void
    {
        $conversation = new Conversation();
        $conversationId = $this->setId($conversation);

        $message = new Message();
        $message->setConversation($conversation);

        $this->serializer
            ->expects($this->once())
            ->method('serialize')
            ->with($message, 'json', ['groups' => ['message:read']])
            ->willReturn('{"content":"hello"}');

        $capturedUpdate = null;
        $this->hub
            ->expects($this->once())
            ->method('publish')
            ->willReturnCallback(function (Update $update) use (&$capturedUpdate) {
                $capturedUpdate = $update;

                return 'id';
            });

        $this->publisher->publishMessage($message);

        $this->assertNotNull($capturedUpdate);
        $this->assertSame('/conversations/'.$conversationId.'/messages', $capturedUpdate->getTopics()[0]);
        $this->assertTrue($capturedUpdate->isPrivate());
    }

    public function testPublishMessageTopicUrlFormat(): void
    {
        $conversation = new Conversation();
        $conversationId = $this->setId($conversation);

        $message = new Message();
        $message->setConversation($conversation);

        $this->serializer->method('serialize')->willReturn('{}');

        $capturedUpdate = null;
        $this->hub->method('publish')->willReturnCallback(function (Update $update) use (&$capturedUpdate) {
            $capturedUpdate = $update;

            return 'id';
        });

        $this->publisher->publishMessage($message);

        $this->assertSame('/conversations/'.$conversationId->toRfc4122().'/messages', $capturedUpdate->getTopics()[0]);
    }

    // --- publishModificationRequest ---

    public function testPublishModificationRequestWithNullBookingDoesNotPublish(): void
    {
        $request = new BookingModificationRequest();
        // booking is null

        $this->hub->expects($this->never())->method('publish');
        $this->serializer->expects($this->never())->method('serialize');

        $this->publisher->publishModificationRequest($request, 'created');
    }

    public function testPublishModificationRequestPublishesToCustomerAndHost(): void
    {
        $customer = new User();
        $customerId = $this->setId($customer);

        $hostUser = new User();
        $hostUserId = $this->setId($hostUser);

        $hostProfile = new HostProfile();
        $hostProfile->setUser($hostUser);

        $lodging = new Lodging();
        $lodging->setHost($hostProfile);

        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setLodging($lodging);

        $request = new BookingModificationRequest();
        $request->setBooking($booking);

        $this->serializer
            ->expects($this->once())
            ->method('serialize')
            ->willReturn('{"event":"created"}');

        $publishedTopics = [];
        $this->hub
            ->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function (Update $update) use (&$publishedTopics) {
                $publishedTopics[] = $update->getTopics()[0];

                return 'id';
            });

        $this->publisher->publishModificationRequest($request, 'created');

        $this->assertContains('/users/'.$customerId.'/booking-modifications', $publishedTopics);
        $this->assertContains('/users/'.$hostUserId.'/booking-modifications', $publishedTopics);
    }

    public function testPublishModificationRequestPublishesOnlyToCustomerWhenNoHost(): void
    {
        $customer = new User();
        $customerId = $this->setId($customer);

        $lodging = new Lodging();
        // no HostProfile set — getLodging()->getHost() returns null

        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setLodging($lodging);

        $request = new BookingModificationRequest();
        $request->setBooking($booking);

        $this->serializer->method('serialize')->willReturn('{}');

        $publishedTopics = [];
        $this->hub
            ->expects($this->once())
            ->method('publish')
            ->willReturnCallback(function (Update $update) use (&$publishedTopics) {
                $publishedTopics[] = $update->getTopics()[0];

                return 'id';
            });

        $this->publisher->publishModificationRequest($request, 'created');

        $this->assertSame(['/users/'.$customerId.'/booking-modifications'], $publishedTopics);
    }

    public function testPublishModificationRequestDoesNotPublishWhenNoCustomerAndNoHost(): void
    {
        $lodging = new Lodging();

        $booking = new Booking();
        $booking->setLodging($lodging);
        // no customer, no host profile

        $request = new BookingModificationRequest();
        $request->setBooking($booking);

        // serializer is called unconditionally before the per-recipient guards
        $this->serializer->method('serialize')->willReturn('{}');

        // hub->publish must never be called since both customer and host are null
        $this->hub->expects($this->never())->method('publish');

        $this->publisher->publishModificationRequest($request, 'created');
    }
}
