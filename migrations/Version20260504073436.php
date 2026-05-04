<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504073436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE automated_message_log (id UUID NOT NULL, trigger_type VARCHAR(30) NOT NULL, channel VARCHAR(10) NOT NULL, sent_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, message_template_id UUID NOT NULL, booking_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_E6A9F50665A55141 ON automated_message_log (message_template_id)');
        $this->addSql('CREATE INDEX IDX_E6A9F5063301C60 ON automated_message_log (booking_id)');
        $this->addSql('CREATE INDEX idx_auto_msg_log_booking_trigger ON automated_message_log (booking_id, trigger_type)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AUTO_MSG_TEMPLATE_BOOKING_CHANNEL ON automated_message_log (message_template_id, booking_id, channel)');
        $this->addSql('CREATE TABLE message_template (id UUID NOT NULL, name VARCHAR(100) NOT NULL, trigger_type VARCHAR(30) NOT NULL, subject VARCHAR(255) NOT NULL, body TEXT NOT NULL, channels JSON NOT NULL, delay_minutes INT NOT NULL, enabled BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, host_profile_id UUID NOT NULL, lodging_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9E46DB92646948B1 ON message_template (host_profile_id)');
        $this->addSql('CREATE INDEX IDX_9E46DB9287335AF1 ON message_template (lodging_id)');
        $this->addSql('CREATE INDEX idx_message_template_host_trigger ON message_template (host_profile_id, trigger_type)');
        $this->addSql('ALTER TABLE automated_message_log ADD CONSTRAINT FK_E6A9F50665A55141 FOREIGN KEY (message_template_id) REFERENCES message_template (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE automated_message_log ADD CONSTRAINT FK_E6A9F5063301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE message_template ADD CONSTRAINT FK_9E46DB92646948B1 FOREIGN KEY (host_profile_id) REFERENCES host_profile (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE message_template ADD CONSTRAINT FK_9E46DB9287335AF1 FOREIGN KEY (lodging_id) REFERENCES lodging (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE automated_message_log DROP CONSTRAINT FK_E6A9F50665A55141');
        $this->addSql('ALTER TABLE automated_message_log DROP CONSTRAINT FK_E6A9F5063301C60');
        $this->addSql('ALTER TABLE message_template DROP CONSTRAINT FK_9E46DB92646948B1');
        $this->addSql('ALTER TABLE message_template DROP CONSTRAINT FK_9E46DB9287335AF1');
        $this->addSql('DROP TABLE automated_message_log');
        $this->addSql('DROP TABLE message_template');
    }
}
