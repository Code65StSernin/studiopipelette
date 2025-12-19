<?php

namespace App\Service;

use App\Entity\Facture;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

class FacturePdfGenerator
{
    private string $tempPath;
    private string $publicDir;

    public function __construct(
        ParameterBagInterface $params,
        private Environment $twig,
    ) {
        $projectDir = $params->get('kernel.project_dir');
        $this->tempPath = $projectDir . '/var/tmp/factures/';
        $this->publicDir = $projectDir . '/public';
    }

    /**
     * Génère un fichier PDF pour une facture donnée et retourne son chemin sur le disque.
     */
    public function generate(Facture $facture): string
    {
        // S'assurer que le dossier temporaire existe
        if (!is_dir($this->tempPath)) {
            mkdir($this->tempPath, 0777, true);
        }

        // 1. Générer le HTML de la facture via Twig (template dédié pour le PDF)
        // On passe un chemin relatif à partir du chroot (dossier public)
        $backgroundUrl = '/assets/img/pdf/papier_sosand.jpg';

        $adresseLivraison = $this->buildAdresseLivraison($facture);

        $html = $this->twig->render('facture/pdf.html.twig', [
            'facture' => $facture,
            'backgroundUrl' => $backgroundUrl,
            'adresseLivraison' => $adresseLivraison,
        ]);

        // 2. Configurer Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true); // nécessaire pour les images locales/externes
        $options->setChroot($this->publicDir);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();

        // 3. Sauvegarder le PDF dans un fichier temporaire
        $fileBaseName = 'facture_' . $facture->getNumero();
        $pdfPath = $this->tempPath . $fileBaseName . '.pdf';

        file_put_contents($pdfPath, $output);

        return $pdfPath;
    }

    private function buildAdresseLivraison(Facture $facture): string
    {
        $order = $facture->getOrder();

        if ($facture->getModeLivraison() === 'domicile') {
            // 1) On tente de reconstruire l'adresse à partir des adresses de l'utilisateur
            $user = $order->getUser();
            $addressText = null;

            if ($user) {
                $addresses = $user->getAddresses();
                $shippingAddress = null;

                if ($addresses) {
                    foreach ($addresses as $addr) {
                        if ($addr->isDefault()) {
                            $shippingAddress = $addr;
                            break;
                        }
                    }
                    if ($shippingAddress === null && count($addresses) > 0) {
                        $first = $addresses->first();
                        $shippingAddress = $first !== false ? $first : null;
                    }
                }

                if ($shippingAddress) {
                    $ligne1 = trim($shippingAddress->getStreetNumber() . ' ' . $shippingAddress->getStreet());
                    $ligne2Parts = [];
                    if ($shippingAddress->getComplement()) {
                        $ligne2Parts[] = $shippingAddress->getComplement();
                    }
                    $ligne2Parts[] = trim($shippingAddress->getPostalCode() . ' ' . $shippingAddress->getCity());
                    $ligne2 = implode(' - ', array_filter($ligne2Parts));

                    $addressText = trim($ligne1 . "\n" . $ligne2);
                }
            }

            // 2) Fallback : utiliser les champs immuables de la facture si on n'a rien trouvé
            if (!$addressText) {
                $ligne1 = trim((string) $facture->getClientAdresse());
                $ligne2Parts = [];
                if ($facture->getClientCodePostal() || $facture->getClientVille()) {
                    $ligne2Parts[] = trim(($facture->getClientCodePostal() ?? '') . ' ' . ($facture->getClientVille() ?? ''));
                }
                if ($facture->getClientPays()) {
                    $ligne2Parts[] = $facture->getClientPays();
                }
                $ligne2 = implode(' - ', array_filter($ligne2Parts));

                $addressText = trim($ligne1 . "\n" . $ligne2);
            }

            return $addressText;
        }

        // Point relais
        $parts = [];
        if ($order->getRelayName()) {
            $parts[] = $order->getRelayName();
        }
        if ($order->getRelayAddress()) {
            $parts[] = $order->getRelayAddress();
        }

        return implode("\n", $parts);
    }
}
