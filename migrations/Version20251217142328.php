<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251217142328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        // Adapter les index pour MySQL utf8mb4 (limite de longueur de clé)
        $this->addSql('ALTER TABLE article DROP INDEX UNIQ_23A0E66989D9B62, ADD UNIQUE INDEX UNIQ_23A0E66989D9B62 (slug(191))');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66365BF48 FOREIGN KEY (sous_categorie_id) REFERENCES sous_categorie (id)');
        $this->addSql('ALTER TABLE article_parfum ADD CONSTRAINT FK_98522ACC7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_parfum ADD CONSTRAINT FK_98522ACCCECF0658 FOREIGN KEY (parfum_id) REFERENCES parfum (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_couleur ADD CONSTRAINT FK_D1DFC3527294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_couleur ADD CONSTRAINT FK_D1DFC352C31BA576 FOREIGN KEY (couleur_id) REFERENCES couleur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categorie ADD slug VARCHAR(150) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_497DD634989D9B62 ON categorie (slug(100))');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_FE8664108D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori_article ADD CONSTRAINT FK_6FB6AA41FF17033F FOREIGN KEY (favori_id) REFERENCES favori (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori_article ADD CONSTRAINT FK_6FB6AA417294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ligne_facture ADD CONSTRAINT FK_611F5A297F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2294102D4 FOREIGN KEY (code_promo_id) REFERENCES code (id)');
        $this->addSql('ALTER TABLE panier_ligne ADD CONSTRAINT FK_7EDDF43EF77D927C FOREIGN KEY (panier_id) REFERENCES panier (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_ligne ADD CONSTRAINT FK_7EDDF43E7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784187294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sous_categorie ADD slug VARCHAR(150) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_52743D7B989D9B62 ON sous_categorie (slug(100))');
        $this->addSql('ALTER TABLE user_used_codes ADD CONSTRAINT FK_A406550DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_used_codes ADD CONSTRAINT FK_A406550D27DAFE17 FOREIGN KEY (code_id) REFERENCES code (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81A76ED395');
        $this->addSql('ALTER TABLE article DROP INDEX UNIQ_23A0E66989D9B62, ADD UNIQUE INDEX UNIQ_23A0E66989D9B62 (slug(191))');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66BCF5E72D');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66365BF48');
        $this->addSql('ALTER TABLE article_couleur DROP FOREIGN KEY FK_D1DFC3527294869C');
        $this->addSql('ALTER TABLE article_couleur DROP FOREIGN KEY FK_D1DFC352C31BA576');
        $this->addSql('ALTER TABLE article_parfum DROP FOREIGN KEY FK_98522ACC7294869C');
        $this->addSql('ALTER TABLE article_parfum DROP FOREIGN KEY FK_98522ACCCECF0658');
        $this->addSql('DROP INDEX UNIQ_497DD634989D9B62 ON categorie');
        $this->addSql('ALTER TABLE categorie DROP slug');
        $this->addSql('ALTER TABLE facture DROP FOREIGN KEY FK_FE8664108D9F6D38');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCA76ED395');
        $this->addSql('ALTER TABLE favori_article DROP FOREIGN KEY FK_6FB6AA41FF17033F');
        $this->addSql('ALTER TABLE favori_article DROP FOREIGN KEY FK_6FB6AA417294869C');
        $this->addSql('ALTER TABLE ligne_facture DROP FOREIGN KEY FK_611F5A297F2DEE08');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2A76ED395');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2294102D4');
        $this->addSql('ALTER TABLE panier_ligne DROP FOREIGN KEY FK_7EDDF43EF77D927C');
        $this->addSql('ALTER TABLE panier_ligne DROP FOREIGN KEY FK_7EDDF43E7294869C');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784187294869C');
        $this->addSql('DROP INDEX UNIQ_52743D7B989D9B62 ON sous_categorie');
        $this->addSql('ALTER TABLE sous_categorie DROP slug');
        $this->addSql('ALTER TABLE user_used_codes DROP FOREIGN KEY FK_A406550DA76ED395');
        $this->addSql('ALTER TABLE user_used_codes DROP FOREIGN KEY FK_A406550D27DAFE17');
    }
}
