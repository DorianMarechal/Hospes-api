<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CreatePaymentRequest;
use App\Entity\Payment;
use App\Enum\BookingStatus;
use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        assert($data instanceof CreatePaymentRequest);

        $booking = $this->bookingRepository->find($uriVariables['bookingId']);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        if (!$booking->getCustomer()?->getId()?->equals($user->getId())) {
            throw new HttpException(403, 'You can only pay for your own bookings');
        }

        if (BookingStatus::CONFIRMED !== $booking->getStatus()) {
            throw new HttpException(422, 'Only confirmed bookings can be paid');
        }

        $hostProfile = $booking->getLodging()?->getHost();
        if (null === $hostProfile || null === $hostProfile->getPaymentProviderAccountId()) {
            throw new HttpException(422, 'The host has not configured a payment provider');
        }

        $now = new \DateTimeImmutable();

        $payment = new Payment();
        $payment->setBooking($booking);
        $payment->setAmount($data->amount);
        $payment->setType(PaymentType::BOOKING);
        $payment->setMethod($data->method);
        $payment->setStatus(PaymentStatus::PENDING);
        $payment->setProvider($hostProfile->getPaymentProvider()?->value);
        $payment->setProviderTransactionId($data->providerTransactionId);
        $payment->setCreatedAt($now);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }
}
