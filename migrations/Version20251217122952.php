<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251217122952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1) Ajouter la colonne slug (temporairement nullable)
        $this->addSql('ALTER TABLE article ADD slug VARCHAR(255) DEFAULT NULL');

        // 2) Générer un slug unique pour chaque article existant
        $slugger = new AsciiSlugger('fr');
        $articles = $this->connection->fetchAllAssociative('SELECT id, nom FROM article');

        $usedSlugs = [];

        foreach ($articles as $row) {
            $id = (int) $row['id'];
            $nom = (string) ($row['nom'] ?? ('article-' . $id));

            $baseSlug = strtolower($slugger->slug($nom)->toString());
            if ($baseSlug === '') {
                $baseSlug = 'article-' . $id;
            }

            $slug = $baseSlug;
            $i = 1;
            while (in_array($slug, $usedSlugs, true)) {
                $slug = $baseSlug . '-' . $i;
                $i++;
            }
            $usedSlugs[] = $slug;

            $this->addSql(sprintf(
                "UPDATE article SET slug = '%s' WHERE id = %d",
                addslashes($slug),
                $id
            ));
        }

        // 3) Rendre le slug NOT NULL et unique
        $this->addSql('ALTER TABLE article CHANGE slug slug VARCHAR(255) NOT NULL');
        // Sur MySQL en utf8mb4, on limite l’index à 191 caractères
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ARTICLE_SLUG ON article (slug(191))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_ARTICLE_SLUG ON article');
        $this->addSql('ALTER TABLE article DROP slug');
    }
}