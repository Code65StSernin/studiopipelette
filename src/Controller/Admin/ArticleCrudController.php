<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArticleCrudController extends AbstractCrudController
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Article::class;
    }
    
    public function persistEntity($entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Article && $entityInstance->getNom()) {
            $slug = strtolower($this->slugger->slug($entityInstance->getNom())->toString());
            $entityInstance->setSlug($slug);
        }
        $this->validateTaillesJson($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }
    
    public function updateEntity($entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Article && $entityInstance->getNom()) {
            $slug = strtolower($this->slugger->slug($entityInstance->getNom())->toString());
            $entityInstance->setSlug($slug);
        }
        $this->validateTaillesJson($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }
    
    private function validateTaillesJson($article): void
    {
        $tailles = $article->getTailles();
        
        if ($tailles && is_array($tailles)) {
            foreach ($tailles as $index => $taille) {
                if (!is_array($taille)) {
                    throw new \Exception("Erreur dans le champ 'Tailles' : chaque élément doit être un objet avec les propriétés 'taille', 'prix' et 'stock'.");
                }
                
                if (!isset($taille['taille']) || !isset($taille['prix']) || !isset($taille['stock'])) {
                    throw new \Exception("Erreur dans le champ 'Tailles' à l'index {$index} : les propriétés 'taille', 'prix' et 'stock' sont obligatoires.");
                }
                
                // Validation des types
                if (!is_numeric($taille['prix'])) {
                    throw new \Exception("Erreur dans le champ 'Tailles' à l'index {$index} : 'prix' doit être un nombre.");
                }
                if (!is_numeric($taille['stock'])) {
                    throw new \Exception("Erreur dans le champ 'Tailles' à l'index {$index} : 'stock' doit être un nombre entier.");
                }
            }
        }
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article')
            ->setEntityLabelInPlural('Articles')
            ->setPageTitle('index', 'Gestion des articles')
            ->setPageTitle('new', 'Créer un article')
            ->setPageTitle('edit', 'Modifier l\'article')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        // Créer l'action de duplication
        $duplicateAction = Action::new('duplicate', 'Dupliquer', 'fa fa-copy')
            ->linkToCrudAction('duplicateArticle')
            ->setCssClass('btn btn-secondary');

        return $actions
            ->add(Crud::PAGE_INDEX, $duplicateAction);
    }

    public function configureFields(string $pageName): iterable
    {
        // Page INDEX - Données essentielles uniquement
        if ($pageName === Crud::PAGE_INDEX) {
            yield IdField::new('id', 'ID');
            yield TextField::new('nom', 'Nom');
            yield AssociationField::new('categorie', 'Catégorie');
            yield AssociationField::new('sousCategorie', 'Sous-catégorie');
            yield TextField::new('taillesResume', 'Tailles');
            yield BooleanField::new('actif', 'Actif');
            yield DateTimeField::new('createdAt', 'Créé le');
            return;
        }

        // Pages NEW et EDIT - Avec onglets
        
        // ONGLET 1 : Données générales
        yield FormField::addTab('Informations générales')->setIcon('fa fa-info-circle');
        yield TextField::new('nom', 'Nom de l\'article')
            ->setHelp('Nom complet du produit')
            ->setColumns(12);
        yield AssociationField::new('categorie', 'Catégorie')
            ->setHelp('Catégorie principale de l\'article (obligatoire)')
            ->setRequired(true)
            ->setColumns(6);
        yield AssociationField::new('sousCategorie', 'Sous-catégorie')
            ->setHelp('Sous-catégorie de l\'article (optionnel)')
            ->setRequired(false)
            ->setColumns(6);
        yield TextareaField::new('description', 'Description')
            ->setHelp('Description détaillée du produit')
            ->setColumns(12);
        yield BooleanField::new('actif', 'Article actif')
            ->setHelp('Si décoché, l\'article ne sera pas visible sur le site')
            ->setColumns(6);
        
        // ONGLET 2 : Composition et caractéristiques
        yield FormField::addTab('Composition et caractéristiques')->setIcon('fa fa-flask');
        yield TextField::new('origine', 'Origine')
            ->setHelp('Ex: France, Italie, etc.')
            ->setColumns(6);
        yield TextField::new('materiau', 'Matériau')
            ->setHelp('Ex: Argent, Acier inoxydable, Laiton, etc.')
            ->setColumns(6);
        yield AssociationField::new('collections', 'Collections')
            ->setHelp('Sélectionnez une ou plusieurs collections')
            ->setColumns(6);
        yield AssociationField::new('couleurs', 'Couleurs')
            ->setHelp('Sélectionnez une ou plusieurs couleurs (facultatif)')
            ->setColumns(6);
        yield TextareaField::new('compositionFabrication', 'Composition et fabrication')
            ->setHelp('Détails sur la composition et la fabrication')
            ->setColumns(12);
        
        // ONGLET 3 : Tailles, Prix et Livraison
        yield FormField::addTab('Tailles, Prix et Livraison')->setIcon('fa fa-boxes');
        yield TextareaField::new('taillesJson', 'Tailles disponibles (JSON)')
            ->onlyOnForms()
            ->setHelp('
                <strong>Format :</strong> Tableau JSON avec pour chaque taille : <code>taille</code>, <code>prix</code>, <code>barre</code> (prix barré, optionnel), <code>stock</code><br>
                <strong>Exemple :</strong><br>
                <pre style="background:#f5f5f5;padding:10px;border-radius:4px;margin-top:5px;">[
    {"taille": "S", "prix": 15.00, "barre": 20.00, "stock": 10},
    {"taille": "M", "prix": 20.00, "barre": 25.00, "stock": 5},
    {"taille": "L", "prix": 25.00, "stock": 3}
]</pre>
                <small><strong>Note :</strong> La propriété "barre" est optionnelle. Respectez bien les guillemets doubles et les virgules. Les prix doivent être des nombres décimaux (avec un point, pas de virgule).</small>
            ')
            ->setFormTypeOption('attr', ['rows' => 8, 'style' => 'font-family: monospace;'])
            ->setColumns(12);
        yield TextareaField::new('informationsLivraison', 'Informations de livraison')
            ->setHelp('Délais et conditions de livraison')
            ->setColumns(12);
        
        // ONGLET 4 : Sous-titre et contenu additionnel
        yield FormField::addTab('Section additionnelle')->setIcon('fa fa-paragraph');
        yield TextField::new('sousTitre', 'Sous-titre')
            ->setHelp('Titre de la section additionnelle (ex: "Une Évasion Gourmande")')
            ->setColumns(12);
        yield TextareaField::new('sousTitreContenu', 'Contenu du sous-titre')
            ->setHelp('Paragraphe associé au sous-titre')
            ->setColumns(12);
        
        // ONGLET 5 : Informations système (uniquement en édition)
        if ($pageName === Crud::PAGE_EDIT) {
            yield FormField::addTab('Informations système')->setIcon('fa fa-clock');
            yield DateTimeField::new('createdAt', 'Créé le')
                ->setFormTypeOption('disabled', true)
                ->setColumns(6);
            yield DateTimeField::new('updatedAt', 'Modifié le')
                ->setFormTypeOption('disabled', true)
                ->setColumns(6);
        }
    }

    public function duplicateArticle(
        AdminUrlGenerator $adminUrlGenerator,
        EntityManagerInterface $entityManager
    ): RedirectResponse
    {
        // Récupérer l'article à dupliquer
        $article = $this->getContext()->getEntity()->getInstance();
        
        // Créer un nouvel article
        $newArticle = new Article();
        $newArticle->setNom('Copie de ' . $article->getNom());
        
        // Générer un slug unique pour éviter les collisions
        $slug = $this->slugger->slug($newArticle->getNom())->lower() . '-' . uniqid();
        $newArticle->setSlug($slug);

        $newArticle->setDescription($article->getDescription());
        $newArticle->setCategorie($article->getCategorie());
        $newArticle->setSousCategorie($article->getSousCategorie());
        $newArticle->setOrigine($article->getOrigine());
        $newArticle->setMateriau($article->getMateriau());
        $newArticle->setTailles($article->getTailles());
        $newArticle->setCompositionFabrication($article->getCompositionFabrication());
        $newArticle->setInformationsLivraison($article->getInformationsLivraison());
        $newArticle->setSousTitre($article->getSousTitre());
        $newArticle->setSousTitreContenu($article->getSousTitreContenu());
        $newArticle->setActif(false); // Par défaut inactif
        
        // Copier les relations ManyToMany
        foreach ($article->getCollections() as $collection) {
            $newArticle->addCollection($collection);
        }
        foreach ($article->getCouleurs() as $couleur) {
            $newArticle->addCouleur($couleur);
        }
        
        // Note: Les photos ne sont PAS dupliquées (nécessiterait de copier les fichiers physiques)
        
        $entityManager->persist($newArticle);
        $entityManager->flush();
        
        $this->addFlash('success', 'Article dupliqué avec succès ! (Les photos ne sont pas dupliquées)');
        
        // Rediriger vers la page d'édition du nouvel article
        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::EDIT)
            ->setEntityId($newArticle->getId())
            ->generateUrl();
        
        return new RedirectResponse($url);
    }
}
