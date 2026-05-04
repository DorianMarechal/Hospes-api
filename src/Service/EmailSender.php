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

        $locale = $customer->getPreferredLocale();
        $template = $this->resolveTemplate('booking_confirmation', $locale);

        $this->send(
            $customer->getEmail(),
            $this->localizedSubject($locale, [
                'fr' => 'Confirmation de votre réservation '.$booking->getReference(),
                'en' => 'Booking confirmation '.$booking->getReference(),
                'de' => 'Buchungsbestätigung '.$booking->getReference(),
                'es' => 'Confirmación de reserva '.$booking->getReference(),
                'it' => 'Conferma prenotazione '.$booking->getReference(),
            ]),
            $template,
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

        $locale = $customer->getPreferredLocale();
        $template = $this->resolveTemplate('checkin_reminder', $locale);
        $lodgingName = $booking->getLodging()?->getName() ?? '';

        $this->send(
            $customer->getEmail(),
            $this->localizedSubject($locale, [
                'fr' => 'Rappel : votre arrivée demain pour '.$lodgingName,
                'en' => 'Reminder: your arrival tomorrow at '.$lodgingName,
                'de' => 'Erinnerung: Ihre Anreise morgen bei '.$lodgingName,
                'es' => 'Recordatorio: su llegada mañana en '.$lodgingName,
                'it' => 'Promemoria: il suo arrivo domani a '.$lodgingName,
            ]),
            $template,
            ['booking' => $booking],
        );
    }

    public function sendReviewRequest(Booking $booking): void
    {
        $customer = $booking->getCustomer();
        if (null === $customer) {
            return;
        }

        $locale = $customer->getPreferredLocale();
        $template = $this->resolveTemplate('review_request', $locale);

        $this->send(
            $customer->getEmail(),
            $this->localizedSubject($locale, [
                'fr' => 'Comment s\'est passé votre séjour ?',
                'en' => 'How was your stay?',
                'de' => 'Wie war Ihr Aufenthalt?',
                'es' => '¿Cómo fue su estancia?',
                'it' => 'Com\'è stato il suo soggiorno?',
            ]),
            $template,
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

    private function resolveTemplate(string $name, string $locale): string
    {
        $localized = \sprintf('emails/%s/%s.html.twig', $locale, $name);
        if ($this->twig->getLoader()->exists($localized)) {
            return $localized;
        }

        return \sprintf('emails/%s.html.twig', $name);
    }

    /**
     * @param array<string, string> $subjects
     */
    private function localizedSubject(string $locale, array $subjects): string
    {
        return $subjects[$locale] ?? $subjects['fr'] ?? '';
    }
}
