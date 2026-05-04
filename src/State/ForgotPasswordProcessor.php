<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ForgotPasswordRequest;
use App\Repository\UserRepository;
use App\Service\EmailSender;
use Doctrine\ORM\EntityManagerInterface;

class ForgotPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private EmailSender $emailSender,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (!$data instanceof ForgotPasswordRequest) {
            throw new \InvalidArgumentException('Expected '.ForgotPasswordRequest::class);
        }

        $user = $this->userRepository->findOneBy(['email' => $data->email]);

        // Always return success to prevent email enumeration
        if (!$user) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $user->setResetToken(hash('sha256', $token));
        $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->em->flush();

        $this->emailSender->sendPasswordReset($user);

        return null;
    }
}
