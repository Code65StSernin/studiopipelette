<?php

namespace App\Service;

use App\Entity\Societe;
use App\Repository\SocieteRepository;

/**
 * Service centralisant les paramètres applicatifs stockés dans l'entité Societe.
 *
 * Remarque importante :
 * - Ce service ne remplace PAS la configuration bas niveau (DSN de base de données, MAILER_DSN, etc.)
 *   qui reste gérée par les fichiers de config / variables d'environnement.
 * - Il sert à éviter les valeurs en dur dans le code pour les paramètres fonctionnels
 *   (clé Stripe, identifiants Mondial Relay, coordonnées de la société, etc.).
 */
class SocieteConfig
{
    private ?Societe $societe;

    public function __construct(private SocieteRepository $societeRepository)
    {
        $this->societe = $this->societeRepository->findOneBy([]); // on prend le premier enregistrement
    }

    private function get(): ?Societe
    {
        return $this->societe;
    }

    // Infos société
    public function getNom(): ?string         { return $this->get()?->getNom(); }
    public function getAdresse(): ?string     { return $this->get()?->getAdresse(); }
    public function getCodePostal(): ?string  { return $this->get()?->getCodePostal(); }
    public function getVille(): ?string       { return $this->get()?->getVille(); }
    public function getTelephone(): ?string   { return $this->get()?->getTelephone(); }
    public function getEmail(): ?string       { return $this->get()?->getEmail(); }
    public function getSiret(): ?string       { return $this->get()?->getSiret(); }
    public function getCodeNaf(): ?string     { return $this->get()?->getCodeNaf(); }

    // Mondial Relay
    public function getMondialRelayLogin(): ?string       { return $this->get()?->getMondialRelayLogin(); }
    public function getMondialRelayPassword(): ?string    { return $this->get()?->getMondialRelayPassword(); }
    public function getMondialRelayCustomerId(): ?string  { return $this->get()?->getMondialRelayCustomerId(); }
    public function getMondialRelayBrand(): ?string       { return $this->get()?->getMondialRelayBrand(); }

    // Stripe
    public function getStripePublicKey(): ?string { return $this->get()?->getStripePublicKey(); }
    public function getStripeSecretKey(): ?string { return $this->get()?->getStripeSecretKey(); }
    public function getStripeWebhookSecret(): ?string { return $this->get()?->getStripeWebhookSecret(); }

    // Frais Bancaires
    public function getStripeFraisPourcentage(): ?float { return $this->get()?->getStripeFraisPourcentage(); }
    public function getStripeFraisFixe(): ?float { return $this->get()?->getStripeFraisFixe(); }
    public function getTpeFraisPourcentage(): ?float { return $this->get()?->getTpeFraisPourcentage(); }

    // SMTP
    public function getSmtpHost(): ?string       { return $this->get()?->getSmtpHost(); }
    public function getSmtpPort(): ?int          { return $this->get()?->getSmtpPort(); }
    public function getSmtpUser(): ?string       { return $this->get()?->getSmtpUser(); }
    public function getSmtpPassword(): ?string   { return $this->get()?->getSmtpPassword(); }
    public function getSmtpFromEmail(): ?string  { return $this->get()?->getSmtpFromEmail(); }

    // Charges sociales et fiscales
    public function getPourcentageUrssaf(): ?float { return $this->get()?->getPourcentageUrssaf(); }
    public function getPourcentageUrssafBic(): ?float { return $this->get()?->getPourcentageUrssafBic(); }
    public function getPourcentageUrssafBnc(): ?float { return $this->get()?->getPourcentageUrssafBnc(); }
    public function getPourcentageCpf(): ?float    { return $this->get()?->getPourcentageCpf(); }
    public function getPourcentageIr(): ?float     { return $this->get()?->getPourcentageIr(); }

    // Etalement site
    public function getTotalSite(): ?float         { return $this->get()?->getTotalSite(); }
    public function getPourcentageMensuel(): ?float { return $this->get()?->getPourcentageMensuel(); }

    // Configuration applicative
    public function getAdminPin(): string
    {
        $pin = $this->get()?->getAdminPin();
        $pin = is_string($pin) ? trim($pin) : '';
        return $pin !== '' ? $pin : '1234';
    }

    // Programme de fidélité
    public function isFideliteActive(): bool      { return (bool) $this->get()?->isFideliteActive(); }
    public function getFideliteMode(): string     { return (string) $this->get()?->getFideliteMode(); }
    public function getFideliteVisitsX(): ?int    { return $this->get()?->getFideliteVisitsX(); }
    public function getFideliteVisitsY(): ?float  { return $this->get()?->getFideliteVisitsY(); }
    public function getFidelitePointsX(): ?float  { return $this->get()?->getFidelitePointsX(); }
    public function getFidelitePointsY(): ?float  { return $this->get()?->getFidelitePointsY(); }
    public function getFidelitePointsZ(): ?float  { return $this->get()?->getFidelitePointsZ(); }
}
