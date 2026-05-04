<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504091538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE insurance_claim (id UUID NOT NULL, provider VARCHAR(30) NOT NULL, policy_id VARCHAR(255) DEFAULT NULL, claim_id VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, reason TEXT DEFAULT NULL, claim_amount INT DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, booking_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_insurance_claim_booking ON insurance_claim (booking_id)');
        $this->addSql('ALTER TABLE insurance_claim ADD CONSTRAINT FK_8BDE4243301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE booking ADD insurance_proposed BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE insurance_claim DROP CONSTRAINT FK_8BDE4243301C60');
        $this->addSql('DROP TABLE insurance_claim');
        $this->addSql('ALTER TABLE booking DROP insurance_proposed');
    }
}
