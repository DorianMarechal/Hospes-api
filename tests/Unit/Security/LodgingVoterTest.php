<?php

namespace App\Tests\Unit\Security;

use App\Entity\HostProfile;
use App\Entity\Lodging;
use App\Entity\User;
use App\Security\Voter\LodgingVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Uid\Uuid;

class LodgingVoterTest extends TestCase
{
    private LodgingVoter $voter;
    private const VIEW = LodgingVoter::VIEW;
    private const EDIT = LodgingVoter::EDIT;
    private const DELETE = LodgingVoter::DELETE;

    protected function setUp(): void
    {
        $roleHierarchy = new RoleHierarchy([
            'ROLE_ADMIN' => ['ROLE_HOST', 'ROLE_CUSTOMER'],
            'ROLE_HOST' => ['ROLE_USER'],
            'ROLE_CUSTOMER' => ['ROLE_USER'],
            'ROLE_STAFF' => ['ROLE_USER'],
        ]);
        $this->voter = new LodgingVoter($roleHierarchy);
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

    private function createHostWithLodging(): array
    {
        $hostProfile = new HostProfile();
        $this->setId($hostProfile);

        $host = new User();
        $host->setRoles(['ROLE_HOST']);
        $host->setHostProfile($hostProfile);

        $lodging = new Lodging();
        $lodging->setHost($hostProfile);

        return [$host, $lodging];
    }

    public function testViewIsAlwaysGranted(): void
    {
        $lodging = new Lodging();
        $token = $this->createToken(null);

        $result = $this->voter->vote($token, $lodging, [self::VIEW]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testEditGrantedForOwner(): void
    {
        [$ownerHost, $lodging] = $this->createHostWithLodging();
        $token = $this->createToken($ownerHost);

        $result = $this->voter->vote($token, $lodging, [self::EDIT]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testEditDeniedForOtherHost(): void
    {
        [$ownerHost, $ownerLodging] = $this->createHostWithLodging();
        [$otherHost, $otherLodging] = $this->createHostWithLodging();
        $tokenOtherHost = $this->createToken($otherHost);

        $result = $this->voter->vote($tokenOtherHost, $ownerLodging, [self::EDIT]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testEditGrantedForAdmin(): void
    {
        $admin = new User();
        $admin->setRoles(['ROLE_ADMIN']);
        $tokenAdmin = $this->createToken($admin);

        [$ownerHost, $ownerLodging] = $this->createHostWithLodging();

        $result = $this->voter->vote($tokenAdmin, $ownerLodging, [self::EDIT]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testEditDeniedForAnonymous(): void
    {
        $token = $this->createToken(null);

        [$ownerHost, $ownerLodging] = $this->createHostWithLodging();

        $result = $this->voter->vote($token, $ownerLodging, [self::EDIT]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeleteGrantedForOwner(): void
    {
        [$ownerHost, $ownerLodging] = $this->createHostWithLodging();
        $token = $this->createToken($ownerHost);

        $result = $this->voter->vote($token, $ownerLodging, [self::DELETE]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeleteDeniedForOtherHost(): void
    {
        [$ownerHost, $ownerLodging] = $this->createHostWithLodging();
        [$otherHost, $otherLodging] = $this->createHostWithLodging();
        $tokenOtherHost = $this->createToken($otherHost);

        $result = $this->voter->vote($tokenOtherHost, $ownerLodging, [self::DELETE]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAbstainsForWrongAttribute(): void
    {
        $lodging = new Lodging();
        $token = $this->createToken(null);

        $result = $this->voter->vote($token, $lodging, ['WRONG_ATTRIBUTE']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testAbstainsForWrongSubject(): void
    {
        $user = new User();
        $token = $this->createToken(null);

        $result = $this->voter->vote($token, $user, [self::EDIT]);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
