<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260428204858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema with UUID PKs, btree_gist EXCLUDE constraint, indexes and unique constraints';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS btree_gist');
        $this->addSql('CREATE TABLE blocked_date (id UUID NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, reason TEXT DEFAULT NULL, source VARCHAR(10) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, lodging_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_5683F00487335AF1 ON blocked_date (lodging_id)');
        $this->addSql('CREATE INDEX idx_blocked_date_lodging_dates ON blocked_date (lodging_id, start_date, end_date)');
        $this->addSql('CREATE TABLE booking (id UUID NOT NULL, reference VARCHAR(30) NOT NULL, checkin DATE NOT NULL, checkout DATE NOT NULL, guests_count INT NOT NULL, number_of_nights INT NOT NULL, nights_total INT NOT NULL, cleaning_fee INT NOT NULL, tourist_tax_total INT NOT NULL, deposit_amount INT NOT NULL, total_price INT NOT NULL, cancellation_policy VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, cancellation_reason TEXT DEFAULT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, lodging_id UUID NOT NULL, customer_id UUID NOT NULL, cancelled_by_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E00CEDDEAEA34913 ON booking (reference)');
        $this->addSql('CREATE INDEX IDX_E00CEDDE87335AF1 ON booking (lodging_id)');
        $this->addSql('CREATE INDEX IDX_E00CEDDE9395C3F3 ON booking (customer_id)');
        $this->addSql('CREATE INDEX IDX_E00CEDDE187B2D12 ON booking (cancelled_by_id)');
        $this->addSql('CREATE TABLE booking_night (id UUID NOT NULL, date DATE NOT NULL, price INT NOT NULL, source VARCHAR(80) NOT NULL, booking_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_80A6F2223301C60 ON booking_night (booking_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_80A6F2223301C60AA9E377A ON booking_night (booking_id, date)');
        $this->addSql('CREATE TABLE host_legal_identifier (id UUID NOT NULL, type VARCHAR(50) NOT NULL, value VARCHAR(100) NOT NULL, country VARCHAR(2) NOT NULL, is_verified BOOLEAN NOT NULL, verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, host_profile_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_469CEF07646948B1 ON host_legal_identifier (host_profile_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_legal_identifier_profile_type_country ON host_legal_identifier (host_profile_id, type, country)');
        $this->addSql('CREATE TABLE host_profile (id UUID NOT NULL, business_name VARCHAR(200) NOT NULL, legal_form VARCHAR(50) DEFAULT NULL, country VARCHAR(2) NOT NULL, billing_address VARCHAR(255) NOT NULL, billing_city VARCHAR(100) NOT NULL, billing_postal_code VARCHAR(10) NOT NULL, billing_country VARCHAR(2) NOT NULL, timezone VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B6AB863EA76ED395 ON host_profile (user_id)');
        $this->addSql('CREATE TABLE lodging (id UUID NOT NULL, name VARCHAR(150) NOT NULL, type VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, capacity INT NOT NULL, base_price_week INT NOT NULL, base_price_weekend INT NOT NULL, cleaning_fee INT DEFAULT NULL, tourist_tax_per_person INT DEFAULT NULL, deposit_amount INT DEFAULT NULL, cancellation_policy VARCHAR(255) NOT NULL, min_stay INT DEFAULT NULL, max_stay INT DEFAULT NULL, orphan_protection BOOLEAN DEFAULT false NOT NULL, checkin_time TIME(0) WITHOUT TIME ZONE NOT NULL, checkout_time TIME(0) WITHOUT TIME ZONE NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(100) NOT NULL, region VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(10) NOT NULL, country VARCHAR(2) NOT NULL, latitude NUMERIC(10, 7) DEFAULT NULL, longitude NUMERIC(10, 7) DEFAULT NULL, average_rating NUMERIC(3, 2) DEFAULT NULL, review_count INT DEFAULT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, host_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_8D35182A1FB8D185 ON lodging (host_id)');
        $this->addSql('CREATE TABLE lodging_image (id UUID NOT NULL, url TEXT NOT NULL, alt_text VARCHAR(255) NOT NULL, position INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, lodging_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_97BA929087335AF1 ON lodging_image (lodging_id)');
        $this->addSql('CREATE TABLE price_override (id UUID NOT NULL, date DATE NOT NULL, price INT NOT NULL, label VARCHAR(100) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, lodging_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_E0B53DF287335AF1 ON price_override (lodging_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_price_override_lodging_date ON price_override (lodging_id, date)');
        $this->addSql('CREATE TABLE season (id UUID NOT NULL, name VARCHAR(80) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, price_week INT NOT NULL, price_weekend INT NOT NULL, min_stay INT DEFAULT NULL, max_stay INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, lodging_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_F0E45BA987335AF1 ON season (lodging_id)');
        $this->addSql('CREATE INDEX idx_season_lodging_dates ON season (lodging_id, start_date, end_date)');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(20) DEFAULT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, reset_token VARCHAR(100) DEFAULT NULL, reset_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('ALTER TABLE blocked_date ADD CONSTRAINT FK_5683F00487335AF1 FOREIGN KEY (lodging_id) REFERENCES lodging (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE87335AF1 FOREIGN KEY (lodging_id) REFERENCES lodging (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE9395C3F3 FOREIGN KEY (customer_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE187B2D12 FOREIGN KEY (cancelled_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE booking_night ADD CONSTRAINT FK_80A6F2223301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE host_legal_identifier ADD CONSTRAINT FK_469CEF07646948B1 FOREIGN KEY (host_profile_id) REFERENCES host_profile (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE host_profile ADD CONSTRAINT FK_B6AB863EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE lodging ADD CONSTRAINT FK_8D35182A1FB8D185 FOREIGN KEY (host_id) REFERENCES host_profile (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE lodging_image ADD CONSTRAINT FK_97BA929087335AF1 FOREIGN KEY (lodging_id) REFERENCES lodging (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE price_override ADD CONSTRAINT FK_E0B53DF287335AF1 FOREIGN KEY (lodging_id) REFERENCES lodging (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE season ADD CONSTRAINT FK_F0E45BA987335AF1 FOREIGN KEY (lodging_id) REFERENCES lodging (id) NOT DEFERRABLE');

        // EXCLUDE USING gist: prevent double bookings at DB level (defense in depth)
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT booking_no_overlap EXCLUDE USING gist (lodging_id WITH =, daterange(checkin, checkout) WITH &&) WHERE (status NOT IN (\'cancelled\'))');

        // Partial index for pending booking TTL cleanup
        $this->addSql('CREATE INDEX idx_booking_pending_expiry ON booking (expires_at) WHERE status = \'pending\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blocked_date DROP CONSTRAINT FK_5683F00487335AF1');
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT FK_E00CEDDE87335AF1');
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT FK_E00CEDDE9395C3F3');
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT FK_E00CEDDE187B2D12');
        $this->addSql('ALTER TABLE booking_night DROP CONSTRAINT FK_80A6F2223301C60');
        $this->addSql('ALTER TABLE host_legal_identifier DROP CONSTRAINT FK_469CEF07646948B1');
        $this->addSql('ALTER TABLE host_profile DROP CONSTRAINT FK_B6AB863EA76ED395');
        $this->addSql('ALTER TABLE lodging DROP CONSTRAINT FK_8D35182A1FB8D185');
        $this->addSql('ALTER TABLE lodging_image DROP CONSTRAINT FK_97BA929087335AF1');
        $this->addSql('ALTER TABLE price_override DROP CONSTRAINT FK_E0B53DF287335AF1');
        $this->addSql('ALTER TABLE season DROP CONSTRAINT FK_F0E45BA987335AF1');
        $this->addSql('DROP TABLE blocked_date');
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE booking_night');
        $this->addSql('DROP TABLE host_legal_identifier');
        $this->addSql('DROP TABLE host_profile');
        $this->addSql('DROP TABLE lodging');
        $this->addSql('DROP TABLE lodging_image');
        $this->addSql('DROP TABLE price_override');
        $this->addSql('DROP TABLE season');
        $this->addSql('DROP TABLE "user"');
    }
}
