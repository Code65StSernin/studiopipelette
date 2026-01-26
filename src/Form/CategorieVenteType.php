<?php

namespace App\Form;

use App\Entity\CategorieVente;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CategorieVenteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la catégorie',
                'attr' => ['class' => 'form-control']
            ])
            ->add('prestation', ChoiceType::class, [
                'label' => 'Type de catégorie',
                'choices' => [
                    'Vente' => false,
                    'Prestation' => true,
                ],
                'expanded' => true,
                'multiple' => false,
                'label_attr' => ['class' => 'radio-inline'],
                'attr' => ['class' => 'd-flex gap-3 my-3']
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image (Format carré recommandé)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG, WEBP)',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('couleur', ColorType::class, [
                'label' => 'Couleur de fond',
                'required' => false,
                'attr' => ['class' => 'form-control form-control-color']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CategorieVente::class,
        ]);
    }
}
