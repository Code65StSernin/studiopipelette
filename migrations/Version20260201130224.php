<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260201130224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66365BF48 FOREIGN KEY (sous_categorie_id) REFERENCES sous_categorie (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66670C757F FOREIGN KEY (fournisseur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE article_collection ADD CONSTRAINT FK_C094B4B37294869C FOREIGN KEY (article_id) REFERENCES article (id)');
        $this->addSql('ALTER TABLE article_collection ADD CONSTRAINT FK_C094B4B3514956FD FOREIGN KEY (collection_id) REFERENCES collection (id)');
        $this->addSql('ALTER TABLE article_couleur ADD CONSTRAINT FK_D1DFC3527294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_couleur ADD CONSTRAINT FK_D1DFC352C31BA576 FOREIGN KEY (couleur_id) REFERENCES couleur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avoir ADD CONSTRAINT FK_659B1A437DC7170A FOREIGN KEY (vente_id) REFERENCES vente (id)');
        $this->addSql('ALTER TABLE bon_achat ADD CONSTRAINT FK_5B8DF14A19EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE btob_categorie ADD CONSTRAINT FK_7350E78EA4054AA8 FOREIGN KEY (bto_b_id) REFERENCES bto_b (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE btob_categorie ADD CONSTRAINT FK_7350E78EBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE client_photo ADD CONSTRAINT FK_B7DA18AF19EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE collection_couleur ADD CONSTRAINT FK_6821F534514956FD FOREIGN KEY (collection_id) REFERENCES collection (id)');
        $this->addSql('ALTER TABLE collection_couleur ADD CONSTRAINT FK_6821F534C31BA576 FOREIGN KEY (couleur_id) REFERENCES couleur (id)');
        $this->addSql('ALTER TABLE contrainte_prestation_tarif ADD CONSTRAINT FK_9C79430EEF86AAA3 FOREIGN KEY (contrainte_prestation_id) REFERENCES contrainte_prestation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contrainte_prestation_tarif ADD CONSTRAINT FK_9C79430E357C0A59 FOREIGN KEY (tarif_id) REFERENCES tarif (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dispo_prestation_tarif ADD CONSTRAINT FK_CA9DEC985974D221 FOREIGN KEY (dispo_prestation_id) REFERENCES dispo_prestation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dispo_prestation_tarif ADD CONSTRAINT FK_CA9DEC98357C0A59 FOREIGN KEY (tarif_id) REFERENCES tarif (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_FE8664108D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori_article ADD CONSTRAINT FK_6FB6AA41FF17033F FOREIGN KEY (favori_id) REFERENCES favori (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori_article ADD CONSTRAINT FK_6FB6AA417294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ligne_facture ADD CONSTRAINT FK_611F5A297F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id)');
        $this->addSql('ALTER TABLE ligne_vente ADD CONSTRAINT FK_8B26C07C7DC7170A FOREIGN KEY (vente_id) REFERENCES vente (id)');
        $this->addSql('ALTER TABLE ligne_vente ADD CONSTRAINT FK_8B26C07C357C0A59 FOREIGN KEY (tarif_id) REFERENCES tarif (id)');
        $this->addSql('ALTER TABLE ligne_vente ADD CONSTRAINT FK_8B26C07C7294869C FOREIGN KEY (article_id) REFERENCES article (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1E7DC7170A FOREIGN KEY (vente_id) REFERENCES vente (id)');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2294102D4 FOREIGN KEY (code_promo_id) REFERENCES code (id)');
        $this->addSql('ALTER TABLE panier_ligne ADD CONSTRAINT FK_7EDDF43EF77D927C FOREIGN KEY (panier_id) REFERENCES panier (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_ligne ADD CONSTRAINT FK_7EDDF43E7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784187294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B7841819EB6921 FOREIGN KEY (client_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_tarif ADD CONSTRAINT FK_4D0E596EB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_tarif ADD CONSTRAINT FK_4D0E596E357C0A59 FOREIGN KEY (tarif_id) REFERENCES tarif (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE societe ADD stripe_frais_pourcentage DOUBLE PRECISION DEFAULT NULL, ADD stripe_frais_fixe DOUBLE PRECISION DEFAULT NULL, ADD tpe_frais_pourcentage DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE sous_categorie_vente ADD CONSTRAINT FK_B1838AF0BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_vente (id)');
        $this->addSql('ALTER TABLE sous_categorie_vente ADD CONSTRAINT FK_B1838AF0354A207A FOREIGN KEY (categorie_stock_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE tarif ADD CONSTRAINT FK_E7189C945405660 FOREIGN KEY (categorie_vente_id) REFERENCES categorie_vente (id)');
        $this->addSql('ALTER TABLE tarif ADD CONSTRAINT FK_E7189C96658140A FOREIGN KEY (sous_categorie_vente_id) REFERENCES sous_categorie_vente (id)');
        $this->addSql('ALTER TABLE user CHANGE fidelite_cagnotte fidelite_cagnotte DOUBLE PRECISION DEFAULT 0 NOT NULL, CHANGE fidelite_points fidelite_points DOUBLE PRECISION DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649A4054AA8 FOREIGN KEY (bto_b_id) REFERENCES bto_b (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649FADC9094 FOREIGN KEY (depot_vente_id) REFERENCES depot_vente (id)');
        $this->addSql('ALTER TABLE user_used_codes ADD CONSTRAINT FK_A406550DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_used_codes ADD CONSTRAINT FK_A406550D27DAFE17 FOREIGN KEY (code_id) REFERENCES code (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4C19EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81A76ED395');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66BCF5E72D');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66365BF48');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66670C757F');
        $this->addSql('ALTER TABLE article_collection DROP FOREIGN KEY FK_C094B4B37294869C');
        $this->addSql('ALTER TABLE article_collection DROP FOREIGN KEY FK_C094B4B3514956FD');
        $this->addSql('ALTER TABLE article_couleur DROP FOREIGN KEY FK_D1DFC3527294869C');
        $this->addSql('ALTER TABLE article_couleur DROP FOREIGN KEY FK_D1DFC352C31BA576');
        $this->addSql('ALTER TABLE avoir DROP FOREIGN KEY FK_659B1A437DC7170A');
        $this->addSql('ALTER TABLE bon_achat DROP FOREIGN KEY FK_5B8DF14A19EB6921');
        $this->addSql('ALTER TABLE btob_categorie DROP FOREIGN KEY FK_7350E78EA4054AA8');
        $this->addSql('ALTER TABLE btob_categorie DROP FOREIGN KEY FK_7350E78EBCF5E72D');
        $this->addSql('ALTER TABLE client_photo DROP FOREIGN KEY FK_B7DA18AF19EB6921');
        $this->addSql('ALTER TABLE collection_couleur DROP FOREIGN KEY FK_6821F534514956FD');
        $this->addSql('ALTER TABLE collection_couleur DROP FOREIGN KEY FK_6821F534C31BA576');
        $this->addSql('ALTER TABLE contrainte_prestation_tarif DROP FOREIGN KEY FK_9C79430EEF86AAA3');
        $this->addSql('ALTER TABLE contrainte_prestation_tarif DROP FOREIGN KEY FK_9C79430E357C0A59');
        $this->addSql('ALTER TABLE dispo_prestation_tarif DROP FOREIGN KEY FK_CA9DEC985974D221');
        $this->addSql('ALTER TABLE dispo_prestation_tarif DROP FOREIGN KEY FK_CA9DEC98357C0A59');
        $this->addSql('ALTER TABLE facture DROP FOREIGN KEY FK_FE8664108D9F6D38');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCA76ED395');
        $this->addSql('ALTER TABLE favori_article DROP FOREIGN KEY FK_6FB6AA41FF17033F');
        $this->addSql('ALTER TABLE favori_article DROP FOREIGN KEY FK_6FB6AA417294869C');
        $this->addSql('ALTER TABLE ligne_facture DROP FOREIGN KEY FK_611F5A297F2DEE08');
        $this->addSql('ALTER TABLE ligne_vente DROP FOREIGN KEY FK_8B26C07C7DC7170A');
        $this->addSql('ALTER TABLE ligne_vente DROP FOREIGN KEY FK_8B26C07C357C0A59');
        $this->addSql('ALTER TABLE ligne_vente DROP FOREIGN KEY FK_8B26C07C7294869C');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E7DC7170A');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2A76ED395');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2294102D4');
        $this->addSql('ALTER TABLE panier_ligne DROP FOREIGN KEY FK_7EDDF43EF77D927C');
        $this->addSql('ALTER TABLE panier_ligne DROP FOREIGN KEY FK_7EDDF43E7294869C');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784187294869C');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B7841819EB6921');
        $this->addSql('ALTER TABLE reservation_tarif DROP FOREIGN KEY FK_4D0E596EB83297E7');
        $this->addSql('ALTER TABLE reservation_tarif DROP FOREIGN KEY FK_4D0E596E357C0A59');
        $this->addSql('ALTER TABLE societe DROP stripe_frais_pourcentage, DROP stripe_frais_fixe, DROP tpe_frais_pourcentage');
        $this->addSql('ALTER TABLE sous_categorie_vente DROP FOREIGN KEY FK_B1838AF0BCF5E72D');
        $this->addSql('ALTER TABLE sous_categorie_vente DROP FOREIGN KEY FK_B1838AF0354A207A');
        $this->addSql('ALTER TABLE tarif DROP FOREIGN KEY FK_E7189C945405660');
        $this->addSql('ALTER TABLE tarif DROP FOREIGN KEY FK_E7189C96658140A');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649A4054AA8');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649FADC9094');
        $this->addSql('ALTER TABLE user CHANGE fidelite_cagnotte fidelite_cagnotte DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE fidelite_points fidelite_points DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE user_used_codes DROP FOREIGN KEY FK_A406550DA76ED395');
        $this->addSql('ALTER TABLE user_used_codes DROP FOREIGN KEY FK_A406550D27DAFE17');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4C19EB6921');
    }
}
