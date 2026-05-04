<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Task;
use App\Enum\TaskStatus;
use App\Enum\TaskType;
use Doctrine\ORM\EntityManagerInterface;

class TaskAutoCreator
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function createCleaningTaskForCheckout(Booking $booking): ?Task
    {
        $lodging = $booking->getLodging();
        if (null === $lodging) {
            return null;
        }

        $hostProfile = $lodging->getHost();
        if (null === $hostProfile) {
            return null;
        }

        $checkout = $booking->getCheckout();
        $now = new \DateTimeImmutable();

        $task = new Task();
        $task->setLodging($lodging);
        $task->setBooking($booking);
        $task->setHostProfile($hostProfile);
        $task->setType(TaskType::CLEANING);
        $task->setStatus(TaskStatus::PENDING);
        $task->setDueDate($checkout ?? new \DateTimeImmutable());
        $task->setNotes(\sprintf('Ménage après départ - Réservation %s', $booking->getReference() ?? ''));
        $task->setCreatedAt($now);
        $task->setUpdatedAt($now);

        $this->em->persist($task);

        return $task;
    }
}
