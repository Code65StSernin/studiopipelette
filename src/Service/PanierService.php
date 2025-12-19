<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Panier;
use App\Entity\PanierLigne;
use App\Entity\User;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security;

class PanierService
{
    private const COOKIE_NAME = 'panier_session_id';
    private const COOKIE_LIFETIME = 60 * 60 * 24 * 30; // 30 jours

    public function __construct(
        private EntityManagerInterface $entityManager,
        private PanierRepository $panierRepository,
        private RequestStack $requestStack,
        private Security $security
    ) {
    }

    /**
     * Récupère ou crée le panier actuel (connecté ou non)
     */
    public function getPanier(): Panier
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            // Utilisateur connecté : récupérer son panier
            $panier = $this->panierRepository->findByUser($user);
            
            if (!$panier) {
                // Créer un nouveau panier pour l'utilisateur
                $panier = new Panier();
                $panier->setUser($user);
                $this->entityManager->persist($panier);
                $this->entityManager->flush();
            }

            // Fusionner avec le panier session si existant
            $this->mergePanierSession($panier);

            return $panier;
        }

        // Utilisateur non connecté : selon le consentement cookies, utiliser un cookie ou la session PHP
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new \RuntimeException('Aucune requête en cours');
        }

        $hasConsent = $request->cookies->get('cookie_consent') === 'accepted';

        if ($hasConsent) {
            // Mode actuel : identifiant stocké dans un cookie persistant
            $sessionId = $this->getOrCreateSessionId();
        } else {
            // Sans consentement : identifiant basé sur la session PHP (cookie de session, supprimé à la fermeture du navigateur)
            $sessionId = $request->getSession()->getId();
        }
        $panier = $this->panierRepository->findBySessionId($sessionId);

        if (!$panier) {
            // Créer un nouveau panier avec sessionId
            $panier = new Panier();
            $panier->setSessionId($sessionId);
            $this->entityManager->persist($panier);
            $this->entityManager->flush();
        }

        return $panier;
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

        // Le cookie sera ajouté à la réponse via un event listener ou manuellement
        // Pour l'instant, on retourne le sessionId
        return $sessionId;
    }

    /**
     * Ajoute le cookie de session à la réponse
     */
    public function addSessionCookie(Response $response): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        // Ne jamais poser de cookie panier si l'utilisateur a refusé les cookies
        if (
            !$request
            || $request->cookies->get('cookie_consent') === 'refused'
            || $request->cookies->has(self::COOKIE_NAME)
        ) {
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
     * Fusionne le panier session dans le panier utilisateur lors de la connexion
     */
    private function mergePanierSession(Panier $panierUser): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            return;
        }

        $sessionId = $request->cookies->get(self::COOKIE_NAME);

        if (!$sessionId) {
            return;
        }

        $panierSession = $this->panierRepository->findBySessionId($sessionId);

        if (!$panierSession || $panierSession->isEmpty()) {
            return;
        }

        // Fusionner les lignes du panier session dans le panier utilisateur
        foreach ($panierSession->getLignes() as $ligneSession) {
            $ligneExistante = $panierUser->contientArticle(
                $ligneSession->getArticle(),
                $ligneSession->getTaille()
            );

            if ($ligneExistante) {
                // Additionner les quantités
                $nouvelleQuantite = $ligneExistante->getQuantite() + $ligneSession->getQuantite();
                $ligneExistante->setQuantite($nouvelleQuantite);
            } else {
                // Ajouter la ligne au panier utilisateur
                $nouvelleLigne = new PanierLigne();
                $nouvelleLigne->setArticle($ligneSession->getArticle());
                $nouvelleLigne->setTaille($ligneSession->getTaille());
                $nouvelleLigne->setQuantite($ligneSession->getQuantite());
                $nouvelleLigne->setPrixUnitaire($ligneSession->getPrixUnitaire());
                $panierUser->addLigne($nouvelleLigne);
            }
        }

        // Supprimer le panier session
        $this->entityManager->remove($panierSession);
        $this->entityManager->flush();
    }

    /**
     * Ajoute un article au panier
     * @throws \Exception Si le stock est insuffisant
     */
    public function ajouterArticle(Article $article, string $taille, int $quantite = 1): void
    {
        // Vérifier le stock
        $stock = $article->getStockParTaille($taille);
        
        if ($stock === null || $stock < 1) {
            throw new \Exception('Cet article n\'est plus disponible en stock pour la taille ' . $taille);
        }

        $panier = $this->getPanier();
        $ligneExistante = $panier->contientArticle($article, $taille);

        if ($ligneExistante) {
            // Vérifier si le stock est suffisant pour la nouvelle quantité
            $nouvelleQuantite = $ligneExistante->getQuantite() + $quantite;
            
            if ($stock < $nouvelleQuantite) {
                throw new \Exception('Stock insuffisant. Disponible : ' . $stock);
            }

            $ligneExistante->setQuantite($nouvelleQuantite);
        } else {
            // Vérifier si le stock est suffisant
            if ($stock < $quantite) {
                throw new \Exception('Stock insuffisant. Disponible : ' . $stock);
            }

            // Créer une nouvelle ligne
            $ligne = new PanierLigne();
            $ligne->setArticle($article);
            $ligne->setTaille($taille);
            $ligne->setQuantite($quantite);
            $ligne->setPrixUnitaire($article->getPrixParTaille($taille) ?? 0);
            
            $panier->addLigne($ligne);
        }

        $this->entityManager->flush();
    }

    /**
     * Modifie la quantité d'une ligne du panier
     */
    public function modifierQuantite(int $ligneId, int $nouvelleQuantite): void
    {
        if ($nouvelleQuantite < 1) {
            throw new \InvalidArgumentException('La quantité doit être supérieure à 0');
        }

        $ligne = $this->entityManager->getRepository(PanierLigne::class)->find($ligneId);

        if (!$ligne) {
            throw new \Exception('Ligne de panier introuvable');
        }

        // Vérifier que cette ligne appartient au panier actuel
        $panier = $this->getPanier();
        if ($ligne->getPanier()->getId() !== $panier->getId()) {
            throw new \Exception('Cette ligne ne vous appartient pas');
        }

        // Vérifier le stock
        $stock = $ligne->getStock();
        if ($stock === null || $stock < $nouvelleQuantite) {
            throw new \Exception('Stock insuffisant. Disponible : ' . ($stock ?? 0));
        }

        $ligne->setQuantite($nouvelleQuantite);
        $this->entityManager->flush();
    }

    /**
     * Supprime une ligne du panier
     */
    public function supprimerLigne(int $ligneId): void
    {
        $ligne = $this->entityManager->getRepository(PanierLigne::class)->find($ligneId);

        if (!$ligne) {
            throw new \Exception('Ligne de panier introuvable');
        }

        // Vérifier que cette ligne appartient au panier actuel
        $panier = $this->getPanier();
        if ($ligne->getPanier()->getId() !== $panier->getId()) {
            throw new \Exception('Cette ligne ne vous appartient pas');
        }

        $panier->removeLigne($ligne);
        $this->entityManager->remove($ligne);
        $this->entityManager->flush();
    }

    /**
     * Vide complètement le panier
     */
    public function viderPanier(): void
    {
        $panier = $this->getPanier();

        foreach ($panier->getLignes() as $ligne) {
            $this->entityManager->remove($ligne);
        }

        // Réinitialiser également les informations de code promo
        if (method_exists($panier, 'setCodePromo')) {
            $panier->setCodePromo(null);
        }
        if (method_exists($panier, 'setCodePromoPourcentage')) {
            $panier->setCodePromoPourcentage(null);
        }

        $this->entityManager->flush();
    }

    /**
     * Supprime le cookie de session du panier côté navigateur
     */
    public function clearSessionCookie(Response $response): void
    {
        $cookie = Cookie::create(self::COOKIE_NAME)
            ->withValue('')
            ->withExpires(1) // date passée pour forcer la suppression
            ->withPath('/')
            ->withSecure(false)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);
    }

    /**
     * Retourne le nombre d'articles dans le panier actuel
     */
    public function getNombreArticles(): int
    {
        try {
            $panier = $this->getPanier();
            return $panier->getNombreArticles();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Vérifie si tous les articles du panier sont disponibles en stock
     */
    public function verifierStock(): array
    {
        $panier = $this->getPanier();
        $erreurs = [];

        foreach ($panier->getLignes() as $ligne) {
            if (!$ligne->isStockSuffisant()) {
                $erreurs[] = sprintf(
                    '%s (taille %s) : stock insuffisant (demandé: %d, disponible: %d)',
                    $ligne->getArticle()->getNom(),
                    $ligne->getTaille(),
                    $ligne->getQuantite(),
                    $ligne->getStock() ?? 0
                );
            }
        }

        return $erreurs;
    }
}

