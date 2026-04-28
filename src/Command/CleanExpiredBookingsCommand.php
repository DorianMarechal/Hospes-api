<?php

namespace App\Command;

use App\Service\PendingBookingCleaner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:bookings:clean-expired',
    description: 'Cancel pending bookings that have exceeded their TTL',
)]
class CleanExpiredBookingsCommand extends Command
{
    public function __construct(
        private PendingBookingCleaner $cleaner,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->cleaner->cleanExpired();

        $io->success(sprintf('%d expired booking(s) cancelled.', $count));

        return Command::SUCCESS;
    }
}
