<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Service\AccountingService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AccountingTransactionsProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private AccountingService $accountingService,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return \App\Dto\AccountingTransaction[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }

        $hostProfile = $user->getHostProfile();
        if (null === $hostProfile) {
            throw new AccessDeniedHttpException('Host profile required.');
        }

        $request = $this->requestStack->getCurrentRequest();
        $from = $request?->query->get('from');
        $to = $request?->query->get('to');

        return $this->accountingService->getTransactions($hostProfile, $from, $to);
    }
}
