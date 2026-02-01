<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/stock')]
class CaisseStockController extends AbstractController
{
    #[Route('/api/search', name: 'app_caisse_stock_search', methods: ['GET'])]
    public function search(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $query = $request->query->get('q');
        if (!$query) {
            return new JsonResponse([]);
        }

        $articles = $articleRepository->createQueryBuilder('a')
            ->where('a.nom LIKE :query OR a.gencod LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($articles as $article) {
            /** @var Article $article */
            $tailles = $article->getTailles();
            
            if ($tailles && count($tailles) > 0) {
                foreach ($tailles as $t) {
                    $results[] = [
                        'id' => $article->getId() . '_' . $t['taille'], // Unique ID combining Article ID and Size
                        'articleId' => $article->getId(),
                        'taille' => $t['taille'],
                        'label' => $article->getNom() . ' (' . $t['taille'] . ')',
                        'stock' => (int)($t['stock'] ?? 0),
                        'prix' => $t['prix'] ?? 0
                    ];
                }
            }
        }

        return new JsonResponse($results);
    }

    #[Route('/api/update', name: 'app_caisse_stock_update', methods: ['POST'])]
    public function update(Request $request, ArticleRepository $articleRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $articleId = $data['articleId'] ?? null;
        $taille = $data['taille'] ?? null;
        $quantity = (int)($data['quantity'] ?? 0);
        $type = $data['type'] ?? null; // 'entree' or 'sortie'

        if (!$articleId || !$taille || !$quantity || !$type) {
            return new JsonResponse(['success' => false, 'message' => 'Données incomplètes'], 400);
        }

        $article = $articleRepository->find($articleId);
        if (!$article) {
            return new JsonResponse(['success' => false, 'message' => 'Article non trouvé'], 404);
        }

        $tailles = $article->getTailles();
        $updated = false;
        $newStock = 0;

        foreach ($tailles as $key => $t) {
            if ($t['taille'] === $taille) {
                $currentStock = (int)($t['stock'] ?? 0);
                
                if ($type === 'sortie') {
                    if ($currentStock < $quantity) {
                         return new JsonResponse(['success' => false, 'message' => 'Stock insuffisant'], 400);
                    }
                    $tailles[$key]['stock'] = $currentStock - $quantity;
                } elseif ($type === 'entree') {
                    $tailles[$key]['stock'] = $currentStock + $quantity;
                }
                
                $newStock = $tailles[$key]['stock'];
                $updated = true;
                break;
            }
        }

        if ($updated) {
            $article->setTailles($tailles);
            $em->flush();
            return new JsonResponse(['success' => true, 'newStock' => $newStock]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Taille non trouvée'], 404);
    }
}
