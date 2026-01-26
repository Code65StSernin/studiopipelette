<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;

class ReservationClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('clientName', TextType::class, [
                'label' => 'Nom complet',
                'attr' => ['placeholder' => 'Votre nom complet']
            ])
            ->add('clientEmail', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => ['placeholder' => 'votre.email@example.com']
            ])
            ->add('clientPhone', TelType::class, [
                'label' => 'Numéro de téléphone (facultatif)',
                'required' => false,
                'attr' => ['placeholder' => '0612345678']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
