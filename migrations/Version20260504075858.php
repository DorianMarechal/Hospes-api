<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504075858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ADD currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL');
        $this->addSql('ALTER TABLE booking_night ADD currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL');
        $this->addSql('ALTER TABLE deposit ADD currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL');
        $this->addSql('ALTER TABLE lodging ADD currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL');
        $this->addSql('ALTER TABLE payment ADD currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP currency');
        $this->addSql('ALTER TABLE booking_night DROP currency');
        $this->addSql('ALTER TABLE deposit DROP currency');
        $this->addSql('ALTER TABLE lodging DROP currency');
        $this->addSql('ALTER TABLE payment DROP currency');
    }
}
