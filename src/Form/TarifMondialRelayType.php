<?php

namespace App\Form;

use App\Entity\TarifMondialRelay;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TarifMondialRelayType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('poids', IntegerType::class, [
                'label' => 'Poids maximum (en grammes)',
                'attr' => ['class' => 'form-control'],
                'help' => 'Exemple: 1000 pour 1kg',
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
            'data_class' => TarifMondialRelay::class,
        ]);
    }
}
