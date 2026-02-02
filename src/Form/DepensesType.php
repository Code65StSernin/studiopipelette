<?php

namespace App\Form;

use App\Entity\Depenses;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepensesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date',
                'attr' => ['class' => 'form-control']
            ])
            ->add('objet', TextType::class, [
                'label' => 'Objet',
                'attr' => ['class' => 'form-control']
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'attr' => ['class' => 'form-control']
            ])
            ->add('pointage', CheckboxType::class, [
                'label' => 'Pointage (Comptabilisé)',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('remboursementAnticipe', CheckboxType::class, [
                'label' => 'Remboursement Anticipé',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Depenses::class,
        ]);
    }
}
