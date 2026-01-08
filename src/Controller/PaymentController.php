<?php

namespace App\Controller;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Service\SocieteConfig;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    #[Route('/paiement', name: 'app_payment', methods: ['GET'])]
    public function index(SocieteConfig $societeConfig): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('payment/index.html.twig', [
            'stripePublicKey' => $societeConfig->getStripePublicKey(),
        ]);
    }

    #[Route('/paiement/create-checkout-session', name: 'app_payment_create_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request, UrlGeneratorInterface $urlGenerator, SocieteConfig $societeConfig): JsonResponse
    {
        $secretKey = $societeConfig->getStripeSecretKey();
        if (!$secretKey) {
            return new JsonResponse(['error' => 'Clé Stripe secrète manquante.'], 500);
        }

        Stripe::setApiKey($secretKey);

        // TODO: remplace par ton Panier réel (total TTC en centimes)
        // Exemple : 29,90 € => 2990
        $amount = (int) $request->request->get('amount', 0);
        if ($amount <= 0) {
            return new JsonResponse(['error' => 'Montant invalide.'], 400);
        }

        $session = Session::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $amount,
                    'product_data' => [
                        'name' => 'Commande Studio Pipelette',
                    ],
                ],
            ]],
            'success_url' => $urlGenerator->generate('app_payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $urlGenerator->generate('app_payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new JsonResponse(['id' => $session->id]);
    }

    #[Route('/paiement/success', name: 'app_payment_success', methods: ['GET'])]
    public function success(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('payment/success.html.twig');
    }

    #[Route('/paiement/cancel', name: 'app_payment_cancel', methods: ['GET'])]
    public function cancel(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('payment/cancel.html.twig');
    }
}
