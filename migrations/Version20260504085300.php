<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504085300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE access_code (id UUID NOT NULL, code VARCHAR(20) NOT NULL, lock_provider VARCHAR(30) DEFAULT NULL, lock_id VARCHAR(100) DEFAULT NULL, valid_from TIMESTAMP(0) WITH TIME ZONE NOT NULL, valid_to TIMESTAMP(0) WITH TIME ZONE NOT NULL, revoked BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, booking_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ACCESS_CODE_BOOKING ON access_code (booking_id)');
        $this->addSql('CREATE TABLE guest (id UUID NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, nationality VARCHAR(2) DEFAULT NULL, birth_date DATE DEFAULT NULL, id_type VARCHAR(20) DEFAULT NULL, id_number VARCHAR(50) DEFAULT NULL, gdpr_consent BOOLEAN NOT NULL, gdpr_consent_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, booking_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_guest_booking ON guest (booking_id)');
        $this->addSql('CREATE TABLE lodging_translation (id UUID NOT NULL, locale VARCHAR(5) NOT NULL, name VARCHAR(150) NOT NULL, description TEXT DEFAULT NULL, lodging_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_15274FE287335AF1 ON lodging_translation (lodging_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_LODGING_TRANSLATION_LOCALE ON lodging_translation (lodging_id, locale)');
        $this->addSql('CREATE TABLE promotion_code (id UUID NOT NULL, code VARCHAR(30) NOT NULL, type VARCHAR(10) NOT NULL, value INT NOT NULL, max_uses INT DEFAULT NULL, uses_count INT NOT NULL, valid_from DATE DEFAULT NULL, valid_to DATE DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, host_profile_id UUID NOT NULL, lodging_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_C1EFB807646948B1 ON promotion_code (host_profile_id)');
        $this->addSql('CREATE INDEX IDX_C1EFB80787335AF1 ON promotion_code (lodging_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PROMO_CODE ON promotion_code (code)');
        $this->addSql('ALTER TABLE access_code ADD CONSTRAINT FK_81CC569E3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE guest ADD CONSTRAINT FK_ACB79A353301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE lodging_translation ADD CONSTRAINT FK_15274FE287335AF1 FOREIGN KEY (lodging_id) REFERENCES lodging (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE promotion_code ADD CONSTRAINT FK_C1EFB807646948B1 FOREIGN KEY (host_profile_id) REFERENCES host_profile (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE promotion_code ADD CONSTRAINT FK_C1EFB80787335AF1 FOREIGN KEY (lodging_id) REFERENCES lodging (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE booking ADD discount_amount INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE booking ADD promotion_code VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD preferred_locale VARCHAR(5) DEFAULT \'fr\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE access_code DROP CONSTRAINT FK_81CC569E3301C60');
        $this->addSql('ALTER TABLE guest DROP CONSTRAINT FK_ACB79A353301C60');
        $this->addSql('ALTER TABLE lodging_translation DROP CONSTRAINT FK_15274FE287335AF1');
        $this->addSql('ALTER TABLE promotion_code DROP CONSTRAINT FK_C1EFB807646948B1');
        $this->addSql('ALTER TABLE promotion_code DROP CONSTRAINT FK_C1EFB80787335AF1');
        $this->addSql('DROP TABLE access_code');
        $this->addSql('DROP TABLE guest');
        $this->addSql('DROP TABLE lodging_translation');
        $this->addSql('DROP TABLE promotion_code');
        $this->addSql('ALTER TABLE booking DROP discount_amount');
        $this->addSql('ALTER TABLE booking DROP promotion_code');
        $this->addSql('ALTER TABLE "user" DROP preferred_locale');
    }
}
