<?php
namespace App\Controller\Admin;

use App\Entity\UnavailabilityRule;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

class UnavailabilityRuleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UnavailabilityRule::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Règle indisponibilité')
            ->setEntityLabelInPlural('Règles indisponibilité');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Nom');
        yield BooleanField::new('active', 'Active');
        yield TimeField::new('timeStart', 'Heure début');
        yield TimeField::new('timeEnd', 'Heure fin');
        
        yield ChoiceField::new('recurrenceType', 'Type de récurrence')
            ->setChoices([
                'Ponctuelle' => 'once',
                'Quotidienne' => 'daily',
                'Hebdomadaire' => 'weekly',
                'Mensuelle' => 'monthly',
                'Annuelle' => 'yearly'
            ])
            ->setFormTypeOption('mapped', false)
            ->setRequired(true);

        yield DateField::new('startDate', 'Date de début')
            ->setFormTypeOption('mapped', false)
            ->setRequired(false);

        yield DateField::new('endDate', 'Date de fin')
            ->setFormTypeOption('mapped', false)
            ->setRequired(false);

        yield ChoiceField::new('daysOfWeek', 'Jours de la semaine')
            ->setChoices([
                'Lundi' => 1,
                'Mardi' => 2,
                'Mercredi' => 3,
                'Jeudi' => 4,
                'Vendredi' => 5,
                'Samedi' => 6,
                'Dimanche' => 0,
            ])
            ->setFormTypeOption('mapped', false)
            ->allowMultipleChoices()
            ->renderExpanded();
        
        yield TextareaField::new('recurrence', 'Récurrence (JSON)')->onlyOnDetail();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var UnavailabilityRule $entityInstance */
        $this->handleRecurrenceData($this->getContext()->getRequest()->request->all('UnavailabilityRule'), $entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var UnavailabilityRule $entityInstance */
        $this->handleRecurrenceData($this->getContext()->getRequest()->request->all('UnavailabilityRule'), $entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function handleRecurrenceData(array $formData, UnavailabilityRule $entityInstance): void
    {
        $data = [];
        $data['type'] = $formData['recurrenceType'] ?? 'once';
        if (!empty($formData['startDate'])) $data['startDate'] = $formData['startDate'];
        if (!empty($formData['endDate'])) $data['endDate'] = $formData['endDate'];
        if (!empty($formData['daysOfWeek'])) $data['daysOfWeek'] = $formData['daysOfWeek'];
        
        $entityInstance->setRecurrence(json_encode($data));
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);

        /** @var UnavailabilityRule $entityInstance */
        $entityInstance = $entityDto->getInstance();
        $recurrence = $entityInstance->getRecurrence();
        if ($recurrence) {
            $data = json_decode($recurrence, true);
            if (isset($data['type'])) {
                $formBuilder->get('recurrenceType')->setData($data['type']);
            }
            if (isset($data['startDate'])) {
                $formBuilder->get('startDate')->setData(new \DateTime($data['startDate']));
            }
            if (isset($data['endDate'])) {
                $formBuilder->get('endDate')->setData(new \DateTime($data['endDate']));
            }
            if (isset($data['daysOfWeek'])) {
                $formBuilder->get('daysOfWeek')->setData($data['daysOfWeek']);
            }
        }

        return $formBuilder;
    }
}
