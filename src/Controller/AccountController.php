<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController
{
    #[Route('/api/delete-account', name: 'app_delete_account', methods: ['POST'])]
    public function deleteAccount(
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security
    ): JsonResponse {
        // Vérifier que l'utilisateur est connecté
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour supprimer votre compte.'], 401);
        }

        // Vérifier la phrase de confirmation
        $confirmationText = $request->request->get('confirmation_text');
        
        if ($confirmationText !== 'Oui, je veux supprimer mon compte') {
            return new JsonResponse(['error' => 'La phrase de confirmation est incorrecte.'], 400);
        }

        // Supprimer le compte
        $entityManager->remove($user);
        $entityManager->flush();

        // Déconnecter l'utilisateur
        $security->logout(false);

        return new JsonResponse([
            'success' => true,
            'message' => 'Votre compte a été supprimé avec succès. Nous sommes désolés de vous voir partir.'
        ]);
    }
}

