<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ForgotPasswordRequest;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ForgotPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        assert($data instanceof ForgotPasswordRequest);

        $user = $this->userRepository->findOneBy(['email' => $data->email]);

        // Always return success to prevent email enumeration
        if (!$user) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->em->flush();

        // TODO: send email with reset link containing $token

        return null;
    }
}
