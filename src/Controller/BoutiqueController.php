<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use App\Repository\CouleurRepository;
use App\Repository\ArticleCollectionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

class BoutiqueController extends AbstractController
{
    #[Route('/boutique', name: 'app_boutique')]
    public function index(
        Request $request,
        ArticleRepository $articleRepository,
        CategorieRepository $categorieRepository,
        CouleurRepository $couleurRepository,
        ArticleCollectionRepository $collectionRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Récupérer les filtres depuis les paramètres GET
        $categorieId = $request->query->get('categorie');
        $couleurId = $request->query->get('couleur');
        $collectionId = $request->query->get('collection');
        $searchQuery = $request->query->get('q');
        $page = max(1, (int) $request->query->get('page', 1));
        
        // Nombre d'articles par page
        $limit = 8;
        $offset = ($page - 1) * $limit;
        
        // Construire la requête avec les filtres
        $qb = $entityManager->createQueryBuilder();
        $qb->select('DISTINCT a')
           ->from('App\Entity\Article', 'a')
           ->where('a.actif = :actif')
           ->setParameter('actif', true)
           ->orderBy('a.id', 'DESC');

        // Joindre couleurs et collections une seule fois avec des alias fixes
        $qb->leftJoin('a.couleurs', 'c');
        $qb->leftJoin('a.collections', 'col');
        
        // Filtre par catégorie
        if ($categorieId) {
            $qb->andWhere('a.categorie = :categorie')
               ->setParameter('categorie', $categorieId);
        }
        
        // Filtre par couleur (ManyToMany)
        if ($couleurId) {
            $qb->andWhere('c.id = :couleur')
               ->setParameter('couleur', $couleurId);
        }
        
        // Filtre par collection (ManyToMany)
        if ($collectionId) {
            $qb->andWhere('col.id = :collection')
               ->setParameter('collection', $collectionId);
        }
        
        // Filtre par recherche textuelle
        if ($searchQuery) {
            // Nettoyer la recherche
            $searchQuery = trim($searchQuery);
            
            // Rechercher dans nom, description, couleurs et collections
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(a.nom)', ':search'),
                    $qb->expr()->like('LOWER(a.description)', ':search'),
                    $qb->expr()->like('LOWER(c.nom)', ':search'),
                    $qb->expr()->like('LOWER(col.nom)', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($searchQuery) . '%');
        }
        
        // Compter le nombre total d'articles correspondant aux filtres
        $totalArticles = count($qb->getQuery()->getResult());
        $totalPages = ceil($totalArticles / $limit);
        
        // Récupérer les articles avec pagination
        $articles = $qb->setFirstResult($offset)
                       ->setMaxResults($limit)
                       ->getQuery()
                       ->getResult();
        
        // Récupérer toutes les options de filtres
        $categories = $categorieRepository->findAll();
        $couleurs = $couleurRepository->findAll();
        $collections = $collectionRepository->findAll();
        
        return $this->render('boutique/index.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'couleurs' => $couleurs,
            'collections' => $collections,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalArticles' => $totalArticles,
            'filters' => [
                'categorie' => $categorieId,
                'couleur' => $couleurId,
                'collection' => $collectionId,
                'q' => $searchQuery,
            ],
        ]);
    }
}

