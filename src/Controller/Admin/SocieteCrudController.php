<?php

namespace App\Controller\Admin;

use App\Entity\Societe;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class SocieteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Societe::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Société')
            ->setEntityLabelInPlural('Société')
            ->setPageTitle(Crud::PAGE_INDEX, 'Configuration de la société')
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_INDEX) {
            yield IdField::new('id')->onlyOnIndex();
            yield TextField::new('nom', 'Nom');
            yield TextField::new('email', 'Email');
            yield TextField::new('telephone', 'Téléphone');
            return;
        }

        // Onglet 1 : Infos société
        yield FormField::addTab('Société')->setIcon('fa fa-building');
        yield TextField::new('nom', 'Nom de la société')
            ->setColumns(12);
        yield TextField::new('adresse', 'Adresse postale')
            ->setColumns(12);
        yield TextField::new('codePostal', 'Code postal')
            ->setColumns(4);
        yield TextField::new('ville', 'Ville')
            ->setColumns(8);
        yield TextField::new('telephone', 'Téléphone')
            ->setColumns(6);
        yield TextField::new('email', 'Email de contact')
            ->setColumns(6);
        yield TextField::new('siret', 'Numéro SIRET')
            ->setColumns(6);
        yield TextField::new('codeNaf', 'Code NAF')
            ->setColumns(6);

        // Onglet 2 : Mondial Relay
        yield FormField::addTab('Mondial Relay')->setIcon('fa fa-truck');
        yield TextField::new('mondialRelayLogin', 'Login')
            ->setColumns(6);
        yield TextField::new('mondialRelayPassword', 'Mot de passe')
            ->setColumns(6);
        yield TextField::new('mondialRelayCustomerId', 'Customer ID')
            ->setColumns(6);
        yield TextField::new('mondialRelayBrand', 'Brand')
            ->setColumns(6);

        // Onglet 3 : Stripe
        yield FormField::addTab('Stripe')->setIcon('fa fa-credit-card');
        yield TextField::new('stripePublicKey', 'Clé publique')
            ->setColumns(12);
        yield TextField::new('stripeSecretKey', 'Clé secrète')
            ->setColumns(12);
        yield TextField::new('stripeWebhookSecret', 'Secret webhook')
            ->setColumns(12)
            ->setHelp('Secret utilisé pour vérifier la signature des webhooks Stripe');

        // Onglet 8 : Frais Bancaires
        yield FormField::addTab('Frais Bancaires')->setIcon('fa fa-university');
        yield NumberField::new('stripeFraisPourcentage', 'Stripe %')
            ->setNumDecimals(2)
            ->setColumns(4)
            ->setHelp('Commission Stripe variable (ex: 1.5%)');
        yield NumberField::new('stripeFraisFixe', 'Stripe Fixe (€)')
            ->setNumDecimals(2)
            ->setColumns(4)
            ->setHelp('Frais fixe par transaction Stripe (ex: 0.25€)');
        yield NumberField::new('tpeFraisPourcentage', 'TPE Caisse %')
            ->setNumDecimals(2)
            ->setColumns(4)
            ->setHelp('Commission TPE pour les paiements CB en caisse (ex: 1.75%)');

        // Onglet 9 : Fidélité
        yield FormField::addTab('Fidélité')->setIcon('fa fa-star');
        yield BooleanField::new('fideliteActive', 'Activer le programme de fidélité')
            ->setColumns(12);
        
        yield ChoiceField::new('fideliteMode', 'Mode de fidélité')
            ->setChoices([
                'Par visites (X passages = Y € offerts)' => 'visits',
                'Par points (X pts/€ -> Y pts = Z € offerts)' => 'points',
            ])
            ->setColumns(12)
            ->renderAsBadges(false);

        yield ChoiceField::new('fideliteScope', 'Génération des points')
            ->setChoices([
                'Uniquement en Caisse' => 'caisse',
                'Uniquement Boutique en ligne' => 'boutique',
                'Caisse et Boutique' => 'both',
            ])
            ->setColumns(12)
            ->setHelp('Choisissez où les achats génèrent des points/visites.');

        // Visites
        yield FormField::addFieldset('Mode Visites')->setHelp('Utilisé si le mode "Par visites" est sélectionné');
        yield IntegerField::new('fideliteVisitsX', 'Nombre de ventes (X)')
            ->setColumns(6);
        yield NumberField::new('fideliteVisitsY', 'Montant offert (Y)')
            ->setNumDecimals(2)
            ->setColumns(6);

        // Points
        yield FormField::addFieldset('Mode Points')->setHelp('Utilisé si le mode "Par points" est sélectionné');
        yield NumberField::new('fidelitePointsX', 'Points par Euro (X)')
            ->setNumDecimals(2)
            ->setColumns(4);
        yield NumberField::new('fidelitePointsY', 'Seuil de points (Y)')
            ->setNumDecimals(2)
            ->setColumns(4);
        yield NumberField::new('fidelitePointsZ', 'Montant offert (Z)')
            ->setNumDecimals(2)
            ->setColumns(4);

        // Onglet 4 : Base de données
        yield FormField::addTab('Base de données')->setIcon('fa fa-database');
        yield TextField::new('dbHost', 'Hôte')
            ->setColumns(6);
        yield TextField::new('dbName', 'Nom de la base')
            ->setColumns(6);
        yield TextField::new('dbUser', 'Utilisateur')
            ->setColumns(6);
        yield TextField::new('dbPassword', 'Mot de passe')
            ->setColumns(6);

        // Onglet 5 : SMTP / Email
        yield FormField::addTab('SMTP / Email')->setIcon('fa fa-envelope');
        yield TextField::new('smtpHost', 'Serveur SMTP')
            ->setColumns(6);
        yield IntegerField::new('smtpPort', 'Port SMTP')
            ->setColumns(6);
        yield TextField::new('smtpUser', 'Utilisateur SMTP')
            ->setColumns(6);
        yield TextField::new('smtpPassword', 'Mot de passe SMTP')
            ->setColumns(6);

        yield TextField::new('smtpFromEmail', 'Email d\'expédition')
            ->setColumns(12);

        // Onglet 6 : Charges sociales et fiscales
        yield FormField::addTab('Charges sociales et fiscales')->setIcon('fa fa-percent');
        
        // Champs séparés BIC / BNC
        yield NumberField::new('pourcentageUrssafBic', 'URSSAF BIC (%)')
            ->setColumns(4)
            ->setHelp('Pourcentage URSSAF pour les ventes physiques (Articles)');
            
        yield NumberField::new('pourcentageUrssafBnc', 'URSSAF BNC (%)')
            ->setColumns(4)
            ->setHelp('Pourcentage URSSAF pour les prestations de service (Tarifs)');
            
        yield NumberField::new('pourcentageCpf', 'Pourcentage CPF (%)')
            ->setColumns(4)
            ->setHelp('Pourcentage de formation professionnelle (CPF) à appliquer au CA global');
            
        yield NumberField::new('pourcentageIr', 'Pourcentage IR (%)')
            ->setColumns(4)
            ->setHelp('Pourcentage d\'impôt sur le revenu à appliquer au CA global');

        // Onglet 7 : Etalement site
        yield FormField::addTab('Etalement du site')->setIcon('fa fa-money-bill-wave');
        yield NumberField::new('totalSite', 'Coût total du site (€)')
            ->setNumDecimals(2)
            ->setColumns(6);
        yield NumberField::new('pourcentageMensuel', 'Pourcentage mensuel (%)')
            ->setNumDecimals(2)
            ->setColumns(6)
            ->setHelp('Pourcentage du CA port compris prélevé chaque mois pour le remboursement');
    }
}

