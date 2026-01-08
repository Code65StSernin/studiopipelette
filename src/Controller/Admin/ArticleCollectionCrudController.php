<?php

namespace App\Controller\Admin;

use App\Entity\ArticleCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class ArticleCollectionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ArticleCollection::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Collection')
            ->setEntityLabelInPlural('Collections')
            ->setPageTitle('index', 'Gestion des collections')
            ->setDefaultSort(['nom' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        
        if ($pageName === Crud::PAGE_INDEX) {
            yield TextField::new('nom', 'Nom');
            yield AssociationField::new('couleurs', 'Couleurs')
                ->formatValue(function ($value) {
                    if ($value === null) {
                        return 'Aucune';
                    }
                    return $value->count() . ' couleur(s)';
                });
            yield AssociationField::new('articles', 'Nombre d\'articles')
                ->setSortable(false)
                ->formatValue(function ($value) {
                    if ($value === null) {
                        return 0;
                    }
                    return $value->count();
                });
        } else {
            yield TextField::new('nom', 'Nom de la collection')
                ->setHelp('Nom de la collection')
                ->setRequired(true);
            yield AssociationField::new('couleurs', 'Couleurs')
                ->setHelp('Sélectionnez une ou plusieurs couleurs associées à cette collection')
                ->setRequired(false);
        }
    }
}

