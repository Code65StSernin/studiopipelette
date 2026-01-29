<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260126160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tables pour le fond de caisse et les remises en banque';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE fond_caisse (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, montant DOUBLE PRECISION NOT NULL, cloture TINYINT(1) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE remise_banque (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, montant DOUBLE PRECISION NOT NULL, details LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE fond_caisse');
        $this->addSql('DROP TABLE remise_banque');
    }
}

