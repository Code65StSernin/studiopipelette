<?php

namespace App\Form;

use App\Entity\Code;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code Promo',
                'help' => 'Sera automatiquement mis en majuscules',
                'attr' => ['class' => 'form-control']
            ])
            ->add('pourcentageRemise', NumberType::class, [
                'label' => '% de remise',
                'scale' => 2,
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateFin', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['class' => 'form-control']
            ])
            ->add('usageUnique', CheckboxType::class, [
                'label' => 'Utilisable une seule fois par client',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('premiereCommandeSeulement', CheckboxType::class, [
                'label' => 'Uniquement pour la première commande',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Code::class,
        ]);
    }
}
