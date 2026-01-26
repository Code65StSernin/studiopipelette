<?php
namespace App\Controller\Admin;

use App\Entity\Creneau;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;

class CreneauCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Creneau::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Créneau')->setEntityLabelInPlural('Créneaux');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield DateField::new('date', 'Date');
        yield TimeField::new('startTime', 'Heure début');
        yield TimeField::new('endTime', 'Heure fin');
        yield TextField::new('slotKey', 'Clé');
        yield IntegerField::new('capacity', 'Capacité');
        yield BooleanField::new('isBlocked', 'Bloqué');
    }

    /*
    public function index(AdminContext $context): Response
    {
        return $this->render('admin/creneau/calendar.html.twig');
    }
    */
}
