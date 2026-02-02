<?php

namespace App\Controller\Caisse;

use App\Entity\Societe;
use App\Form\SocieteType;
use App\Repository\SocieteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/parametres/societe')]
class SocieteController extends AbstractController
{
    #[Route('/', name: 'app_caisse_societe_index', methods: ['GET'])]
    public function index(SocieteRepository $societeRepository): Response
    {
        // En général, il n'y a qu'une seule configuration société.
        // On redirige vers l'édition de la première trouvée, ou on en crée une si aucune n'existe.
        $societe = $societeRepository->findOneBy([], ['id' => 'ASC']);

        if ($societe) {
            return $this->redirectToRoute('app_caisse_societe_edit', ['id' => $societe->getId()]);
        }
        
        // Optionnel : Créer une nouvelle si inexistante (ou rediriger vers new)
        // Pour simplifier, on affiche une page vide ou message si pas de config
        return $this->render('caisse/societe/index.html.twig', [
            'societes' => [], // Cas vide
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_societe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Societe $societe, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SocieteType::class, $societe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La configuration de la société a bien été mise à jour.');

            return $this->redirectToRoute('app_caisse_societe_edit', ['id' => $societe->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/societe/edit.html.twig', [
            'societe' => $societe,
            'form' => $form->createView(),
        ]);
    }
}
