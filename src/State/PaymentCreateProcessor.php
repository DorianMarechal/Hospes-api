<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CreatePaymentRequest;
use App\Entity\Payment;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use App\Payment\PaymentGatewayFactory;
use App\Repository\BookingRepository;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private PaymentRepository $paymentRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private PaymentGatewayFactory $gatewayFactory,
        private RequestStack $requestStack,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof CreatePaymentRequest) {
            throw new \InvalidArgumentException('Expected '.CreatePaymentRequest::class);
        }

        $booking = $this->bookingRepository->find($uriVariables['bookingId']);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new HttpException(401, 'Authentication required');
        }

        if (!$booking->getCustomer()?->getId()?->equals($user->getId())) {
            throw new HttpException(403, 'You can only pay for your own bookings');
        }

        if (BookingStatus::CONFIRMED !== $booking->getStatus()) {
            throw new HttpException(422, 'Only confirmed bookings can be paid');
        }

        $idempotencyKey = $this->requestStack->getCurrentRequest()?->headers->get('Idempotency-Key');
        if (null !== $idempotencyKey) {
            $existing = $this->paymentRepository->findOneBy(['idempotencyKey' => $idempotencyKey]);
            if (null !== $existing) {
                return $existing;
            }
        }

        $amount = $booking->getTotalPrice();
        if (null === $amount || $amount <= 0) {
            throw new HttpException(422, 'Booking has no valid total price');
        }

        $hostProfile = $booking->getLodging()?->getHost();
        if (null === $hostProfile || null === $hostProfile->getPaymentProviderAccountId() || null === $hostProfile->getPaymentProvider()) {
            throw new HttpException(422, 'The host has not configured a payment provider');
        }

        $currency = strtolower($booking->getCurrency());
        $gateway = $this->gatewayFactory->get($hostProfile->getPaymentProvider());
        $result = $gateway->createPayment(
            $amount,
            $currency,
            $hostProfile->getPaymentProviderAccountId(),
            'Booking '.$booking->getReference(),
        );

        $now = new \DateTimeImmutable();

        $payment = new Payment();
        $payment->setBooking($booking);
        $payment->setAmount($amount);
        $payment->setCurrency($booking->getCurrency());
        $payment->setType(PaymentType::BOOKING);
        $payment->setMethod($data->method);
        $payment->setStatus(PaymentStatus::PENDING);
        $payment->setProvider($hostProfile->getPaymentProvider()->value);
        $payment->setProviderTransactionId($result['transactionId']);
        $payment->setIdempotencyKey($idempotencyKey);
        $payment->setCreatedAt($now);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }
}
