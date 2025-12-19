<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251218075429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Ajout du champ booléen "debloque" sur la table `order`
        $this->addSql('ALTER TABLE `order` ADD debloque TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // Suppression du champ "debloque"
        $this->addSql('ALTER TABLE `order` DROP debloque');
    }
}
