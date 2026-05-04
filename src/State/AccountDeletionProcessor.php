<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AccountDeletionProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new HttpException(401, 'Authentication required');
        }

        $this->revokeRefreshTokens($user);
        $this->anonymizeUser($user);
        $this->entityManager->flush();

        return null;
    }

    private function revokeRefreshTokens(User $user): void
    {
        $this->entityManager->createQuery('DELETE FROM '.RefreshToken::class.' rt WHERE rt.username = :username')
            ->setParameter('username', $user->getUserIdentifier())
            ->execute();
    }

    private function anonymizeUser(User $user): void
    {
        $anonymizedId = substr($user->getId()?->toRfc4122() ?? 'unknown', 0, 8);
        $now = new \DateTimeImmutable();

        $user->setEmail('deleted-'.$anonymizedId.'@anonymized.local');
        $user->setFirstName('Utilisateur');
        $user->setLastName('Supprimé');
        $user->setPhone(null);
        $user->setPassword('');
        $user->setIsActive(false);
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $user->setUpdatedAt($now);
    }
}
