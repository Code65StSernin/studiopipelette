<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class OrderClientController extends AbstractController
{
    #[Route('/commandes', name: 'app_commandes')]
    public function index(OrderRepository $orderRepository, Request $request): Response
    {
        $user = $this->getUser();

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $orders = $orderRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );

        $total = $orderRepository->count(['user' => $user]);
        $totalPages = max(1, (int) ceil($total / $limit));

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }
}
