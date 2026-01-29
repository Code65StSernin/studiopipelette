<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Reservation;
use App\Entity\Photo;
use App\Service\ImageService;
use App\Form\ClientType;
use App\Repository\UserRepository;
use App\Repository\VenteRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/clients')]
class ClientController extends AbstractController
{
    #[Route('/', name: 'app_client_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, PaginatorInterface $paginator, Request $request, EntityManagerInterface $entityManager): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->where('u.clientDepotVente = :clientDepotVente')
            ->setParameter('clientDepotVente', false)
            ->orderBy('u.nom', 'ASC');

        if ($q) {
            $queryBuilder
                ->andWhere('u.nom LIKE :q OR u.prenom LIKE :q OR u.email LIKE :q OR u.telephone LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        // Fetch missed reservation counts
        $missedCounts = $entityManager->getRepository(Reservation::class)->createQueryBuilder('r')
            ->select('r.clientEmail, COUNT(r.id) as count')
            ->where('r.status = :status')
            ->setParameter('status', Reservation::STATUS_MISSED)
            ->groupBy('r.clientEmail')
            ->getQuery()
            ->getArrayResult();

        $missedMap = [];
        foreach ($missedCounts as $row) {
            if ($row['clientEmail']) {
                $missedMap[$row['clientEmail']] = $row['count'];
            }
        }

        return $this->render('caisse/client/index.html.twig', [
            'clients' => $pagination,
            'missedMap' => $missedMap,
        ]);
    }

    #[Route('/{id}/missed', name: 'app_client_missed', methods: ['GET'])]
    public function missedReservations(User $user, EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
        $queryBuilder = $em->getRepository(Reservation::class)->createQueryBuilder('r')
            ->where('r.clientEmail = :email')
            ->andWhere('r.status = :status')
            ->setParameter('email', $user->getEmail())
            ->setParameter('status', Reservation::STATUS_MISSED)
            ->orderBy('r.dateStart', 'DESC');
            
        $pagination = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), 15);
        
        return $this->render('caisse/client/missed.html.twig', [
            'client' => $user,
            'reservations' => $pagination
        ]);
    }

    #[Route('/new', name: 'app_client_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(ClientType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Generate random password
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $digits = '0123456789';
            $password = '';
            for ($i = 0; $i < 8; $i++) {
                $password .= $chars[rand(0, strlen($chars) - 1)];
            }
            for ($i = 0; $i < 2; $i++) {
                $password .= $digits[rand(0, strlen($digits) - 1)];
            }
            $plainPassword = str_shuffle($password);

            // Hash password
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plainPassword
            );
            $user->setPassword($hashedPassword);
            
            // Set default role
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Le client a bien été créé.');

            return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/client/new.html.twig', [
            'client' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClientType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le client a bien été modifié.');

            return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/client/edit.html.twig', [
            'client' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_client_show', methods: ['GET'])]
    public function show(
        User $user, 
        VenteRepository $venteRepository, 
        OrderRepository $orderRepository, 
        PaginatorInterface $paginator, 
        Request $request
    ): Response
    {
        // Achats Caisse (Ventes)
        $queryVentes = $venteRepository->createQueryBuilder('v')
            ->where('v.client = :user')
            ->setParameter('user', $user)
            ->orderBy('v.dateVente', 'DESC')
            ->getQuery();

        $ventes = $paginator->paginate(
            $queryVentes,
            $request->query->getInt('page_ventes', 1),
            5, // Limit per page
            ['pageParameterName' => 'page_ventes']
        );

        // Achats Boutique (Orders)
        $queryOrders = $orderRepository->createQueryBuilder('o')
            ->where('o.user = :user')
            ->andWhere('o.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'paid') // On ne montre que les commandes payées ? Ou toutes ? Le user a dit "achats faits", donc validés.
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery();

        $orders = $paginator->paginate(
            $queryOrders,
            $request->query->getInt('page_orders', 1),
            5, // Limit per page
            ['pageParameterName' => 'page_orders']
        );

        return $this->render('caisse/client/show.html.twig', [
            'client' => $user,
            'ventes' => $ventes,
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}', name: 'app_client_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Le client a bien été supprimé.');
        }

        return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/photos', name: 'app_client_photos', methods: ['GET', 'POST'])]
    public function photos(Request $request, User $client, ImageService $imageService, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $files = $request->files->get('photos');
            
            // Check for upload errors (like max size exceeded leading to empty files)
            if (empty($files)) {
                $maxSize = ini_get('upload_max_filesize');
                $this->addFlash('danger', "Aucun fichier reçu. Vérifiez que la taille totale ne dépasse pas la limite du serveur ($maxSize).");
                return $this->redirectToRoute('app_client_show', ['id' => $client->getId(), 'open_photos' => 1]);
            }

            if (!is_array($files)) {
                $files = [$files];
            }
            
            $count = 0;
            foreach ($files as $file) {
                if ($file) {
                    try {
                        $filename = $imageService->uploadClientPhoto($file, $client->getId());
                        $photo = new Photo();
                        $photo->setFilename($filename);
                        $photo->setClient($client);
                        $photo->setCreatedAt(new \DateTime());
                        $photo->setType('image');
                        $entityManager->persist($photo);
                        $count++;
                    } catch (\Exception $e) {
                        $this->addFlash('danger', 'Erreur lors de l\'upload : ' . $e->getMessage());
                    }
                }
            }
            
            if ($count > 0) {
                $entityManager->flush();
                $this->addFlash('success', $count . ' photo(s) ajoutée(s) avec succès.');
            }
            
            return $this->redirectToRoute('app_client_show', ['id' => $client->getId(), 'open_photos' => 1]);
        }

        return $this->redirectToRoute('app_client_show', ['id' => $client->getId(), 'open_photos' => 1]);
    }

    #[Route('/photo/{id}/delete', name: 'app_client_photo_delete', methods: ['POST'])]
    public function deletePhoto(Request $request, Photo $photo, ImageService $imageService, EntityManagerInterface $entityManager): Response
    {
        $clientId = $photo->getClient()->getId();
        if ($this->isCsrfTokenValid('delete'.$photo->getId(), $request->request->get('_token'))) {
            // Supprimer le fichier physique
            $imageService->deleteClientPhoto($photo->getFilename(), $clientId);
            
            // Supprimer l'entité
            $entityManager->remove($photo);
            $entityManager->flush();
            $this->addFlash('success', 'Photo supprimée.');
        }

        return $this->redirectToRoute('app_client_show', ['id' => $clientId, 'open_photos' => 1]);
    }

    #[Route('/photo/{id}/rotate', name: 'app_client_photo_rotate', methods: ['POST'])]
    public function rotatePhoto(Request $request, Photo $photo, ImageService $imageService): JsonResponse
    {
        try {
            $imageService->rotateClientPhoto($photo->getFilename(), $photo->getClient()->getId());
            return new JsonResponse(['status' => 'success']);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
