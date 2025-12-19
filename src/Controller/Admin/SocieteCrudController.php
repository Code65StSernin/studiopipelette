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
        yield NumberField::new('pourcentageUrssaf', 'Pourcentage URSSAF (%)')
            ->setColumns(4)
            ->setHelp('Pourcentage de cotisations sociales URSSAF à appliquer au CA');
        yield NumberField::new('pourcentageCpf', 'Pourcentage CPF (%)')
            ->setColumns(4)
            ->setHelp('Pourcentage de formation professionnelle (CPF) à appliquer au CA');
        yield NumberField::new('pourcentageIr', 'Pourcentage IR (%)')
            ->setColumns(4)
            ->setHelp('Pourcentage d\'impôt sur le revenu à appliquer au CA');
    }
}

