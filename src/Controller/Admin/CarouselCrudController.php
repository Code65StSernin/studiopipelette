<?php

namespace App\Controller\Admin;

use App\Entity\Carousel;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CarouselCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Carousel::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Slide carousel')
            ->setEntityLabelInPlural('Carousel d\'accueil')
            ->setDefaultSort(['dateDebut' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        yield TextField::new('petitTitre', 'Petit titre');
        yield TextField::new('grandTitre', 'Grand titre');
        yield TextField::new('texteBouton', 'Texte du bouton')->setRequired(false);
        yield TextField::new('lienBouton', 'Lien du bouton')->setRequired(false)
            ->setHelp('URL absolue ou relative (ex: /boutique)');

        yield DateTimeField::new('dateDebut', 'Date de début');
        yield DateTimeField::new('dateFin', 'Date de fin');

        yield ImageField::new('image', 'Image')
            ->setUploadDir('public/assets/img/carousel')
            ->setBasePath('assets/img/carousel')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(true)
            ->setHelp('Image de fond du slide (taille recommandée similaire aux images actuelles du carousel).');
    }
}
