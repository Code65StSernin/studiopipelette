<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ClientType;
use App\Repository\UserRepository;
use App\Repository\VenteRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/clients')]
class ClientController extends AbstractController
{
    #[Route('/', name: 'app_client_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, PaginatorInterface $paginator, Request $request): Response
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

        return $this->render('caisse/client/index.html.twig', [
            'clients' => $pagination,
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
}
