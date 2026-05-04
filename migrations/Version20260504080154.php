<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504080154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE task (id UUID NOT NULL, type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, due_date DATE NOT NULL, notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, lodging_id UUID NOT NULL, booking_id UUID DEFAULT NULL, assignee_id UUID DEFAULT NULL, host_profile_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_527EDB2587335AF1 ON task (lodging_id)');
        $this->addSql('CREATE INDEX IDX_527EDB253301C60 ON task (booking_id)');
        $this->addSql('CREATE INDEX IDX_527EDB2559EC7D60 ON task (assignee_id)');
        $this->addSql('CREATE INDEX IDX_527EDB25646948B1 ON task (host_profile_id)');
        $this->addSql('CREATE INDEX idx_task_assignee_status_due ON task (assignee_id, status, due_date)');
        $this->addSql('CREATE INDEX idx_task_lodging_due ON task (lodging_id, due_date)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB2587335AF1 FOREIGN KEY (lodging_id) REFERENCES lodging (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB253301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB2559EC7D60 FOREIGN KEY (assignee_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25646948B1 FOREIGN KEY (host_profile_id) REFERENCES host_profile (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB2587335AF1');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB253301C60');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB2559EC7D60');
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB25646948B1');
        $this->addSql('DROP TABLE task');
    }
}
