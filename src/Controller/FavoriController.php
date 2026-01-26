<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Service\FavoriService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/favoris')]
class FavoriController extends AbstractController
{
    public function __construct(
        private FavoriService $favoriService
    ) {
    }

    /**
     * Affiche la liste des favoris
     */
    #[Route('', name: 'app_favoris', methods: ['GET'])]
    public function index(): Response
    {
        $favori = $this->favoriService->getFavori();

        $response = $this->render('favoris/index.html.twig', [
            'favori' => $favori,
        ]);

        // Ajouter le cookie de session si nécessaire
        $this->favoriService->addSessionCookie($response);

        return $response;
    }

    /**
     * Ajoute un article aux favoris
     */
    #[Route('/ajouter/{id}', name: 'app_favoris_ajouter', methods: ['POST'])]
    public function ajouter(int $id, ArticleRepository $articleRepository): Response
    {
        $article = $articleRepository->find($id);

        if (!$article || !$article->isActif() || $article->getVisibilite() === Article::VISIBILITY_SHOP) {
            $this->addFlash('danger', 'Article introuvable ou non disponible');
            return $this->redirectToRoute('app_home');
        }

        try {
            $this->favoriService->ajouterArticle($article);
            $this->addFlash('success', 'Article ajouté aux favoris !');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        $response = $this->redirectToRoute('app_favoris');
        
        // Ajouter le cookie de session si nécessaire
        $this->favoriService->addSessionCookie($response);

        return $response;
    }

    /**
     * Bascule l'état favori d'un article (AJAX-friendly)
     */
    #[Route('/toggle/{id}', name: 'app_favoris_toggle', methods: ['POST'])]
    public function toggle(int $id, ArticleRepository $articleRepository): Response
    {
        $article = $articleRepository->find($id);

        if (!$article) {
            return $this->json(['success' => false, 'message' => 'Article introuvable'], 404);
        }

        // Si on essaie d'ajouter un article non disponible
        if (!$this->favoriService->estDansFavoris($article)) {
             if (!$article->isActif() || $article->getVisibilite() === Article::VISIBILITY_SHOP) {
                 return $this->json(['success' => false, 'message' => 'Article non disponible'], 403);
             }
        }

        try {
            $estAjoute = $this->favoriService->toggleArticle($article);
            $nombreArticles = $this->favoriService->getNombreArticles();
            
            return $this->json([
                'success' => true,
                'estFavori' => $estAjoute,
                'nombreArticles' => $nombreArticles,
                'message' => $estAjoute ? 'Ajouté aux favoris' : 'Retiré des favoris'
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Retire un article des favoris
     */
    #[Route('/retirer/{id}', name: 'app_favoris_retirer', methods: ['POST'])]
    public function retirer(int $id, ArticleRepository $articleRepository): Response
    {
        $article = $articleRepository->find($id);

        if (!$article) {
            $this->addFlash('danger', 'Article introuvable');
            return $this->redirectToRoute('app_favoris');
        }

        try {
            $this->favoriService->retirerArticle($article);
            $this->addFlash('success', 'Article retiré des favoris');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_favoris');
    }

    /**
     * Vide complètement les favoris
     */
    #[Route('/vider', name: 'app_favoris_vider', methods: ['POST'])]
    public function vider(): Response
    {
        try {
            $this->favoriService->viderFavoris();
            $this->addFlash('success', 'Favoris vidés');
        } catch (\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_favoris');
    }
}

