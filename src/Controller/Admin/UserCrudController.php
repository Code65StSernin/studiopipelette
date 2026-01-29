<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setPageTitle('index', 'Gestion des utilisateurs')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield EmailField::new('email', 'Email');
        yield TextField::new('nom', 'Nom');
        yield TextField::new('prenom', 'Prénom');
        yield TextField::new('telephone', 'Téléphone');
        yield BooleanField::new('clientDepotVente', 'Client Dépôt-Vente');
        yield ImageField::new('image', 'Photo de profil')
            ->setBasePath('uploads/users')
            ->setUploadDir('public/uploads/users')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false);
        yield AssociationField::new('btoB', 'Client BtoB')
            ->setHelp('Associer cet utilisateur à un client BtoB (optionnel)');
        yield AssociationField::new('depotVente', 'Groupe Dépôt-Vente')
            ->setHelp('Associer cet utilisateur à un groupe Dépôt-Vente (optionnel)');
        yield ArrayField::new('roles', 'Rôles');
        yield BooleanField::new('isVerified', 'Email vérifié');
        yield DateTimeField::new('createdAt', 'Date de création')->onlyOnIndex();
        
        // Le mot de passe n'est PAS affiché dans le CRUD
        // Pour modifier un mot de passe, l'admin devra utiliser la fonctionnalité de réinitialisation
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        // Générer un mot de passe aléatoire sécurisé pour les nouveaux utilisateurs
        if (!$entityInstance->getId()) {
            $randomPassword = $this->generateRandomPassword();
            $hashedPassword = $this->passwordHasher->hashPassword($entityInstance, $randomPassword);
            $entityInstance->setPassword($hashedPassword);
            
            // Affecter le rôle ROLE_USER par défaut si aucun rôle n'est défini
            if (empty($entityInstance->getRoles()) || $entityInstance->getRoles() === ['ROLE_USER']) {
                $entityInstance->setRoles(['ROLE_USER']);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Génère un mot de passe aléatoire sécurisé
     * Respecte les règles : 12 caractères, majuscule, minuscule, chiffre, caractère spécial
     */
    private function generateRandomPassword(): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()';
        
        // Garantir au moins un de chaque type
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        // Compléter jusqu'à 12 caractères avec des caractères aléatoires
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 0; $i < 8; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Mélanger les caractères
        return str_shuffle($password);
    }
}

