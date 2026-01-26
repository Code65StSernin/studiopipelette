<?php

namespace App\Controller\Admin;

use App\Entity\ContraintePrestation;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

class ContraintePrestationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ContraintePrestation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Contrainte de prestation')
            ->setEntityLabelInPlural('Contraintes de prestations')
            ->setPageTitle('index', 'Contraintes de prestations')
            ->setPageTitle('new', 'Créer une contrainte de prestation')
            ->setPageTitle('edit', 'Modifier une contrainte de prestation')
            ->setPageTitle('detail', 'Détails de la contrainte de prestation')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['nom']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('actif', 'Actif'))
            ->add(EntityFilter::new('tarifs', 'Prestations'));
    }

    public function configureFields(string $pageName): iterable
    {
        // Affichage dans la liste (index)
        if ($pageName === Crud::PAGE_INDEX) {
            yield IdField::new('id')->setLabel('ID');
            yield TextField::new('nom')
                ->setLabel('Nom')
                ->formatValue(function ($value, $entity) {
                    return $value ?: '<em>Aucun nom</em>';
                });
            yield AssociationField::new('tarifs')
                ->setLabel('Prestations')
                ->formatValue(function ($value, $entity) {
                    if (!$entity || $entity->getTarifs()->isEmpty()) {
                        return '<span class="badge badge-warning">Aucune</span>';
                    }
                    $count = $entity->getTarifs()->count();
                    return sprintf('<span class="badge badge-info">%d prestation(s)</span>', $count);
                });
            yield ChoiceField::new('joursInterdits')
                ->setLabel('Jours interdits')
                ->formatValue(function ($value, $entity) {
                    if (!$entity || !$entity->getJoursInterdits() || empty($entity->getJoursInterdits())) {
                        return '<span class="badge badge-success">Aucun</span>';
                    }
                    return '<span class="badge badge-danger">' . $entity->getJoursInterditsFormates() . '</span>';
                });
            yield IntegerField::new('limiteParJour')
                ->setLabel('Limite/jour')
                ->formatValue(function ($value, $entity) {
                    if ($entity && $entity->getLimiteParJour() === null) {
                        return '<span class="badge badge-success">Illimité</span>';
                    }
                    return $value ? sprintf('<span class="badge badge-primary">%d</span>', $value) : '<span class="badge badge-success">Illimité</span>';
                });
            yield BooleanField::new('actif')
                ->setLabel('Active')
                ->renderAsSwitch(false);
            yield DateTimeField::new('createdAt')
                ->setLabel('Créée le')
                ->setFormat('dd/MM/yyyy');
            return;
        }

        // Affichage dans les formulaires (new/edit) et détails
        yield IdField::new('id')
            ->hideOnForm()
            ->setLabel('ID');

        yield TextField::new('nom')
            ->setLabel('Nom de la contrainte (optionnel)')
            ->setHelp('Un nom pour identifier facilement cette contrainte (ex: "Limitation vendredi", "Prestations spéciales", "Limite manucure")')
            ->setRequired(false)
            ->setColumns('col-md-12')
            ->setFormTypeOption('attr', ['placeholder' => 'Ex: Limitation vendredi']);

        yield AssociationField::new('tarifs')
            ->setLabel('Prestations concernées')
            ->setHelp('Sélectionnez une ou plusieurs prestations (tarifs) auxquelles cette contrainte s\'applique. Utilisez la recherche pour trouver rapidement une prestation.')
            ->setRequired(true)
            ->autocomplete()
            ->setColumns('col-md-12')
            ->formatValue(function ($value, $entity) {
                if (!$entity || $entity->getTarifs()->isEmpty()) {
                    return '<span class="badge badge-warning">Aucune prestation sélectionnée</span>';
                }
                $noms = [];
                foreach ($entity->getTarifs() as $tarif) {
                    $noms[] = $tarif->getNom();
                }
                return '<span class="badge badge-info">' . implode('</span> <span class="badge badge-info">', $noms) . '</span>';
            });

        yield ChoiceField::new('joursInterdits')
            ->setLabel('Jours interdits')
            ->setHelp('Sélectionnez les jours de la semaine où ces prestations ne peuvent PAS être réalisées. Laissez vide si aucun jour n\'est interdit.')
            ->setChoices(ContraintePrestation::JOURS_DISPONIBLES)
            ->allowMultipleChoices()
            ->renderExpanded(true)
            ->renderAsBadges()
            ->setRequired(false)
            ->formatValue(function ($value, $entity) {
                if (!$entity || !$entity->getJoursInterdits() || empty($entity->getJoursInterdits())) {
                    return '<span class="badge badge-secondary">Aucun jour interdit</span>';
                }
                return $entity->getJoursInterditsFormates();
            })
            ->setColumns('col-md-12');

        yield IntegerField::new('limiteParJour')
            ->setLabel('Limite par jour')
            ->setHelp('Nombre maximum de fois que ces prestations peuvent être réalisées par jour. Exemple: 3 signifie que chaque prestation ne peut être réalisée que 3 fois maximum par jour. Laissez vide pour aucune limite.')
            ->setRequired(false)
            ->setColumns('col-md-6')
            ->setFormTypeOption('attr', ['min' => 1, 'placeholder' => 'Aucune limite'])
            ->formatValue(function ($value, $entity) {
                if ($entity && $entity->getLimiteParJour() === null) {
                    return '<span class="badge badge-success">Aucune limite</span>';
                }
                return $value ? sprintf('<span class="badge badge-primary">%d fois par jour</span>', $value) : '<span class="badge badge-success">Aucune limite</span>';
            });

        yield BooleanField::new('actif')
            ->setLabel('Contrainte active')
            ->setHelp('Désactivez cette contrainte pour la rendre temporairement inopérante sans la supprimer')
            ->setColumns('col-md-6');

        yield DateTimeField::new('createdAt')
            ->setLabel('Date de création')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');

        yield DateTimeField::new('updatedAt')
            ->setLabel('Dernière modification')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('dd/MM/yyyy HH:mm');
    }
}

