<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:purge-inactive-accounts',
    description: 'Anonymize accounts inactive for more than 3 years (GDPR retention policy)',
)]
class PurgeInactiveAccountsCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $threshold = new \DateTimeImmutable('-3 years');

        $users = $this->userRepository->createQueryBuilder('u')
            ->andWhere('u.isActive = true')
            ->andWhere('u.updatedAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($users as $user) {
            $anonymizedId = substr($user->getId()?->toRfc4122() ?? 'unknown', 0, 8);
            $now = new \DateTimeImmutable();

            $user->setEmail('purged-'.$anonymizedId.'@anonymized.local');
            $user->setFirstName('Utilisateur');
            $user->setLastName('Supprimé');
            $user->setPhone(null);
            $user->setPassword('');
            $user->setIsActive(false);
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            $user->setUpdatedAt($now);
            ++$count;
        }

        $this->entityManager->flush();

        $io->success(\sprintf('%d inactive accounts anonymized.', $count));

        return Command::SUCCESS;
    }
}
