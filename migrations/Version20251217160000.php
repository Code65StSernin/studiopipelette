<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class Version20251217160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des slugs pour les catégories et sous-catégories';
    }

    public function up(Schema $schema): void
    {
        $slugger = new AsciiSlugger('fr');

        // Générer les slugs pour les catégories
        $categories = $this->connection->fetchAllAssociative('SELECT id, nom FROM categorie');
        $usedCatSlugs = [];

        foreach ($categories as $row) {
            $id = (int) $row['id'];
            $nom = (string) ($row['nom'] ?? ('categorie-' . $id));

            $baseSlug = strtolower($slugger->slug($nom)->toString());
            if ($baseSlug === '') {
                $baseSlug = 'categorie-' . $id;
            }

            $slug = $baseSlug;
            $i = 1;
            while (in_array($slug, $usedCatSlugs, true)) {
                $slug = $baseSlug . '-' . $i;
                ++$i;
            }
            $usedCatSlugs[] = $slug;

            $this->addSql(
                "UPDATE categorie SET slug = :slug WHERE id = :id",
                ['slug' => $slug, 'id' => $id]
            );
        }

        // Générer les slugs pour les sous-catégories
        $sousCategories = $this->connection->fetchAllAssociative('SELECT id, nom FROM sous_categorie');
        $usedSousSlugs = [];

        foreach ($sousCategories as $row) {
            $id = (int) $row['id'];
            $nom = (string) ($row['nom'] ?? ('sous-categorie-' . $id));

            $baseSlug = strtolower($slugger->slug($nom)->toString());
            if ($baseSlug === '') {
                $baseSlug = 'sous-categorie-' . $id;
            }

            $slug = $baseSlug;
            $i = 1;
            while (in_array($slug, $usedSousSlugs, true)) {
                $slug = $baseSlug . '-' . $i;
                ++$i;
            }
            $usedSousSlugs[] = $slug;

            $this->addSql(
                "UPDATE sous_categorie SET slug = :slug WHERE id = :id",
                ['slug' => $slug, 'id' => $id]
            );
        }

        // Rendre les slugs NOT NULL (on ne crée pas d'index unique pour éviter les problèmes de longueur de clé)
        $this->addSql('ALTER TABLE categorie CHANGE slug slug VARCHAR(150) NOT NULL');
        $this->addSql('ALTER TABLE sous_categorie CHANGE slug slug VARCHAR(150) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Pas d'opération inverse spécifique : les colonnes slug sont gérées
        // par la migration précédente (Version20251217142328).
    }
}

