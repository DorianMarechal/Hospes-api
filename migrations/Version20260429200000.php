<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add booking_modification_request table for double validation workflow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE booking_modification_request (id UUID NOT NULL, booking_id UUID NOT NULL, requested_by_id UUID NOT NULL, proposed_checkin DATE NOT NULL, proposed_checkout DATE NOT NULL, proposed_number_of_nights INT NOT NULL, proposed_nights_total INT NOT NULL, proposed_cleaning_fee INT NOT NULL, proposed_tourist_tax_total INT NOT NULL, proposed_deposit_amount INT NOT NULL, proposed_total_price INT NOT NULL, status VARCHAR(20) NOT NULL, expires_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, responded_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_modification_request_booking_status ON booking_modification_request (booking_id, status)');
        $this->addSql('ALTER TABLE booking_modification_request ADD CONSTRAINT FK_MOD_REQ_BOOKING FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE booking_modification_request ADD CONSTRAINT FK_MOD_REQ_USER FOREIGN KEY (requested_by_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking_modification_request DROP CONSTRAINT FK_MOD_REQ_BOOKING');
        $this->addSql('ALTER TABLE booking_modification_request DROP CONSTRAINT FK_MOD_REQ_USER');
        $this->addSql('DROP TABLE booking_modification_request');
    }
}
