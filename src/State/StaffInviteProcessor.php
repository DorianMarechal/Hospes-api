<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\InviteStaffRequest;
use App\Entity\StaffAssignment;
use App\Entity\StaffLodging;
use App\Entity\StaffPermission;
use App\Entity\User;
use App\Enum\StaffPermission as StaffPermissionEnum;
use App\Repository\LodgingRepository;
use App\Service\EmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StaffInviteProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
        private LodgingRepository $lodgingRepository,
        private EmailSender $emailSender,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StaffAssignment
    {
        if (!$data instanceof InviteStaffRequest) {
            throw new \InvalidArgumentException('Expected '.InviteStaffRequest::class);
        }

        $host = $this->security->getUser();
        if (!$host instanceof User) {
            throw new HttpException(401, 'Authentication required');
        }

        $assignment = new StaffAssignment();
        $assignment->setHost($host);
        $assignment->setInvitationEmail($data->email);
        $assignment->setInvitationToken(bin2hex(random_bytes(32)));
        $assignment->setInvitationExpiresAt(new \DateTimeImmutable('+7 days'));
        $assignment->setCreatedAt(new \DateTimeImmutable());
        $assignment->setUpdatedAt(new \DateTimeImmutable());

        foreach ($data->permissions as $permissionName) {
            $perm = StaffPermissionEnum::from($permissionName);
            $sp = new StaffPermission();
            $sp->setPermission($perm);
            $assignment->addPermission($sp);
        }

        $hostProfile = $host->getHostProfile();
        foreach ($data->lodgingIds as $lodgingId) {
            $lodging = $this->lodgingRepository->find($lodgingId);
            if (!$lodging) {
                throw new BadRequestHttpException(sprintf('Lodging %s not found', $lodgingId));
            }
            if (!$lodging->getHost()?->getId()?->equals($hostProfile?->getId())) {
                throw new BadRequestHttpException(sprintf('Lodging %s does not belong to you', $lodgingId));
            }
            $sl = new StaffLodging();
            $sl->setLodging($lodging);
            $assignment->addLodging($sl);
        }

        $this->em->persist($assignment);
        $this->em->flush();

        $this->emailSender->sendStaffInvitation($assignment, $data->email);

        return $assignment;
    }
}
