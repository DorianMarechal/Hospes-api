<?php

namespace App\Command;

use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Service\EmailSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-checkin-reminders',
    description: 'Send check-in reminder emails for bookings arriving tomorrow',
)]
class SendCheckinRemindersCommand extends Command
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private EmailSender $emailSender,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tomorrow = (new \DateTimeImmutable('+1 day'))->setTime(0, 0);

        $bookings = $this->bookingRepository->createQueryBuilder('b')
            ->andWhere('b.checkin = :tomorrow')
            ->andWhere('b.status = :status')
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('status', BookingStatus::CONFIRMED)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($bookings as $booking) {
            $this->emailSender->sendCheckinReminder($booking);
            ++$count;
        }

        $io->success(\sprintf('%d check-in reminders sent.', $count));

        return Command::SUCCESS;
    }
}
