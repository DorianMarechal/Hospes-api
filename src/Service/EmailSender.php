<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\StaffAssignment;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailSender
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $fromEmail = 'noreply@hospes.io',
    ) {
    }

    public function sendBookingConfirmation(Booking $booking): void
    {
        $customer = $booking->getCustomer();
        if (null === $customer) {
            return;
        }

        $this->send(
            $customer->getEmail(),
            'Confirmation de votre réservation '.$booking->getReference(),
            'emails/booking_confirmation.html.twig',
            ['booking' => $booking],
        );
    }

    public function sendNewBookingToHost(Booking $booking): void
    {
        $host = $booking->getLodging()?->getHost()?->getUser();
        if (null === $host) {
            return;
        }

        $this->send(
            $host->getEmail(),
            'Nouvelle réservation '.$booking->getReference(),
            'emails/booking_new_host.html.twig',
            ['booking' => $booking],
        );
    }

    public function sendBookingCancellation(Booking $booking, User $recipient): void
    {
        $this->send(
            $recipient->getEmail(),
            'Annulation de la réservation '.$booking->getReference(),
            'emails/booking_cancellation.html.twig',
            ['booking' => $booking],
        );
    }

    public function sendStaffInvitation(StaffAssignment $assignment, string $email): void
    {
        $hostName = $assignment->getHost()?->getFirstName().' '.$assignment->getHost()?->getLastName();

        $this->send(
            $email,
            'Invitation à rejoindre l\'équipe de '.$hostName,
            'emails/staff_invitation.html.twig',
            ['assignment' => $assignment, 'hostName' => $hostName],
        );
    }

    public function sendPasswordReset(User $user): void
    {
        $this->send(
            $user->getEmail(),
            'Réinitialisation de votre mot de passe',
            'emails/password_reset.html.twig',
            ['user' => $user],
        );
    }

    public function sendCheckinReminder(Booking $booking): void
    {
        $customer = $booking->getCustomer();
        if (null === $customer) {
            return;
        }

        $this->send(
            $customer->getEmail(),
            'Rappel : votre arrivée demain pour '.$booking->getLodging()?->getName(),
            'emails/checkin_reminder.html.twig',
            ['booking' => $booking],
        );
    }

    public function sendReviewRequest(Booking $booking): void
    {
        $customer = $booking->getCustomer();
        if (null === $customer) {
            return;
        }

        $this->send(
            $customer->getEmail(),
            'Comment s\'est passé votre séjour ?',
            'emails/review_request.html.twig',
            ['booking' => $booking],
        );
    }

    public function sendAutomatedMessage(?string $to, string $subject, string $body): void
    {
        if (null === $to) {
            return;
        }

        $html = $this->twig->render('emails/automated_message.html.twig', [
            'subject' => $subject,
            'body' => $body,
        ]);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($to)
            ->subject($subject)
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function send(?string $to, string $subject, string $template, array $context): void
    {
        if (null === $to) {
            return;
        }

        $html = $this->twig->render($template, $context);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($to)
            ->subject($subject)
            ->html($html);

        $this->mailer->send($email);
    }
}
