<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212181048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE panier (id INT AUTO_INCREMENT NOT NULL, session_id VARCHAR(191) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_24CC0DF2613FECDF (session_id), INDEX IDX_24CC0DF2A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE panier_ligne (id INT AUTO_INCREMENT NOT NULL, taille VARCHAR(50) NOT NULL, quantite INT NOT NULL, prix_unitaire DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, panier_id INT NOT NULL, article_id INT NOT NULL, INDEX IDX_7EDDF43EF77D927C (panier_id), INDEX IDX_7EDDF43E7294869C (article_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_ligne ADD CONSTRAINT FK_7EDDF43EF77D927C FOREIGN KEY (panier_id) REFERENCES panier (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_ligne ADD CONSTRAINT FK_7EDDF43E7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66365BF48 FOREIGN KEY (sous_categorie_id) REFERENCES sous_categorie (id)');
        $this->addSql('ALTER TABLE article_parfum ADD CONSTRAINT FK_98522ACC7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_parfum ADD CONSTRAINT FK_98522ACCCECF0658 FOREIGN KEY (parfum_id) REFERENCES parfum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_couleur ADD CONSTRAINT FK_D1DFC3527294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_couleur ADD CONSTRAINT FK_D1DFC352C31BA576 FOREIGN KEY (couleur_id) REFERENCES couleur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784187294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2A76ED395');
        $this->addSql('ALTER TABLE panier_ligne DROP FOREIGN KEY FK_7EDDF43EF77D927C');
        $this->addSql('ALTER TABLE panier_ligne DROP FOREIGN KEY FK_7EDDF43E7294869C');
        $this->addSql('DROP TABLE panier');
        $this->addSql('DROP TABLE panier_ligne');
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81A76ED395');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66BCF5E72D');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66365BF48');
        $this->addSql('ALTER TABLE article_couleur DROP FOREIGN KEY FK_D1DFC3527294869C');
        $this->addSql('ALTER TABLE article_couleur DROP FOREIGN KEY FK_D1DFC352C31BA576');
        $this->addSql('ALTER TABLE article_parfum DROP FOREIGN KEY FK_98522ACC7294869C');
        $this->addSql('ALTER TABLE article_parfum DROP FOREIGN KEY FK_98522ACCCECF0658');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784187294869C');
    }
}
