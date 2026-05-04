<?php

namespace App\Tests\Unit\Service;

use App\Entity\Booking;
use App\Entity\HostProfile;
use App\Entity\Lodging;
use App\Entity\Task;
use App\Enum\TaskStatus;
use App\Enum\TaskType;
use App\Service\TaskAutoCreator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TaskAutoCreatorTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private TaskAutoCreator $creator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->creator = new TaskAutoCreator($this->em);
    }

    private function createBookingWithLodgingAndHost(): Booking
    {
        $hostProfile = new HostProfile();

        $lodging = new Lodging();
        $lodging->setHost($hostProfile);
        $lodging->setName('Gîte des Pins');

        $checkout = new \DateTimeImmutable('2026-07-15');

        $booking = new Booking();
        $booking->setLodging($lodging);
        $booking->setCheckout($checkout);
        $booking->setReference('HOS-TEST9999-26');

        return $booking;
    }

    // TAC-1: creates a cleaning task with correct type, status, and dueDate
    public function testCreateCleaningTaskForCheckoutCreatesTaskWithCorrectFields(): void
    {
        $booking = $this->createBookingWithLodgingAndHost();
        $checkout = $booking->getCheckout();

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Task::class));

        $task = $this->creator->createCleaningTaskForCheckout($booking);

        $this->assertNotNull($task);
        $this->assertInstanceOf(Task::class, $task);
        $this->assertSame(TaskType::CLEANING, $task->getType());
        $this->assertSame(TaskStatus::PENDING, $task->getStatus());
        $this->assertSame($checkout, $task->getDueDate());
        $this->assertSame($booking->getLodging(), $task->getLodging());
        $this->assertSame($booking, $task->getBooking());
        $this->assertSame($booking->getLodging()->getHost(), $task->getHostProfile());
        $this->assertStringContainsString('HOS-TEST9999-26', (string) $task->getNotes());
        $this->assertNotNull($task->getCreatedAt());
        $this->assertNotNull($task->getUpdatedAt());
    }

    // TAC-2: returns null when booking has no lodging
    public function testCreateCleaningTaskForCheckoutReturnsNullWhenNoLodging(): void
    {
        $booking = new Booking();
        // No lodging set

        $this->em->expects($this->never())->method('persist');

        $task = $this->creator->createCleaningTaskForCheckout($booking);

        $this->assertNull($task);
    }

    // TAC-3: returns null when lodging has no host
    public function testCreateCleaningTaskForCheckoutReturnsNullWhenLodgingHasNoHost(): void
    {
        $lodging = new Lodging();
        $lodging->setName('Sans hôte');
        // No host set on lodging

        $booking = new Booking();
        $booking->setLodging($lodging);
        $booking->setCheckout(new \DateTimeImmutable('2026-07-20'));

        $this->em->expects($this->never())->method('persist');

        $task = $this->creator->createCleaningTaskForCheckout($booking);

        $this->assertNull($task);
    }

    // TAC-4: when checkout is null, dueDate falls back to current date
    public function testCreateCleaningTaskForCheckoutUsesFallbackDueDateWhenCheckoutIsNull(): void
    {
        $hostProfile = new HostProfile();
        $lodging = new Lodging();
        $lodging->setHost($hostProfile);

        $booking = new Booking();
        $booking->setLodging($lodging);
        $booking->setReference('HOS-NULL0000-26');
        // No checkout set — triggers fallback

        $this->em->expects($this->once())->method('persist');

        $before = new \DateTimeImmutable();
        $task = $this->creator->createCleaningTaskForCheckout($booking);
        $after = new \DateTimeImmutable();

        $this->assertNotNull($task);
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $task->getDueDate()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $task->getDueDate()->getTimestamp());
    }
}
