<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\UpdateProfileRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UpdateProfileProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof UpdateProfileRequest) {
            throw new \InvalidArgumentException('Expected '.UpdateProfileRequest::class);
        }

        $user = $this->security->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw new \RuntimeException('Expected authenticated user');
        }

        $user->setFirstName($data->firstName);
        $user->setLastName($data->lastName);
        $user->setPhone($data->phone);
        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $user;
    }
}
