<?php

namespace App\Tests\Integration;

use App\Entity\Deposit;
use App\Enum\DepositStatus;
use App\Tests\Factory\BookingFactory;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DepositConstraintsTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testOneDepositPerBooking(): void
    {
        $booking = BookingFactory::createOne();
        $now = new \DateTimeImmutable();

        $deposit1 = new Deposit();
        $deposit1->setBooking($booking->_real());
        $deposit1->setAmount(30000);
        $deposit1->setStatus(DepositStatus::HELD);
        $deposit1->setCreatedAt($now);
        $this->em->persist($deposit1);
        $this->em->flush();

        // Second deposit on same booking
        $deposit2 = new Deposit();
        $deposit2->setBooking($booking->_real());
        $deposit2->setAmount(30000);
        $deposit2->setStatus(DepositStatus::HELD);
        $deposit2->setCreatedAt($now);
        $this->em->persist($deposit2);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->flush();
    }
}
