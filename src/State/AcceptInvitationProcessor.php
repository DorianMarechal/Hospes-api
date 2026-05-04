<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\AcceptInvitationRequest;
use App\Entity\User;
use App\Repository\StaffAssignmentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AcceptInvitationProcessor implements ProcessorInterface
{
    public function __construct(
        private StaffAssignmentRepository $staffAssignmentRepository,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        if (!$data instanceof AcceptInvitationRequest) {
            throw new \InvalidArgumentException('Expected '.AcceptInvitationRequest::class);
        }

        $token = $uriVariables['token'] ?? '';
        $assignment = $this->staffAssignmentRepository->findByInvitationToken($token);

        if (!$assignment) {
            throw new NotFoundHttpException('Invalid invitation token');
        }

        $expiresAt = $assignment->getInvitationExpiresAt();
        if (null === $expiresAt || $expiresAt < new \DateTimeImmutable()) {
            throw new BadRequestHttpException('Invitation has expired');
        }

        if (null !== $assignment->getInvitationAcceptedAt()) {
            throw new BadRequestHttpException('Invitation already accepted');
        }

        $email = $assignment->getInvitationEmail();
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);

        if (null !== $existingUser) {
            $user = $existingUser;
            $roles = array_unique(array_merge($user->getRoles(), ['ROLE_STAFF']));
            $user->setRoles($roles);
            $user->setUpdatedAt(new \DateTimeImmutable());
        } else {
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($data->firstName);
            $user->setLastName($data->lastName);
            $user->setPassword($this->passwordHasher->hashPassword($user, $data->password));
            $user->setRoles(['ROLE_STAFF']);
            $user->setIsActive(true);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());
            $this->em->persist($user);
        }

        $assignment->setStaff($user);
        $assignment->setInvitationAcceptedAt(new \DateTimeImmutable());
        $assignment->setInvitationToken(null);
        $assignment->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $user;
    }
}
