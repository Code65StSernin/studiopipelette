<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute CouleurFond/CouleurTexte aux tarifs et initialise des couleurs lisibles (sans orange).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tarif ADD couleur_fond VARCHAR(7) DEFAULT NULL, ADD couleur_texte VARCHAR(7) DEFAULT NULL');

        $ids = $this->connection->fetchFirstColumn('SELECT id FROM tarif');
        foreach ($ids as $id) {
            $bg = $this->pickColorForId((int) $id);
            $text = $this->textColorForBackground($bg);
            $this->addSql('UPDATE tarif SET couleur_fond = ?, couleur_texte = ? WHERE id = ?', [$bg, $text, (int) $id]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tarif DROP couleur_fond, DROP couleur_texte');
    }

    private function pickColorForId(int $id): string
    {
        $palette = $this->palette();
        $idx = (int) (abs(crc32((string) $id)) % count($palette));
        return $palette[$idx];
    }

    private function palette(): array
    {
        return [
            '#1E88E5',
            '#3949AB',
            '#5E35B1',
            '#8E24AA',
            '#C2185B',
            '#00897B',
            '#00ACC1',
            '#43A047',
            '#7CB342',
            '#9E9D24',
            '#6D4C41',
            '#546E7A',
            '#E53935',
        ];
    }

    private function textColorForBackground(string $hex): string
    {
        $hex = strtoupper(trim($hex));
        if (str_starts_with($hex, '#')) {
            $hex = substr($hex, 1);
        }
        if (strlen($hex) !== 6) {
            return '#FFFFFF';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $l = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $l > 0.6 ? '#000000' : '#FFFFFF';
    }
}

