<?php

namespace App\Form;

use App\Entity\Offre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class OffreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('petitTexte', TextType::class, [
                'label' => 'Petit texte',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Offre spéciale']
            ])
            ->add('grandTexte', TextType::class, [
                'label' => 'Grand texte',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: -20% sur tout']
            ])
            ->add('texteBouton', TextType::class, [
                'label' => 'Texte du bouton',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: J\'en profite']
            ])
            ->add('lienBouton', TextType::class, [
                'label' => 'Lien du bouton',
                'required' => false,
                'help' => 'URL absolue ou nom de route Symfony',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['class' => 'form-control']
            ])
            ->add('imageGauche', CheckboxType::class, [
                'label' => 'Image à gauche ?',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label']
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image (PNG uniquement)',
                'mapped' => false,
                'required' => $options['is_new'],
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image PNG valide',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offre::class,
            'is_new' => false,
        ]);
        $resolver->setAllowedTypes('is_new', 'bool');
    }
}
