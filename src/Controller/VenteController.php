<?php

namespace App\Controller;

use App\Repository\VenteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/ventes')]
class VenteController extends AbstractController
{
    #[Route('/', name: 'app_vente_index', methods: ['GET'])]
    public function index(Request $request, VenteRepository $venteRepository): Response
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

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

        $ventes = $qb->getQuery()->getResult();

        return $this->render('vente/index.html.twig', [
            'ventes' => $ventes,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
