<?php

namespace App\Command;

use App\Repository\ChannelConnectionRepository;
use App\Service\ChannelSyncService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-channels',
    description: 'Sync all active channel connections (pull bookings, push availability)',
)]
class SyncChannelsCommand extends Command
{
    public function __construct(
        private ChannelConnectionRepository $connectionRepository,
        private ChannelSyncService $syncService,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connections = $this->connectionRepository->findAllActive();

        $totalImported = 0;

        foreach ($connections as $connection) {
            try {
                $imported = $this->syncService->sync($connection);
                $totalImported += $imported;
            } catch (\Throwable $e) {
                $this->logger->error('Channel sync failed', [
                    'connection' => (string) $connection->getId(),
                    'channel' => $connection->getChannel()?->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $io->success(\sprintf(
            'Synced %d channel(s), imported %d new booking(s).',
            \count($connections),
            $totalImported,
        ));

        return Command::SUCCESS;
    }
}
