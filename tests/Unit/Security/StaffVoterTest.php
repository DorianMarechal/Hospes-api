<?php

namespace App\Tests\Unit\Security;

use App\Entity\StaffAssignment;
use App\Entity\User;
use App\Security\Voter\StaffVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Uuid;

class StaffVoterTest extends TestCase
{
    private StaffVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new StaffVoter();
    }

    private function createToken(?User $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function setId(object $entity, ?Uuid $id = null): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $id ?? Uuid::v7());
    }

    public function testManageGrantedForAssignmentHost(): void
    {
        $host = new User();
        $this->setId($host);
        $host->setRoles(['ROLE_HOST']);

        $assignment = new StaffAssignment();
        $assignment->setHost($host);

        $token = $this->createToken($host);

        $result = $this->voter->vote($token, $assignment, [StaffVoter::STAFF_MANAGE]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testManageDeniedForOtherHost(): void
    {
        $host = new User();
        $this->setId($host);

        $otherHost = new User();
        $this->setId($otherHost);
        $otherHost->setRoles(['ROLE_HOST']);

        $assignment = new StaffAssignment();
        $assignment->setHost($host);

        $token = $this->createToken($otherHost);

        $result = $this->voter->vote($token, $assignment, [StaffVoter::STAFF_MANAGE]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testManageDeniedForAnonymous(): void
    {
        $host = new User();
        $this->setId($host);

        $assignment = new StaffAssignment();
        $assignment->setHost($host);

        $token = $this->createToken(null);

        $result = $this->voter->vote($token, $assignment, [StaffVoter::STAFF_MANAGE]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAbstainsForWrongAttribute(): void
    {
        $assignment = new StaffAssignment();
        $token = $this->createToken(null);

        $result = $this->voter->vote($token, $assignment, ['WRONG_ATTRIBUTE']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testAbstainsForWrongSubject(): void
    {
        $user = new User();
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $user, [StaffVoter::STAFF_MANAGE]);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
