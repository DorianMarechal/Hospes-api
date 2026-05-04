<?php

namespace App\Service;

use App\Entity\BookingModificationRequest;
use App\Entity\Message;
use App\Entity\Notification;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

class MercurePublisher
{
    public function __construct(
        private HubInterface $hub,
        private SerializerInterface $serializer,
    ) {
    }

    public function publishNotification(Notification $notification): void
    {
        $userId = $notification->getUser()?->getId();
        if (null === $userId) {
            return;
        }

        $this->publish(
            $this->userTopic($userId, 'notifications'),
            $notification,
            ['notification:read'],
        );
    }

    public function publishMessage(Message $message): void
    {
        $conversation = $message->getConversation();
        if (null === $conversation) {
            return;
        }

        $topic = '/conversations/'.$conversation->getId().'/messages';

        $this->publish($topic, $message, ['message:read']);
    }

    public function publishModificationRequest(BookingModificationRequest $request, string $event): void
    {
        $booking = $request->getBooking();
        if (null === $booking) {
            return;
        }

        $customer = $booking->getCustomer();
        $host = $booking->getLodging()?->getHost()?->getUser();

        $data = $this->serializer->serialize([
            'event' => $event,
            'modificationRequest' => $request,
        ], 'json', ['groups' => ['modification_request:read']]);

        if (null !== $customer?->getId()) {
            $this->hub->publish(new Update(
                $this->userTopic($customer->getId(), 'booking-modifications'),
                $data,
                true,
            ));
        }

        if (null !== $host?->getId()) {
            $this->hub->publish(new Update(
                $this->userTopic($host->getId(), 'booking-modifications'),
                $data,
                true,
            ));
        }
    }

    /**
     * @param string[] $groups
     */
    private function publish(string $topic, object $entity, array $groups): void
    {
        $data = $this->serializer->serialize($entity, 'json', ['groups' => $groups]);
        $this->hub->publish(new Update($topic, $data, true));
    }

    private function userTopic(Uuid $userId, string $channel): string
    {
        return '/users/'.$userId.'/'.$channel;
    }
}
