<?php

namespace App\Controller\Caisse;

use App\Entity\DepotVente;
use App\Form\DepotVenteType;
use App\Repository\DepotVenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/btob/depot-vente')]
class DepotVenteController extends AbstractController
{
    #[Route('/', name: 'app_caisse_depot_vente_index', methods: ['GET'])]
    public function index(DepotVenteRepository $depotVenteRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $depotVenteRepository->createQueryBuilder('d')
            ->orderBy('d.nom', 'ASC');

        if ($q) {
            $queryBuilder
                ->where('d.nom LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('caisse/depot_vente/index.html.twig', [
            'depot_ventes' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_depot_vente_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $depotVente = new DepotVente();
        $form = $this->createForm(DepotVenteType::class, $depotVente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($depotVente);
            $entityManager->flush();

            $this->addFlash('success', 'Le dépôt-vente a bien été créé.');

            return $this->redirectToRoute('app_caisse_depot_vente_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/depot_vente/new.html.twig', [
            'depot_vente' => $depotVente,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_depot_vente_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DepotVente $depotVente, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DepotVenteType::class, $depotVente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le dépôt-vente a bien été modifié.');

            return $this->redirectToRoute('app_caisse_depot_vente_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/depot_vente/edit.html.twig', [
            'depot_vente' => $depotVente,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_depot_vente_delete', methods: ['POST'])]
    public function delete(Request $request, DepotVente $depotVente, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$depotVente->getId(), $request->request->get('_token'))) {
            $entityManager->remove($depotVente);
            $entityManager->flush();
            $this->addFlash('success', 'Le dépôt-vente a bien été supprimé.');
        }

        return $this->redirectToRoute('app_caisse_depot_vente_index', [], Response::HTTP_SEE_OTHER);
    }
}
