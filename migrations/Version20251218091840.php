<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251218091840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table recette (recettes diverses)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE recette (
            id INT AUTO_INCREMENT NOT NULL,
            date DATE NOT NULL,
            objet VARCHAR(255) NOT NULL,
            montant DOUBLE PRECISION NOT NULL,
            pointage TINYINT(1) NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE recette');
    }
}
