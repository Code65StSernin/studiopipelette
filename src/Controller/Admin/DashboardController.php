<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\Vente;
use App\Entity\User;
use App\Entity\NewsletterSubscriber;
use App\Entity\Reservation;
use App\Entity\Article;
use App\Entity\ArticleCollection;
use App\Entity\Categorie;
use App\Entity\SousCategorie;
use App\Entity\Couleur;
use App\Entity\Tarif;
use App\Entity\Code;
use App\Entity\BtoB;
use App\Entity\Photo;
use App\Entity\DepotVente;
use App\Entity\Carousel;
use App\Entity\Faq;
use App\Entity\Cgv;
use App\Entity\PrivacyPolicy;
use App\Entity\CookiePolicy;
use App\Entity\Societe;
use App\Entity\Recette;
use App\Entity\Depenses;
use App\Entity\UnavailabilityRule;
use App\Entity\DispoPrestation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use App\Repository\ArticleRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\LigneFactureRepository;
use App\Repository\FactureRepository;
use App\Repository\DepensesRepository;
use App\Repository\RecetteRepository;
use App\Repository\VenteRepository;
use App\Service\SocieteConfig;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private UserRepository $userRepository,
        private OrderRepository $orderRepository,
        private ArticleRepository $articleRepository,
        private NewsletterSubscriberRepository $newsletterSubscriberRepository,
        private LigneFactureRepository $ligneFactureRepository,
        private FactureRepository $factureRepository,
        private DepensesRepository $depensesRepository,
        private RecetteRepository $recetteRepository,
        private VenteRepository $venteRepository,
        private RequestStack $requestStack,
        private SocieteConfig $societeConfig,
    ) {}

    #[Route('/admin', name: 'admin')]
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
        
        // Définir la plage de dates pour le graph (peut être plus large que le filtre de stats si on veut voir l'évolution, 
        // mais l'utilisateur a demandé que la sélection de période change TOUT, donc on utilise fromFilter/toFilter)
        // CEPENDANT, pour un graph d'évolution, si on sélectionne "Aujourd'hui", c'est plat.
        // On va respecter la demande : le graph reflète la période sélectionnée.
        
        $onlineSeries = $this->orderRepository->getSalesSeries($fromFilter, $groupBy);
        $caisseSeries = $this->venteRepository->getSalesSeries($fromFilter, $groupBy);

        // Fusionner les labels et les données
        // On génère tous les jours/mois entre from et to pour avoir une échelle continue
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
            'newUsers' => 0, // Sera calculé ci-dessous
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
        
        // Fix newUsers count query above if array criteria not supported this way for ranges usually
        // Let's rely on previous logic for range count if needed, but for now passing the simple logic
        // Actually, let's fix the user count properly
        $stats['newUsers'] = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $fromFilter)
            ->setParameter('to', $toFilter)
            ->getQuery()
            ->getSingleScalarResult();


        return $this->render('admin/dashboard.html.twig', [
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
            // Pour le Pie Chart : Répartition BIC/BNC
            'repartition' => [
                'bic' => $caTotalBicEuros,
                'bnc' => $caTotalBncEuros,
            ]
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Studio Pipelette')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('Retour au site', 'fas fa-arrow-left', '/');
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Planning');
        yield MenuItem::linkToRoute('Agenda', 'fas fa-calendar-alt', 'app_caisse_planning');
        yield MenuItem::linkToCrud('Indisponibilités', 'fas fa-calendar-times', UnavailabilityRule::class);
        yield MenuItem::linkToCrud('Exceptions', 'fas fa-exclamation-triangle', DispoPrestation::class);

        yield MenuItem::section('Activités');
        yield MenuItem::linkToCrud('Commandes', 'fas fa-shopping-cart', Order::class);
        yield MenuItem::linkToCrud('Réservations', 'fas fa-calendar-alt', Reservation::class);

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkToCrud('Articles', 'fas fa-tshirt', Article::class);
        yield MenuItem::linkToCrud('Photos', 'fas fa-images', Photo::class);
        yield MenuItem::linkToCrud('Collections', 'fas fa-layer-group', ArticleCollection::class);
        yield MenuItem::linkToCrud('Catégories', 'fas fa-tags', Categorie::class);
        yield MenuItem::linkToCrud('Sous-Catégories', 'fas fa-tag', SousCategorie::class);
        yield MenuItem::linkToCrud('Couleurs', 'fas fa-palette', Couleur::class);
        yield MenuItem::linkToCrud('Tarifs (Prestations)', 'fas fa-money-bill', Tarif::class);

        yield MenuItem::section('Clients');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Newsletter', 'fas fa-envelope', NewsletterSubscriber::class);
        yield MenuItem::linkToCrud('Partenaires B2B', 'fas fa-handshake', BtoB::class);

        yield MenuItem::section('Gestion');
        yield MenuItem::linkToCrud('Dépenses', 'fas fa-file-invoice-dollar', Depenses::class);
        yield MenuItem::linkToCrud('Recettes', 'fas fa-file-invoice', Recette::class);
        yield MenuItem::linkToCrud('Société', 'fas fa-building', Societe::class);
        yield MenuItem::linkToCrud('Dépôt Vente', 'fas fa-box', DepotVente::class);

        yield MenuItem::section('Paramètres');
        yield MenuItem::linkToCrud('Carousel', 'fas fa-images', Carousel::class);
        yield MenuItem::linkToCrud('Promo', 'fas fa-percent', Code::class);
        yield MenuItem::linkToCrud('FAQ', 'fas fa-question-circle', Faq::class);
        yield MenuItem::linkToCrud('CGV', 'fas fa-file-contract', Cgv::class);
        yield MenuItem::linkToCrud('Politique Confidentialité', 'fas fa-user-shield', PrivacyPolicy::class);
        yield MenuItem::linkToCrud('Politique Cookies', 'fas fa-cookie-bite', CookiePolicy::class);
    }
}
