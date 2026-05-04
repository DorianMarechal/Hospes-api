<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504074406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE channel_booking (id UUID NOT NULL, channel VARCHAR(20) NOT NULL, external_reservation_id VARCHAR(255) NOT NULL, external_status VARCHAR(20) NOT NULL, imported_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, last_sync_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, booking_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_channel_booking_booking ON channel_booking (booking_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CHANNEL_BOOKING_EXTERNAL ON channel_booking (channel, external_reservation_id)');
        $this->addSql('CREATE TABLE channel_connection (id UUID NOT NULL, channel VARCHAR(20) NOT NULL, external_listing_id VARCHAR(255) DEFAULT NULL, credentials JSON NOT NULL, last_sync_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, lodging_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9F3E23F087335AF1 ON channel_connection (lodging_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CHANNEL_LODGING ON channel_connection (lodging_id, channel)');
        $this->addSql('ALTER TABLE channel_booking ADD CONSTRAINT FK_574FD2DF3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE channel_connection ADD CONSTRAINT FK_9F3E23F087335AF1 FOREIGN KEY (lodging_id) REFERENCES lodging (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE channel_booking DROP CONSTRAINT FK_574FD2DF3301C60');
        $this->addSql('ALTER TABLE channel_connection DROP CONSTRAINT FK_9F3E23F087335AF1');
        $this->addSql('DROP TABLE channel_booking');
        $this->addSql('DROP TABLE channel_connection');
    }
}
