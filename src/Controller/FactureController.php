<?php

namespace App\Controller;

use App\Repository\FactureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class FactureController extends AbstractController
{
    #[Route('/factures', name: 'app_factures')]
    public function index(FactureRepository $factureRepository, Request $request): Response
    {
        $user = $this->getUser();

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // Récupérer les factures paginées de l'utilisateur connecté
        $factures = $factureRepository->findBy(
            ['clientEmail' => $user->getUserIdentifier()],
            ['dateCreation' => 'DESC'],
            $limit,
            $offset
        );

        $total = $factureRepository->count(['clientEmail' => $user->getUserIdentifier()]);
        $totalPages = max(1, (int) ceil($total / $limit));

        return $this->render('facture/index.html.twig', [
            'factures' => $factures,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/factures/{id}/details', name: 'app_facture_details', methods: ['GET'])]
    public function details(int $id, FactureRepository $factureRepository): Response
    {
        $user = $this->getUser();
        $facture = $factureRepository->find($id);

        if (!$facture || $facture->getClientEmail() !== $user->getUserIdentifier()) {
            throw $this->createNotFoundException('Facture non trouvée');
        }

        return $this->render('facture/_details.html.twig', [
            'facture' => $facture,
        ]);
    }
}