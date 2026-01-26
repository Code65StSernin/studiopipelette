<?php

namespace App\Controller\Admin;

use App\Entity\DispoPrestation;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DispoPrestationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DispoPrestation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Suspension des prestations')
            ->setEntityLabelInPlural('Suspensions des prestations')
            ->setPageTitle('index', 'Suspensions des prestations')
            ->setPageTitle('new', 'Créer une suspension des prestations')
            ->setPageTitle('edit', 'Modifier une suspension des prestations')
            ->setPageTitle('detail', 'Détails de la suspension des prestations');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('Tarif')
                ->setLabel('Tarif'),
            DateField::new('dateDebut')
                ->setLabel('Date de début'),
            DateField::new('dateFin')
                ->setLabel('Date de fin'),
            TextField::new('motif')
                ->setLabel('Motif'),
        ];
    }
}
