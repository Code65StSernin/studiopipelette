<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211150204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address CHANGE street_number street_number VARCHAR(5) NOT NULL, CHANGE street street VARCHAR(90) NOT NULL, CHANGE complement complement VARCHAR(90) DEFAULT NULL, CHANGE postal_code postal_code VARCHAR(5) NOT NULL, CHANGE city city VARCHAR(30) NOT NULL');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81A76ED395');
        $this->addSql('ALTER TABLE address CHANGE street_number street_number VARCHAR(10) NOT NULL, CHANGE street street VARCHAR(100) NOT NULL, CHANGE complement complement VARCHAR(100) DEFAULT NULL, CHANGE postal_code postal_code VARCHAR(10) NOT NULL, CHANGE city city VARCHAR(100) NOT NULL');
    }
}
