<?php

namespace App\Controller;

use App\Entity\FondCaisse;
use App\Repository\VenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Vente;
use App\Entity\LigneVente;
use App\Service\SocieteConfig;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/caisse/ventes')]
class VenteController extends AbstractController
{
    #[Route('/{id}/annuler', name: 'app_vente_annuler', methods: ['POST'])]
    public function annuler(
        Request $request,
        Vente $vente,
        EntityManagerInterface $entityManager,
        SocieteConfig $societeConfig
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $montant = $data['montant'] ?? null;
        $motif = $data['motif'] ?? null;
        $pin = $data['pin'] ?? null;

        if (!$montant || !$motif || !$pin) {
            return new JsonResponse(['success' => false, 'message' => 'Données manquantes'], 400);
        }

        // Vérification PIN
        if (!hash_equals($societeConfig->getAdminPin(), trim($pin))) {
            return new JsonResponse(['success' => false, 'message' => 'Code PIN incorrect'], 403);
        }

        // Création de la vente d'annulation (montant négatif)
        $annulation = new Vente();
        $annulation->setDateVente(new \DateTimeImmutable());
        $annulation->setClient($vente->getClient());
        $annulation->setModePaiement($vente->getModePaiement());
        $annulation->setCommentaire($motif . ' (Annulation vente #' . $vente->getId() . ')');
        
        // On s'assure que le montant est négatif
        $montantAbsolu = abs((float)str_replace(',', '.', $montant));
        $annulation->setMontantTotal((string)(-$montantAbsolu));

        $entityManager->persist($annulation);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/', name: 'app_vente_index', methods: ['GET'])]
    public function index(Request $request, VenteRepository $venteRepository, EntityManagerInterface $entityManager): Response
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;

        $criteria = [];
        // Filtering logic will be implemented here or in repository
        // Simple implementation: fetch all and filter or use query builder
        
        $qb = $venteRepository->createQueryBuilder('v')
            ->orderBy('v.dateVente', 'DESC');

        if ($startDate) {
            $qb->andWhere('v.dateVente >= :startDate')
               ->setParameter('startDate', new \DateTime($startDate));
        }

        if ($endDate) {
            // Add 23:59:59 to end date to include the whole day
            $endDateTime = new \DateTime($endDate);
            $endDateTime->setTime(23, 59, 59);
            $qb->andWhere('v.dateVente <= :endDate')
               ->setParameter('endDate', $endDateTime);
        }

        $countQb = $venteRepository->createQueryBuilder('v')
            ->select('COUNT(v.id)');
        if ($startDate) {
            $countQb->andWhere('v.dateVente >= :startDate')
                ->setParameter('startDate', new \DateTime($startDate));
        }
        if ($endDate) {
            $endDateTime = new \DateTime($endDate);
            $endDateTime->setTime(23, 59, 59);
            $countQb->andWhere('v.dateVente <= :endDate')
                ->setParameter('endDate', $endDateTime);
        }
        $totalResults = (int) $countQb->getQuery()->getSingleScalarResult();
        $pagesCount = max(1, (int) ceil($totalResults / $perPage));
        if ($page > $pagesCount) {
            $page = $pagesCount;
        }

        // Clone query builder for totals calculation before pagination
        $qbTotal = clone $qb;
        $allVentes = $qbTotal->getQuery()->getResult();

        $qb->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage);

        $ventes = $qb->getQuery()->getResult();

        // Sécurise les ventes dont le client référencé a été supprimé
        foreach ($ventes as $vente) {
            try {
                if ($vente->getClient()) {
                    // Force l'initialisation du proxy via l'accès à une propriété non-identifiante
                    $vente->getClient()->getNom();
                }
            } catch (\Doctrine\ORM\EntityNotFoundException $e) {
                // Si l'entité Client n'existe plus en base, on neutralise la relation
                $vente->setClient(null);
            }
        }

        $globalTotalCommission = 0;
        $globalTotalMontantCaisse = 0;

        foreach ($allVentes as $vente) {
            $venteTotalCommission = 0;
            
            foreach ($vente->getLigneVentes() as $ligne) {
                $isDepot = false;
                $commissionRate = 0;
                $article = $ligne->getArticle();
                
                if ($article && $article->getFournisseur() && $article->getFournisseur()->isClientDepotVente()) {
                    $isDepot = true;
                    $depotVente = $article->getFournisseur()->getDepotVente();
                    if ($depotVente) {
                        $commissionRate = $depotVente->getCommission();
                    }
                }

                $ligneMontant = $ligne->getPrixUnitaire() * $ligne->getQuantite();

                if ($isDepot) {
                    $commissionAmount = $ligneMontant * $commissionRate / 100;
                    $venteTotalCommission += $commissionAmount;
                } else {
                    $venteTotalCommission += $ligneMontant;
                }
            }
            
            $globalTotalCommission += $venteTotalCommission;
            $globalTotalMontantCaisse += $vente->getMontantTotal();
        }

        return $this->render('vente/index.html.twig', [
            'ventes' => $ventes,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'globalTotalCommission' => $globalTotalCommission,
            'globalTotalMontantCaisse' => $globalTotalMontantCaisse,
            'page' => $page,
            'pagesCount' => $pagesCount,
            'perPage' => $perPage,
            'totalResults' => $totalResults,
        ]);
    }
}
