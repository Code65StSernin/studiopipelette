<?php

namespace App\Controller\Admin;

use App\Entity\Parfum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class ParfumCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Parfum::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Parfum')
            ->setEntityLabelInPlural('Parfums')
            ->setPageTitle('index', 'Gestion des parfums')
            ->setDefaultSort(['nom' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('nom', 'Nom du parfum')
            ->setHelp('Nom du parfum (ex: Citron Orange et Melon, Vanille, Lavande, etc.)')
            ->setRequired(true);
        
        if ($pageName === Crud::PAGE_INDEX) {
            yield AssociationField::new('articles', 'Nombre d\'articles')
                ->formatValue(function ($value) {
                    return $value->count();
                });
        }
    }
}

