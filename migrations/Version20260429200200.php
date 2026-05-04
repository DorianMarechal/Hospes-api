<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429200200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add consented_at column to user table for GDPR consent tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD consented_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP consented_at');
    }
}
