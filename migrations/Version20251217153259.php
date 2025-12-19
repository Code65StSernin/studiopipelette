<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251217153259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Ajouter uniquement les colonnes manquantes à societe
        // Les autres contraintes et index sont déjà gérés par les migrations précédentes
        $this->addSql("
            SET @dbname = DATABASE();
            SET @tablename = 'societe';
            SET @columnname = 'code_postal';
            SET @preparedStatement = (SELECT IF(
              (
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE
                  (table_name = @tablename)
                  AND (table_schema = @dbname)
                  AND (column_name = @columnname)
              ) > 0,
              'SELECT 1',
              CONCAT('ALTER TABLE ', @tablename, ' ADD ', @columnname, ' VARCHAR(10) DEFAULT NULL')
            ));
            PREPARE alterIfNotExists FROM @preparedStatement;
            EXECUTE alterIfNotExists;
            DEALLOCATE PREPARE alterIfNotExists;
        ");
        
        $this->addSql("
            SET @dbname = DATABASE();
            SET @tablename = 'societe';
            SET @columnname = 'ville';
            SET @preparedStatement = (SELECT IF(
              (
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE
                  (table_name = @tablename)
                  AND (table_schema = @dbname)
                  AND (column_name = @columnname)
              ) > 0,
              'SELECT 1',
              CONCAT('ALTER TABLE ', @tablename, ' ADD ', @columnname, ' VARCHAR(100) DEFAULT NULL')
            ));
            PREPARE alterIfNotExists FROM @preparedStatement;
            EXECUTE alterIfNotExists;
            DEALLOCATE PREPARE alterIfNotExists;
        ");
        
        $this->addSql("
            SET @dbname = DATABASE();
            SET @tablename = 'societe';
            SET @columnname = 'stripe_webhook_secret';
            SET @preparedStatement = (SELECT IF(
              (
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE
                  (table_name = @tablename)
                  AND (table_schema = @dbname)
                  AND (column_name = @columnname)
              ) > 0,
              'SELECT 1',
              CONCAT('ALTER TABLE ', @tablename, ' ADD ', @columnname, ' VARCHAR(255) DEFAULT NULL')
            ));
            PREPARE alterIfNotExists FROM @preparedStatement;
            EXECUTE alterIfNotExists;
            DEALLOCATE PREPARE alterIfNotExists;
        ");
    }

    public function down(Schema $schema): void
    {
        // Supprimer les colonnes ajoutées à societe
        $this->addSql('ALTER TABLE societe DROP COLUMN IF EXISTS code_postal, DROP COLUMN IF EXISTS ville, DROP COLUMN IF EXISTS stripe_webhook_secret');
    }
}
