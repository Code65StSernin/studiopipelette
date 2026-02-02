<?php

namespace App\Form;

use App\Entity\DepotVente;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepotVenteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du dépôt-vente',
                'attr' => ['class' => 'form-control']
            ])
            ->add('commission', IntegerType::class, [
                'label' => 'Commission (%)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('users', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getPrenom() . ' ' . $user->getNom();
                },
                'multiple' => true,
                'expanded' => false,
                'label' => 'Clients Dépôt-Vente',
                'attr' => ['class' => 'form-control tom-select'],
                'by_reference' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.clientDepotVente = :val')
                        ->setParameter('val', true)
                        ->orderBy('u.nom', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DepotVente::class,
        ]);
    }
}
