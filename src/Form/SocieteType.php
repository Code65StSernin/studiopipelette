<?php

namespace App\Form;

use App\Entity\Societe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SocieteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Infos société
            ->add('nom', TextType::class, [
                'label' => 'Nom de la société',
                'attr' => ['class' => 'form-control']
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse postale',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email de contact',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('siret', TextType::class, [
                'label' => 'Numéro SIRET',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('codeNaf', TextType::class, [
                'label' => 'Code NAF',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])

            // Réseaux Sociaux
            ->add('lienFacebook', TextType::class, [
                'label' => 'Lien Facebook',
                'required' => false,
                'help' => 'URL complète (ex: https://facebook.com/votrepage)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('lienInstagram', TextType::class, [
                'label' => 'Lien Instagram',
                'required' => false,
                'help' => 'URL complète (ex: https://instagram.com/votrecompte)',
                'attr' => ['class' => 'form-control']
            ])

            // Transporteurs
            ->add('enableMondialRelay', CheckboxType::class, [
                'label' => 'Activer Mondial Relay',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('enableLettreSuivie', CheckboxType::class, [
                'label' => 'Activer Lettre Suivie',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])

            // Mondial Relay
            ->add('mondialRelayLogin', TextType::class, [
                'label' => 'Login',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('mondialRelayPassword', TextType::class, [
                'label' => 'Mot de passe',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('mondialRelayCustomerId', TextType::class, [
                'label' => 'Customer ID',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('mondialRelayBrand', TextType::class, [
                'label' => 'Brand',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])

            // Stripe
            ->add('stripePublicKey', TextType::class, [
                'label' => 'Clé publique',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('stripeSecretKey', TextType::class, [
                'label' => 'Clé secrète',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('stripeWebhookSecret', TextType::class, [
                'label' => 'Secret webhook',
                'required' => false,
                'help' => 'Secret utilisé pour vérifier la signature des webhooks Stripe',
                'attr' => ['class' => 'form-control']
            ])

            // Frais Bancaires
            ->add('stripeFraisPourcentage', NumberType::class, [
                'label' => 'Stripe %',
                'scale' => 2,
                'required' => false,
                'help' => 'Commission Stripe variable (ex: 1.5%)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('stripeFraisFixe', NumberType::class, [
                'label' => 'Stripe Fixe (€)',
                'scale' => 2,
                'required' => false,
                'help' => 'Frais fixe par transaction Stripe (ex: 0.25€)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('tpeFraisPourcentage', NumberType::class, [
                'label' => 'TPE Caisse %',
                'scale' => 2,
                'required' => false,
                'help' => 'Commission TPE pour les paiements CB en caisse (ex: 1.75%)',
                'attr' => ['class' => 'form-control']
            ])

            // Base de données
            ->add('dbHost', TextType::class, [
                'label' => 'Hôte',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('dbName', TextType::class, [
                'label' => 'Nom de la base',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('dbUser', TextType::class, [
                'label' => 'Utilisateur',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('dbPassword', TextType::class, [
                'label' => 'Mot de passe',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])

            // SMTP / Email
            ->add('smtpHost', TextType::class, [
                'label' => 'Hôte SMTP',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('smtpPort', IntegerType::class, [
                'label' => 'Port SMTP',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('smtpUser', TextType::class, [
                'label' => 'Utilisateur SMTP',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('smtpPassword', TextType::class, [
                'label' => 'Mot de passe SMTP',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('smtpFromEmail', EmailType::class, [
                'label' => 'Email Expéditeur',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])

            // Charges sociales et fiscales
            ->add('pourcentageUrssafBic', NumberType::class, [
                'label' => 'URSSAF BIC %',
                'scale' => 2,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('pourcentageUrssafBnc', NumberType::class, [
                'label' => 'URSSAF BNC %',
                'scale' => 2,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('pourcentageCpf', NumberType::class, [
                'label' => 'CPF %',
                'scale' => 2,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('pourcentageIr', NumberType::class, [
                'label' => 'Impôt sur le Revenu %',
                'scale' => 2,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('totalSite', NumberType::class, [
                'label' => 'Coût total site (€)',
                'scale' => 2,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('pourcentageMensuel', NumberType::class, [
                'label' => 'Charge mensuelle %',
                'scale' => 2,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Societe::class,
        ]);
    }
}
