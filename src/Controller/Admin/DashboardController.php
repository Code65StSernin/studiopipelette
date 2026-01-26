<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\DispoPrestation;
use App\Entity\Tarif;
use App\Entity\ContraintePrestation;
use App\Entity\Reservation;
use App\Entity\UnavailabilityRule;
use App\Entity\Calendrier;
use App\Entity\Order;
use App\Entity\BtoB;
use App\Entity\Categorie;
use App\Entity\Couleur;
use App\Entity\ArticleCollection;
use App\Entity\Photo;
use App\Entity\SousCategorie;
use App\Entity\User;
use App\Entity\Cgv;
use App\Entity\Code;
use App\Entity\NewsletterSubscriber;
use App\Entity\Carousel;
use App\Entity\Offre;
use App\Entity\Societe;
use App\Entity\Depenses;
use App\Entity\Recette;
use App\Entity\PrivacyPolicy;
use App\Entity\CookiePolicy;
use App\Entity\Faq;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use App\Repository\ArticleRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\LigneFactureRepository;
use App\Repository\FactureRepository;
use App\Repository\DepensesRepository;
use App\Repository\RecetteRepository;
use App\Repository\ReservationRepository;
use App\Service\SocieteConfig;
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
        private RequestStack $requestStack,
        private SocieteConfig $societeConfig,
    ) {}

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $request = $this->requestStack->getCurrentRequest();

        $now = new \DateTimeImmutable();

        // Période sélectionnée via datepicker (par défaut : mois en cours)
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

        // Inscriptions
        $totalUsers = $this->userRepository->count([]);
        $newUsersRange = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $fromFilter)
            ->setParameter('to', $toFilter)
            ->getQuery()
            ->getSingleScalarResult();

        // Commandes
        $totalOrders = $this->orderRepository->count([]);
        $paidOrdersTotal = (int) $this->orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.status = :status')
            ->setParameter('status', Order::STATUS_PAID)
            ->getQuery()
            ->getSingleScalarResult();

        $paidOrdersRange = (int) $this->orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.status = :status')
            ->andWhere('o.createdAt BETWEEN :from AND :to')
            ->setParameter('status', Order::STATUS_PAID)
            ->setParameter('from', $fromFilter)
            ->setParameter('to', $toFilter)
            ->getQuery()
            ->getSingleScalarResult();

        // CA total et sur la période sélectionnée (en centimes)
        $revenueTotalCents = (int) $this->orderRepository->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.amountTotalCents), 0)')
            ->andWhere('o.status = :status')
            ->setParameter('status', Order::STATUS_PAID)
            ->getQuery()
            ->getSingleScalarResult();

        $revenueRangeCents = (int) $this->orderRepository->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.amountTotalCents), 0)')
            ->andWhere('o.status = :status')
            ->andWhere('o.createdAt BETWEEN :from AND :to')
            ->setParameter('status', Order::STATUS_PAID)
            ->setParameter('from', $fromFilter)
            ->setParameter('to', $toFilter)
            ->getQuery()
            ->getSingleScalarResult();

        // Articles en rupture de stock
        // - $outOfStockArticles : liste des articles ayant AU MOINS une taille à 0
        // - $outOfStockSizes   : nombre total de tailles dont le stock est à 0
        $outOfStockArticles = [];
        $outOfStockSizes = 0;
        foreach ($this->articleRepository->findBy(['actif' => true]) as $article) {
            /** @var \App\Entity\Article $article */
            $tailles = $article->getTailles() ?? [];
            $hasZero = false;
            foreach ($tailles as $t) {
                $stock = $t['stock'] ?? 0;
                if ($stock <= 0) {
                    $outOfStockSizes++;
                    $hasZero = true;
                }
            }
            if ($hasZero) {
                $outOfStockArticles[] = $article;
            }
        }

        // Newsletter
        $newsletterTotal = $this->newsletterSubscriberRepository->count([]);
        $newsletterActive = (int) $this->newsletterSubscriberRepository->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        // Top articles (période sélectionnée)
        $topPeriod = $request->query->get('topPeriod', 'month');
        switch ($topPeriod) {
            case 'today':
                $topFrom = $now->setTime(0, 0);
                break;
            case 'year':
                $topFrom = $now->modify('first day of january this year')->setTime(0, 0);
                break;
            case 'month':
            default:
                $topFrom = $now->modify('first day of this month')->setTime(0, 0);
                break;
        }
        $topTo = $now;

        // période précédente (même durée)
        $interval = $topTo->getTimestamp() - $topFrom->getTimestamp();
        $prevTo = $topFrom->modify('-1 second');
        $prevFrom = $prevTo->modify(sprintf('-%d seconds', $interval));

        $topCurrent = $this->ligneFactureRepository->findTopArticles($topFrom, $topTo, 5);
        $topPrev = $this->ligneFactureRepository->findTopArticles($prevFrom, $prevTo, 5);

        $prevIndex = [];
        foreach ($topPrev as $row) {
            $prevIndex[$row['designation']] = (int) $row['quantite'];
        }

        foreach ($topCurrent as &$row) {
            $q = (int) $row['quantite'];
            $prevQ = $prevIndex[$row['designation']] ?? 0;
            if ($prevQ > 0) {
                $row['progress'] = round((($q - $prevQ) / $prevQ) * 100);
            } else {
                $row['progress'] = null;
            }
        }
        unset($row);

        // Série pour le graphique de ventes
        // salesPeriod contrôle l'échelle demandée dans l'UI,
        // mais on agrège en jours ou en mois selon le cas.
        $salesPeriod = $request->query->get('salesPeriod', 'month');

        switch ($salesPeriod) {
            case 'day':
                // Derniers 30 jours
                $fromSales = $now->modify('-29 days')->setTime(0, 0);
                break;
            case 'week':
                // Semaine courante (lundi -> dimanche)
                $fromSales = $now->modify('monday this week')->setTime(0, 0);
                break;
            case 'year':
                // Année courante (1er janvier -> 31 décembre)
                $fromSales = $now->modify('first day of january this year')->setTime(0, 0);
                break;
            case 'month':
            default:
                // Mois courant (1er jour -> dernier jour)
                $fromSales = $now->modify('first day of this month')->setTime(0, 0);
                break;
        }

        // On regroupe toujours par jour, sauf pour l'affichage "année" où l'on regroupe par mois.
        $groupBy = $salesPeriod === 'year' ? 'month' : 'day';
        $salesSeries = $this->orderRepository->getSalesSeries($fromSales, $groupBy);

        // Indexer les totaux par libellé existant
        $totalsByLabel = [];
        foreach ($salesSeries as $row) {
            $totalsByLabel[$row['label']] = $row['total'] / 100;
        }

        $salesLabels = [];
        $salesData = [];

        if ($salesPeriod === 'day') {
            // 30 derniers jours (de fromSales à aujourd'hui)
            $cursor = $fromSales;
            while ($cursor <= $now) {
                $label = $cursor->format('d/m');
                $salesLabels[] = $label;
                $salesData[] = $totalsByLabel[$label] ?? 0;
                $cursor = $cursor->modify('+1 day');
            }
        } elseif ($salesPeriod === 'week') {
            // Jours de la semaine courante (lundi -> dimanche)
            $cursor = $fromSales;
            for ($i = 0; $i < 7; $i++) {
                $label = $cursor->format('d/m');
                $salesLabels[] = $label;
                $salesData[] = $totalsByLabel[$label] ?? 0;
                $cursor = $cursor->modify('+1 day');
            }
        } elseif ($salesPeriod === 'month') {
            // Tous les jours du mois courant
            $firstDay = $fromSales;
            $lastDay = $now->modify('last day of this month')->setTime(0, 0);
            $cursor = $firstDay;
            while ($cursor <= $lastDay) {
                $label = $cursor->format('d/m');
                $salesLabels[] = $label;
                $salesData[] = $totalsByLabel[$label] ?? 0;
                $cursor = $cursor->modify('+1 day');
            }
        } else { // année
            // Tous les mois de l'année courante
            $year = (int) $now->format('Y');
            for ($month = 1; $month <= 12; $month++) {
                $date = (new \DateTimeImmutable())
                    ->setDate($year, $month, 1)
                    ->setTime(0, 0);
                $label = $date->format('m/Y');
                $salesLabels[] = $label;
                $salesData[] = $totalsByLabel[$label] ?? 0;
            }
        }

        // Calcul des CA brut / commercial / encaissé pour la période filtrée
        // On se base sur les factures liées aux commandes payées dans l'intervalle
        $facturesRange = $this->factureRepository
            ->createQueryBuilder('f')
            ->join('f.order', 'o')
            ->andWhere('o.status = :status')
            ->andWhere('o.createdAt BETWEEN :from AND :to')
            ->setParameter('status', Order::STATUS_PAID)
            ->setParameter('from', $fromFilter)
            ->setParameter('to', $toFilter)
            ->getQuery()
            ->getResult();

        $caBrutCents = 0;
        $caCommercialCents = 0;

        foreach ($facturesRange as $facture) {
            /** @var \App\Entity\Facture $facture */
            $totalTtc = $facture->getTotalTTC(); // en centimes
            $caCommercialCents += $totalTtc;

            $remise = $facture->getRemisePourcentage();
            $fraisLivraison = $facture->getFraisLivraison() ?? 0;
            $btobRemise = $facture->getBtobRemiseCents() ?? 0;

            // Montant des produits après remise (TTC) = total TTC - frais de livraison
            $produitsApresRemise = max(0, $totalTtc - $fraisLivraison);
            
            if ($remise !== null && $remise > 0) {
                $factor = 1 - ($remise / 100);

                if ($factor <= 0) {
                    $netBtoB = $produitsApresRemise;
                } else {
                    $netBtoB = (int) round($produitsApresRemise / $factor);
                }
            } else {
                // Pas de remise promo -> Net BtoB = Produits après remise (qui est juste Total - Livraison)
                $netBtoB = $produitsApresRemise;
            }

            // CA Brut = Net BtoB + Remise BtoB + Frais Livraison (si on veut le Brut total)
            // L'utilisateur dit : "CA brut c'est le CA sans la remise BtoB ni les codes promo"
            // Donc Brut Produits + Livraison
            $caBrutCents += $netBtoB + $btobRemise + $fraisLivraison;
        }

        // Si, pour une raison quelconque, aucune facture n'a été trouvée,
        // on considère que le CA commercial = somme des montants de commande
        if ($caCommercialCents === 0 && $revenueRangeCents > 0) {
            $caCommercialCents = $revenueRangeCents;
        }

        // CA port compris en euros (CA commercial / 100)
        $caPortCompris = $caCommercialCents / 100;
        
        // Pourcentages depuis SocieteConfig
        $pourcentageUrssaf = $this->societeConfig->getPourcentageUrssaf() ?? 0;
        $pourcentageCpf = $this->societeConfig->getPourcentageCpf() ?? 0;
        $pourcentageIr = $this->societeConfig->getPourcentageIr() ?? 0;
        
        // Charges sociales (URSSAF + CPF)
        $chargesSociales = $caPortCompris * (($pourcentageUrssaf + $pourcentageCpf) / 100);
        
        // Impôt sur le revenu
        $impotRevenu = $caPortCompris * ($pourcentageIr / 100);
        
        // Total charges à payer
        $chargesAPayer = $chargesSociales + $impotRevenu;

        // Frais bancaires (paiement CB) :
        // 1,5 % du CA facturé port compris + 0,25 € par commande payée sur la période
        $fraisPaiementCb = ($caPortCompris * 0.015) + ($paidOrdersRange * 0.25);

        // CA encaissé = CA commercial - frais de paiement CB
        $caEncaisseCents = (int) round($caCommercialCents - ($fraisPaiementCb * 100));

        // Dépenses sur la période (en euros)
        $depensesTotal = (float) $this->depensesRepository->createQueryBuilder('d')
            ->select('COALESCE(SUM(d.montant), 0)')
            ->andWhere('d.date BETWEEN :from AND :to')
            ->setParameter('from', $fromFilter->format('Y-m-d'))
            ->setParameter('to', $toFilter->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        // Dépenses pointées (réellement sorties de trésorerie) sur la période (en euros)
        $depensesPointees = (float) $this->depensesRepository->createQueryBuilder('d')
            ->select('COALESCE(SUM(d.montant), 0)')
            ->andWhere('d.pointage = :pointe')
            ->andWhere('d.date BETWEEN :from AND :to')
            ->setParameter('pointe', true)
            ->setParameter('from', $fromFilter->format('Y-m-d'))
            ->setParameter('to', $toFilter->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        // Résultats
        $caEncaisseEuros = $caEncaisseCents / 100;
        $resultatBrut = $caEncaisseEuros - $chargesAPayer;      // CA encaissé - charges (URSSAF + IR)
        $resultatNet  = $resultatBrut - $depensesTotal;         // Résultat brut - dépenses

        // Etalement site
        $totalSite = $this->societeConfig->getTotalSite() ?? 0;
        $pourcentageMensuel = $this->societeConfig->getPourcentageMensuel() ?? 0;
        $montantRemboursement = $caPortCompris * ($pourcentageMensuel / 100);
        $caTotalEuros = $revenueTotalCents / 100;
        $totalRembourseCalcul = $caTotalEuros * ($pourcentageMensuel / 100);
        
        $remboursementsAnticipes = (float) $this->depensesRepository->createQueryBuilder('d')
            ->select('COALESCE(SUM(d.montant), 0)')
            ->andWhere('d.remboursementAnticipe = :true')
            ->setParameter('true', true)
            ->getQuery()
            ->getSingleScalarResult();

        $totalRembourse = $totalRembourseCalcul + $remboursementsAnticipes;
        $resteARegler = max(0, $totalSite - $totalRembourse);

        // Somme des recettes pointées sur la période
        $recettesPointees = (float) $this->recetteRepository->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.montant), 0)')
            ->andWhere('r.pointage = :pointe')
            ->andWhere('r.date BETWEEN :from AND :to')
            ->setParameter('pointe', true)
            ->setParameter('from', $fromFilter->format('Y-m-d'))
            ->setParameter('to', $toFilter->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        // Trésorerie
        // - Théorique : CA encaissé - charges à payer (URSSAF + IR) - dépenses NON cochées
        // - Réelle    : Recettes pointées - Dépenses pointées
        $depensesNonPointees = max(0.0, $depensesTotal - $depensesPointees);
        $tresorerieTheorique = $caEncaisseEuros - $chargesAPayer - $depensesNonPointees;
        $tresorerieReelle = $recettesPointees - $depensesPointees;

        $stats = [
            'totalUsers' => $totalUsers,
            'newUsersRange' => $newUsersRange,
            'totalOrders' => $totalOrders,
            'paidOrders' => $paidOrdersRange,
            'paidOrdersTotal' => $paidOrdersTotal,
            'revenueTotalCents' => $revenueTotalCents,
            'revenueRangeCents' => $revenueRangeCents,
            'caBrutCents' => $caBrutCents,
            'caCommercialCents' => $caCommercialCents,
            'caEncaisseCents' => $caEncaisseCents,
            'depensesTotal' => $depensesTotal,
            'resultatBrut' => $resultatBrut,
            'resultatNet' => $resultatNet,
            'tresorerieTheorique' => $tresorerieTheorique,
            'tresorerieReelle' => $tresorerieReelle,
            'newsletterTotal' => $newsletterTotal,
            'newsletterActive' => $newsletterActive,
            'chargesSociales' => $chargesSociales,
            'impotRevenu' => $impotRevenu,
            'chargesAPayer' => $chargesAPayer,
            'fraisPaiementCb' => $fraisPaiementCb,
            'outOfStockSizes' => $outOfStockSizes,
            'totalSite' => $totalSite,
            'pourcentageMensuel' => $pourcentageMensuel,
            'montantRemboursement' => $montantRemboursement,
            'totalRembourse' => $totalRembourse,
            'resteARegler' => $resteARegler,
        ];

        $recentOrders = $this->orderRepository->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'topArticles' => $topCurrent,
            'topPeriod' => $topPeriod,
            'salesSeries' => $salesSeries,
            'salesLabels' => $salesLabels,
            'salesData' => $salesData,
            'salesPeriod' => $salesPeriod,
            'fromDate' => $fromFilter->format('Y-m-d'),
            'toDate' => $toFilter->format('Y-m-d'),
            'outOfStockArticles' => $outOfStockArticles,
        ]);
    }

    // Calendar route is handled by AdminCalendarController


    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Studio Pipelette - Administration')
            ->setFaviconPath('assets/img/favicon.ico')
            ->setLocales(['fr' => '🇫🇷 Français']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        
        // Section PRODUITS
        yield MenuItem::section('Produits');
        yield MenuItem::linkToCrud('Articles', 'fa fa-shopping-bag', Article::class);
        yield MenuItem::linkToCrud('Photos', 'fa fa-images', Photo::class);

        // Section TABLES (référentiels)
        yield MenuItem::section('Tables');
        yield MenuItem::linkToCrud('Catégories', 'fa fa-folder', Categorie::class);
        yield MenuItem::linkToCrud('Sous-catégories', 'fa fa-folder-open', SousCategorie::class);
        yield MenuItem::linkToCrud('Collections', 'fa fa-gem', ArticleCollection::class);
        yield MenuItem::linkToCrud('Couleurs', 'fa fa-palette', Couleur::class);
        yield MenuItem::linkToCrud('Tarifs', 'fa fa-tags', Tarif::class);
        yield MenuItem::subMenu('Exceptions', 'fa fa-exclamation-triangle')->setSubItems([
            MenuItem::linkToCrud('Suspension des prestations', 'fa fa-calendar-alt', DispoPrestation::class),
            MenuItem::linkToCrud('Contraintes de prestations', 'fa fa-lock', ContraintePrestation::class),
        ]);
        yield MenuItem::subMenu('Calendrier', 'fa fa-calendar')->setSubItems([
            MenuItem::linkToCrud('Réservations', 'fa fa-book', Reservation::class),
            MenuItem::linkToRoute('Créneaux', 'fa fa-clock', 'admin_calendar'),
            MenuItem::linkToCrud('Règles d\'indisponibilité', 'fa fa-ban', UnavailabilityRule::class),
        ]);

        // Section CONFIGURATION
        yield MenuItem::section('Configuration');
        yield MenuItem::linkToCrud('Société', 'fa fa-building', Societe::class);
        yield MenuItem::linkToCrud('Carousel', 'fa fa-images', Carousel::class);
        yield MenuItem::linkToCrud('Offres', 'fa fa-bullhorn', Offre::class);
        yield MenuItem::linkToCrud('Codes promo', 'fa fa-percent', Code::class);
        yield MenuItem::linkToCrud('BtoB', 'fa fa-briefcase', BtoB::class);
        yield MenuItem::subMenu('Comptabilité', 'fa fa-coins')->setSubItems([
            MenuItem::linkToCrud('Dépenses', 'fa fa-money-bill-wave', Depenses::class),
            MenuItem::linkToCrud('Recettes', 'fa fa-wallet', Recette::class),
        ]);

        // Section CLIENTS
        yield MenuItem::section('Clients');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Newsletter', 'fa fa-envelope', NewsletterSubscriber::class);

        // Section COMMANDES
        yield MenuItem::section('Commandes');
        yield MenuItem::linkToCrud('Commandes', 'fa fa-shopping-cart', Order::class);

        // Section PAGES LÉGALES
        yield MenuItem::section('Pages légales');
        yield MenuItem::linkToCrud('C.G.V', 'fa fa-gavel', Cgv::class);
        yield MenuItem::linkToCrud('Politique de confidentialité', 'fa fa-user-secret', PrivacyPolicy::class);
        yield MenuItem::linkToCrud('Politique de cookies', 'fa fa-cookie-bite', CookiePolicy::class);
        yield MenuItem::linkToCrud('FAQ', 'fa fa-question-circle', Faq::class);
    }
}
