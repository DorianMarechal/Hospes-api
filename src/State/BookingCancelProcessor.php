<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\BookingStatusHistory;
use App\Entity\Payment;
use App\Enum\BookingStatus;
use App\Enum\PaymentProvider;
use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use App\Payment\PaymentGatewayFactory;
use App\Repository\BookingRepository;
use App\Repository\PaymentRepository;
use App\Service\CancellationPolicyResolver;
use App\Service\NotificationDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingCancelProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookingRepository $bookingRepository,
        private PaymentRepository $paymentRepository,
        private Security $security,
        private NotificationDispatcher $notificationDispatcher,
        private CancellationPolicyResolver $cancellationPolicyResolver,
        private PaymentGatewayFactory $gatewayFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $booking = $this->bookingRepository->find($uriVariables['id']);
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        $status = $booking->getStatus();
        if (!\in_array($status, [BookingStatus::PENDING, BookingStatus::CONFIRMED])) {
            throw new HttpException(422, 'Only pending or confirmed bookings can be cancelled');
        }

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        $now = new \DateTimeImmutable();

        $history = new BookingStatusHistory();
        $history->setBooking($booking);
        $history->setPreviousStatus($status);
        $history->setNewStatus(BookingStatus::CANCELLED);
        $history->setChangedBy($user);
        $history->setReason($data->reason ?? null);
        $history->setCreatedAt($now);

        $booking->setStatus(BookingStatus::CANCELLED);
        $booking->setCancelledBy($user);
        $booking->setCancellationReason($data->reason ?? null);
        $booking->setUpdatedAt($now);

        $this->entityManager->persist($history);
        $this->notificationDispatcher->bookingCancelled($booking);

        // Auto-refund based on cancellation policy
        $this->processAutoRefund($booking, $user, $now);

        $this->entityManager->flush();

        return $booking;
    }

    private function processAutoRefund(\App\Entity\Booking $booking, \App\Entity\User $cancelledBy, \DateTimeImmutable $now): void
    {
        if (!$this->paymentRepository->hasSucceededPayment($booking)) {
            return;
        }

        $resolution = $this->cancellationPolicyResolver->resolve($booking, $cancelledBy, $now);
        if (!$resolution['eligible'] || 0 === $resolution['refundAmount']) {
            return;
        }

        $totalRefundRemaining = $resolution['refundAmount'];
        $payments = $this->paymentRepository->findByBooking($booking);

        foreach ($payments as $payment) {
            if (PaymentStatus::SUCCEEDED !== $payment->getStatus() || PaymentType::BOOKING !== $payment->getType()) {
                continue;
            }

            $refundAmount = min($totalRefundRemaining, $payment->getAmount());
            if (0 === $refundAmount) {
                continue;
            }

            $provider = PaymentProvider::tryFrom($payment->getProvider() ?? '');
            $refundTransactionId = null;

            if ($provider && $payment->getProviderTransactionId()) {
                try {
                    $connectedAccountId = $booking->getLodging()?->getHost()?->getPaymentProviderAccountId() ?? '';
                    $gateway = $this->gatewayFactory->get($provider);
                    $refundTransactionId = $gateway->refund($payment->getProviderTransactionId(), $refundAmount, $connectedAccountId);
                } catch (\Throwable $e) {
                    $this->logger->error('Auto-refund failed', [
                        'paymentId' => $payment->getId()?->toRfc4122(),
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            $payment->setStatus(PaymentStatus::REFUNDED);
            $payment->setUpdatedAt($now);

            $refund = new Payment();
            $refund->setBooking($booking);
            $refund->setAmount($refundAmount);
            $refund->setType(PaymentType::REFUND);
            $refund->setMethod($payment->getMethod());
            $refund->setStatus(PaymentStatus::SUCCEEDED);
            $refund->setProvider($payment->getProvider());
            $refund->setProviderTransactionId($refundTransactionId);
            $refund->setRefundReason('Auto-refund on cancellation');
            $refund->setCreatedAt($now);

            $this->entityManager->persist($refund);
            $totalRefundRemaining -= $refundAmount;

            if (0 === $totalRefundRemaining) {
                break;
            }
        }
    }
}
