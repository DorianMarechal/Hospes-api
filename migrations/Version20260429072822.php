<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429072822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment, deposit tables and payment provider fields on host_profile';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE payment (id UUID NOT NULL, amount INT NOT NULL, type VARCHAR(255) NOT NULL, method VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, provider VARCHAR(20) DEFAULT NULL, provider_transaction_id VARCHAR(255) DEFAULT NULL, refund_reason VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, booking_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_payment_booking ON payment (booking_id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) NOT DEFERRABLE');

        $this->addSql('CREATE TABLE deposit (id UUID NOT NULL, amount INT NOT NULL, status VARCHAR(255) NOT NULL, retained_amount INT DEFAULT 0 NOT NULL, retained_reason VARCHAR(500) DEFAULT NULL, released_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, booking_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_95DB9D393301C60 ON deposit (booking_id)');
        $this->addSql('ALTER TABLE deposit ADD CONSTRAINT FK_95DB9D393301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) NOT DEFERRABLE');

        $this->addSql('ALTER TABLE host_profile ADD payment_provider VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE host_profile ADD payment_provider_account_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE host_profile ADD payment_provider_onboarded_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840D3301C60');
        $this->addSql('DROP TABLE payment');
        $this->addSql('ALTER TABLE deposit DROP CONSTRAINT FK_95DB9D393301C60');
        $this->addSql('DROP TABLE deposit');
        $this->addSql('ALTER TABLE host_profile DROP payment_provider');
        $this->addSql('ALTER TABLE host_profile DROP payment_provider_account_id');
        $this->addSql('ALTER TABLE host_profile DROP payment_provider_onboarded_at');
    }
}
