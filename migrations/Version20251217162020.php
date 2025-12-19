<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251217162020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Ajouter les colonnes pour les charges sociales et fiscales
        $this->addSql("
            SET @dbname = DATABASE();
            SET @tablename = 'societe';
            SET @columnname = 'pourcentage_urssaf';
            SET @preparedStatement = (SELECT IF(
              (
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE
                  (table_name = @tablename)
                  AND (table_schema = @dbname)
                  AND (column_name = @columnname)
              ) > 0,
              'SELECT 1',
              CONCAT('ALTER TABLE ', @tablename, ' ADD ', @columnname, ' DOUBLE PRECISION DEFAULT NULL')
            ));
            PREPARE alterIfNotExists FROM @preparedStatement;
            EXECUTE alterIfNotExists;
            DEALLOCATE PREPARE alterIfNotExists;
        ");
        
        $this->addSql("
            SET @dbname = DATABASE();
            SET @tablename = 'societe';
            SET @columnname = 'pourcentage_cpf';
            SET @preparedStatement = (SELECT IF(
              (
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE
                  (table_name = @tablename)
                  AND (table_schema = @dbname)
                  AND (column_name = @columnname)
              ) > 0,
              'SELECT 1',
              CONCAT('ALTER TABLE ', @tablename, ' ADD ', @columnname, ' DOUBLE PRECISION DEFAULT NULL')
            ));
            PREPARE alterIfNotExists FROM @preparedStatement;
            EXECUTE alterIfNotExists;
            DEALLOCATE PREPARE alterIfNotExists;
        ");
        
        $this->addSql("
            SET @dbname = DATABASE();
            SET @tablename = 'societe';
            SET @columnname = 'pourcentage_ir';
            SET @preparedStatement = (SELECT IF(
              (
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE
                  (table_name = @tablename)
                  AND (table_schema = @dbname)
                  AND (column_name = @columnname)
              ) > 0,
              'SELECT 1',
              CONCAT('ALTER TABLE ', @tablename, ' ADD ', @columnname, ' DOUBLE PRECISION DEFAULT NULL')
            ));
            PREPARE alterIfNotExists FROM @preparedStatement;
            EXECUTE alterIfNotExists;
            DEALLOCATE PREPARE alterIfNotExists;
        ");
    }

    public function down(Schema $schema): void
    {
        // Supprimer les colonnes pour les charges sociales et fiscales
        $this->addSql('ALTER TABLE societe DROP COLUMN IF EXISTS pourcentage_urssaf, DROP COLUMN IF EXISTS pourcentage_cpf, DROP COLUMN IF EXISTS pourcentage_ir');
    }
}
