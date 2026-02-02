<?php

namespace App\Controller\Caisse;

use App\Entity\Order;
use App\Entity\Vente;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use App\Repository\ArticleRepository;
use App\Repository\LigneFactureRepository;
use App\Repository\DepensesRepository;
use App\Repository\RecetteRepository;
use App\Repository\VenteRepository;
use App\Service\SocieteConfig;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')] // Or ROLE_CAISSE? User said "Admin Dashboard in Caisse", so likely needs permissions. Keeping Admin for now as it shows sensitive financial data.
class DashboardController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private OrderRepository $orderRepository,
        private ArticleRepository $articleRepository,
        private LigneFactureRepository $ligneFactureRepository,
        private DepensesRepository $depensesRepository,
        private RecetteRepository $recetteRepository,
        private VenteRepository $venteRepository,
        private RequestStack $requestStack,
        private SocieteConfig $societeConfig,
    ) {}

    #[Route('/caisse/dashboard', name: 'app_caisse_dashboard')]
    public function index(): Response
    {
        $request = $this->requestStack->getCurrentRequest();
        $now = new \DateTimeImmutable();

        // --- FILTRES DE DATE ---
        $defaultFrom = $now->modify('first day of this month')->setTime(0, 0);
        $defaultTo = $now->setTime(23, 59, 59);

        $fromParam = $request?->query->get('from');
        $toParam = $request?->query->get('to');

        $fromFilter = $defaultFrom;
        if ($fromParam) {
            $tmp = \DateTimeImmutable::createFromFormat('Y-m-d', $fromParam);
            if ($tmp instanceof \DateTimeImmutable) {
                $fromFilter = $tmp->setTime(0, 0);
            }
        }

        $toFilter = $defaultTo;
        if ($toParam) {
            $tmp = \DateTimeImmutable::createFromFormat('Y-m-d', $toParam);
            if ($tmp instanceof \DateTimeImmutable) {
                $toFilter = $tmp->setTime(23, 59, 59);
            }
        }

        // --- 1. CHIFFRES CLÉS EN LIGNE (Order) ---
        // Commandes payées
        $paidOrdersRange = (int) $this->orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.status = :status')
            ->andWhere('o.createdAt BETWEEN :from AND :to')
            ->setParameter('status', Order::STATUS_PAID)
            ->setParameter('from', $fromFilter)
            ->setParameter('to', $toFilter)
            ->getQuery()
            ->getSingleScalarResult();

        // CA Online (Tout est BIC)
        $caOnlineCents = (int) $this->orderRepository->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.amountTotalCents), 0)')
            ->andWhere('o.status = :status')
            ->andWhere('o.createdAt BETWEEN :from AND :to')
            ->setParameter('status', Order::STATUS_PAID)
            ->setParameter('from', $fromFilter)
            ->setParameter('to', $toFilter)
            ->getQuery()
            ->getSingleScalarResult();

        // --- 2. CHIFFRES CLÉS CAISSE (Vente) ---
        $ventesRange = $this->venteRepository->createQueryBuilder('v')
            ->andWhere('v.dateVente BETWEEN :from AND :to')
            ->andWhere('v.isAnnule = :false')
            ->setParameter('from', $fromFilter)
            ->setParameter('to', $toFilter)
            ->setParameter('false', false)
            ->getQuery()
            ->getResult();

        $nbVentesCaisse = count($ventesRange);
        $caCaisseTotal = 0.0;
        $caCaisseBic = 0.0;
        $caCaisseBnc = 0.0;
        $fraisCbCaisseTotal = 0.0;
        $tpeFraisPourcentage = $this->societeConfig->getTpeFraisPourcentage() ?? 1.75;

        foreach ($ventesRange as $vente) {
            /** @var Vente $vente */
            $montantTotal = (float)$vente->getMontantTotal();
            
            // Calculer paiements non monétaires (ex: Bon d'achat)
            $nonMonetaryAmount = 0.0;
            foreach ($vente->getPaiements() as $paiement) {
                $methode = $paiement->getMethode();
                if (in_array($methode, ['BonAchat', 'Fidelite'])) {
                    $nonMonetaryAmount += (float)$paiement->getMontant();
                } elseif ($methode === 'CB') {
                    $fraisCbCaisseTotal += (float)$paiement->getMontant() * ($tpeFraisPourcentage / 100);
                }
            }

            // CA Réel = Total - Paiements internes
            $realCA = max(0, $montantTotal - $nonMonetaryAmount);
            $caCaisseTotal += $realCA;

            if ($realCA > 0) {
                // Répartition BIC (Articles) / BNC (Tarifs)
                $sumBic = 0.0;
                $sumBnc = 0.0;

                foreach ($vente->getLigneVentes() as $ligne) {
                    $ligneTotal = (float)$ligne->getPrixUnitaire() * $ligne->getQuantite();
                    if ($ligne->getArticle() !== null) {
                        $sumBic += $ligneTotal;
                    } elseif ($ligne->getTarif() !== null) {
                        $sumBnc += $ligneTotal;
                    } else {
                        // Par défaut, si pas d'info (ex: suppression), on met en BIC
                        $sumBic += $ligneTotal;
                    }
                }

                $sumLines = $sumBic + $sumBnc;
                if ($sumLines > 0) {
                    $ratioBic = $sumBic / $sumLines;
                    $ratioBnc = $sumBnc / $sumLines;
                    $caCaisseBic += ($realCA * $ratioBic);
                    $caCaisseBnc += ($realCA * $ratioBnc);
                } else {
                    $caCaisseBic += $realCA;
                }
            }
        }

        // Conversion CA Caisse en centimes pour homogénéité
        $caCaisseCents = (int)round($caCaisseTotal * 100);
        $caCaisseBicCents = (int)round($caCaisseBic * 100);
        $caCaisseBncCents = (int)round($caCaisseBnc * 100);

        // --- 3. CONSOLIDATION & CHARGES ---
        $caGlobalCents = $caOnlineCents + $caCaisseCents;
        $caGlobalEuros = $caGlobalCents / 100;

        // BIC Total = Online (tout est BIC) + Caisse BIC
        $caTotalBicEuros = ($caOnlineCents / 100) + $caCaisseBic;
        // BNC Total = Caisse BNC
        $caTotalBncEuros = $caCaisseBnc;

        // Configuration Taux
        $pourcentageUrssafLegacy = $this->societeConfig->getPourcentageUrssaf() ?? 0;
        $pourcentageUrssafBic = $this->societeConfig->getPourcentageUrssafBic() ?? $pourcentageUrssafLegacy;
        $pourcentageUrssafBnc = $this->societeConfig->getPourcentageUrssafBnc() ?? $pourcentageUrssafLegacy;
        $pourcentageCpf = $this->societeConfig->getPourcentageCpf() ?? 0;
        $pourcentageIr = $this->societeConfig->getPourcentageIr() ?? 0;

        // Calcul Charges
        $chargesBic = $caTotalBicEuros * ($pourcentageUrssafBic / 100);
        $chargesBnc = $caTotalBncEuros * ($pourcentageUrssafBnc / 100);
        $chargesCpf = $caGlobalEuros * ($pourcentageCpf / 100);
        $chargesSociales = $chargesBic + $chargesBnc + $chargesCpf;
        $impotRevenu = $caGlobalEuros * ($pourcentageIr / 100);
        $chargesAPayer = $chargesSociales + $impotRevenu;

        // Frais Bancaires (Stripe + TPE)
        $stripeFraisPourcentage = $this->societeConfig->getStripeFraisPourcentage() ?? 1.5;
        $stripeFraisFixe = $this->societeConfig->getStripeFraisFixe() ?? 0.25;
        $fraisStripe = (($caOnlineCents / 100) * ($stripeFraisPourcentage / 100)) + ($paidOrdersRange * $stripeFraisFixe);
        $fraisPaiementCb = $fraisStripe + $fraisCbCaisseTotal;

        // Résultats
        $caEncaisseEuros = $caGlobalEuros - $fraisPaiementCb;
        
        // Dépenses
        $depensesTotal = (float) $this->depensesRepository->createQueryBuilder('d')
            ->select('COALESCE(SUM(d.montant), 0)')
            ->andWhere('d.date BETWEEN :from AND :to')
            ->setParameter('from', $fromFilter->format('Y-m-d'))
            ->setParameter('to', $toFilter->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        $resultatBrut = $caEncaisseEuros - $chargesAPayer;
        $resultatNet = $resultatBrut - $depensesTotal;

        // Trésorerie
        $depensesPointees = (float) $this->depensesRepository->createQueryBuilder('d')
            ->select('COALESCE(SUM(d.montant), 0)')
            ->andWhere('d.pointage = :pointe')
            ->andWhere('d.date BETWEEN :from AND :to')
            ->setParameter('pointe', true)
            ->setParameter('from', $fromFilter->format('Y-m-d'))
            ->setParameter('to', $toFilter->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        $recettesPointees = (float) $this->recetteRepository->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.montant), 0)')
            ->andWhere('r.pointage = :pointe')
            ->andWhere('r.date BETWEEN :from AND :to')
            ->setParameter('pointe', true)
            ->setParameter('from', $fromFilter->format('Y-m-d'))
            ->setParameter('to', $toFilter->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        $depensesNonPointees = max(0.0, $depensesTotal - $depensesPointees);
        $tresorerieTheorique = $caEncaisseEuros - $chargesAPayer - $depensesNonPointees;
        $tresorerieReelle = $recettesPointees - $depensesPointees;

        // --- 4. DATA POUR GRAPHIQUES ---
        $salesPeriod = $request->query->get('salesPeriod', 'day'); // day, week, month, year
        $groupBy = $salesPeriod === 'year' ? 'month' : 'day';
        
        // Définir la plage de dates pour le graph
        $onlineSeries = $this->orderRepository->getSalesSeries($fromFilter, $groupBy);
        $caisseSeries = $this->venteRepository->getSalesSeries($fromFilter, $groupBy);

        // Fusionner les labels et les données
        $chartLabels = [];
        $chartDataOnline = [];
        $chartDataCaisse = [];

        $cursor = clone $fromFilter;
        $end = clone $toFilter;
        
        // Indexer les résultats
        $onlineIndexed = [];
        foreach ($onlineSeries as $row) { $onlineIndexed[$row['label']] = $row['total']; }
        $caisseIndexed = [];
        foreach ($caisseSeries as $row) { $caisseIndexed[$row['label']] = $row['total']; }

        while ($cursor <= $end) {
            $label = match ($groupBy) {
                'year', 'month' => $cursor->format('m/Y'),
                'week' => 'S' . $cursor->format('W') . ' ' . $cursor->format('Y'),
                default => $cursor->format('d/m'),
            };

            // Éviter les doublons si on boucle jour par jour mais qu'on groupe par mois
            if (!in_array($label, $chartLabels)) {
                $chartLabels[] = $label;
                $chartDataOnline[] = ($onlineIndexed[$label] ?? 0) / 100; // En euros
                $chartDataCaisse[] = ($caisseIndexed[$label] ?? 0) / 100; // En euros
            }

            // Incrémenter
            if ($groupBy === 'month' || $groupBy === 'year') {
                $cursor = $cursor->modify('first day of next month');
            } else {
                $cursor = $cursor->modify('+1 day');
            }
        }

        // --- 5. TOP ARTICLES ---
        $topOnline = $this->ligneFactureRepository->findTopArticlesOnline($fromFilter, $toFilter, 5);
        $topCaisse = $this->ligneFactureRepository->findTopArticlesCaisse($fromFilter, $toFilter, 5);

        // --- 6. RUPTURES DE STOCK ---
        $outOfStockArticles = [];
        $outOfStockSizes = 0;
        foreach ($this->articleRepository->findBy(['actif' => true]) as $article) {
            $hasZero = false;
            foreach (($article->getTailles() ?? []) as $t) {
                if (($t['stock'] ?? 0) <= 0) {
                    $outOfStockSizes++;
                    $hasZero = true;
                }
            }
            if ($hasZero) {
                $outOfStockArticles[] = $article;
            }
        }

        $stats = [
            'totalUsers' => $this->userRepository->count([]),
            'newUsers' => 0,
            'nbVentesCaisse' => $nbVentesCaisse,
            'paidOrders' => $paidOrdersRange,
            
            'caGlobal' => $caGlobalEuros,
            'caOnline' => $caOnlineCents / 100,
            'caCaisse' => $caCaisseTotal,
            
            'chargesBic' => $chargesBic,
            'chargesBnc' => $chargesBnc,
            'chargesCpf' => $chargesCpf,
            'chargesSociales' => $chargesSociales,
            'impotRevenu' => $impotRevenu,
            'chargesAPayer' => $chargesAPayer,
            
            'resultatBrut' => $resultatBrut,
            'resultatNet' => $resultatNet,
            'depensesTotal' => $depensesTotal,
            
            'tresorerieTheorique' => $tresorerieTheorique,
            'tresorerieReelle' => $tresorerieReelle,
            
            'outOfStockSizes' => $outOfStockSizes,
        ];
        
        $stats['newUsers'] = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $fromFilter)
            ->setParameter('to', $toFilter)
            ->getQuery()
            ->getSingleScalarResult();


        return $this->render('caisse/dashboard/index.html.twig', [
            'stats' => $stats,
            'chart' => [
                'labels' => $chartLabels,
                'online' => $chartDataOnline,
                'caisse' => $chartDataCaisse,
            ],
            'topOnline' => $topOnline,
            'topCaisse' => $topCaisse,
            'outOfStockArticles' => $outOfStockArticles,
            'filters' => [
                'from' => $fromFilter->format('Y-m-d'),
                'to' => $toFilter->format('Y-m-d'),
                'salesPeriod' => $salesPeriod,
            ],
            'repartition' => [
                'bic' => $caTotalBicEuros,
                'bnc' => $caTotalBncEuros,
            ]
        ]);
    }
}
