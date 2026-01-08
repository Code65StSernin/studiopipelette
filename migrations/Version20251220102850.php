<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251220102850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplacer Finition par ArticleCollection, supprimer poids_grammes';
    }

    public function up(Schema $schema): void
    {
        // Créer la table collection (pour ArticleCollection)
        if (!$schema->hasTable('collection')) {
            $this->addSql('CREATE TABLE collection (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
        
        // Créer la table de jointure collection_couleur
        if (!$schema->hasTable('collection_couleur')) {
            $this->addSql('CREATE TABLE collection_couleur (collection_id INT NOT NULL, couleur_id INT NOT NULL, INDEX IDX_COLLECTION_COULEUR_COLLECTION (collection_id), INDEX IDX_COLLECTION_COULEUR_COULEUR (couleur_id), PRIMARY KEY(collection_id, couleur_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE collection_couleur ADD CONSTRAINT FK_COLLECTION_COULEUR_COLLECTION FOREIGN KEY (collection_id) REFERENCES collection (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE collection_couleur ADD CONSTRAINT FK_COLLECTION_COULEUR_COULEUR FOREIGN KEY (couleur_id) REFERENCES couleur (id) ON DELETE CASCADE');
        }
        
        // Créer la table article_collection si elle n'existe pas
        if (!$schema->hasTable('article_collection')) {
            $this->addSql('CREATE TABLE article_collection (article_id INT NOT NULL, collection_id INT NOT NULL, INDEX IDX_ARTICLE_COLLECTION_ARTICLE (article_id), INDEX IDX_ARTICLE_COLLECTION_COLLECTION (collection_id), PRIMARY KEY(article_id, collection_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE article_collection ADD CONSTRAINT FK_ARTICLE_COLLECTION_ARTICLE FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE article_collection ADD CONSTRAINT FK_ARTICLE_COLLECTION_COLLECTION FOREIGN KEY (collection_id) REFERENCES collection (id) ON DELETE CASCADE');
        }
        
        // Supprimer la colonne poids_grammes de article si elle existe
        $table = $schema->getTable('article');
        if ($table->hasColumn('poids_grammes')) {
            $this->addSql('ALTER TABLE article DROP COLUMN poids_grammes');
        }
        
        // Supprimer les tables finition et article_finition si elles existent
        if ($schema->hasTable('article_finition')) {
            $this->addSql('DROP TABLE article_finition');
        }
        if ($schema->hasTable('finition')) {
            $this->addSql('DROP TABLE finition');
        }
    }

    public function down(Schema $schema): void
    {
        // Recréer la colonne poids_grammes
        $table = $schema->getTable('article');
        if (!$table->hasColumn('poids_grammes')) {
            $this->addSql('ALTER TABLE article ADD poids_grammes DOUBLE PRECISION DEFAULT NULL');
        }
        
        // Recréer les tables finition
        if (!$schema->hasTable('finition')) {
            $this->addSql('CREATE TABLE finition (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
        if (!$schema->hasTable('article_finition')) {
            $this->addSql('CREATE TABLE article_finition (article_id INT NOT NULL, finition_id INT NOT NULL, INDEX IDX_ARTICLE_FINITION_ARTICLE (article_id), INDEX IDX_ARTICLE_FINITION_FINITION (finition_id), PRIMARY KEY(article_id, finition_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE article_finition ADD CONSTRAINT FK_ARTICLE_FINITION_ARTICLE FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE article_finition ADD CONSTRAINT FK_ARTICLE_FINITION_FINITION FOREIGN KEY (finition_id) REFERENCES finition (id) ON DELETE CASCADE');
        }
        
        // Supprimer les tables collection
        if ($schema->hasTable('article_collection')) {
            $this->addSql('DROP TABLE article_collection');
        }
        if ($schema->hasTable('collection_couleur')) {
            $this->addSql('DROP TABLE collection_couleur');
        }
        if ($schema->hasTable('collection')) {
            $this->addSql('DROP TABLE collection');
        }
    }
}
