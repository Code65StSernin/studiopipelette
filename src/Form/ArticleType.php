<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\ArticleCollection;
use App\Entity\Categorie;
use App\Entity\Couleur;
use App\Entity\SousCategorie;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'article',
                'attr' => ['class' => 'form-control']
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'label' => 'Catégorie',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('sousCategorie', EntityType::class, [
                'class' => SousCategorie::class,
                'choice_label' => 'nom',
                'label' => 'Sous-catégorie',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('visibilite', ChoiceType::class, [
                'label' => 'Visibilité',
                'choices' => [
                    'En ligne' => Article::VISIBILITY_ONLINE,
                    'En boutique' => Article::VISIBILITY_SHOP,
                    'Les deux' => Article::VISIBILITY_BOTH,
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Article actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('origine', TextType::class, [
                'label' => 'Origine',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('materiau', TextType::class, [
                'label' => 'Matériau',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('collections', EntityType::class, [
                'class' => ArticleCollection::class,
                'choice_label' => 'nom',
                'label' => 'Collections',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => ['class' => 'form-select tom-select'] // Assuming TomSelect is available
            ])
            ->add('couleurs', EntityType::class, [
                'class' => Couleur::class,
                'choice_label' => 'nom',
                'label' => 'Couleurs',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'attr' => ['class' => 'form-select tom-select']
            ])
            ->add('compositionFabrication', TextareaType::class, [
                'label' => 'Composition et fabrication',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('taillesJson', TextareaType::class, [
                'label' => 'Tailles disponibles (JSON)',
                'help' => 'Format: [{"taille": "S", "prix": 15.00, "stock": 10}, ...]',
                'required' => false,
                'attr' => ['class' => 'form-control font-monospace', 'rows' => 5]
            ])
            ->add('informationsLivraison', TextareaType::class, [
                'label' => 'Informations de livraison',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('sousTitre', TextType::class, [
                'label' => 'Sous-titre',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('sousTitreContenu', TextareaType::class, [
                'label' => 'Contenu du sous-titre',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('fournisseur', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getNom() . ' ' . $user->getPrenom() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Fournisseur (Dépôt-vente)',
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.clientDepotVente = :isClientDepotVente')
                        ->setParameter('isClientDepotVente', true)
                        ->orderBy('u.nom', 'ASC');
                },
                'attr' => ['class' => 'form-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
