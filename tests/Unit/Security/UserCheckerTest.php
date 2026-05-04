<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends TestCase
{
    private UserChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new UserChecker();
    }

    public function testCheckPreAuthWithActiveUserDoesNotThrow(): void
    {
        $user = new User();
        $user->setIsActive(true);

        // Must not throw
        $this->checker->checkPreAuth($user);
        $this->addToAssertionCount(1);
    }

    public function testCheckPreAuthWithInactiveUserThrowsCustomUserMessageAccountStatusException(): void
    {
        $user = new User();
        $user->setIsActive(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Your account has been deactivated.');

        $this->checker->checkPreAuth($user);
    }

    public function testCheckPreAuthWithNonUserInterfaceImplementationIsSilentlySkipped(): void
    {
        $nonAppUser = $this->createStub(UserInterface::class);

        // Must not throw — the guard is `if (!$user instanceof User)`
        $this->checker->checkPreAuth($nonAppUser);
        $this->addToAssertionCount(1);
    }

    public function testCheckPostAuthDoesNothing(): void
    {
        $user = new User();
        $user->setIsActive(false);

        // checkPostAuth has an empty body — must never throw
        $this->checker->checkPostAuth($user);
        $this->addToAssertionCount(1);
    }
}
