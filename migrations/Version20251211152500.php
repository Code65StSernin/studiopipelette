<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour affecter explicitement ROLE_USER à tous les utilisateurs existants
 */
final class Version20251211152500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Affecter explicitement ROLE_USER à tous les utilisateurs qui n\'ont pas de rôle défini';
    }

    public function up(Schema $schema): void
    {
        // Mettre à jour tous les utilisateurs qui ont un tableau de rôles vide
        $this->addSql('UPDATE user SET roles = \'["ROLE_USER"]\' WHERE roles = \'[]\' OR roles IS NULL OR roles = \'null\'');
    }

    public function down(Schema $schema): void
    {
        // Optionnel : remettre les rôles vides
        $this->addSql('UPDATE user SET roles = \'[]\' WHERE roles = \'["ROLE_USER"]\'');
    }
}

