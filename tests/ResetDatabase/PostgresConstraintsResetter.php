<?php

namespace App\Tests\ResetDatabase;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\ORM\ResetDatabase\OrmResetter;

#[When('test')]
#[AsDecorator(OrmResetter::class)]
final readonly class PostgresConstraintsResetter implements OrmResetter
{
    public function __construct(
        private OrmResetter $decorated,
    ) {
    }

    public function resetBeforeFirstTest(KernelInterface $kernel): void
    {
        $this->decorated->resetBeforeFirstTest($kernel);
        $this->addPostgresConstraints($kernel);
    }

    public function resetBeforeEachTest(KernelInterface $kernel): void
    {
        $this->decorated->resetBeforeEachTest($kernel);
        $this->addPostgresConstraints($kernel);
    }

    private function addPostgresConstraints(KernelInterface $kernel): void
    {
        /** @var Connection $connection */
        $connection = $kernel->getContainer()->get('doctrine')->getConnection();

        $connection->executeStatement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        $connection->executeStatement(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'booking_no_overlap'
                ) THEN
                    ALTER TABLE booking ADD CONSTRAINT booking_no_overlap
                        EXCLUDE USING gist (
                            lodging_id WITH =,
                            daterange(checkin, checkout) WITH &&
                        ) WHERE (status NOT IN ('cancelled'));
                END IF;
            END $$;
        SQL);
    }
}
