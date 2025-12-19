<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251216152634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cgv (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cookie_policy (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE privacy_policy (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        // Contenus par défaut (Lorem ipsum) pour les pages légales
        $this->addSql('INSERT INTO cgv (content) VALUES (\'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed non risus. Suspendisse lectus tortor, dignissim sit amet, adipiscing nec, ultricies sed, dolor.</p><p>Cras elementum ultrices diam. Maecenas ligula massa, varius a, semper congue, euismod non, mi.</p>\')');
        $this->addSql('INSERT INTO privacy_policy (content) VALUES (\'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam.</p><p>Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris.</p>\')');
        $this->addSql('INSERT INTO cookie_policy (content) VALUES (\'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet.</p><p>Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta.</p>\')');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66365BF48 FOREIGN KEY (sous_categorie_id) REFERENCES sous_categorie (id)');
        $this->addSql('ALTER TABLE article_parfum ADD CONSTRAINT FK_98522ACC7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_parfum ADD CONSTRAINT FK_98522ACCCECF0658 FOREIGN KEY (parfum_id) REFERENCES parfum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_couleur ADD CONSTRAINT FK_D1DFC3527294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_couleur ADD CONSTRAINT FK_D1DFC352C31BA576 FOREIGN KEY (couleur_id) REFERENCES couleur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_FE8664108D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori_article ADD CONSTRAINT FK_6FB6AA41FF17033F FOREIGN KEY (favori_id) REFERENCES favori (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori_article ADD CONSTRAINT FK_6FB6AA417294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ligne_facture ADD CONSTRAINT FK_611F5A297F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_ligne ADD CONSTRAINT FK_7EDDF43EF77D927C FOREIGN KEY (panier_id) REFERENCES panier (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_ligne ADD CONSTRAINT FK_7EDDF43E7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784187294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE cgv');
        $this->addSql('DROP TABLE cookie_policy');
        $this->addSql('DROP TABLE privacy_policy');
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81A76ED395');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66BCF5E72D');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66365BF48');
        $this->addSql('ALTER TABLE article_couleur DROP FOREIGN KEY FK_D1DFC3527294869C');
        $this->addSql('ALTER TABLE article_couleur DROP FOREIGN KEY FK_D1DFC352C31BA576');
        $this->addSql('ALTER TABLE article_parfum DROP FOREIGN KEY FK_98522ACC7294869C');
        $this->addSql('ALTER TABLE article_parfum DROP FOREIGN KEY FK_98522ACCCECF0658');
        $this->addSql('ALTER TABLE facture DROP FOREIGN KEY FK_FE8664108D9F6D38');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCA76ED395');
        $this->addSql('ALTER TABLE favori_article DROP FOREIGN KEY FK_6FB6AA41FF17033F');
        $this->addSql('ALTER TABLE favori_article DROP FOREIGN KEY FK_6FB6AA417294869C');
        $this->addSql('ALTER TABLE ligne_facture DROP FOREIGN KEY FK_611F5A297F2DEE08');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2A76ED395');
        $this->addSql('ALTER TABLE panier_ligne DROP FOREIGN KEY FK_7EDDF43EF77D927C');
        $this->addSql('ALTER TABLE panier_ligne DROP FOREIGN KEY FK_7EDDF43E7294869C');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784187294869C');
    }
}
