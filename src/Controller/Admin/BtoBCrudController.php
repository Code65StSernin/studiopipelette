<?php

namespace App\Controller\Admin;

use App\Entity\BtoB;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BtoBCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BtoB::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('BtoB')
            ->setEntityLabelInPlural('BtoB')
            ->setPageTitle('index', 'Gestion des clients BtoB')
            ->setDefaultSort(['nom' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('nom', 'Nom')
            ->setHelp('Nom du client BtoB')
            ->setRequired(true);
        yield IntegerField::new('remiseParCategorie', 'Remise par catégorie (%)')
            ->setHelp('Pourcentage de remise appliqué sur la catégorie sélectionnée');
        yield AssociationField::new('categories', 'Catégories');
    }
}
