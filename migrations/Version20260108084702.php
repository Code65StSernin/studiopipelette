<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108084702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Créer la table bto_b et la clé étrangère vers categorie';
    }

    public function up(Schema $schema): void
    {
        // Création table bto_b si absente
        if (!$schema->hasTable('bto_b')) {
            $this->addSql('CREATE TABLE bto_b (id INT AUTO_INCREMENT NOT NULL, categorie_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, remise_par_categorie INT NOT NULL, INDEX IDX_BTOB_CATEGORIE (categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE bto_b ADD CONSTRAINT FK_BTOB_CATEGORIE FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('bto_b')) {
            $this->addSql('DROP TABLE bto_b');
        }
    }
}
