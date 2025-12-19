<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211151938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajouter ON DELETE CASCADE à la contrainte de clé étrangère user_id dans la table address';
    }

    public function up(Schema $schema): void
    {
        // Supprimer l'ancienne contrainte de clé étrangère
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81A76ED395');
        
        // Recréer la contrainte avec ON DELETE CASCADE
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Supprimer la contrainte avec CASCADE
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81A76ED395');
        
        // Recréer la contrainte sans CASCADE
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
