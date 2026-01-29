<?php

namespace App\Controller\Admin;

use App\Entity\DepotVente;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

use Doctrine\ORM\QueryBuilder;

class DepotVenteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DepotVente::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Dépôt-vente')
            ->setEntityLabelInPlural('Dépôt-vente')
            ->setPageTitle('index', 'Gestion des clients Dépôt-vente')
            ->setDefaultSort(['nom' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('nom', 'Nom')
            ->setHelp('Nom du groupe Dépôt-vente')
            ->setRequired(true);
        yield IntegerField::new('commission', 'Commission (%)')
            ->setHelp('Pourcentage de commission retenu par la boutique');
        yield AssociationField::new('users', 'Utilisateurs')
            ->setHelp('Sélectionnez les utilisateurs (client dépôt-vente = OUI) à ajouter à ce groupe.')
            ->setQueryBuilder(function (QueryBuilder $queryBuilder) {
                return $queryBuilder->andWhere('entity.clientDepotVente = :val')
                    ->setParameter('val', true);
            })
            ->setFormTypeOption('by_reference', false);
    }
}
