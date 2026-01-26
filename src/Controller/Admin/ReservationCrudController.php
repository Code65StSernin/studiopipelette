<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ReservationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('prestations')->setLabel('Prestations'),
            TextField::new('clientName')->setLabel('Nom du client'),
            EmailField::new('clientEmail')->setLabel('Email'),
            TelephoneField::new('clientPhone')->setLabel('Téléphone'),
            DateTimeField::new('dateStart')->setLabel('Date de début'),
            DateTimeField::new('dateEnd')->setLabel('Date de fin'),
            NumberField::new('totalPrice')->setLabel('Prix total')->setNumDecimals(2),
        ];
    }
}
