<?php

namespace App\Controller\Caisse;

use App\Entity\Recette;
use App\Form\RecetteType;
use App\Repository\RecetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/recette')]
class RecetteController extends AbstractController
{
    #[Route('/', name: 'app_caisse_recette_index', methods: ['GET'])]
    public function index(RecetteRepository $recetteRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $recetteRepository->createQueryBuilder('r')
            ->orderBy('r.date', 'DESC');

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('caisse/recette/index.html.twig', [
            'recettes' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_recette_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recette = new Recette();
        $recette->setDate(new \DateTime());
        $form = $this->createForm(RecetteType::class, $recette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($recette);
            $entityManager->flush();

            $this->addFlash('success', 'Recette ajoutée avec succès.');

            return $this->redirectToRoute('app_caisse_recette_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/recette/new.html.twig', [
            'recette' => $recette,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_recette_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recette $recette, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecetteType::class, $recette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Recette modifiée avec succès.');

            return $this->redirectToRoute('app_caisse_recette_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/recette/edit.html.twig', [
            'recette' => $recette,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_recette_delete', methods: ['POST'])]
    public function delete(Request $request, Recette $recette, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$recette->getId(), $request->request->get('_token'))) {
            $entityManager->remove($recette);
            $entityManager->flush();
            $this->addFlash('success', 'Recette supprimée.');
        }

        return $this->redirectToRoute('app_caisse_recette_index', [], Response::HTTP_SEE_OTHER);
    }
}
