<?php

namespace App\Form;

use App\Entity\BtoB;
use App\Entity\DepotVente;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Nom du client']
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Prénom du client']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'Email du client']
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['placeholder' => 'Téléphone du client']
            ])
            ->add('image', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WEBP)',
                    ])
                ],
            ])
            ->add('btoB', EntityType::class, [
                'class' => BtoB::class,
                'choice_label' => 'nom', // Assumons que BtoB a une propriété 'nom' ou __toString
                'label' => 'Client BtoB',
                'required' => false,
                'placeholder' => 'Aucun',
            ])
            ->add('depotVente', EntityType::class, [
                'class' => DepotVente::class,
                'choice_label' => 'nom', // Assumons que DepotVente a une propriété 'nom'
                'label' => 'Groupe Dépôt-Vente',
                'required' => false,
                'placeholder' => 'Aucun',
            ])
            ->add('clientDepotVente', CheckboxType::class, [
                'label' => 'Client Dépôt-Vente',
                'required' => false,
            ])
            ->add('isVerified', CheckboxType::class, [
                'label' => 'Email vérifié',
                'required' => false,
            ])
            ->add('canBookOnline', CheckboxType::class, [
                'label' => 'Peut réserver en ligne',
                'required' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('fideliteCagnotte', NumberType::class, [
                'label' => 'Cagnotte (€)',
                'required' => false,
                'scale' => 2,
            ])
            ->add('fidelitePoints', NumberType::class, [
                'label' => 'Points fidélité',
                'required' => false,
                'scale' => 2,
            ])
            ->add('fideliteVisits', IntegerType::class, [
                'label' => 'Nombre de visites',
                'required' => false,
            ])
            ->add('fiche', TextareaType::class, [
                'label' => 'Fiche client',
                'required' => false,
                'attr' => [
                    'class' => 'wysiwyg-editor',
                    'rows' => 10,
                    'placeholder' => 'Notes sur le client...'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
