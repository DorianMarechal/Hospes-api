<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504115203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE booking_extra (id UUID NOT NULL, quantity INT NOT NULL, unit_price INT NOT NULL, total_price INT NOT NULL, booking_id UUID NOT NULL, extra_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_DC43F0D03301C60 ON booking_extra (booking_id)');
        $this->addSql('CREATE INDEX IDX_DC43F0D02B959FC6 ON booking_extra (extra_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BOOKING_EXTRA ON booking_extra (booking_id, extra_id)');
        $this->addSql('CREATE TABLE extra (id UUID NOT NULL, name VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL, price INT NOT NULL, price_type VARCHAR(15) NOT NULL, enabled BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, lodging_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_4D3F0D6587335AF1 ON extra (lodging_id)');
        $this->addSql('CREATE TABLE guidebook (id UUID NOT NULL, checkin_instructions TEXT DEFAULT NULL, house_rules TEXT DEFAULT NULL, wifi_name VARCHAR(100) DEFAULT NULL, wifi_password VARCHAR(100) DEFAULT NULL, local_recommendations TEXT DEFAULT NULL, emergency_contacts TEXT DEFAULT NULL, checkout_instructions TEXT DEFAULT NULL, parking_info TEXT DEFAULT NULL, transport_info TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, lodging_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_GUIDEBOOK_LODGING ON guidebook (lodging_id)');
        $this->addSql('ALTER TABLE booking_extra ADD CONSTRAINT FK_DC43F0D03301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE booking_extra ADD CONSTRAINT FK_DC43F0D02B959FC6 FOREIGN KEY (extra_id) REFERENCES extra (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE extra ADD CONSTRAINT FK_4D3F0D6587335AF1 FOREIGN KEY (lodging_id) REFERENCES lodging (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE guidebook ADD CONSTRAINT FK_C83A75D87335AF1 FOREIGN KEY (lodging_id) REFERENCES lodging (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE booking ADD guest_portal_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD guest_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD guest_first_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD guest_last_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD guest_phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E00CEDDEF4EB5F1E ON booking (guest_portal_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking_extra DROP CONSTRAINT FK_DC43F0D03301C60');
        $this->addSql('ALTER TABLE booking_extra DROP CONSTRAINT FK_DC43F0D02B959FC6');
        $this->addSql('ALTER TABLE extra DROP CONSTRAINT FK_4D3F0D6587335AF1');
        $this->addSql('ALTER TABLE guidebook DROP CONSTRAINT FK_C83A75D87335AF1');
        $this->addSql('DROP TABLE booking_extra');
        $this->addSql('DROP TABLE extra');
        $this->addSql('DROP TABLE guidebook');
        $this->addSql('DROP INDEX UNIQ_E00CEDDEF4EB5F1E');
        $this->addSql('ALTER TABLE booking DROP guest_portal_token');
        $this->addSql('ALTER TABLE booking DROP guest_email');
        $this->addSql('ALTER TABLE booking DROP guest_first_name');
        $this->addSql('ALTER TABLE booking DROP guest_last_name');
        $this->addSql('ALTER TABLE booking DROP guest_phone');
    }
}
