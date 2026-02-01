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
use App\Entity\Avoir;
use App\Entity\LigneVente;
use App\Entity\Facture;
use App\Entity\LigneFacture;
use App\Repository\FactureRepository;
use App\Service\FacturePdfGenerator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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
        $type = $data['type'] ?? 'remboursement';

        if (!$montant || !$motif || !$pin) {
            return new JsonResponse(['success' => false, 'message' => 'Données manquantes'], 400);
        }

        if ($vente->isAnnule()) {
            return new JsonResponse(['success' => false, 'message' => 'Cette vente est déjà annulée'], 400);
        }

        if (!$vente->isCommission100()) {
            return new JsonResponse(['success' => false, 'message' => 'Seules les ventes avec 100% de commission peuvent être annulées'], 400);
        }

        // Vérification si un paiement a été effectué par Avoir
        foreach ($vente->getPaiements() as $paiement) {
            if ($paiement->getMethode() === 'Avoir') {
                return new JsonResponse(['success' => false, 'message' => 'Impossible d\'annuler une vente payée totalement ou partiellement par Avoir'], 400);
            }
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

        // Calcul du ratio pour proratiser les lignes (cas d'un remboursement partiel)
        $montantOrigine = (float)$vente->getMontantTotal();
        $ratio = $montantOrigine > 0 ? ($montantAbsolu / $montantOrigine) : 1;

        // Duplication des lignes de vente en négatif (proratisées)
        foreach ($vente->getLigneVentes() as $ligneOrigine) {
            $ligneAnnulation = new LigneVente();
            $ligneAnnulation->setVente($annulation);
            $ligneAnnulation->setArticle($ligneOrigine->getArticle());
            $ligneAnnulation->setTarif($ligneOrigine->getTarif());
            $ligneAnnulation->setNom($ligneOrigine->getNom());
            // On inverse la quantité
            $ligneAnnulation->setQuantite(-$ligneOrigine->getQuantite());
            // On applique le ratio au prix unitaire pour conserver la répartition (ex: commission dépôt-vente)
            $prixProratise = $ligneOrigine->getPrixUnitaire() * $ratio;
            $ligneAnnulation->setPrixUnitaire((string)$prixProratise);
            
            $entityManager->persist($ligneAnnulation);
        }

        // Marquer la vente d'origine comme annulée
        $vente->setIsAnnule(true);

        $entityManager->persist($annulation);

        // Gestion de l'avoir si demandé
        if ($type === 'avoir') {
            $avoir = new Avoir();
            $avoir->setVente($annulation);
            $avoir->setMontant((string)$montantAbsolu);
            
            // Code temporaire pour permettre le persist et récupérer l'ID
            $avoir->setCode('TMP_' . uniqid());
            
            $entityManager->persist($avoir);
            $entityManager->flush(); // Premier flush pour l'ID
            
            // Génération du code EAN13 : 30054450 + ID sur 5 chiffres
            $code = '30054450' . str_pad((string)$avoir->getId(), 5, '0', STR_PAD_LEFT);
            $avoir->setCode($code);
            
            // Mettre à jour le commentaire de l'annulation
            $annulation->setCommentaire($annulation->getCommentaire() . ' - Avoir ' . $code);
            
            $entityManager->flush(); // Second flush pour valider le code
        } else {
            $entityManager->flush();
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/ticket', name: 'app_vente_ticket', methods: ['GET'])]
    public function ticket(Vente $vente, SocieteConfig $societeConfig): Response
    {
        return $this->render('vente/ticket.html.twig', [
            'vente' => $vente,
            'societe' => $societeConfig,
        ]);
    }

    #[Route('/', name: 'app_vente_index', methods: ['GET'])]
    public function index(Request $request, VenteRepository $venteRepository, EntityManagerInterface $entityManager, \App\Repository\UserRepository $userRepository): Response
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $clientId = $request->query->get('client');
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

        if ($clientId) {
            $qb->andWhere('v.client = :client')
               ->setParameter('client', $clientId);
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
        if ($clientId) {
            $countQb->andWhere('v.client = :client')
                ->setParameter('client', $clientId);
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
        
        $currentClient = null;
        if ($clientId) {
            $currentClient = $userRepository->find($clientId);
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
            
            // Calcul de la part payée en Bon d'achat ou Fidélité
            $nonMonetaryAmount = 0;
            foreach ($vente->getPaiements() as $paiement) {
                if (in_array($paiement->getMethode(), ['BonAchat', 'Fidelite'])) {
                    $nonMonetaryAmount += (float)$paiement->getMontant();
                }
            }

            $montantTotal = (float)$vente->getMontantTotal();
            $ratio = 1;
            
            if ($montantTotal > 0 && $nonMonetaryAmount > 0) {
                $ratio = max(0, ($montantTotal - $nonMonetaryAmount) / $montantTotal);
            }
            
            $globalTotalCommission += ($venteTotalCommission * $ratio);
            $globalTotalMontantCaisse += max(0, $montantTotal - $nonMonetaryAmount);
        }

        return $this->render('vente/index.html.twig', [
            'ventes' => $ventes,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currentClient' => $currentClient,
            'globalTotalCommission' => $globalTotalCommission,
            'globalTotalMontantCaisse' => $globalTotalMontantCaisse,
            'page' => $page,
            'pagesCount' => $pagesCount,
            'perPage' => $perPage,
            'totalResults' => $totalResults,
        ]);
    }

    #[Route('/{id}/facture', name: 'app_vente_facture', methods: ['GET'])]
    public function generateFacture(
        Vente $vente,
        EntityManagerInterface $entityManager,
        FacturePdfGenerator $pdfGenerator,
        FactureRepository $factureRepository
    ): Response {
        $facture = $vente->getFacture();

        if (!$facture) {
            $facture = new Facture();
            $facture->setVente($vente);
            
            // Génération du numéro séquentiel (FB + AAMM + NNNN)
            $sequentialNumber = $factureRepository->getNextSequentialNumberForCurrentMonth();
            $numero = $facture->generateNumero($sequentialNumber);
            $facture->setNumero($numero);
            
            $dateVente = $vente->getDateVente();
            if ($dateVente instanceof \DateTimeImmutable) {
                $dateVente = \DateTime::createFromImmutable($dateVente);
            }
            $facture->setDateCreation($dateVente);
            
            $client = $vente->getClient();
            if ($client) {
                $facture->setClientNom($client->getNom());
                $facture->setClientPrenom($client->getPrenom());
                $facture->setClientEmail($client->getEmail());
            } else {
                $facture->setClientNom('Client');
                $facture->setClientPrenom('Inconnu');
                $facture->setClientEmail('');
            }
            
            $facture->setModeLivraison('boutique');
            $facture->setFraisLivraison(0);
            $facture->setTotalTTC((int)($vente->getMontantTotal() * 100));
            
            foreach ($vente->getLigneVentes() as $ligneVente) {
                $ligneFacture = new LigneFacture();
                $ligneFacture->setFacture($facture);
                
                $designation = $ligneVente->getNom();
                if (!$designation && $ligneVente->getArticle()) {
                    $designation = $ligneVente->getArticle()->getNom();
                }
                $ligneFacture->setArticleDesignation($designation ?? 'Article inconnu');
                $ligneFacture->setArticleTaille('-');
                
                $ligneFacture->setQuantite($ligneVente->getQuantite());
                $ligneFacture->setPrixUnitaire((int)($ligneVente->getPrixUnitaire() * 100));
                $ligneFacture->setPrixTotal((int)($ligneVente->getPrixUnitaire() * $ligneVente->getQuantite() * 100));
                
                $entityManager->persist($ligneFacture);
                $facture->addLigneFacture($ligneFacture);
            }
            
            $entityManager->persist($facture);
            $vente->setFacture($facture);
            $entityManager->flush();
        }

        $pdfPath = $pdfGenerator->generate($facture);

        return new BinaryFileResponse($pdfPath, 200, [], true, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }
}
