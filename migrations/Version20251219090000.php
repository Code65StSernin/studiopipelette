<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251219090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs origine sur Depenses/Recette et des indicateurs de transfert Stripe sur Facture';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE depenses ADD origine VARCHAR(50) DEFAULT NULL");
        $this->addSql("ALTER TABLE recette ADD origine VARCHAR(50) DEFAULT NULL");
        $this->addSql("ALTER TABLE facture ADD stripe_transfere TINYINT(1) NOT NULL DEFAULT 0, ADD stripe_transfer_batch_id VARCHAR(100) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE depenses DROP origine");
        $this->addSql("ALTER TABLE recette DROP origine");
        $this->addSql("ALTER TABLE facture DROP stripe_transfere, DROP stripe_transfer_batch_id");
    }
}
