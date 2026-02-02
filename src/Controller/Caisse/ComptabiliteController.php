<?php

namespace App\Controller\Caisse;

use App\Repository\DepensesRepository;
use App\Repository\RecetteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/comptabilite')]
class ComptabiliteController extends AbstractController
{
    #[Route('/', name: 'app_caisse_comptabilite_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('caisse/comptabilite/index.html.twig');
    }

    #[Route('/journal-recettes', name: 'app_caisse_comptabilite_journal_recettes', methods: ['GET'])]
    public function journalRecettes(RecetteRepository $recetteRepository, Request $request): Response
    {
        $year = $request->query->getInt('year', (int)date('Y'));
        
        $startDate = new \DateTime($year . '-01-01 00:00:00');
        $endDate = new \DateTime($year . '-12-31 23:59:59');

        $recettes = $recetteRepository->createQueryBuilder('r')
            ->where('r.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('r.date', 'ASC')
            ->getQuery()
            ->getResult();

        $total = 0;
        foreach ($recettes as $r) {
            $total += $r->getMontant();
        }

        return $this->render('caisse/comptabilite/journal_recettes.html.twig', [
            'recettes' => $recettes,
            'year' => $year,
            'total' => $total,
        ]);
    }

    #[Route('/journal-depenses', name: 'app_caisse_comptabilite_journal_depenses', methods: ['GET'])]
    public function journalDepenses(DepensesRepository $depensesRepository, Request $request): Response
    {
        $year = $request->query->getInt('year', (int)date('Y'));
        
        $startDate = new \DateTime($year . '-01-01 00:00:00');
        $endDate = new \DateTime($year . '-12-31 23:59:59');

        $depenses = $depensesRepository->createQueryBuilder('d')
            ->where('d.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('d.date', 'ASC')
            ->getQuery()
            ->getResult();

        $total = 0;
        foreach ($depenses as $d) {
            $total += $d->getMontant();
        }

        return $this->render('caisse/comptabilite/journal_depenses.html.twig', [
            'depenses' => $depenses,
            'year' => $year,
            'total' => $total,
        ]);
    }

    #[Route('/grand-livre', name: 'app_caisse_comptabilite_grand_livre', methods: ['GET'])]
    public function grandLivre(DepensesRepository $depensesRepository, RecetteRepository $recetteRepository, Request $request): Response
    {
        $year = $request->query->getInt('year', (int)date('Y'));
        
        $startDate = new \DateTime($year . '-01-01 00:00:00');
        $endDate = new \DateTime($year . '-12-31 23:59:59');

        $recettes = $recetteRepository->createQueryBuilder('r')
            ->where('r.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();

        $depenses = $depensesRepository->createQueryBuilder('d')
            ->where('d.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();

        $operations = [];

        foreach ($recettes as $r) {
            $operations[] = [
                'date' => $r->getDate(),
                'type' => 'Recette',
                'libelle' => $r->getObjet(),
                'debit' => 0,
                'credit' => $r->getMontant(),
                'pointage' => $r->isPointage(),
                'entity' => $r
            ];
        }

        foreach ($depenses as $d) {
            $operations[] = [
                'date' => $d->getDate(),
                'type' => 'Dépense',
                'libelle' => $d->getObjet(),
                'debit' => $d->getMontant(),
                'credit' => 0,
                'pointage' => $d->isPointage(),
                'entity' => $d
            ];
        }

        // Sort by date
        usort($operations, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        // Calculate running balance
        $solde = 0;
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($operations as &$op) {
            $solde += $op['credit'] - $op['debit'];
            $op['solde'] = $solde;
            $totalDebit += $op['debit'];
            $totalCredit += $op['credit'];
        }

        return $this->render('caisse/comptabilite/grand_livre.html.twig', [
            'operations' => $operations,
            'year' => $year,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'soldeFinal' => $solde,
        ]);
    }
}
