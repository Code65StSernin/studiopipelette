<?php

namespace App\Service;

use App\Entity\Facture;
use App\Entity\Order;
use App\Service\SocieteConfig;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class OrderMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private SocieteConfig $societeConfig,
    ) {
    }

    /**
     * Envoie l'email de confirmation de commande au client.
     * Optionnellement, on pourrait attacher le PDF de facture si souhaité.
     */
    public function sendOrderConfirmation(Order $order, Facture $facture, string $pdfPath): void
    {
        $user = $order->getUser();

        if (!$user || !$user->getEmail()) {
            return;
        }

        $fromEmail = $this->societeConfig->getSmtpFromEmail() ?? 'noreply@code65.fr';
        $societeNom = $this->societeConfig->getNom() ?? 'So\'Sand';
        
        $email = (new TemplatedEmail())
            ->from($fromEmail)
            ->to($user->getEmail())
            ->subject('Merci pour votre commande')
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context([
                'order' => $order,
                'user'  => $user,
                'facture' => $facture,
            ]);

        // Pièce jointe : facture PDF
        if (is_file($pdfPath)) {
            $email->attachFromPath(
                $pdfPath,
                'facture-' . $facture->getNumero() . '.pdf',
                'application/pdf'
            );
        }

        // Pièce jointe : Conditions Générales de Vente (CGV)
        $projectDir = \dirname(__DIR__, 2);
        $cgvPath = $projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'Conditions Générales de Vente.pdf';

        if (is_file($cgvPath)) {
            $email->attachFromPath(
                $cgvPath,
                'Conditions Générales de Vente.pdf',
                'application/pdf'
            );
        }

        $this->mailer->send($email);
    }

    /**
     * Envoie l'email d'expédition de colis Mondial Relay au client.
     */
    public function sendShipmentNotification(Order $order): void
    {
        $user = $order->getUser();

        if (!$user || !$user->getEmail()) {
            return;
        }

        $shipmentNumber = $order->getMondialRelayShipmentNumber();
        if (!$shipmentNumber) {
            // Rien à envoyer si on n'a pas de numéro d'expédition
            return;
        }

        $trackingUrl = 'https://www.mondialrelay.fr/suivi-de-colis/';
        $fromEmail = $this->societeConfig->getSmtpFromEmail() ?? 'noreply@code65.fr';
        $societeNom = $this->societeConfig->getNom() ?? 'So\'Sand';

        $email = (new TemplatedEmail())
            ->from($fromEmail)
            ->to($user->getEmail())
            ->subject('Votre commande ' . $societeNom . ' a été expédiée')
            ->htmlTemplate('emails/shipment_notification.html.twig')
            ->context([
                'order'          => $order,
                'user'           => $user,
                'shipmentNumber' => $shipmentNumber,
                'trackingUrl'    => $trackingUrl,
            ]);

        $this->mailer->send($email);
    }
}
