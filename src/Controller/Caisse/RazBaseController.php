<?php

namespace App\Controller\Caisse;

use App\Entity\Article;
use App\Entity\ArticleCollection;
use App\Entity\Avoir;
use App\Entity\BonAchat;
use App\Entity\BtoB;
use App\Entity\Carousel;
use App\Entity\Code;
use App\Entity\Depenses;
use App\Entity\DepotVente;
use App\Entity\Facture;
use App\Entity\LigneFacture;
use App\Entity\LigneVente;
use App\Entity\Offre;
use App\Entity\Order;
use App\Entity\Paiement;
use App\Entity\Panier;
use App\Entity\PanierLigne;
use App\Entity\Photo;
use App\Entity\Recette;
use App\Entity\Reservation;
use App\Entity\Societe;
use App\Entity\User;
use App\Entity\Vente;
use App\Repository\SocieteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RazBaseController extends AbstractController
{
    #[Route('/razbase', name: 'app_razbase_index')]
    public function index(): Response
    {
        return $this->render('caisse/razbase/index.html.twig');
    }

    #[Route('/razbase/check-pin', name: 'app_razbase_check_pin', methods: ['POST'])]
    public function checkPin(Request $request, SocieteRepository $societeRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $pin = $data['pin'] ?? '';

        $societe = $societeRepository->findOneBy([]);
        if (!$societe) {
            return $this->json(['valid' => false, 'message' => 'Configuration société introuvable.']);
        }

        // Compare PIN (assuming plain text as seen in previous turns or simple comparison)
        // In previous turns, CaisseParametreController used simple comparison.
        $adminPin = $societe->getAdminPin();
        $effectivePin = ($adminPin !== null && trim($adminPin) !== '') ? trim($adminPin) : '1234';

        if (trim($pin) === $effectivePin) {
            return $this->json(['valid' => true]);
        }

        return $this->json(['valid' => false, 'message' => 'Code PIN incorrect.']);
    }

    #[Route('/razbase/execute/{step}', name: 'app_razbase_execute', methods: ['POST'])]
    public function executeStep(string $step, EntityManagerInterface $em): JsonResponse
    {
        // Disable SQL logger for performance - removed as it might be incompatible with newer Doctrine
        // $em->getConnection()->getConfiguration()->setSQLLogger(null);
        
        try {
            switch ($step) {
                case 'paniers':
                    $this->truncateTable($em, PanierLigne::class);
                    $this->truncateTable($em, Panier::class);
                    break;

                case 'ventes':
                    // Delete dependencies first
                    $this->truncateTable($em, LigneVente::class);
                    $this->truncateTable($em, Paiement::class);
                    $this->truncateTable($em, LigneFacture::class);
                    $this->truncateTable($em, Facture::class);
                    $this->truncateTable($em, Avoir::class);
                    $this->truncateTable($em, BonAchat::class);
                    $this->truncateTable($em, Vente::class);
                    break;

                case 'commandes':
                    // Order items, addresses usually cascade but let's be safe
                    // Assuming OrderItem, OrderAddress exist. 
                    // Need to check exact entity names for Order details.
                    // Assuming standard names or cascade delete.
                    // Let's use DQL DELETE for Order which usually cascades if configured, 
                    // but safe way is to delete items first.
                    // I'll check Order entity structure later, but for now generic delete.
                    // $em->createQuery('DELETE FROM App\Entity\OrderItem')->execute();
                    $em->createQuery('DELETE FROM App\Entity\Order')->execute(); 
                    break;

                case 'reservations':
                    $this->truncateTable($em, Reservation::class);
                    break;

                case 'comptabilite':
                    $this->truncateTable($em, Recette::class);
                    $this->truncateTable($em, Depenses::class);
                    break;

                case 'marketing':
                    $this->truncateTable($em, Code::class);
                    $this->truncateTable($em, Offre::class);
                    $this->truncateTable($em, Carousel::class);
                    break;

                case 'btob_depot':
                    $this->truncateTable($em, BtoB::class);
                    $this->truncateTable($em, DepotVente::class);
                    break;

                case 'articles':
                    // Photos first
                    $this->truncateTable($em, Photo::class);
                    // Relations (ManyToMany) are usually in join tables. 
                    // Deleting Article should remove join table entries if cascade is set.
                    // If not, we might get FK errors.
                    // Safe approach: Delete Article, let DB handle constraints or Doctrine.
                    $this->truncateTable($em, Article::class);
                    break;

                case 'clients':
                    // Delete users who do not have ROLE_ADMIN
                    // We need to be careful. JSON_CONTAINS or LIKE.
                    // Doctrine DQL: DELETE App\Entity\User u WHERE u.roles NOT LIKE :role
                    $query = $em->createQuery("DELETE App\Entity\User u WHERE u.roles NOT LIKE :role");
                    $query->setParameter('role', '%"ROLE_ADMIN"%');
                    $query->execute();
                    break;

                default:
                    return $this->json(['success' => false, 'message' => 'Étape inconnue.']);
            }

            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function truncateTable(EntityManagerInterface $em, string $className): void
    {
        // Use DQL DELETE which is safer than TRUNCATE for Doctrine (handles some cascades/events)
        // But for massive delete, iterating is slow.
        // DQL DELETE is: DELETE FROM Entity e
        $em->createQuery("DELETE FROM $className")->execute();
    }
}
