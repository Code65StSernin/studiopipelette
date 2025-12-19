<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArticleController extends AbstractController
{
    #[Route('/article/{slug}', name: 'app_article_show')]
    public function show(string $slug, ArticleRepository $articleRepository): Response
    {
        $article = $articleRepository->findOneBy(['slug' => $slug]);
        
        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }
        
        // Vérifier que l'article est actif (sauf pour les admins)
        if (!$article->isActif() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('Article non disponible');
        }
        
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }
}

