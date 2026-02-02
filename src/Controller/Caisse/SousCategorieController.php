<?php

namespace App\Controller\Caisse;

use App\Entity\SousCategorie;
use App\Form\SousCategorieType;
use App\Repository\SousCategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/caisse/sous-categories')]
class SousCategorieController extends AbstractController
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    #[Route('/', name: 'app_caisse_sous_categorie_index', methods: ['GET'])]
    public function index(SousCategorieRepository $sousCategorieRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $sousCategorieRepository->createQueryBuilder('s')
            ->orderBy('s.nom', 'ASC');

        if ($q) {
            $queryBuilder
                ->where('s.nom LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('caisse/boutique_sous_categorie/index.html.twig', [
            'sous_categories' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_sous_categorie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sousCategorie = new SousCategorie();
        $form = $this->createForm(SousCategorieType::class, $sousCategorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($sousCategorie->getNom()) {
                $slug = strtolower($this->slugger->slug($sousCategorie->getNom())->toString());
                $sousCategorie->setSlug($slug);
            }

            $entityManager->persist($sousCategorie);
            $entityManager->flush();

            $this->addFlash('success', 'La sous-catégorie a bien été créée.');
            return $this->redirectToRoute('app_caisse_sous_categorie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/boutique_sous_categorie/new.html.twig', [
            'sous_categorie' => $sousCategorie,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_sous_categorie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SousCategorie $sousCategorie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SousCategorieType::class, $sousCategorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($sousCategorie->getNom()) {
                $slug = strtolower($this->slugger->slug($sousCategorie->getNom())->toString());
                $sousCategorie->setSlug($slug);
            }

            $entityManager->flush();

            $this->addFlash('success', 'La sous-catégorie a bien été modifiée.');
            return $this->redirectToRoute('app_caisse_sous_categorie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/boutique_sous_categorie/edit.html.twig', [
            'sous_categorie' => $sousCategorie,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_sous_categorie_delete', methods: ['POST'])]
    public function delete(Request $request, SousCategorie $sousCategorie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sousCategorie->getId(), $request->request->get('_token'))) {
            // Check if has articles
            if (!$sousCategorie->getArticles()->isEmpty()) {
                $this->addFlash('error', 'Impossible de supprimer cette sous-catégorie car elle contient des articles.');
                return $this->redirectToRoute('app_caisse_sous_categorie_index');
            }

            $entityManager->remove($sousCategorie);
            $entityManager->flush();
            $this->addFlash('success', 'La sous-catégorie a bien été supprimée.');
        }

        return $this->redirectToRoute('app_caisse_sous_categorie_index', [], Response::HTTP_SEE_OTHER);
    }
}
