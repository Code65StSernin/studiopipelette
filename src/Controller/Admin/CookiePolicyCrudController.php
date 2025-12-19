<?php

namespace App\Controller\Admin;

use App\Entity\CookiePolicy;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

class CookiePolicyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CookiePolicy::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Politique de cookies')
            ->setEntityLabelInPlural('Politique de cookies');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextEditorField::new('content', 'Contenu');
    }
}

