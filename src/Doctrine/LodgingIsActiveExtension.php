<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Lodging;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class LodgingIsActiveExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private Security $security,
        private RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (Lodging::class !== $resourceClass) {
            return;
        }

        $user = $this->security->getUser();

        if (null !== $user) {
            /** @var \App\Entity\User $user */
            $reachableRoles = $this->roleHierarchy->getReachableRoleNames($user->getRoles());

            if (\in_array('ROLE_ADMIN', $reachableRoles, true)) {
                return;
            }

            if (\in_array('ROLE_HOST', $reachableRoles, true)) {
                $rootAlias = $queryBuilder->getRootAliases()[0];
                $hostProfile = $user->getHostProfile();
                if (null !== $hostProfile) {
                    $queryBuilder->andWhere(\sprintf('%s.isActive = :active OR %s.host = :hostProfile', $rootAlias, $rootAlias));
                    $queryBuilder->setParameter('active', true);
                    $queryBuilder->setParameter('hostProfile', $hostProfile->getId(), 'uuid');
                } else {
                    $queryBuilder->andWhere(\sprintf('%s.isActive = :active', $rootAlias));
                    $queryBuilder->setParameter('active', true);
                }

                return;
            }
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere(\sprintf('%s.isActive = :active', $rootAlias));
        $queryBuilder->setParameter('active', true);
    }
}
