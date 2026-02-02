<?php

namespace App\Controller\Caisse;

use App\Entity\Couleur;
use App\Form\CouleurType;
use App\Repository\CouleurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/couleurs')]
class CouleurController extends AbstractController
{
    #[Route('/', name: 'app_caisse_couleur_index', methods: ['GET'])]
    public function index(CouleurRepository $couleurRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $couleurRepository->createQueryBuilder('c')
            ->orderBy('c.nom', 'ASC');

        if ($q) {
            $queryBuilder->andWhere('c.nom LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('caisse/couleur/index.html.twig', [
            'couleurs' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_couleur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $couleur = new Couleur();
        $form = $this->createForm(CouleurType::class, $couleur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($couleur);
            $entityManager->flush();

            $this->addFlash('success', 'La couleur a bien été créée.');
            return $this->redirectToRoute('app_caisse_couleur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/couleur/new.html.twig', [
            'couleur' => $couleur,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_couleur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Couleur $couleur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CouleurType::class, $couleur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La couleur a bien été modifiée.');
            return $this->redirectToRoute('app_caisse_couleur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/couleur/edit.html.twig', [
            'couleur' => $couleur,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_couleur_delete', methods: ['POST'])]
    public function delete(Request $request, Couleur $couleur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $couleur->getId(), $request->request->get('_token'))) {
            $entityManager->remove($couleur);
            $entityManager->flush();
            $this->addFlash('success', 'La couleur a bien été supprimée.');
        }

        return $this->redirectToRoute('app_caisse_couleur_index', [], Response::HTTP_SEE_OTHER);
    }
}
