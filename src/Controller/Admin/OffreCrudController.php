<?php

namespace App\Controller\Admin;

use App\Entity\Offre;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OffreCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Offre::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('petitTexte', 'Petit texte')
            ->setMaxLength(255)
            ->setRequired(false);

        yield TextField::new('grandTexte', 'Grand texte')
            ->setMaxLength(255);

        yield TextField::new('texteBouton', 'Texte du bouton')
            ->setMaxLength(255)
            ->setRequired(false);

        yield TextField::new('lienBouton', 'Lien du bouton')
            ->setMaxLength(255)
            ->setRequired(false)
            ->setHelp('URL absolue ou nom de route Symfony (ex: app_boutique)');

        yield DateTimeField::new('dateDebut', 'Date de début')
            ->setRequired(true);

        yield DateTimeField::new('dateFin', 'Date de fin')
            ->setRequired(true);

        yield BooleanField::new('imageGauche', 'Image à gauche');

        yield ImageField::new('image', 'Image (PNG uniquement)')
            ->setBasePath('assets/img/offres')
            ->setUploadDir('public/assets/img/offres')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired(false)
            ->setHelp('Format PNG uniquement')
            ->setFormTypeOptions([
                'attr' => [
                    'accept' => 'image/png',
                ],
            ]);
    }
}

