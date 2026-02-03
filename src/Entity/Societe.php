<?php

namespace App\Entity;

use App\Repository\SocieteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SocieteRepository::class)]
class Societe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $codePostal = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $codeNaf = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lienFacebook = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lienInstagram = null;

    // Mondial Relay
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mondialRelayLogin = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mondialRelayPassword = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mondialRelayCustomerId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mondialRelayBrand = null;

    // Stripe
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePublicKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSecretKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeWebhookSecret = null;

    // Frais Bancaires
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $stripeFraisPourcentage = 1.5;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $stripeFraisFixe = 0.25;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $tpeFraisPourcentage = 1.75;

    // Base de données
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $dbHost = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $dbName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $dbUser = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dbPassword = null;

    // SMTP / Email
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $smtpUser = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $smtpPassword = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $smtpHost = null;

    #[ORM\Column(nullable: true)]
    private ?int $smtpPort = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $smtpFromEmail = null;

    // Charges sociales et fiscales
    /**
     * @deprecated Utiliser pourcentageUrssafBic et pourcentageUrssafBnc
     */
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pourcentageUrssaf = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pourcentageUrssafBic = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pourcentageUrssafBnc = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pourcentageCpf = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pourcentageIr = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $totalSite = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $pourcentageMensuel = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $adminPin = null;

    // Fidélité
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $fideliteActive = false;

    #[ORM\Column(length: 20, options: ['default' => 'visits'])]
    private string $fideliteMode = 'visits'; // 'visits' or 'points'

    #[ORM\Column(length: 20, options: ['default' => 'both'])]
    private string $fideliteScope = 'both'; // 'caisse', 'boutique', 'both'

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $fideliteVisitsX = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $fideliteVisitsY = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $fidelitePointsX = null; // Points par Euro

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $fidelitePointsY = null; // Seuil Points

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $fidelitePointsZ = null; // Récompense Euro

    // Options d'expédition
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private ?bool $enableMondialRelay = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $enableLettreSuivie = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): self
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): self
    {
        $this->siret = $siret;

        return $this;
    }

    public function getCodeNaf(): ?string
    {
        return $this->codeNaf;
    }

    public function setCodeNaf(?string $codeNaf): self
    {
        $this->codeNaf = $codeNaf;

        return $this;
    }

    public function getLienFacebook(): ?string
    {
        return $this->lienFacebook;
    }

    public function setLienFacebook(?string $lienFacebook): self
    {
        $this->lienFacebook = $lienFacebook;

        return $this;
    }

    public function getLienInstagram(): ?string
    {
        return $this->lienInstagram;
    }

    public function setLienInstagram(?string $lienInstagram): self
    {
        $this->lienInstagram = $lienInstagram;

        return $this;
    }

    public function getMondialRelayLogin(): ?string
    {
        return $this->mondialRelayLogin;
    }

    public function setMondialRelayLogin(?string $mondialRelayLogin): self
    {
        $this->mondialRelayLogin = $mondialRelayLogin;

        return $this;
    }

    public function getMondialRelayPassword(): ?string
    {
        return $this->mondialRelayPassword;
    }

    public function setMondialRelayPassword(?string $mondialRelayPassword): self
    {
        $this->mondialRelayPassword = $mondialRelayPassword;

        return $this;
    }

    public function getMondialRelayCustomerId(): ?string
    {
        return $this->mondialRelayCustomerId;
    }

    public function setMondialRelayCustomerId(?string $mondialRelayCustomerId): self
    {
        $this->mondialRelayCustomerId = $mondialRelayCustomerId;

        return $this;
    }

    public function getMondialRelayBrand(): ?string
    {
        return $this->mondialRelayBrand;
    }

    public function setMondialRelayBrand(?string $mondialRelayBrand): self
    {
        $this->mondialRelayBrand = $mondialRelayBrand;

        return $this;
    }

    public function getStripePublicKey(): ?string
    {
        return $this->stripePublicKey;
    }

    public function setStripePublicKey(?string $stripePublicKey): self
    {
        $this->stripePublicKey = $stripePublicKey;

        return $this;
    }

    public function getStripeSecretKey(): ?string
    {
        return $this->stripeSecretKey;
    }

    public function setStripeSecretKey(?string $stripeSecretKey): self
    {
        $this->stripeSecretKey = $stripeSecretKey;

        return $this;
    }

    public function getStripeWebhookSecret(): ?string
    {
        return $this->stripeWebhookSecret;
    }

    public function setStripeWebhookSecret(?string $stripeWebhookSecret): self
    {
        $this->stripeWebhookSecret = $stripeWebhookSecret;

        return $this;
    }

    public function getDbHost(): ?string
    {
        return $this->dbHost;
    }

    public function setDbHost(?string $dbHost): self
    {
        $this->dbHost = $dbHost;

        return $this;
    }

    public function getDbName(): ?string
    {
        return $this->dbName;
    }

    public function setDbName(?string $dbName): self
    {
        $this->dbName = $dbName;

        return $this;
    }

    public function getDbUser(): ?string
    {
        return $this->dbUser;
    }

    public function setDbUser(?string $dbUser): self
    {
        $this->dbUser = $dbUser;

        return $this;
    }

    public function getDbPassword(): ?string
    {
        return $this->dbPassword;
    }

    public function setDbPassword(?string $dbPassword): self
    {
        $this->dbPassword = $dbPassword;

        return $this;
    }

    public function getSmtpUser(): ?string
    {
        return $this->smtpUser;
    }

    public function setSmtpUser(?string $smtpUser): self
    {
        $this->smtpUser = $smtpUser;

        return $this;
    }

    public function getSmtpPassword(): ?string
    {
        return $this->smtpPassword;
    }

    public function setSmtpPassword(?string $smtpPassword): self
    {
        $this->smtpPassword = $smtpPassword;

        return $this;
    }

    public function getSmtpHost(): ?string
    {
        return $this->smtpHost;
    }

    public function setSmtpHost(?string $smtpHost): self
    {
        $this->smtpHost = $smtpHost;

        return $this;
    }

    public function getSmtpPort(): ?int
    {
        return $this->smtpPort;
    }

    public function setSmtpPort(?int $smtpPort): self
    {
        $this->smtpPort = $smtpPort;

        return $this;
    }

    public function getSmtpFromEmail(): ?string
    {
        return $this->smtpFromEmail;
    }

    public function setSmtpFromEmail(?string $smtpFromEmail): self
    {
        $this->smtpFromEmail = $smtpFromEmail;

        return $this;
    }

    public function getPourcentageUrssaf(): ?float
    {
        return $this->pourcentageUrssaf;
    }

    public function setPourcentageUrssaf(?float $pourcentageUrssaf): self
    {
        $this->pourcentageUrssaf = $pourcentageUrssaf;

        return $this;
    }

    public function getPourcentageUrssafBic(): ?float
    {
        return $this->pourcentageUrssafBic;
    }

    public function setPourcentageUrssafBic(?float $pourcentageUrssafBic): self
    {
        $this->pourcentageUrssafBic = $pourcentageUrssafBic;

        return $this;
    }

    public function getPourcentageUrssafBnc(): ?float
    {
        return $this->pourcentageUrssafBnc;
    }

    public function setPourcentageUrssafBnc(?float $pourcentageUrssafBnc): self
    {
        $this->pourcentageUrssafBnc = $pourcentageUrssafBnc;

        return $this;
    }

    public function getPourcentageCpf(): ?float
    {
        return $this->pourcentageCpf;
    }

    public function setPourcentageCpf(?float $pourcentageCpf): self
    {
        $this->pourcentageCpf = $pourcentageCpf;

        return $this;
    }

    public function getPourcentageIr(): ?float
    {
        return $this->pourcentageIr;
    }

    public function setPourcentageIr(?float $pourcentageIr): self
    {
        $this->pourcentageIr = $pourcentageIr;

        return $this;
    }

    public function getTotalSite(): ?float
    {
        return $this->totalSite;
    }

    public function setTotalSite(?float $totalSite): self
    {
        $this->totalSite = $totalSite;

        return $this;
    }

    public function getPourcentageMensuel(): ?float
    {
        return $this->pourcentageMensuel;
    }

    public function setPourcentageMensuel(?float $pourcentageMensuel): self
    {
        $this->pourcentageMensuel = $pourcentageMensuel;

        return $this;
    }

    public function getAdminPin(): ?string
    {
        return $this->adminPin;
    }

    public function setAdminPin(?string $adminPin): self
    {
        $this->adminPin = $adminPin;

        return $this;
    }

    public function isFideliteActive(): bool
    {
        return $this->fideliteActive;
    }

    public function setFideliteActive(bool $fideliteActive): self
    {
        $this->fideliteActive = $fideliteActive;
        return $this;
    }

    public function getFideliteMode(): string
    {
        return $this->fideliteMode;
    }

    public function setFideliteMode(string $fideliteMode): self
    {
        $this->fideliteMode = $fideliteMode;
        return $this;
    }

    public function getFideliteScope(): string
    {
        return $this->fideliteScope;
    }

    public function setFideliteScope(string $fideliteScope): self
    {
        $this->fideliteScope = $fideliteScope;
        return $this;
    }

    public function getFideliteVisitsX(): ?int
    {
        return $this->fideliteVisitsX;
    }

    public function setFideliteVisitsX(?int $fideliteVisitsX): self
    {
        $this->fideliteVisitsX = $fideliteVisitsX;
        return $this;
    }

    public function getFideliteVisitsY(): ?float
    {
        return $this->fideliteVisitsY;
    }

    public function setFideliteVisitsY(?float $fideliteVisitsY): self
    {
        $this->fideliteVisitsY = $fideliteVisitsY;
        return $this;
    }

    public function getFidelitePointsX(): ?float
    {
        return $this->fidelitePointsX;
    }

    public function setFidelitePointsX(?float $fidelitePointsX): self
    {
        $this->fidelitePointsX = $fidelitePointsX;
        return $this;
    }

    public function getFidelitePointsY(): ?float
    {
        return $this->fidelitePointsY;
    }

    public function setFidelitePointsY(?float $fidelitePointsY): self
    {
        $this->fidelitePointsY = $fidelitePointsY;
        return $this;
    }

    public function getFidelitePointsZ(): ?float
    {
        return $this->fidelitePointsZ;
    }

    public function setFidelitePointsZ(?float $fidelitePointsZ): self
    {
        $this->fidelitePointsZ = $fidelitePointsZ;
        return $this;
    }

    public function getStripeFraisPourcentage(): ?float
    {
        return $this->stripeFraisPourcentage;
    }

    public function setStripeFraisPourcentage(?float $stripeFraisPourcentage): self
    {
        $this->stripeFraisPourcentage = $stripeFraisPourcentage;
        return $this;
    }

    public function getStripeFraisFixe(): ?float
    {
        return $this->stripeFraisFixe;
    }

    public function setStripeFraisFixe(?float $stripeFraisFixe): self
    {
        $this->stripeFraisFixe = $stripeFraisFixe;
        return $this;
    }

    public function getTpeFraisPourcentage(): ?float
    {
        return $this->tpeFraisPourcentage;
    }

    public function setTpeFraisPourcentage(?float $tpeFraisPourcentage): self
    {
        $this->tpeFraisPourcentage = $tpeFraisPourcentage;
        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Société #' . $this->id;
    }

    public function isEnableMondialRelay(): ?bool
    {
        return $this->enableMondialRelay;
    }

    public function setEnableMondialRelay(bool $enableMondialRelay): self
    {
        $this->enableMondialRelay = $enableMondialRelay;
        return $this;
    }

    public function isEnableLettreSuivie(): ?bool
    {
        return $this->enableLettreSuivie;
    }

    public function setEnableLettreSuivie(bool $enableLettreSuivie): self
    {
        $this->enableLettreSuivie = $enableLettreSuivie;
        return $this;
    }
}

