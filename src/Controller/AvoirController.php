<?php

namespace App\Controller;

use App\Repository\AvoirRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/avoirs')]
class AvoirController extends AbstractController
{
    #[Route('/', name: 'app_avoir_index', methods: ['GET'])]
    public function index(Request $request, AvoirRepository $avoirRepository): Response
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;

        $qb = $avoirRepository->createQueryBuilder('a')
            ->orderBy('a.dateCreation', 'DESC');

        if ($startDate) {
            $qb->andWhere('a.dateCreation >= :startDate')
               ->setParameter('startDate', new \DateTime($startDate));
        }

        if ($endDate) {
            $endDateTime = new \DateTime($endDate);
            $endDateTime->setTime(23, 59, 59);
            $qb->andWhere('a.dateCreation <= :endDate')
               ->setParameter('endDate', $endDateTime);
        }

        // Clone query builder for total count
        $countQb = clone $qb;
        $countQb->select('COUNT(a.id)');
        $totalResults = (int) $countQb->getQuery()->getSingleScalarResult();

        $pagesCount = max(1, (int) ceil($totalResults / $perPage));
        if ($page > $pagesCount) {
            $page = $pagesCount;
        }

        $qb->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage);

        $avoirs = $qb->getQuery()->getResult();

        // Sécurise les relations (Vente et Client) qui pourraient ne plus exister
        foreach ($avoirs as $avoir) {
            try {
                $vente = $avoir->getVente();
                if ($vente) {
                    // Force le chargement du proxy Vente
                    $vente->getModePaiement();
                    
                    try {
                        if ($vente->getClient()) {
                            // Force le chargement du proxy Client
                            $vente->getClient()->getNom();
                        }
                    } catch (\Doctrine\ORM\EntityNotFoundException $e) {
                        // Si le client n'existe plus
                        $vente->setClient(null);
                    }
                }
            } catch (\Doctrine\ORM\EntityNotFoundException $e) {
                // Si la vente n'existe plus
                $avoir->setVente(null);
            }
        }

        return $this->render('avoir/index.html.twig', [
            'avoirs' => $avoirs,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'page' => $page,
            'pagesCount' => $pagesCount,
            'perPage' => $perPage,
            'totalResults' => $totalResults,
        ]);
    }
}
