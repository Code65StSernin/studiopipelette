<?php

namespace App\Controller\Caisse;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/caisse/articles')]
class ArticleController extends AbstractController
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    #[Route('/', name: 'app_caisse_article_index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $articleRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if ($q) {
            $queryBuilder
                ->where('a.nom LIKE :q OR a.gencod LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('caisse/article/index.html.twig', [
            'articles' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Logic from ArticleCrudController::persistEntity
            if ($article->getNom()) {
                $slug = strtolower($this->slugger->slug($article->getNom())->toString());
                $article->setSlug($slug);
            }
            $this->validateTaillesJson($article);

            $entityManager->persist($article);
            
            // Gencod generation requires ID if we want to use ID in it, but here we can't get ID before flush if using auto-increment.
            // However, ArticleCrudController does persist -> generate -> flush.
            // Let's do persist -> flush -> generate -> flush.
            $entityManager->flush();

            if (!$article->getGencod()) {
                $this->generateGencod($article);
                $entityManager->flush();
            }

            $this->addFlash('success', 'L\'article a bien été créé.');

            return $this->redirectToRoute('app_caisse_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/article/new.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Logic from ArticleCrudController::updateEntity
            if ($article->getNom()) {
                $slug = strtolower($this->slugger->slug($article->getNom())->toString());
                $article->setSlug($slug);
            }
            $this->validateTaillesJson($article);

            if (!$article->getGencod()) {
                $this->generateGencod($article);
            }

            $entityManager->flush();

            $this->addFlash('success', 'L\'article a bien été modifié.');

            return $this->redirectToRoute('app_caisse_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/article/edit.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            $entityManager->remove($article);
            $entityManager->flush();
            $this->addFlash('success', 'L\'article a bien été supprimé.');
        }

        return $this->redirectToRoute('app_caisse_article_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/duplicate', name: 'app_caisse_article_duplicate', methods: ['GET'])]
    public function duplicate(Article $article, EntityManagerInterface $entityManager): Response
    {
        $newArticle = new Article();
        $newArticle->setNom('Copie de ' . $article->getNom());
        
        $slug = $this->slugger->slug($newArticle->getNom())->lower() . '-' . uniqid();
        $newArticle->setSlug($slug);

        $newArticle->setDescription($article->getDescription());
        $newArticle->setCategorie($article->getCategorie());
        $newArticle->setSousCategorie($article->getSousCategorie());
        $newArticle->setOrigine($article->getOrigine());
        $newArticle->setMateriau($article->getMateriau());
        $newArticle->setTailles($article->getTailles());
        $newArticle->setCompositionFabrication($article->getCompositionFabrication());
        $newArticle->setInformationsLivraison($article->getInformationsLivraison());
        $newArticle->setSousTitre($article->getSousTitre());
        $newArticle->setSousTitreContenu($article->getSousTitreContenu());
        $newArticle->setVisibilite($article->getVisibilite());
        $newArticle->setFournisseur($article->getFournisseur());
        $newArticle->setActif(false);
        
        foreach ($article->getCollections() as $collection) {
            $newArticle->addCollection($collection);
        }
        foreach ($article->getCouleurs() as $couleur) {
            $newArticle->addCouleur($couleur);
        }
        
        $entityManager->persist($newArticle);
        $entityManager->flush();
        
        $this->generateGencod($newArticle);
        $entityManager->flush();
        
        $this->addFlash('success', 'Article dupliqué avec succès ! (Les photos ne sont pas dupliquées)');
        
        return $this->redirectToRoute('app_caisse_article_edit', ['id' => $newArticle->getId()]);
    }

    private function validateTaillesJson($article): void
    {
        $tailles = $article->getTailles();
        
        if ($tailles && is_array($tailles)) {
            foreach ($tailles as $index => $taille) {
                if (!is_array($taille)) {
                    throw new \Exception("Erreur dans le champ 'Tailles' : chaque élément doit être un objet avec les propriétés 'taille', 'prix' et 'stock'.");
                }
                
                if (!isset($taille['taille']) || !isset($taille['prix']) || !isset($taille['stock'])) {
                    throw new \Exception("Erreur dans le champ 'Tailles' à l'index {$index} : les propriétés 'taille', 'prix' et 'stock' sont obligatoires.");
                }
                
                if (!is_numeric($taille['prix'])) {
                    throw new \Exception("Erreur dans le champ 'Tailles' à l'index {$index} : 'prix' doit être un nombre.");
                }
                if (!is_numeric($taille['stock'])) {
                    throw new \Exception("Erreur dans le champ 'Tailles' à l'index {$index} : 'stock' doit être un nombre entier.");
                }
            }
        }
    }

    private function generateGencod(Article $article): void
    {
        $id = $article->getId();
        if (!$id) {
            return;
        }

        $prefix = '3009765';
        $paddedId = str_pad((string)$id, 5, '0', STR_PAD_LEFT);
        $base = $prefix . $paddedId;

        $sumOdd = 0;
        $sumEven = 0;

        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$base[$i];
            if (($i + 1) % 2 !== 0) {
                $sumOdd += $digit;
            } else {
                $sumEven += $digit;
            }
        }

        $total = $sumOdd + ($sumEven * 3);
        $nextTen = ceil($total / 10) * 10;
        $checkDigit = $nextTen - $total;

        $article->setGencod($base . $checkDigit);
    }
}
