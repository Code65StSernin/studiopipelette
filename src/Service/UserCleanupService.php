<?php

namespace App\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserCleanupService
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Supprime les comptes non vérifiés créés il y a plus de 24 heures
     * 
     * @return int Nombre de comptes supprimés
     */
    public function removeUnverifiedOldAccounts(): int
    {
        // Date limite : 24 heures en arrière
        $limitDate = new \DateTimeImmutable('-24 hours');
        
        // Récupérer tous les utilisateurs non vérifiés créés avant la date limite
        $usersToDelete = $this->userRepository->createQueryBuilder('u')
            ->where('u.isVerified = :isVerified')
            ->andWhere('u.createdAt < :limitDate')
            ->setParameter('isVerified', false)
            ->setParameter('limitDate', $limitDate)
            ->getQuery()
            ->getResult();
        
        $count = count($usersToDelete);
        
        // Supprimer les utilisateurs
        foreach ($usersToDelete as $user) {
            $this->entityManager->remove($user);
        }
        
        $this->entityManager->flush();
        
        return $count;
    }
}

