<?php

namespace App\Controller\Caisse;

use App\Entity\DispoPrestation;
use App\Form\DispoPrestationType;
use App\Repository\DispoPrestationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/planning/exceptions')]
class DispoPrestationController extends AbstractController
{
    #[Route('/', name: 'app_caisse_dispo_prestation_index', methods: ['GET'])]
    public function index(DispoPrestationRepository $dispoPrestationRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $dispoPrestationRepository->createQueryBuilder('d')
            ->orderBy('d.id', 'DESC');

        if ($q) {
            $queryBuilder
                ->where('d.motif LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('caisse/dispo_prestation/index.html.twig', [
            'dispo_prestations' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_dispo_prestation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $dispoPrestation = new DispoPrestation();
        $form = $this->createForm(DispoPrestationType::class, $dispoPrestation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($dispoPrestation);
            $entityManager->flush();

            $this->addFlash('success', 'L\'exception a bien été créée.');

            return $this->redirectToRoute('app_caisse_dispo_prestation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/dispo_prestation/new.html.twig', [
            'dispo_prestation' => $dispoPrestation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_dispo_prestation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DispoPrestation $dispoPrestation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DispoPrestationType::class, $dispoPrestation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L\'exception a bien été modifiée.');

            return $this->redirectToRoute('app_caisse_dispo_prestation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/dispo_prestation/edit.html.twig', [
            'dispo_prestation' => $dispoPrestation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_dispo_prestation_delete', methods: ['POST'])]
    public function delete(Request $request, DispoPrestation $dispoPrestation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$dispoPrestation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($dispoPrestation);
            $entityManager->flush();
            $this->addFlash('success', 'L\'exception a bien été supprimée.');
        }

        return $this->redirectToRoute('app_caisse_dispo_prestation_index', [], Response::HTTP_SEE_OTHER);
    }
}
