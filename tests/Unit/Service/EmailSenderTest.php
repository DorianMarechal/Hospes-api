<?php

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Entity\HostProfile;
use App\Entity\Lodging;
use App\Entity\User;
use App\Service\EmailSender;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

class EmailSenderTest extends TestCase
{
    private MailerInterface $mailer;
    private Environment $twig;
    private LoaderInterface $loader;
    private EmailSender $sender;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->loader = $this->createMock(LoaderInterface::class);

        $this->twig = $this->createMock(Environment::class);
        $this->twig->method('getLoader')->willReturn($this->loader);

        $this->sender = new EmailSender($this->mailer, $this->twig, 'noreply@hospes.io');
    }

    // --- sendBookingConfirmation ---

    public function testSendBookingConfirmationWithNullCustomerDoesNotCallMailer(): void
    {
        $booking = new Booking();
        // customer is null by default

        $this->mailer->expects($this->never())->method('send');
        $this->twig->expects($this->never())->method('render');

        $this->sender->sendBookingConfirmation($booking);
    }

    public function testSendBookingConfirmationCallsMailerWithCorrectRecipientEmail(): void
    {
        $customer = new User();
        $customer->setEmail('guest@example.com');
        $customer->setPreferredLocale('fr');

        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setReference('HOS-ABC12345-26');

        // Localized template does not exist, falls back to default
        $this->loader->method('exists')->willReturn(false);
        $this->twig->method('render')->willReturn('<html>confirmation</html>');

        $capturedEmail = null;
        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (Email $email) use (&$capturedEmail) {
                $capturedEmail = $email;
            });

        $this->sender->sendBookingConfirmation($booking);

        $this->assertNotNull($capturedEmail);
        $addresses = $capturedEmail->getTo();
        $this->assertCount(1, $addresses);
        $this->assertSame('guest@example.com', $addresses[0]->getAddress());
    }

    public function testSendBookingConfirmationSubjectContainsReference(): void
    {
        $customer = new User();
        $customer->setEmail('guest@example.com');
        $customer->setPreferredLocale('fr');

        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setReference('HOS-XYZ99999-26');

        $this->loader->method('exists')->willReturn(false);
        $this->twig->method('render')->willReturn('<html></html>');

        $capturedEmail = null;
        $this->mailer->method('send')->willReturnCallback(function (Email $email) use (&$capturedEmail) {
            $capturedEmail = $email;
        });

        $this->sender->sendBookingConfirmation($booking);

        $this->assertStringContainsString('HOS-XYZ99999-26', $capturedEmail->getSubject());
    }

    // --- resolveTemplate (tested through sendBookingConfirmation) ---

    public function testResolveTemplateReturnsLocalizedPathWhenTemplateExists(): void
    {
        $customer = new User();
        $customer->setEmail('guest@example.com');
        $customer->setPreferredLocale('en');

        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setReference('HOS-001');

        // The localized template exists
        $this->loader
            ->expects($this->once())
            ->method('exists')
            ->with('emails/en/booking_confirmation.html.twig')
            ->willReturn(true);

        $capturedTemplate = null;
        $this->twig
            ->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template) use (&$capturedTemplate) {
                $capturedTemplate = $template;

                return '<html></html>';
            });

        $this->mailer->method('send');

        $this->sender->sendBookingConfirmation($booking);

        $this->assertSame('emails/en/booking_confirmation.html.twig', $capturedTemplate);
    }

    public function testResolveTemplateFallsBackToDefaultWhenLocalizedTemplateDoesNotExist(): void
    {
        $customer = new User();
        $customer->setEmail('guest@example.com');
        $customer->setPreferredLocale('de');

        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setReference('HOS-002');

        // The localized template does NOT exist
        $this->loader
            ->expects($this->once())
            ->method('exists')
            ->with('emails/de/booking_confirmation.html.twig')
            ->willReturn(false);

        $capturedTemplate = null;
        $this->twig
            ->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template) use (&$capturedTemplate) {
                $capturedTemplate = $template;

                return '<html></html>';
            });

        $this->mailer->method('send');

        $this->sender->sendBookingConfirmation($booking);

        $this->assertSame('emails/booking_confirmation.html.twig', $capturedTemplate);
    }

    // --- localizedSubject (tested through sendBookingConfirmation) ---

    public function testLocalizedSubjectReturnsCorrectLocale(): void
    {
        $customer = new User();
        $customer->setEmail('guest@example.com');
        $customer->setPreferredLocale('en');

        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setReference('HOS-EN-001');

        $this->loader->method('exists')->willReturn(false);
        $this->twig->method('render')->willReturn('<html></html>');

        $capturedEmail = null;
        $this->mailer->method('send')->willReturnCallback(function (Email $email) use (&$capturedEmail) {
            $capturedEmail = $email;
        });

        $this->sender->sendBookingConfirmation($booking);

        $this->assertSame('Booking confirmation HOS-EN-001', $capturedEmail->getSubject());
    }

    public function testLocalizedSubjectFallsBackToFrWhenLocaleNotInMap(): void
    {
        $customer = new User();
        $customer->setEmail('guest@example.com');
        $customer->setPreferredLocale('ja'); // not in subject map

        $booking = new Booking();
        $booking->setCustomer($customer);
        $booking->setReference('HOS-JA-001');

        $this->loader->method('exists')->willReturn(false);
        $this->twig->method('render')->willReturn('<html></html>');

        $capturedEmail = null;
        $this->mailer->method('send')->willReturnCallback(function (Email $email) use (&$capturedEmail) {
            $capturedEmail = $email;
        });

        $this->sender->sendBookingConfirmation($booking);

        // Falls back to 'fr' key
        $this->assertSame('Confirmation de votre réservation HOS-JA-001', $capturedEmail->getSubject());
    }

    // --- sendAutomatedMessage ---

    public function testSendAutomatedMessageWithNullToDoesNotCallMailer(): void
    {
        $this->mailer->expects($this->never())->method('send');
        $this->twig->expects($this->never())->method('render');

        $this->sender->sendAutomatedMessage(null, 'Hello', 'Body text');
    }

    public function testSendAutomatedMessageWithValidToCallsMailer(): void
    {
        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with('emails/automated_message.html.twig', ['subject' => 'Hello', 'body' => 'Welcome!'])
            ->willReturn('<html>Welcome!</html>');

        $capturedEmail = null;
        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (Email $email) use (&$capturedEmail) {
                $capturedEmail = $email;
            });

        $this->sender->sendAutomatedMessage('recipient@example.com', 'Hello', 'Welcome!');

        $this->assertNotNull($capturedEmail);
        $this->assertSame('recipient@example.com', $capturedEmail->getTo()[0]->getAddress());
        $this->assertSame('Hello', $capturedEmail->getSubject());
        $this->assertSame('noreply@hospes.io', $capturedEmail->getFrom()[0]->getAddress());
    }

    // --- sendNewBookingToHost ---

    public function testSendNewBookingToHostDoesNothingWhenHostIsNull(): void
    {
        $booking = new Booking();
        // no lodging — getLodging() returns null

        $this->mailer->expects($this->never())->method('send');

        $this->sender->sendNewBookingToHost($booking);
    }

    public function testSendNewBookingToHostCallsMailerWithHostEmail(): void
    {
        $hostUser = new User();
        $hostUser->setEmail('host@example.com');

        $hostProfile = new HostProfile();
        $hostProfile->setUser($hostUser);

        $lodging = new Lodging();
        $lodging->setHost($hostProfile);

        $booking = new Booking();
        $booking->setLodging($lodging);
        $booking->setReference('HOS-HOST-001');

        $this->twig->method('render')->willReturn('<html></html>');

        $capturedEmail = null;
        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (Email $email) use (&$capturedEmail) {
                $capturedEmail = $email;
            });

        $this->sender->sendNewBookingToHost($booking);

        $this->assertSame('host@example.com', $capturedEmail->getTo()[0]->getAddress());
    }
}
