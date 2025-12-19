<?php

namespace App\Controller\Admin;

use App\Entity\PrivacyPolicy;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

class PrivacyPolicyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PrivacyPolicy::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Politique de confidentialité')
            ->setEntityLabelInPlural('Politique de confidentialité');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextEditorField::new('content', 'Contenu');
    }
}

