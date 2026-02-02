<?php

namespace App\Controller\Caisse;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/caisse/categories')]
class CategorieController extends AbstractController
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    #[Route('/', name: 'app_caisse_categorie_index', methods: ['GET'])]
    public function index(CategorieRepository $categorieRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $categorieRepository->createQueryBuilder('c')
            ->orderBy('c.nom', 'ASC');

        if ($q) {
            $queryBuilder
                ->where('c.nom LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('caisse/boutique_categorie/index.html.twig', [
            'categories' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_categorie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($categorie->getNom()) {
                $slug = strtolower($this->slugger->slug($categorie->getNom())->toString());
                $categorie->setSlug($slug);
            }

            $entityManager->persist($categorie);
            $entityManager->flush();

            $this->addFlash('success', 'La catégorie a bien été créée.');
            return $this->redirectToRoute('app_caisse_categorie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/boutique_categorie/new.html.twig', [
            'categorie' => $categorie,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_categorie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Categorie $categorie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($categorie->getNom()) {
                $slug = strtolower($this->slugger->slug($categorie->getNom())->toString());
                $categorie->setSlug($slug);
            }

            $entityManager->flush();

            $this->addFlash('success', 'La catégorie a bien été modifiée.');
            return $this->redirectToRoute('app_caisse_categorie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/boutique_categorie/edit.html.twig', [
            'categorie' => $categorie,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_categorie_delete', methods: ['POST'])]
    public function delete(Request $request, Categorie $categorie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$categorie->getId(), $request->request->get('_token'))) {
            // Check if category has articles
            if (!$categorie->getArticles()->isEmpty()) {
                $this->addFlash('error', 'Impossible de supprimer cette catégorie car elle contient des articles.');
                return $this->redirectToRoute('app_caisse_categorie_index');
            }

            $entityManager->remove($categorie);
            $entityManager->flush();
            $this->addFlash('success', 'La catégorie a bien été supprimée.');
        }

        return $this->redirectToRoute('app_caisse_categorie_index', [], Response::HTTP_SEE_OTHER);
    }
}
