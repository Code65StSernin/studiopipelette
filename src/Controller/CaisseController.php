<?php

namespace App\Controller;

use App\Entity\LigneVente;
use App\Entity\Vente;
use App\Repository\CategorieVenteRepository;
use App\Repository\SousCategorieVenteRepository;
use App\Repository\TarifRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CaisseController extends AbstractController
{
    #[Route('/caisse', name: 'app_caisse')]
    public function index(
        Request $request,
        CategorieVenteRepository $categorieVenteRepository,
        SousCategorieVenteRepository $sousCategorieVenteRepository,
        TarifRepository $tarifRepository
    ): Response {
        $categorieId = $request->query->get('categorie');
        $sousCategorieId = $request->query->get('sous_categorie');
        
        $currentCategorie = null;
        $currentSousCategorie = null;
        $items = [];
        $isSousCategorieView = false;
        $isTarifView = false;

        if ($sousCategorieId) {
            $currentSousCategorie = $sousCategorieVenteRepository->find($sousCategorieId);
            if ($currentSousCategorie) {
                $currentCategorie = $currentSousCategorie->getCategorie();
                $items = $tarifRepository->findBy(['sousCategorieVente' => $currentSousCategorie]);
                $isTarifView = true;
            }
        } elseif ($categorieId) {
            $currentCategorie = $categorieVenteRepository->find($categorieId);
            if ($currentCategorie) {
                $items = $sousCategorieVenteRepository->findBy(['categorie' => $currentCategorie]);
                $isSousCategorieView = true;
            }
        } else {
            $items = $categorieVenteRepository->findAll();
        }

        return $this->render('caisse/index.html.twig', [
            'items' => $items,
            'currentCategorie' => $currentCategorie,
            'currentSousCategorie' => $currentSousCategorie,
            'isSousCategorieView' => $isSousCategorieView,
            'isTarifView' => $isTarifView,
        ]);
    }

    #[Route('/caisse/client-search', name: 'app_caisse_client_search', methods: ['GET'])]
    public function searchClient(Request $request, UserRepository $userRepository): JsonResponse
    {
        $term = trim((string) $request->query->get('q', ''));

        if ($term === '') {
            return new JsonResponse([]);
        }

        $qb = $userRepository->createQueryBuilder('u')
            ->where('u.nom LIKE :term OR u.prenom LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->setMaxResults(20);

        $users = $qb->getQuery()->getResult();

        $results = [];

        foreach ($users as $user) {
            $labelParts = [];
            if (method_exists($user, 'getNom') && $user->getNom()) {
                $labelParts[] = $user->getNom();
            }
            if (method_exists($user, 'getPrenom') && $user->getPrenom()) {
                $labelParts[] = $user->getPrenom();
            }

            $results[] = [
                'value' => $user->getId(),
                'label' => implode(' ', $labelParts),
            ];
        }

        return new JsonResponse($results);
    }

    #[Route('/caisse/valider', name: 'app_caisse_valider', methods: ['POST'])]
    public function valider(
        Request $request, 
        UserRepository $userRepository, 
        TarifRepository $tarifRepository, 
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $clientId = $data['client'] ?? null;
        $total = $data['total'] ?? 0;
        $method = $data['method'] ?? 'Inconnu';
        $items = $data['items'] ?? [];

        if (!$clientId) {
            return new JsonResponse(['status' => 'error', 'message' => 'Client manquant'], 400);
        }

        $client = $userRepository->find($clientId);
        if (!$client) {
            return new JsonResponse(['status' => 'error', 'message' => 'Client introuvable'], 404);
        }

        $vente = new Vente();
        $vente->setClient($client);
        $vente->setMontantTotal((string) $total);
        $vente->setModePaiement($method);
        // DateVente is set in constructor

        $entityManager->persist($vente);

        foreach ($items as $itemData) {
            $ligneVente = new LigneVente();
            $ligneVente->setVente($vente);
            $ligneVente->setNom($itemData['name']);
            $ligneVente->setPrixUnitaire((string) $itemData['price']);
            $ligneVente->setQuantite(1); // Assuming 1 per item in the cart array as structured in JS

            if (isset($itemData['id'])) {
                $tarif = $tarifRepository->find($itemData['id']);
                if ($tarif) {
                    $ligneVente->setTarif($tarif);
                }
            }

            $entityManager->persist($ligneVente);
        }

        $entityManager->flush();

        return new JsonResponse(['status' => 'success', 'id' => $vente->getId()]);
    }
}
