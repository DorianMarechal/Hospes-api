<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\RefundPaymentRequest;
use App\Entity\Payment;
use App\Enum\PaymentStatus;
use App\Enum\PaymentType;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class PaymentRefundProcessor implements ProcessorInterface
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        assert($data instanceof RefundPaymentRequest);

        $payment = $this->paymentRepository->find($uriVariables['id']);
        if (!$payment) {
            throw new NotFoundHttpException('Payment not found');
        }

        if (PaymentStatus::SUCCEEDED !== $payment->getStatus()) {
            throw new HttpException(422, 'Only succeeded payments can be refunded');
        }

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        $reachableRoles = $this->roleHierarchy->getReachableRoleNames($user->getRoles());
        $isAdmin = \in_array('ROLE_ADMIN', $reachableRoles, true);

        if (!$isAdmin) {
            $lodgingHost = $payment->getBooking()?->getLodging()?->getHost();
            if (null === $lodgingHost || !$lodgingHost->getId()?->equals($user->getHostProfile()?->getId())) {
                throw new HttpException(403, 'You can only refund payments for your own lodgings');
            }
        }

        $now = new \DateTimeImmutable();

        // Mark original payment as refunded
        $payment->setStatus(PaymentStatus::REFUNDED);
        $payment->setUpdatedAt($now);

        // Create refund payment
        $refund = new Payment();
        $refund->setBooking($payment->getBooking());
        $refund->setAmount($payment->getAmount());
        $refund->setType(PaymentType::REFUND);
        $refund->setMethod($payment->getMethod());
        $refund->setStatus(PaymentStatus::SUCCEEDED);
        $refund->setProvider($payment->getProvider());
        $refund->setRefundReason($data->reason);
        $refund->setCreatedAt($now);

        $this->entityManager->persist($refund);
        $this->entityManager->flush();

        return $refund;
    }
}
