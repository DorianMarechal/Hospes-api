<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260420113637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE price_override ADD lodging_id INT NOT NULL');
        $this->addSql('ALTER TABLE price_override DROP lodging');
        $this->addSql('ALTER TABLE price_override ADD CONSTRAINT FK_E0B53DF287335AF1 FOREIGN KEY (lodging_id) REFERENCES lodging (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_E0B53DF287335AF1 ON price_override (lodging_id)');
        $this->addSql('ALTER TABLE season ALTER lodging_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE price_override DROP CONSTRAINT FK_E0B53DF287335AF1');
        $this->addSql('DROP INDEX IDX_E0B53DF287335AF1');
        $this->addSql('ALTER TABLE price_override ADD lodging VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE price_override DROP lodging_id');
        $this->addSql('ALTER TABLE season ALTER lodging_id DROP NOT NULL');
    }
}
