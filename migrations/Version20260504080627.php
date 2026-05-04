<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504080627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE property_owner (id UUID NOT NULL, commission_rate NUMERIC(5, 2) NOT NULL, payment_details JSON DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PROPERTY_OWNER_USER ON property_owner (user_id)');
        $this->addSql('ALTER TABLE property_owner ADD CONSTRAINT FK_9732771A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE lodging ADD property_owner_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE lodging ADD CONSTRAINT FK_8D35182A20BB55BD FOREIGN KEY (property_owner_id) REFERENCES property_owner (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_8D35182A20BB55BD ON lodging (property_owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE property_owner DROP CONSTRAINT FK_9732771A76ED395');
        $this->addSql('DROP TABLE property_owner');
        $this->addSql('ALTER TABLE lodging DROP CONSTRAINT FK_8D35182A20BB55BD');
        $this->addSql('DROP INDEX IDX_8D35182A20BB55BD');
        $this->addSql('ALTER TABLE lodging DROP property_owner_id');
    }
}
