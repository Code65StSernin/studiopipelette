<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251217170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table societe pour les paramètres d\'entreprise et de configuration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS societe (
                id INT AUTO_INCREMENT NOT NULL,
                nom VARCHAR(255) NOT NULL,
                adresse VARCHAR(255) DEFAULT NULL,
                code_postal VARCHAR(10) DEFAULT NULL,
                ville VARCHAR(100) DEFAULT NULL,
                telephone VARCHAR(50) DEFAULT NULL,
                email VARCHAR(180) DEFAULT NULL,
                siret VARCHAR(50) DEFAULT NULL,
                code_naf VARCHAR(20) DEFAULT NULL,
                mondial_relay_login VARCHAR(100) DEFAULT NULL,
                mondial_relay_password VARCHAR(100) DEFAULT NULL,
                mondial_relay_customer_id VARCHAR(50) DEFAULT NULL,
                mondial_relay_brand VARCHAR(50) DEFAULT NULL,
                stripe_public_key VARCHAR(255) DEFAULT NULL,
                stripe_secret_key VARCHAR(255) DEFAULT NULL,
                stripe_webhook_secret VARCHAR(255) DEFAULT NULL,
                db_host VARCHAR(100) DEFAULT NULL,
                db_name VARCHAR(100) DEFAULT NULL,
                db_user VARCHAR(100) DEFAULT NULL,
                db_password VARCHAR(255) DEFAULT NULL,
                smtp_user VARCHAR(180) DEFAULT NULL,
                smtp_password VARCHAR(255) DEFAULT NULL,
                smtp_host VARCHAR(255) DEFAULT NULL,
                smtp_port INT DEFAULT NULL,
                smtp_from_email VARCHAR(180) DEFAULT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE societe');
    }
}

