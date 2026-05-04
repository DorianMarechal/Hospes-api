<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504070000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 19-20: webhook_event, payment.idempotency_key, notification.params, booking check-in/out';
    }

    public function up(Schema $schema): void
    {
        // Webhook event deduplication table
        $this->addSql('CREATE TABLE webhook_event (id UUID NOT NULL, provider VARCHAR(20) NOT NULL, provider_event_id VARCHAR(255) NOT NULL, event_type VARCHAR(50) NOT NULL, processed_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_webhook_provider_event ON webhook_event (provider, provider_event_id)');

        // Idempotency key for payment deduplication
        $this->addSql('ALTER TABLE payment ADD idempotency_key VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6D28840D7FD1C147 ON payment (idempotency_key)');

        // Notification params (machine-readable context)
        $this->addSql('ALTER TABLE notification ADD params JSON DEFAULT NULL');

        // Check-in/check-out tracking
        $this->addSql('ALTER TABLE booking ADD checked_in_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD checked_out_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE webhook_event');
        $this->addSql('ALTER TABLE payment DROP idempotency_key');
        $this->addSql('ALTER TABLE notification DROP params');
        $this->addSql('ALTER TABLE booking DROP checked_in_at');
        $this->addSql('ALTER TABLE booking DROP checked_out_at');
    }
}
