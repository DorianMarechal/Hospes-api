<?php

namespace App\Command;

use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use App\Repository\ReviewRepository;
use App\Service\EmailSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-review-requests',
    description: 'Send review request emails for bookings that checked out yesterday',
)]
class SendReviewRequestsCommand extends Command
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private ReviewRepository $reviewRepository,
        private EmailSender $emailSender,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $yesterday = (new \DateTimeImmutable('-1 day'))->setTime(0, 0);

        $bookings = $this->bookingRepository->createQueryBuilder('b')
            ->andWhere('b.checkout = :yesterday')
            ->andWhere('b.status IN (:statuses)')
            ->setParameter('yesterday', $yesterday)
            ->setParameter('statuses', [BookingStatus::CONFIRMED, BookingStatus::COMPLETED])
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($bookings as $booking) {
            $existingReview = $this->reviewRepository->findOneBy(['booking' => $booking]);
            if (null !== $existingReview) {
                continue;
            }
            $this->emailSender->sendReviewRequest($booking);
            ++$count;
        }

        $io->success(\sprintf('%d review requests sent.', $count));

        return Command::SUCCESS;
    }
}
