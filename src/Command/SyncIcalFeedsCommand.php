<?php

namespace App\Command;

use App\Message\SyncIcalFeedsMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:sync-ical-feeds',
    description: 'Dispatch iCal feed sync for all lodgings (run every 15 min via cron)',
)]
class SyncIcalFeedsCommand extends Command
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->messageBus->dispatch(new SyncIcalFeedsMessage());

        $io->success('iCal sync dispatched.');

        return Command::SUCCESS;
    }
}
