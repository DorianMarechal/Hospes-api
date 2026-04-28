<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\MyPermissionsResult;
use App\Entity\User;
use App\Repository\StaffAssignmentRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MyPermissionsProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private StaffAssignmentRepository $staffAssignmentRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): MyPermissionsResult
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        $assignment = $this->staffAssignmentRepository->findActiveByStaff($user);
        if (!$assignment) {
            throw new NotFoundHttpException('No active staff assignment found');
        }

        $lodgings = [];
        foreach ($assignment->getLodgings() as $sl) {
            $lodging = $sl->getLodging();
            if ($lodging) {
                $lodgings[] = [
                    'id' => (string) $lodging->getId(),
                    'name' => $lodging->getName(),
                ];
            }
        }

        return new MyPermissionsResult(
            permissions: $assignment->getPermissionNames(),
            lodgings: $lodgings,
            isRevoked: $assignment->isRevoked(),
        );
    }
}
