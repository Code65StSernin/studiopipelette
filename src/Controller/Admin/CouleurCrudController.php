<?php

namespace App\Controller\Admin;

use App\Entity\Couleur;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class CouleurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Couleur::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Couleur')
            ->setEntityLabelInPlural('Couleurs')
            ->setPageTitle('index', 'Gestion des couleurs')
            ->setDefaultSort(['nom' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        
        if ($pageName === Crud::PAGE_INDEX) {
            yield TextField::new('nom', 'Nom');
            yield ColorField::new('codeHex', 'Aperçu');
            yield AssociationField::new('articles', 'Nombre d\'articles')
                ->formatValue(function ($value) {
                    return $value->count();
                });
        } else {
            yield TextField::new('nom', 'Nom de la couleur')
                ->setHelp('Nom de la couleur (ex: Rouge, Bleu, Vert, etc.)')
                ->setRequired(true)
                ->setColumns(6);
            yield ColorField::new('codeHex', 'Code couleur')
                ->setHelp('Code hexadécimal de la couleur (ex: #FF0000 pour rouge)')
                ->setColumns(6);
        }
    }
}

