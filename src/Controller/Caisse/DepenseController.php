<?php

namespace App\Controller\Caisse;

use App\Entity\Depenses;
use App\Form\DepensesType;
use App\Repository\DepensesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/depense')]
class DepenseController extends AbstractController
{
    #[Route('/', name: 'app_caisse_depense_index', methods: ['GET'])]
    public function index(DepensesRepository $depensesRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $depensesRepository->createQueryBuilder('d')
            ->orderBy('d.date', 'DESC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('caisse/depense/index.html.twig', [
            'depenses' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_depense_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $depense = new Depenses();
        $depense->setDate(new \DateTime());
        $form = $this->createForm(DepensesType::class, $depense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($depense);
            $entityManager->flush();

            $this->addFlash('success', 'Dépense ajoutée avec succès.');

            return $this->redirectToRoute('app_caisse_depense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/depense/new.html.twig', [
            'depense' => $depense,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_depense_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Depenses $depense, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DepensesType::class, $depense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Dépense modifiée avec succès.');

            return $this->redirectToRoute('app_caisse_depense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/depense/edit.html.twig', [
            'depense' => $depense,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_depense_delete', methods: ['POST'])]
    public function delete(Request $request, Depenses $depense, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$depense->getId(), $request->request->get('_token'))) {
            $entityManager->remove($depense);
            $entityManager->flush();
            $this->addFlash('success', 'Dépense supprimée.');
        }

        return $this->redirectToRoute('app_caisse_depense_index', [], Response::HTTP_SEE_OTHER);
    }
}
