<?php

namespace App\Controller\Admin;

use App\Entity\Code;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CodeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Code::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Code promo')
            ->setEntityLabelInPlural('Codes promo')
            ->setDefaultSort(['dateDebut' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        yield TextField::new('code', 'Code')
            ->setHelp('Identifiant du code promo (unique, insensible à la casse).');

        yield IntegerField::new('pourcentageRemise', '% de remise')
            ->setHelp('Pourcentage de remise appliqué sur le total produits (hors frais de port).');

        yield DateTimeField::new('dateDebut', 'Date de début');
        yield DateTimeField::new('dateFin', 'Date de fin');

        yield BooleanField::new('usageUnique', 'Utilisable une seule fois par client');
        yield BooleanField::new('premiereCommandeSeulement', 'Uniquement pour la première commande');
    }
}

