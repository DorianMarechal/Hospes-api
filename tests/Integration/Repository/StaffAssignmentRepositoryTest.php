<?php

namespace App\Tests\Integration\Repository;

use App\Repository\StaffAssignmentRepository;
use App\Tests\Factory\StaffAssignmentFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class StaffAssignmentRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private StaffAssignmentRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(StaffAssignmentRepository::class);
    }

    public function test_find_by_host_returns_assignments_for_that_host(): void
    {
        $host = UserFactory::createOne(['roles' => ['ROLE_HOST']]);
        $otherHost = UserFactory::createOne(['roles' => ['ROLE_HOST']]);

        StaffAssignmentFactory::createOne(['host' => $host]);
        StaffAssignmentFactory::createOne(['host' => $host]);
        StaffAssignmentFactory::createOne(['host' => $otherHost]);

        $results = $this->repository->findByHost($host->_real());

        $this->assertCount(2, $results);
    }

    public function test_find_by_invitation_token_returns_matching_assignment(): void
    {
        $assignment = StaffAssignmentFactory::createOne(['invitationToken' => 'token-abc-123']);

        $result = $this->repository->findByInvitationToken('token-abc-123');

        $this->assertNotNull($result);
        $this->assertSame($assignment->getId()->toRfc4122(), $result->getId()->toRfc4122());
    }

    public function test_find_by_invitation_token_returns_null_when_not_found(): void
    {
        $result = $this->repository->findByInvitationToken('nonexistent-token');

        $this->assertNull($result);
    }

    public function test_find_active_by_staff_returns_non_revoked_assignment(): void
    {
        $staff = UserFactory::createOne(['roles' => ['ROLE_STAFF']]);

        $assignment = StaffAssignmentFactory::createOne([
            'staff' => $staff,
            'isRevoked' => false,
        ]);

        $result = $this->repository->findActiveByStaff($staff->_real());

        $this->assertNotNull($result);
        $this->assertSame($assignment->getId()->toRfc4122(), $result->getId()->toRfc4122());
    }

    public function test_find_active_by_staff_excludes_revoked(): void
    {
        $staff = UserFactory::createOne(['roles' => ['ROLE_STAFF']]);

        StaffAssignmentFactory::createOne([
            'staff' => $staff,
            'isRevoked' => true,
        ]);

        $result = $this->repository->findActiveByStaff($staff->_real());

        $this->assertNull($result);
    }

    public function test_find_active_by_staff_returns_null_when_no_assignment(): void
    {
        $staff = UserFactory::createOne(['roles' => ['ROLE_STAFF']]);

        $result = $this->repository->findActiveByStaff($staff->_real());

        $this->assertNull($result);
    }
}
