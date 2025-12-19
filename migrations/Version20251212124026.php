<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212124026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article_couleur (article_id INT NOT NULL, couleur_id INT NOT NULL, INDEX IDX_D1DFC3527294869C (article_id), INDEX IDX_D1DFC352C31BA576 (couleur_id), PRIMARY KEY (article_id, couleur_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE couleur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE article_couleur ADD CONSTRAINT FK_D1DFC3527294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_couleur ADD CONSTRAINT FK_D1DFC352C31BA576 FOREIGN KEY (couleur_id) REFERENCES couleur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66365BF48 FOREIGN KEY (sous_categorie_id) REFERENCES sous_categorie (id)');
        $this->addSql('ALTER TABLE article_parfum ADD CONSTRAINT FK_98522ACC7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_parfum ADD CONSTRAINT FK_98522ACCCECF0658 FOREIGN KEY (parfum_id) REFERENCES parfum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784187294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article_couleur DROP FOREIGN KEY FK_D1DFC3527294869C');
        $this->addSql('ALTER TABLE article_couleur DROP FOREIGN KEY FK_D1DFC352C31BA576');
        $this->addSql('DROP TABLE article_couleur');
        $this->addSql('DROP TABLE couleur');
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81A76ED395');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66BCF5E72D');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66365BF48');
        $this->addSql('ALTER TABLE article_parfum DROP FOREIGN KEY FK_98522ACC7294869C');
        $this->addSql('ALTER TABLE article_parfum DROP FOREIGN KEY FK_98522ACCCECF0658');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784187294869C');
    }
}
