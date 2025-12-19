<?php

namespace App\Controller\Admin;

use App\Entity\Recette;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RecetteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Recette::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Recette')
            ->setEntityLabelInPlural('Recettes')
            ->setDefaultSort(['date' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_INDEX) {
            yield IdField::new('id')->onlyOnIndex();
            yield DateField::new('date', 'Date');
            yield TextField::new('objet', 'Objet');
            yield NumberField::new('montant', 'Montant')->setNumDecimals(2);
            yield BooleanField::new('pointage', 'Pointée ?');
            return;
        }

        yield DateField::new('date', 'Date de la recette')
            ->setRequired(true);

        yield TextField::new('objet', 'Objet / libellé')
            ->setRequired(true);

        yield NumberField::new('montant', 'Montant (€)')
            ->setNumDecimals(2)
            ->setRequired(true);

        yield BooleanField::new('pointage', 'Pointée')
            ->setHelp('Cocher lorsque la recette est pointée en comptabilité.');
    }
}
