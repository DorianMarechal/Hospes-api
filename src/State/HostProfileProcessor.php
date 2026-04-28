<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\HostProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class HostProfileProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        $hostProfile = $user->getHostProfile() ?? new HostProfile();
        $isNew = null === $hostProfile->getId();

        $hostProfile->setBusinessName($data->businessName);
        $hostProfile->setBillingAddress($data->billingAddress);
        $hostProfile->setBillingCity($data->billingCity);
        $hostProfile->setBillingCountry($data->billingCountry);
        $hostProfile->setBillingPostalCode($data->billingPostalCode);
        $hostProfile->setCountry($data->country);
        $hostProfile->setLegalForm($data->legalForm);
        $hostProfile->setTimezone($data->timezone);

        if ($isNew) {
            $hostProfile->setUser($user);
            $hostProfile->setCreatedAt(new \DateTimeImmutable());
        }

        $hostProfile->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($hostProfile);
        $this->entityManager->flush();

        return $hostProfile;
    }
}
