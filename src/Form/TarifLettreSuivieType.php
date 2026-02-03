<?php

namespace App\Form;

use App\Entity\TarifLettreSuivie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TarifLettreSuivieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('poids', IntegerType::class, [
                'label' => 'Poids maximum (en grammes)',
                'attr' => ['class' => 'form-control'],
                'help' => 'Exemple: 20 pour 20g',
            ])
            ->add('tarif', MoneyType::class, [
                'label' => 'Tarif',
                'currency' => 'EUR',
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TarifLettreSuivie::class,
        ]);
    }
}
