<?php

namespace App\Controller\Caisse;

use App\Entity\ArticleCollection;
use App\Form\CollectionType;
use App\Repository\ArticleCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/collections')]
class CollectionController extends AbstractController
{
    #[Route('/', name: 'app_caisse_collection_index', methods: ['GET'])]
    public function index(ArticleCollectionRepository $collectionRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $collectionRepository->createQueryBuilder('c')
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

        return $this->render('caisse/collection/index.html.twig', [
            'collections' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_collection_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $collection = new ArticleCollection();
        $form = $this->createForm(CollectionType::class, $collection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($collection);
            $entityManager->flush();

            $this->addFlash('success', 'La collection a bien été créée.');
            return $this->redirectToRoute('app_caisse_collection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/collection/new.html.twig', [
            'collection' => $collection,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_collection_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ArticleCollection $collection, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CollectionType::class, $collection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La collection a bien été modifiée.');
            return $this->redirectToRoute('app_caisse_collection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/collection/edit.html.twig', [
            'collection' => $collection,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_collection_delete', methods: ['POST'])]
    public function delete(Request $request, ArticleCollection $collection, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $collection->getId(), $request->request->get('_token'))) {
            $entityManager->remove($collection);
            $entityManager->flush();
            $this->addFlash('success', 'La collection a bien été supprimée.');
        }

        return $this->redirectToRoute('app_caisse_collection_index', [], Response::HTTP_SEE_OTHER);
    }
}
