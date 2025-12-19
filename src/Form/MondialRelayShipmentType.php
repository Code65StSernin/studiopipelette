<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MondialRelayShipmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Order $order */
        $order = $options['data'] ?? null;

        $builder
            ->add('mondialRelayRecipientLastName', TextType::class, [
                'label' => 'Nom du destinataire',
                'required' => true,
            ])
            ->add('mondialRelayRecipientFirstName', TextType::class, [
                'label' => 'Prénom du destinataire',
                'required' => true,
            ])
            ->add('mondialRelayParcelsCount', IntegerType::class, [
                'label' => 'Nombre de colis',
                'required' => true,
                'empty_data' => 1,
            ])
            ->add('mondialRelayContentValueCents', MoneyType::class, [
                'label' => 'Valeur du contenu (TTC)',
                'required' => true,
                'currency' => 'EUR',
                'divisor' => 100,
            ])
            ->add('mondialRelayContentDescription', TextType::class, [
                'label' => 'Contenu',
                'required' => true,
                'empty_data' => 'Bougies décoratives',
            ])
            ->add('mondialRelayLengthCm', IntegerType::class, [
                'label' => 'Longueur (cm)',
                'required' => true,
            ])
            ->add('mondialRelayWidthCm', IntegerType::class, [
                'label' => 'Largeur (cm)',
                'required' => true,
            ])
            ->add('mondialRelayHeightCm', IntegerType::class, [
                'label' => 'Profondeur (cm)',
                'required' => true,
            ])
            ->add('mondialRelayWeightKg', NumberType::class, [
                'label' => 'Poids total (kg)',
                'required' => true,
                'scale' => 2,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}

