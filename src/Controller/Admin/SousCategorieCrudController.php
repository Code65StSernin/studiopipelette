<?php

namespace App\Controller\Admin;

use App\Entity\SousCategorie;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Component\String\Slugger\SluggerInterface;

class SousCategorieCrudController extends AbstractCrudController
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    public static function getEntityFqcn(): string
    {
        return SousCategorie::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Sous-catégorie')
            ->setEntityLabelInPlural('Sous-catégories')
            ->setPageTitle('index', 'Gestion des sous-catégories')
            ->setDefaultSort(['nom' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('nom', 'Nom de la sous-catégorie')
            ->setHelp('Nom de la sous-catégorie (ex: Bougies festives, Bougies relaxantes, etc.)')
            ->setRequired(true);
        
        if ($pageName === Crud::PAGE_INDEX) {
            yield AssociationField::new('articles', 'Nombre d\'articles')
                ->formatValue(function ($value) {
                    return $value->count();
                });
        }
    }

    public function persistEntity($entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof SousCategorie && $entityInstance->getNom()) {
            $slug = strtolower($this->slugger->slug($entityInstance->getNom())->toString());
            $entityInstance->setSlug($slug);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity($entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof SousCategorie && $entityInstance->getNom()) {
            $slug = strtolower($this->slugger->slug($entityInstance->getNom())->toString());
            $entityInstance->setSlug($slug);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}

