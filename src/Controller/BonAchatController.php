<?php

namespace App\Controller;

use App\Entity\BonAchat;
use App\Repository\BonAchatRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\SocieteConfig;

#[Route('/caisse/bon-achat')]
class BonAchatController extends AbstractController
{
    #[Route('/', name: 'app_bon_achat_index', methods: ['GET'])]
    public function index(Request $request, BonAchatRepository $bonAchatRepository): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $total = $bonAchatRepository->count([]);
        $pagesCount = max(1, ceil($total / $limit));
        
        $bons = $bonAchatRepository->findBy([], ['dateCreation' => 'DESC'], $limit, $offset);

        return $this->render('bon_achat/index.html.twig', [
            'bons' => $bons,
            'currentPage' => $page,
            'pagesCount' => $pagesCount,
        ]);
    }

    #[Route('/new', name: 'app_bon_achat_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            $montant = $request->request->get('montant');
            $clientId = $request->request->get('client');
            
            if ($montant && $montant > 0 && $clientId) {
                $client = $userRepository->find($clientId);
                if (!$client) {
                    $this->addFlash('error', 'Client introuvable.');
                    return $this->redirectToRoute('app_bon_achat_new');
                }

                $bonAchat = new BonAchat();
                $bonAchat->setClient($client);
                $bonAchat->setMontantInitial((string)$montant);
                $bonAchat->setMontantRestant((string)$montant);
                $bonAchat->setDateCreation(new \DateTimeImmutable());
                $bonAchat->setDateExpiration((new \DateTimeImmutable())->modify('+1 year'));
                
                $entityManager->persist($bonAchat);
                $entityManager->flush();
                
                // Génération du code après flush pour avoir l'ID
                // Format: 30081260 + ID (5 digits)
                // Note: Ce format totalise 13 chiffres.
                $prefix = '30081260';
                $idPart = str_pad((string)$bonAchat->getId(), 5, '0', STR_PAD_LEFT);
                $code = $prefix . $idPart;
                
                $bonAchat->setCode($code);
                $entityManager->flush();

                return $this->redirectToRoute('app_bon_achat_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('bon_achat/new.html.twig', [
            'users' => $userRepository->findBy([], ['nom' => 'ASC', 'prenom' => 'ASC']),
        ]);
    }

    #[Route('/{id}/print', name: 'app_bon_achat_print', methods: ['GET'])]
    public function print(BonAchat $bonAchat, SocieteConfig $societeConfig): Response
    {
        return $this->render('bon_achat/print.html.twig', [
            'bon' => $bonAchat,
            'societe' => $societeConfig,
        ]);
    }
}
