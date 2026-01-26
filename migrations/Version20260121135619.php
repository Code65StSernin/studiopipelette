<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260121135619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE address (id INT AUTO_INCREMENT NOT NULL, street_number VARCHAR(5) NOT NULL, street VARCHAR(90) NOT NULL, complement VARCHAR(90) DEFAULT NULL, postal_code VARCHAR(5) NOT NULL, city VARCHAR(30) NOT NULL, is_default TINYINT NOT NULL, user_id INT NOT NULL, INDEX IDX_D4E6F81A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE article (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(191) NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, sous_titre VARCHAR(255) DEFAULT NULL, sous_titre_contenu LONGTEXT DEFAULT NULL, origine VARCHAR(150) DEFAULT NULL, materiau VARCHAR(150) DEFAULT NULL, tailles JSON DEFAULT NULL, composition_fabrication LONGTEXT DEFAULT NULL, informations_livraison LONGTEXT DEFAULT NULL, actif TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, categorie_id INT NOT NULL, sous_categorie_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_23A0E66989D9B62 (slug), INDEX IDX_23A0E66BCF5E72D (categorie_id), INDEX IDX_23A0E66365BF48 (sous_categorie_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE article_collection (article_id INT NOT NULL, collection_id INT NOT NULL, INDEX IDX_C094B4B37294869C (article_id), INDEX IDX_C094B4B3514956FD (collection_id), PRIMARY KEY (article_id, collection_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE article_couleur (article_id INT NOT NULL, couleur_id INT NOT NULL, INDEX IDX_D1DFC3527294869C (article_id), INDEX IDX_D1DFC352C31BA576 (couleur_id), PRIMARY KEY (article_id, couleur_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bto_b (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, remise_par_categorie INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE btob_categorie (bto_b_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_7350E78EA4054AA8 (bto_b_id), INDEX IDX_7350E78EBCF5E72D (categorie_id), PRIMARY KEY (bto_b_id, categorie_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE calendrier (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, creneaux JSON NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE carousel (id INT AUTO_INCREMENT NOT NULL, petit_titre VARCHAR(150) NOT NULL, grand_titre VARCHAR(255) NOT NULL, texte_bouton VARCHAR(100) DEFAULT NULL, lien_bouton VARCHAR(255) DEFAULT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, image VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(150) DEFAULT NULL, nom VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_497DD634989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cgv (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE code (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, pourcentage_remise DOUBLE PRECISION NOT NULL, usage_unique TINYINT NOT NULL, deja_utilise TINYINT NOT NULL, premiere_commande_seulement TINYINT NOT NULL, UNIQUE INDEX UNIQ_7715309877153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE collection (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE collection_couleur (collection_id INT NOT NULL, couleur_id INT NOT NULL, INDEX IDX_6821F534514956FD (collection_id), INDEX IDX_6821F534C31BA576 (couleur_id), PRIMARY KEY (collection_id, couleur_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contrainte_prestation (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) DEFAULT NULL, jours_interdits JSON DEFAULT NULL, limite_par_jour INT DEFAULT NULL, actif TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contrainte_prestation_tarif (contrainte_prestation_id INT NOT NULL, tarif_id INT NOT NULL, INDEX IDX_9C79430EEF86AAA3 (contrainte_prestation_id), INDEX IDX_9C79430E357C0A59 (tarif_id), PRIMARY KEY (contrainte_prestation_id, tarif_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cookie_policy (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE couleur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) NOT NULL, code_hex VARCHAR(7) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE creneau (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, slot_key VARCHAR(20) NOT NULL, is_blocked TINYINT NOT NULL, capacity INT NOT NULL, UNIQUE INDEX UNIQ_F9668B5F18A4F944 (slot_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE depenses (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, objet VARCHAR(255) NOT NULL, montant DOUBLE PRECISION NOT NULL, pointage TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dispo_prestation (id INT AUTO_INCREMENT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, motif VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dispo_prestation_tarif (dispo_prestation_id INT NOT NULL, tarif_id INT NOT NULL, INDEX IDX_CA9DEC985974D221 (dispo_prestation_id), INDEX IDX_CA9DEC98357C0A59 (tarif_id), PRIMARY KEY (dispo_prestation_id, tarif_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE facture (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(50) NOT NULL, client_nom VARCHAR(100) NOT NULL, client_prenom VARCHAR(100) NOT NULL, client_email VARCHAR(180) NOT NULL, client_adresse VARCHAR(255) DEFAULT NULL, client_code_postal VARCHAR(10) DEFAULT NULL, client_ville VARCHAR(100) DEFAULT NULL, client_pays VARCHAR(100) DEFAULT NULL, date_creation DATETIME NOT NULL, total_ttc INT NOT NULL, mode_livraison VARCHAR(20) NOT NULL, frais_livraison INT DEFAULT NULL, btob_remise_cents INT DEFAULT NULL, remise_pourcentage DOUBLE PRECISION DEFAULT NULL, order_id INT NOT NULL, UNIQUE INDEX UNIQ_FE866410F55AE19E (numero), UNIQUE INDEX UNIQ_FE8664108D9F6D38 (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE faq (id INT AUTO_INCREMENT NOT NULL, question VARCHAR(255) NOT NULL, answer LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE favori (id INT AUTO_INCREMENT NOT NULL, session_id VARCHAR(191) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_EF85A2CC613FECDF (session_id), INDEX IDX_EF85A2CCA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE favori_article (favori_id INT NOT NULL, article_id INT NOT NULL, INDEX IDX_6FB6AA41FF17033F (favori_id), INDEX IDX_6FB6AA417294869C (article_id), PRIMARY KEY (favori_id, article_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ligne_facture (id INT AUTO_INCREMENT NOT NULL, article_designation VARCHAR(255) NOT NULL, article_taille VARCHAR(50) NOT NULL, quantite INT NOT NULL, prix_unitaire INT NOT NULL, prix_total INT NOT NULL, facture_id INT NOT NULL, INDEX IDX_611F5A297F2DEE08 (facture_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE newsletter_subscriber (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, nom VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, unsubscribe_token VARCHAR(64) NOT NULL, is_active TINYINT NOT NULL, UNIQUE INDEX UNIQ_401562C3E7927C74 (email), UNIQUE INDEX UNIQ_401562C3E0674361 (unsubscribe_token), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offre (id INT AUTO_INCREMENT NOT NULL, petit_texte VARCHAR(255) DEFAULT NULL, grand_texte VARCHAR(255) NOT NULL, texte_bouton VARCHAR(255) DEFAULT NULL, lien_bouton VARCHAR(255) DEFAULT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, image_gauche TINYINT NOT NULL, image VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, amount_total_cents INT NOT NULL, amount_products_cents INT NOT NULL, amount_shipping_cents INT NOT NULL, shipping_mode VARCHAR(20) NOT NULL, relay_id VARCHAR(50) DEFAULT NULL, relay_name VARCHAR(255) DEFAULT NULL, relay_address VARCHAR(255) DEFAULT NULL, stripe_checkout_session_id VARCHAR(191) DEFAULT NULL, stripe_payment_intent_id VARCHAR(191) DEFAULT NULL, created_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, mondial_relay_recipient_first_name VARCHAR(50) DEFAULT NULL, mondial_relay_recipient_last_name VARCHAR(50) DEFAULT NULL, mondial_relay_parcels_count INT DEFAULT NULL, mondial_relay_content_value_cents INT DEFAULT NULL, mondial_relay_content_description VARCHAR(255) DEFAULT NULL, mondial_relay_length_cm INT DEFAULT NULL, mondial_relay_width_cm INT DEFAULT NULL, mondial_relay_height_cm INT DEFAULT NULL, mondial_relay_weight_kg DOUBLE PRECISION DEFAULT NULL, mondial_relay_shipment_number VARCHAR(100) DEFAULT NULL, mondial_relay_label_url VARCHAR(255) DEFAULT NULL, is_shipped TINYINT NOT NULL, debloque TINYINT NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_F52993985A18FBC7 (stripe_checkout_session_id), INDEX IDX_F5299398A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE panier (id INT AUTO_INCREMENT NOT NULL, session_id VARCHAR(191) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, code_promo_pourcentage DOUBLE PRECISION DEFAULT NULL, user_id INT DEFAULT NULL, code_promo_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_24CC0DF2613FECDF (session_id), INDEX IDX_24CC0DF2A76ED395 (user_id), INDEX IDX_24CC0DF2294102D4 (code_promo_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE panier_ligne (id INT AUTO_INCREMENT NOT NULL, taille VARCHAR(50) NOT NULL, quantite INT NOT NULL, prix_unitaire DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, panier_id INT NOT NULL, article_id INT NOT NULL, INDEX IDX_7EDDF43EF77D927C (panier_id), INDEX IDX_7EDDF43E7294869C (article_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE photo (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, type VARCHAR(20) DEFAULT \'image\' NOT NULL, created_at DATETIME NOT NULL, article_id INT NOT NULL, INDEX IDX_14B784187294869C (article_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE privacy_policy (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE recette (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, objet VARCHAR(255) NOT NULL, montant DOUBLE PRECISION NOT NULL, pointage TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, date_start DATETIME NOT NULL, date_end DATETIME NOT NULL, client_name VARCHAR(255) NOT NULL, client_email VARCHAR(255) NOT NULL, client_phone VARCHAR(255) DEFAULT NULL, total_price DOUBLE PRECISION NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation_tarif (reservation_id INT NOT NULL, tarif_id INT NOT NULL, INDEX IDX_4D0E596EB83297E7 (reservation_id), INDEX IDX_4D0E596E357C0A59 (tarif_id), PRIMARY KEY (reservation_id, tarif_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE societe (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, code_postal VARCHAR(10) DEFAULT NULL, ville VARCHAR(100) DEFAULT NULL, telephone VARCHAR(50) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, siret VARCHAR(50) DEFAULT NULL, code_naf VARCHAR(20) DEFAULT NULL, mondial_relay_login VARCHAR(100) DEFAULT NULL, mondial_relay_password VARCHAR(100) DEFAULT NULL, mondial_relay_customer_id VARCHAR(50) DEFAULT NULL, mondial_relay_brand VARCHAR(50) DEFAULT NULL, stripe_public_key VARCHAR(255) DEFAULT NULL, stripe_secret_key VARCHAR(255) DEFAULT NULL, stripe_webhook_secret VARCHAR(255) DEFAULT NULL, db_host VARCHAR(100) DEFAULT NULL, db_name VARCHAR(100) DEFAULT NULL, db_user VARCHAR(100) DEFAULT NULL, db_password VARCHAR(255) DEFAULT NULL, smtp_user VARCHAR(180) DEFAULT NULL, smtp_password VARCHAR(255) DEFAULT NULL, smtp_host VARCHAR(255) DEFAULT NULL, smtp_port INT DEFAULT NULL, smtp_from_email VARCHAR(180) DEFAULT NULL, pourcentage_urssaf DOUBLE PRECISION DEFAULT NULL, pourcentage_cpf DOUBLE PRECISION DEFAULT NULL, pourcentage_ir DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sous_categorie (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(150) DEFAULT NULL, nom VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_52743D7B989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tarif (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, tarif NUMERIC(10, 2) NOT NULL, duree_minutes INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE unavailability_rule (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, recurrence LONGTEXT DEFAULT NULL, time_start TIME DEFAULT NULL, time_end TIME DEFAULT NULL, active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(50) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(50) NOT NULL, prenom VARCHAR(50) NOT NULL, is_verified TINYINT NOT NULL, verification_token VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, reset_token VARCHAR(255) DEFAULT NULL, reset_token_expires_at DATETIME DEFAULT NULL, bto_b_id INT DEFAULT NULL, INDEX IDX_8D93D649A4054AA8 (bto_b_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_used_codes (user_id INT NOT NULL, code_id INT NOT NULL, INDEX IDX_A406550DA76ED395 (user_id), INDEX IDX_A406550D27DAFE17 (code_id), PRIMARY KEY (user_id, code_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66365BF48 FOREIGN KEY (sous_categorie_id) REFERENCES sous_categorie (id)');
        $this->addSql('ALTER TABLE article_collection ADD CONSTRAINT FK_C094B4B37294869C FOREIGN KEY (article_id) REFERENCES article (id)');
        $this->addSql('ALTER TABLE article_collection ADD CONSTRAINT FK_C094B4B3514956FD FOREIGN KEY (collection_id) REFERENCES collection (id)');
        $this->addSql('ALTER TABLE article_couleur ADD CONSTRAINT FK_D1DFC3527294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_couleur ADD CONSTRAINT FK_D1DFC352C31BA576 FOREIGN KEY (couleur_id) REFERENCES couleur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE btob_categorie ADD CONSTRAINT FK_7350E78EA4054AA8 FOREIGN KEY (bto_b_id) REFERENCES bto_b (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE btob_categorie ADD CONSTRAINT FK_7350E78EBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
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
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2294102D4 FOREIGN KEY (code_promo_id) REFERENCES code (id)');
        $this->addSql('ALTER TABLE panier_ligne ADD CONSTRAINT FK_7EDDF43EF77D927C FOREIGN KEY (panier_id) REFERENCES panier (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier_ligne ADD CONSTRAINT FK_7EDDF43E7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784187294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_tarif ADD CONSTRAINT FK_4D0E596EB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_tarif ADD CONSTRAINT FK_4D0E596E357C0A59 FOREIGN KEY (tarif_id) REFERENCES tarif (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649A4054AA8 FOREIGN KEY (bto_b_id) REFERENCES bto_b (id)');
        $this->addSql('ALTER TABLE user_used_codes ADD CONSTRAINT FK_A406550DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_used_codes ADD CONSTRAINT FK_A406550D27DAFE17 FOREIGN KEY (code_id) REFERENCES code (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81A76ED395');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66BCF5E72D');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66365BF48');
        $this->addSql('ALTER TABLE article_collection DROP FOREIGN KEY FK_C094B4B37294869C');
        $this->addSql('ALTER TABLE article_collection DROP FOREIGN KEY FK_C094B4B3514956FD');
        $this->addSql('ALTER TABLE article_couleur DROP FOREIGN KEY FK_D1DFC3527294869C');
        $this->addSql('ALTER TABLE article_couleur DROP FOREIGN KEY FK_D1DFC352C31BA576');
        $this->addSql('ALTER TABLE btob_categorie DROP FOREIGN KEY FK_7350E78EA4054AA8');
        $this->addSql('ALTER TABLE btob_categorie DROP FOREIGN KEY FK_7350E78EBCF5E72D');
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
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2A76ED395');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2294102D4');
        $this->addSql('ALTER TABLE panier_ligne DROP FOREIGN KEY FK_7EDDF43EF77D927C');
        $this->addSql('ALTER TABLE panier_ligne DROP FOREIGN KEY FK_7EDDF43E7294869C');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784187294869C');
        $this->addSql('ALTER TABLE reservation_tarif DROP FOREIGN KEY FK_4D0E596EB83297E7');
        $this->addSql('ALTER TABLE reservation_tarif DROP FOREIGN KEY FK_4D0E596E357C0A59');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649A4054AA8');
        $this->addSql('ALTER TABLE user_used_codes DROP FOREIGN KEY FK_A406550DA76ED395');
        $this->addSql('ALTER TABLE user_used_codes DROP FOREIGN KEY FK_A406550D27DAFE17');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE article_collection');
        $this->addSql('DROP TABLE article_couleur');
        $this->addSql('DROP TABLE bto_b');
        $this->addSql('DROP TABLE btob_categorie');
        $this->addSql('DROP TABLE calendrier');
        $this->addSql('DROP TABLE carousel');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE cgv');
        $this->addSql('DROP TABLE code');
        $this->addSql('DROP TABLE collection');
        $this->addSql('DROP TABLE collection_couleur');
        $this->addSql('DROP TABLE contrainte_prestation');
        $this->addSql('DROP TABLE contrainte_prestation_tarif');
        $this->addSql('DROP TABLE cookie_policy');
        $this->addSql('DROP TABLE couleur');
        $this->addSql('DROP TABLE creneau');
        $this->addSql('DROP TABLE depenses');
        $this->addSql('DROP TABLE dispo_prestation');
        $this->addSql('DROP TABLE dispo_prestation_tarif');
        $this->addSql('DROP TABLE facture');
        $this->addSql('DROP TABLE faq');
        $this->addSql('DROP TABLE favori');
        $this->addSql('DROP TABLE favori_article');
        $this->addSql('DROP TABLE ligne_facture');
        $this->addSql('DROP TABLE newsletter_subscriber');
        $this->addSql('DROP TABLE offre');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE panier');
        $this->addSql('DROP TABLE panier_ligne');
        $this->addSql('DROP TABLE photo');
        $this->addSql('DROP TABLE privacy_policy');
        $this->addSql('DROP TABLE recette');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE reservation_tarif');
        $this->addSql('DROP TABLE societe');
        $this->addSql('DROP TABLE sous_categorie');
        $this->addSql('DROP TABLE tarif');
        $this->addSql('DROP TABLE unavailability_rule');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_used_codes');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
