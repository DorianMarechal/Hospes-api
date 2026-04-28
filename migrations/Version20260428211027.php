<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260428211027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add booking_status_history table for tracking booking status changes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE booking_status_history (id UUID NOT NULL, previous_status VARCHAR(255) DEFAULT NULL, new_status VARCHAR(255) NOT NULL, reason TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, booking_id UUID NOT NULL, changed_by_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_B405FC3E828AD0A0 ON booking_status_history (changed_by_id)');
        $this->addSql('CREATE INDEX idx_booking_status_history_booking ON booking_status_history (booking_id)');
        $this->addSql('ALTER TABLE booking_status_history ADD CONSTRAINT FK_B405FC3E3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE booking_status_history ADD CONSTRAINT FK_B405FC3E828AD0A0 FOREIGN KEY (changed_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking_status_history DROP CONSTRAINT FK_B405FC3E3301C60');
        $this->addSql('ALTER TABLE booking_status_history DROP CONSTRAINT FK_B405FC3E828AD0A0');
        $this->addSql('DROP TABLE booking_status_history');
    }
}
