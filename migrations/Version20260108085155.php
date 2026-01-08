<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108085155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Transformer la relation BtoB -> Categorie en ManyToMany';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('btob_categorie')) {
            $this->addSql('CREATE TABLE btob_categorie (bto_b_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_BTOB_CATEGORIE_BTOB (bto_b_id), INDEX IDX_BTOB_CATEGORIE_CATEGORIE (categorie_id), PRIMARY KEY (bto_b_id, categorie_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE btob_categorie ADD CONSTRAINT FK_BTOB_CATEGORIE_BTOB FOREIGN KEY (bto_b_id) REFERENCES bto_b (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE btob_categorie ADD CONSTRAINT FK_BTOB_CATEGORIE_CATEGORIE FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
        }
        $btoB = $schema->getTable('bto_b');
        if ($btoB->hasIndex('IDX_1C992909BCF5E72D')) {
            $this->addSql('DROP INDEX IDX_1C992909BCF5E72D ON bto_b');
        }
        if ($btoB->hasColumn('categorie_id')) {
            $this->addSql('ALTER TABLE bto_b DROP categorie_id');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('btob_categorie')) {
            $this->addSql('DROP TABLE btob_categorie');
        }
        $btoB = $schema->getTable('bto_b');
        if (!$btoB->hasColumn('categorie_id')) {
            $this->addSql('ALTER TABLE bto_b ADD categorie_id INT DEFAULT NULL');
        }
        if (!$btoB->hasIndex('IDX_1C992909BCF5E72D')) {
            $this->addSql('CREATE INDEX IDX_1C992909BCF5E72D ON bto_b (categorie_id)');
        }
    }
}
