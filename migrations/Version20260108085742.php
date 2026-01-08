<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108085742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add relation between User and BtoB';
    }

    public function up(Schema $schema): void
    {
        // Check if column already exists to avoid errors
        $table = $schema->getTable('user');
        if (!$table->hasColumn('bto_b_id')) {
            $this->addSql('ALTER TABLE user ADD bto_b_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649A4054AA8 FOREIGN KEY (bto_b_id) REFERENCES bto_b (id)');
            $this->addSql('CREATE INDEX IDX_8D93D649A4054AA8 ON user (bto_b_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649A4054AA8');
        $this->addSql('DROP INDEX IDX_8D93D649A4054AA8 ON user');
        $this->addSql('ALTER TABLE user DROP bto_b_id');
    }
}
