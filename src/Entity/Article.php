<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Longueur limitée à 191 pour compatibilité avec certaines versions de MySQL
    // (index unique sur utf8mb4 limité à 767 octets)
    #[ORM\Column(length: 191, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'article est obligatoire")]
    #[Assert\Length(max: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sousTitre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $sousTitreContenu = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $origine = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $materiau = null;

    /**
     * @var Collection<int, ArticleCollection>
     */
    #[ORM\ManyToMany(targetEntity: ArticleCollection::class, inversedBy: 'articles')]
    #[ORM\JoinTable(
        name: 'article_collection',
        joinColumns: [
            new ORM\JoinColumn(name: 'article_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\InverseJoinColumn(name: 'collection_id', referencedColumnName: 'id')
        ]
    )]
    private Collection $collections;

    /**
     * @var Collection<int, Couleur>
     */
    #[ORM\ManyToMany(targetEntity: Couleur::class, inversedBy: 'articles')]
    #[ORM\JoinTable(name: 'article_couleur')]
    private Collection $couleurs;

    /**
     * Collection des tailles disponibles avec leurs prix et stock
     * Format: [{"taille": "S", "prix": 15.00, "barre": 20.00, "stock": 10}, {"taille": "M", "prix": 20.00, "barre": 25.00, "stock": 5}, ...]
     * La propriété "barre" (prix barré) est optionnelle
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Assert\NotBlank(message: 'Au moins une taille doit être définie')]
    private ?array $tailles = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $compositionFabrication = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $informationsLivraison = null;

    /**
     * @var Collection<int, Photo>
     */
    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: 'article', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $photos;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Categorie $categorie = null;

    #[ORM\ManyToOne(targetEntity: SousCategorie::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: true)]
    private ?SousCategorie $sousCategorie = null;

    #[ORM\Column]
    private ?bool $actif = false;

    #[ORM\Column(nullable: true)]
    private ?int $poids = null;

    public function getPoids(): ?int
    {
        return $this->poids;
    }

    public function setPoids(?int $poids): static
    {
        $this->poids = $poids;

        return $this;
    }

    #[ORM\Column(length: 20)]
    private ?string $visibilite = self::VISIBILITY_BOTH;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $fournisseur = null;

    #[ORM\Column(length: 13, unique: true, nullable: true)]
    private ?string $gencod = null;

    public const VISIBILITY_ONLINE = 'online';
    public const VISIBILITY_SHOP = 'shop';
    public const VISIBILITY_BOTH = 'both';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->actif = false;
        $this->photos = new ArrayCollection();
        $this->collections = new ArrayCollection();
        $this->couleurs = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        // La catégorie est obligatoire SAUF si visibilité est "En boutique" (shop)
        // Donc si visibilité est "online" ou "both", categorie ne doit pas être null
        if ($this->visibilite !== self::VISIBILITY_SHOP && $this->categorie === null) {
            $context->buildViolation('La catégorie est obligatoire pour les articles visibles en ligne (ou "Les deux").')
                ->atPath('categorie')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function decrementerStock(string $taille, int $quantite): bool
    {
        if (!$this->tailles || empty($this->tailles)) {
            return false;
        }

        foreach ($this->tailles as $key => $t) {
            if ($t['taille'] === $taille) {
                $stockActuel = $t['stock'] ?? 0;

                if ($stockActuel < $quantite) {
                    throw new \RuntimeException(sprintf(
                        'Stock insuffisant pour %s (%s)',
                        $this->nom,
                        $taille
                    ));
                }

                $this->tailles[$key]['stock'] = $stockActuel - $quantite;
                return true;
            }
        }

        return false;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSousTitre(): ?string
    {
        return $this->sousTitre;
    }

    public function setSousTitre(?string $sousTitre): static
    {
        $this->sousTitre = $sousTitre;

        return $this;
    }

    public function getSousTitreContenu(): ?string
    {
        return $this->sousTitreContenu;
    }

    public function setSousTitreContenu(?string $sousTitreContenu): static
    {
        $this->sousTitreContenu = $sousTitreContenu;

        return $this;
    }

    public function getOrigine(): ?string
    {
        return $this->origine;
    }

    public function setOrigine(?string $origine): static
    {
        $this->origine = $origine;

        return $this;
    }

    public function getMateriau(): ?string
    {
        return $this->materiau;
    }

    public function setMateriau(?string $materiau): static
    {
        $this->materiau = $materiau;

        return $this;
    }

    /**
     * @return Collection<int, ArticleCollection>
     */
    public function getCollections(): Collection
    {
        return $this->collections;
    }

    public function addCollection(ArticleCollection $collection): static
    {
        if (!$this->collections->contains($collection)) {
            $this->collections->add($collection);
        }

        return $this;
    }

    public function removeCollection(ArticleCollection $collection): static
    {
        $this->collections->removeElement($collection);

        return $this;
    }

    /**
     * @return Collection<int, Couleur>
     */
    public function getCouleurs(): Collection
    {
        return $this->couleurs;
    }

    public function addCouleur(Couleur $couleur): static
    {
        if (!$this->couleurs->contains($couleur)) {
            $this->couleurs->add($couleur);
        }

        return $this;
    }

    public function removeCouleur(Couleur $couleur): static
    {
        $this->couleurs->removeElement($couleur);

        return $this;
    }

    public function getTailles(): ?array
    {
        return $this->tailles;
    }

    public function setTailles(?array $tailles): static
    {
        $this->tailles = $tailles;

        return $this;
    }

    public function getCompositionFabrication(): ?string
    {
        return $this->compositionFabrication;
    }

    public function setCompositionFabrication(?string $compositionFabrication): static
    {
        $this->compositionFabrication = $compositionFabrication;

        return $this;
    }

    public function getInformationsLivraison(): ?string
    {
        return $this->informationsLivraison;
    }

    public function setInformationsLivraison(?string $informationsLivraison): static
    {
        $this->informationsLivraison = $informationsLivraison;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getVisibilite(): ?string
    {
        return $this->visibilite;
    }

    public function setVisibilite(string $visibilite): static
    {
        $this->visibilite = $visibilite;

        return $this;
    }

    public function getFournisseur(): ?User
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?User $fournisseur): static
    {
        $this->fournisseur = $fournisseur;
        return $this;
    }

    public function getGencod(): ?string
    {
        return $this->gencod;
    }

    public function setGencod(?string $gencod): static
    {
        $this->gencod = $gencod;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Retourne le prix minimum parmi toutes les tailles
     */
    public function getPrixMin(): ?float
    {
        if (!$this->tailles || empty($this->tailles)) {
            return null;
        }

        $prix = array_column($this->tailles, 'prix');
        return !empty($prix) ? min($prix) : null;
    }

    /**
     * Retourne le prix maximum parmi toutes les tailles
     */
    public function getPrixMax(): ?float
    {
        if (!$this->tailles || empty($this->tailles)) {
            return null;
        }

        $prix = array_column($this->tailles, 'prix');
        return !empty($prix) ? max($prix) : null;
    }

    /**
     * Retourne le prix pour une taille spécifique
     */
    public function getPrixParTaille(string $taille): ?float
    {
        if (!$this->tailles || empty($this->tailles)) {
            return null;
        }

        foreach ($this->tailles as $t) {
            if ($t['taille'] === $taille) {
                return $t['prix'] ?? null;
            }
        }

        return null;
    }

    /**
     * Retourne les noms des tailles disponibles
     */
    public function getTaillesDisponibles(): array
    {
        if (!$this->tailles || empty($this->tailles)) {
            return [];
        }

        return array_column($this->tailles, 'taille');
    }

    /**
     * Retourne le stock pour une taille spécifique
     */
    public function getStockParTaille(string $taille): ?int
    {
        if (!$this->tailles || empty($this->tailles)) {
            return null;
        }

        foreach ($this->tailles as $t) {
            if ($t['taille'] === $taille) {
                return $t['stock'] ?? 0;
            }
        }

        return null;
    }

    /**
     * Définit le stock pour une taille spécifique
     */
    public function setStockParTaille(string $taille, int $stock): bool
    {
        if (!$this->tailles || empty($this->tailles)) {
            return false;
        }

        foreach ($this->tailles as $key => $t) {
            if ($t['taille'] === $taille) {
                $this->tailles[$key]['stock'] = $stock;
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne le stock total de toutes les tailles
     */
    public function getStockTotal(): int
    {
        if (!$this->tailles || empty($this->tailles)) {
            return 0;
        }

        $total = 0;
        foreach ($this->tailles as $t) {
            $total += $t['stock'] ?? 0;
        }

        return $total;
    }

    /**
     * Vérifie si une taille spécifique est disponible en stock
     */
    public function isDisponible(string $taille): bool
    {
        $stock = $this->getStockParTaille($taille);
        return $stock !== null && $stock > 0;
    }

    /**
     * Vérifie si l'article a du stock pour au moins une taille
     */
    public function hasStock(): bool
    {
        return $this->getStockTotal() > 0;
    }

    /**
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setArticle($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): static
    {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getArticle() === $this) {
                $photo->setArticle(null);
            }
        }

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getSousCategorie(): ?SousCategorie
    {
        return $this->sousCategorie;
    }

    public function setSousCategorie(?SousCategorie $sousCategorie): static
    {
        $this->sousCategorie = $sousCategorie;

        return $this;
    }

    /**
     * Getter virtuel pour EasyAdmin - Retourne les tailles au format JSON
     */
    public function getTaillesJson(): string
    {
        return $this->tailles ? json_encode($this->tailles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '[]';
    }
    
    /**
     * Getter virtuel pour l'affichage dans la liste EasyAdmin
     */
    public function getTaillesResume(): string
    {
        if (!$this->tailles || !is_array($this->tailles) || empty($this->tailles)) {
            return '-';
        }
        
        $taillesInfo = [];
        foreach ($this->tailles as $taille) {
            if (!is_array($taille)) continue;
            $t = $taille['taille'] ?? '?';
            $p = isset($taille['prix']) ? number_format($taille['prix'], 2) . '€' : '?';
            $s = $taille['stock'] ?? 0;
            $taillesInfo[] = "$t ($p, stock: $s)";
        }
        
        return !empty($taillesInfo) ? implode(' | ', $taillesInfo) : '-';
    }

    /**
     * Setter virtuel pour EasyAdmin - Accepte du JSON et le transforme en tableau
     */
    public function setTaillesJson(string $json): self
    {
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Format JSON invalide : ' . json_last_error_msg());
        }
        $this->tailles = $decoded;
        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Article #' . $this->id;
    }
}
