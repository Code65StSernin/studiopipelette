<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Service\SocieteConfig;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StripeWebhookController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderRepository $orderRepository,
        private LoggerInterface $logger,
        private SocieteConfig $societeConfig,
    ) {}

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        Stripe::setApiKey((string) $this->societeConfig->getStripeSecretKey());

        $payload   = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');

        // Log d'entrée (si absent => webhook jamais appelé)
        $this->logStripeLine('WEBHOOK HIT', [
            'has_signature' => $sigHeader ? 'yes' : 'no',
            'payload_length' => strlen($payload),
        ]);

        try {
            $webhookSecret = (string) ($this->societeConfig->getStripeWebhookSecret() ?? '');
            if (empty($webhookSecret)) {
                $this->logStripeLine('WEBHOOK SECRET MISSING', []);
                return new Response('Webhook secret not configured', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $event = Webhook::constructEvent(
                $payload,
                (string) $sigHeader,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            $this->logStripeLine('INVALID PAYLOAD', ['error' => $e->getMessage()]);
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (SignatureVerificationException $e) {
            $this->logStripeLine('INVALID SIGNATURE', ['error' => $e->getMessage()]);
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logStripeLine('WEBHOOK CONSTRUCT ERROR', ['error' => $e->getMessage()]);
            return new Response('Webhook error', Response::HTTP_BAD_REQUEST);
        }

        $type = (string) ($event->type ?? '');
        $object = $event->data->object ?? null;

        $this->logStripeLine('EVENT RECEIVED', [
            'type' => $type,
            'object_id' => $object->id ?? null,
            'payment_status' => $object->payment_status ?? null,
            'status' => $object->status ?? null,
            'amount_total' => $object->amount_total ?? null,
        ]);

        try {
            switch ($type) {

                /**
                 * ===============================
                 * PAIEMENT IMMÉDIAT RÉUSSI
                 * ===============================
                 */
                case 'checkout.session.completed':
                case 'checkout.session.async_payment_succeeded':

                    if (!$object || empty($object->id)) {
                        break;
                    }

                    $sessionId = (string) $object->id;

                    $order = $this->orderRepository->findOneBy([
                        'stripeCheckoutSessionId' => $sessionId,
                    ]);

                    if (!$order) {
                        $this->logStripeLine('ORDER NOT FOUND', ['session_id' => $sessionId]);
                        break;
                    }

                    // Idempotence : déjà payé → on sort
                    if ($order->getStatus() === Order::STATUS_PAID) {
                        $this->logStripeLine('ORDER ALREADY PAID', [
                            'order_id' => $order->getId(),
                        ]);
                        break;
                    }

                    // Sécurité : Stripe confirme bien "paid"
                    if (($object->payment_status ?? null) !== 'paid') {
                        $this->logStripeLine('PAYMENT NOT PAID -> IGNORE', [
                            'order_id' => $order->getId(),
                            'payment_status' => $object->payment_status ?? null,
                        ]);
                        break;
                    }

                    // Sécurité : vérification du montant
                    if (isset($object->amount_total)) {
                        $stripeAmount = (int) $object->amount_total;
                        if ($stripeAmount !== $order->getAmountTotalCents()) {
                            $this->logStripeLine('AMOUNT MISMATCH', [
                                'order_id' => $order->getId(),
                                'stripe_amount' => $stripeAmount,
                                'order_amount' => $order->getAmountTotalCents(),
                            ]);
                            break;
                        }
                    }

                    if (!empty($object->payment_intent)) {
                        $order->setStripePaymentIntentId((string) $object->payment_intent);
                    }

                    $order
                        ->setStatus(Order::STATUS_PAID)
                        ->setPaidAt(new \DateTimeImmutable());

                    $this->em->flush();

                    $this->logStripeLine('ORDER MARKED PAID', [
                        'order_id' => $order->getId(),
                        'session_id' => $sessionId,
                    ]);

                    break;

                /**
                 * ===============================
                 * PAIEMENT ÉCHOUÉ / EXPIRÉ
                 * ===============================
                 */
                case 'checkout.session.async_payment_failed':
                case 'checkout.session.expired':

                    if (!$object || empty($object->id)) {
                        break;
                    }

                    $sessionId = (string) $object->id;

                    $order = $this->orderRepository->findOneBy([
                        'stripeCheckoutSessionId' => $sessionId,
                    ]);

                    if (!$order) {
                        break;
                    }

                    if ($order->getStatus() === Order::STATUS_PAID) {
                        break;
                    }

                    $order->setStatus(Order::STATUS_CANCELED);
                    $this->em->flush();

                    $this->logStripeLine('ORDER CANCELED', [
                        'order_id' => $order->getId(),
                        'event' => $type,
                    ]);

                    break;

                default:
                    // Events ignorés volontairement
                    break;
            }
        } catch (\Throwable $e) {
            $this->logStripeLine('HANDLER ERROR', [
                'error' => $e->getMessage(),
            ]);

            // 200 pour éviter les retries infinis
            return new Response('Handler error', Response::HTTP_OK);
        }

        return new Response('OK', Response::HTTP_OK);
    }

    private function logStripeLine(string $label, array $context = []): void
    {
        // Log Symfony
        $this->logger->info('[STRIPE] ' . $label, $context);

        // Log fichier dédié
        try {
            $projectDir = $this->getParameter('kernel.project_dir');
            $logDir = $projectDir . '/var/log';

            if (!is_dir($logDir)) {
                @mkdir($logDir, 0777, true);
            }

            $logPath = $logDir . '/stripe_webhook.log';

            $line = sprintf(
                "[%s] %s | %s\n",
                date('Y-m-d H:i:s'),
                $label,
                json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            @file_put_contents($logPath, $line, FILE_APPEND);
        } catch (\Throwable $e) {
            error_log('[STRIPE] logStripeLine failed: ' . $e->getMessage());
        }
    }
}
