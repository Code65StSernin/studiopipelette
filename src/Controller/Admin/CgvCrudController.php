<?php

namespace App\Controller\Admin;

use App\Entity\Cgv;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

class CgvCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Cgv::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('C.G.V')
            ->setEntityLabelInPlural('Conditions Générales de Vente');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextEditorField::new('content', 'Contenu');
    }
}

