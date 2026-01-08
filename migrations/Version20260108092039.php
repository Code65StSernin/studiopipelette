<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108092039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $facture = $schema->getTable('facture');
        if (!$facture->hasColumn('btob_remise_cents')) {
            $this->addSql('ALTER TABLE facture ADD btob_remise_cents INT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE facture DROP btob_remise_cents');
    }
}
