<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Favori;
use App\Entity\User;
use App\Repository\FavoriRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;

class FavoriService
{
    private const COOKIE_NAME = 'favori_session_id';
    private const COOKIE_LIFETIME = 60 * 60 * 24 * 90; // 90 jours

    public function __construct(
        private EntityManagerInterface $entityManager,
        private FavoriRepository $favoriRepository,
        private RequestStack $requestStack,
        private Security $security
    ) {
    }

    /**
     * Récupère ou crée la liste de favoris actuelle (connecté ou non)
     */
    public function getFavori(): Favori
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            // Utilisateur connecté : récupérer ses favoris
            $favori = $this->favoriRepository->findByUser($user);
            
            if (!$favori) {
                // Créer une nouvelle liste de favoris pour l'utilisateur
                $favori = new Favori();
                $favori->setUser($user);
                $this->entityManager->persist($favori);
                $this->entityManager->flush();
            }

            // Fusionner avec les favoris session si existant
            $this->mergeFavoriSession($favori);

            return $favori;
        }

        // Utilisateur non connecté : utiliser le sessionId
        $sessionId = $this->getOrCreateSessionId();
        $favori = $this->favoriRepository->findBySessionId($sessionId);

        if (!$favori) {
            // Créer une nouvelle liste de favoris avec sessionId
            $favori = new Favori();
            $favori->setSessionId($sessionId);
            $this->entityManager->persist($favori);
            $this->entityManager->flush();
        }

        return $favori;
    }

    /**
     * Récupère ou génère un sessionId unique et le stocke dans un cookie
     */
    private function getOrCreateSessionId(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            throw new \RuntimeException('Aucune requête en cours');
        }

        // Vérifier si le cookie existe déjà
        $sessionId = $request->cookies->get(self::COOKIE_NAME);

        if ($sessionId) {
            return $sessionId;
        }

        // Générer un nouveau sessionId unique
        $sessionId = bin2hex(random_bytes(32));

        return $sessionId;
    }

    /**
     * Ajoute le cookie de session à la réponse
     */
    public function addSessionCookie(Response $response): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request || $request->cookies->has(self::COOKIE_NAME)) {
            return;
        }

        $sessionId = $this->getOrCreateSessionId();

        $cookie = Cookie::create(self::COOKIE_NAME)
            ->withValue($sessionId)
            ->withExpires(time() + self::COOKIE_LIFETIME)
            ->withPath('/')
            ->withSecure(false) // Mettre à true en production avec HTTPS
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);
    }

    /**
     * Fusionne les favoris session dans les favoris utilisateur lors de la connexion
     */
    private function mergeFavoriSession(Favori $favoriUser): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            return;
        }

        $sessionId = $request->cookies->get(self::COOKIE_NAME);

        if (!$sessionId) {
            return;
        }

        $favoriSession = $this->favoriRepository->findBySessionId($sessionId);

        if (!$favoriSession || $favoriSession->isEmpty()) {
            return;
        }

        // Fusionner les articles des favoris session dans les favoris utilisateur
        foreach ($favoriSession->getArticles() as $article) {
            if (!$favoriUser->contientArticle($article)) {
                $favoriUser->addArticle($article);
            }
        }

        // Supprimer les favoris session
        $this->entityManager->remove($favoriSession);
        $this->entityManager->flush();
    }

    /**
     * Ajoute un article aux favoris
     */
    public function ajouterArticle(Article $article): void
    {
        $favori = $this->getFavori();
        
        if (!$favori->contientArticle($article)) {
            $favori->addArticle($article);
            $this->entityManager->flush();
        }
    }

    /**
     * Retire un article des favoris
     */
    public function retirerArticle(Article $article): void
    {
        $favori = $this->getFavori();
        
        if ($favori->contientArticle($article)) {
            $favori->removeArticle($article);
            $this->entityManager->flush();
        }
    }

    /**
     * Bascule l'état favori d'un article (toggle)
     */
    public function toggleArticle(Article $article): bool
    {
        $favori = $this->getFavori();
        
        if ($favori->contientArticle($article)) {
            $favori->removeArticle($article);
            $this->entityManager->flush();
            return false; // Retiré
        } else {
            $favori->addArticle($article);
            $this->entityManager->flush();
            return true; // Ajouté
        }
    }

    /**
     * Vérifie si un article est dans les favoris
     */
    public function estDansFavoris(Article $article): bool
    {
        try {
            $favori = $this->getFavori();
            return $favori->contientArticle($article);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Vide complètement les favoris
     */
    public function viderFavoris(): void
    {
        $favori = $this->getFavori();
        $favori->getArticles()->clear();
        $this->entityManager->flush();
    }

    /**
     * Retourne le nombre d'articles dans les favoris actuels
     */
    public function getNombreArticles(): int
    {
        try {
            $favori = $this->getFavori();
            return $favori->getNombreArticles();
        } catch (\Exception $e) {
            return 0;
        }
    }
}

