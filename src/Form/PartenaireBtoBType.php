<?php

namespace App\Form;

use App\Entity\BtoB;
use App\Entity\Categorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PartenaireBtoBType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du partenaire',
                'attr' => ['class' => 'form-control']
            ])
            ->add('remiseParCategorie', IntegerType::class, [
                'label' => 'Remise par catégorie (%)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('categories', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => false, // Using TomSelect or similar if available, else standard select
                'label' => 'Catégories concernées',
                'attr' => ['class' => 'form-control tom-select'], // Assuming tom-select class handles multi-select nicely
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BtoB::class,
        ]);
    }
}
