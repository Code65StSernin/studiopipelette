<?php

namespace App\Controller\Admin;

use App\Entity\Facture;
use App\Entity\Recette;
use App\Repository\DepensesRepository;
use App\Repository\RecetteRepository;
use App\Repository\FactureRepository;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class BankAccountController extends AbstractController
{
    public function __construct(
        private DepensesRepository $depensesRepository,
        private RecetteRepository $recetteRepository,
        private FactureRepository $factureRepository,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('/admin/comptabilite/compte-bancaire', name: 'admin_bank_account')]
    public function bankAccount(Request $request): Response
    {
        $now = new \DateTimeImmutable();
        $monthParam = $request->query->get('month');

        if ($monthParam && preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            [$year, $month] = array_map('intval', explode('-', $monthParam));
            $start = (new \DateTimeImmutable())->setDate($year, $month, 1)->setTime(0, 0);
        } else {
            $start = $now->modify('first day of this month')->setTime(0, 0);
            $monthParam = $start->format('Y-m');
        }

        $end = $start->modify('last day of this month')->setTime(23, 59, 59);

        // Solde initial (réel) : recettes pointées - dépenses pointées avant le début du mois
        $recettesAvant = (float) $this->recetteRepository->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.montant), 0)')
            ->andWhere('r.pointage = :pointe')
            ->andWhere('r.date < :start')
            ->setParameter('pointe', true)
            ->setParameter('start', $start->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        $depensesAvant = (float) $this->depensesRepository->createQueryBuilder('d')
            ->select('COALESCE(SUM(d.montant), 0)')
            ->andWhere('d.pointage = :pointe')
            ->andWhere('d.date < :start')
            ->setParameter('pointe', true)
            ->setParameter('start', $start->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        $soldeInitial = $recettesAvant - $depensesAvant;

        // Opérations du mois
        $recettesMois = $this->recetteRepository->createQueryBuilder('r')
            ->andWhere('r.date BETWEEN :start AND :end')
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->orderBy('r.date', 'ASC')
            ->getQuery()
            ->getResult();

        $depensesMois = $this->depensesRepository->createQueryBuilder('d')
            ->andWhere('d.date BETWEEN :start AND :end')
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->orderBy('d.date', 'ASC')
            ->getQuery()
            ->getResult();

        // Construction d'une liste d'opérations unifiée
        $operations = [];

        foreach ($recettesMois as $r) {
            /** @var Recette $r */
            $operations[] = [
                'date' => $r->getDate(),
                'type' => 'recette',
                'objet' => $r->getObjet(),
                'origine' => $r->getOrigine(),
                'montant' => $r->getMontant(),
                'pointage' => $r->isPointage(),
            ];
        }

        foreach ($depensesMois as $d) {
            /** @var \App\Entity\Depenses $d */
            $operations[] = [
                'date' => $d->getDate(),
                'type' => 'depense',
                'objet' => $d->getObjet(),
                'origine' => $d->getOrigine(),
                'montant' => $d->getMontant(),
                'pointage' => $d->isPointage(),
            ];
        }

        usort($operations, static function (array $a, array $b): int {
            return $a['date'] <=> $b['date'];
        });

        // Totaux du mois
        $totalRecettes = 0.0;
        $totalDepenses = 0.0;
        $totalRecettesPointees = 0.0;
        $totalDepensesPointees = 0.0;

        foreach ($operations as $op) {
            if ($op['type'] === 'recette') {
                $totalRecettes += $op['montant'];
                if ($op['pointage']) {
                    $totalRecettesPointees += $op['montant'];
                }
            } else {
                $totalDepenses += $op['montant'];
                if ($op['pointage']) {
                    $totalDepensesPointees += $op['montant'];
                }
            }
        }

        $soldePrevisionnelFin = $soldeInitial + $totalRecettes - $totalDepenses;
        $soldeReelFin = $soldeInitial + $totalRecettesPointees - $totalDepensesPointees;

        return $this->render('admin/bank_account.html.twig', [
            'month' => $monthParam,
            'start' => $start,
            'end' => $end,
            'soldeInitial' => $soldeInitial,
            'soldePrevisionnelFin' => $soldePrevisionnelFin,
            'soldeReelFin' => $soldeReelFin,
            'operations' => $operations,
        ]);
    }

    #[Route('/admin/comptabilite/stripe-transfert', name: 'admin_stripe_transfer', methods: ['GET', 'POST'])]
    public function stripeTransfer(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $ids = $request->request->all('factures') ?? [];
            if (!empty($ids)) {
                $factures = $this->factureRepository->createQueryBuilder('f')
                    ->join('f.order', 'o')
                    ->andWhere('f.id IN (:ids)')
                    ->setParameter('ids', $ids)
                    ->getQuery()
                    ->getResult();

                $totalNet = 0.0;
                $factureNumeros = [];

                foreach ($factures as $facture) {
                    /** @var Facture $facture */
                    $order = $facture->getOrder();
                    if (!$order || $order->getStatus() !== Order::STATUS_PAID) {
                        continue;
                    }

                    // Montant TTC en euros
                    $totalTtcEuros = $facture->getTotalTTC() / 100;

                    // Frais CB : 1,5% du TTC + 0,25€
                    $fraisCb = ($totalTtcEuros * 0.015) + 0.25;
                    $net = $totalTtcEuros - $fraisCb;

                    $totalNet += $net;
                    $factureNumeros[] = $facture->getNumero();

                    $facture->setStripeTransfere(true);
                }

                if ($totalNet > 0 && !empty($factureNumeros)) {
                    $dateVirement = new \DateTimeImmutable();
                    $batchId = 'STRIPE-' . $dateVirement->format('Ymd-His');

                    $recette = new Recette();
                    $recette
                        ->setDate($dateVirement)
                        ->setMontant(round($totalNet, 2))
                        ->setObjet('Virement Stripe du ' . $dateVirement->format('d/m/Y') . ' (factures ' . implode(', ', $factureNumeros) . ')')
                        ->setOrigine('stripe')
                        ->setPointage(false);

                    foreach ($factures as $facture) {
                        if (!$facture->isStripeTransfere()) {
                            continue;
                        }
                        $facture->setStripeTransferBatchId($batchId);
                        $this->em->persist($facture);
                    }

                    $this->em->persist($recette);
                    $this->em->flush();

                    $this->addFlash('success', sprintf(
                        'Virement Stripe créé pour un montant de %.2f € (batch %s).',
                        $totalNet,
                        $batchId
                    ));
                } else {
                    $this->addFlash('warning', 'Aucune facture valide sélectionnée pour le virement Stripe.');
                }
            } else {
                $this->addFlash('warning', 'Veuillez sélectionner au moins une facture.');
            }

            return $this->redirectToRoute('admin_stripe_transfer');
        }

        // GET : afficher les factures payées non encore transférées
        $factures = $this->factureRepository->createQueryBuilder('f')
            ->join('f.order', 'o')
            ->andWhere('o.status = :status')
            ->andWhere('f.stripeTransfere = :transfere')
            ->setParameter('status', Order::STATUS_PAID)
            ->setParameter('transfere', false)
            ->orderBy('f.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/stripe_transfer.html.twig', [
            'factures' => $factures,
        ]);
    }
}

