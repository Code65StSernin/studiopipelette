<?php
namespace App\Form;

use App\Entity\UnavailabilityRule;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UnavailabilityRuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('recurrenceType', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Type de récurrence',
                'choices' => [
                    'Ponctuelle' => 'once',
                    'Quotidienne' => 'daily',
                    'Hebdomadaire' => 'weekly',
                    'Mensuelle' => 'monthly',
                    'Annuelle' => 'yearly'
                ],
                'required' => true,
            ])
            ->add('startDate', DateType::class, ['widget' => 'single_text', 'required' => false, 'mapped' => false])
            ->add('endDate', DateType::class, ['widget' => 'single_text', 'required' => false, 'mapped' => false])
            ->add('daysOfWeek', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Jours de la semaine',
                'choices' => [
                    'Lundi' => 1,
                    'Mardi' => 2,
                    'Mercredi' => 3,
                    'Jeudi' => 4,
                    'Vendredi' => 5,
                    'Samedi' => 6,
                    'Dimanche' => 0,
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('timeStart', TimeType::class, ['widget' => 'single_text', 'required' => false, 'label' => 'Heure début (optionnel)'])
            ->add('timeEnd', TimeType::class, ['widget' => 'single_text', 'required' => false, 'label' => 'Heure fin (optionnel)'])
            ->add('active', CheckboxType::class, ['required' => false, 'label' => 'Active']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UnavailabilityRule::class,
        ]);
    }
}
