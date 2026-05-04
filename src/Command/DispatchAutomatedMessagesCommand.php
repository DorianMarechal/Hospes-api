<?php

namespace App\Command;

use App\Service\AutomatedMessageDispatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:dispatch-automated-messages',
    description: 'Dispatch automated messages for time-based triggers (checkin-1d, checkin-3h, checkout+1d)',
)]
class DispatchAutomatedMessagesCommand extends Command
{
    public function __construct(
        private AutomatedMessageDispatcher $dispatcher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->dispatcher->processScheduledMessages();

        $io->success(\sprintf('%d automated message(s) dispatched.', $count));

        return Command::SUCCESS;
    }
}
