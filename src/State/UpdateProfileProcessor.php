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
        assert($data instanceof UpdateProfileRequest);

        $user = $this->security->getUser();
        \assert($user instanceof \App\Entity\User);

        $user->setFirstName($data->firstName);
        $user->setLastName($data->lastName);
        $user->setPhone($data->phone);
        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $user;
    }
}
