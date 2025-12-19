<?php

namespace App\Controller\Admin;

use App\Entity\Photo;
use App\Entity\Article;
use App\Service\ImageService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PhotoCrudController extends AbstractCrudController
{
    public function __construct(private ImageService $imageService)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Photo::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Photo')
            ->setEntityLabelInPlural('Photos')
            ->setPageTitle('index', 'Gestion des photos')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        
        yield AssociationField::new('article', 'Article')
            ->setRequired(true)
            ->setHelp('Sélectionnez l\'article auquel associer cette photo');
        
        if ($pageName === Crud::PAGE_INDEX) {
            yield TextField::new('filename', 'Fichier');
            yield ImageField::new('filename', 'Aperçu')
                ->setBasePath('/') // Racine web
                ->formatValue(function ($value, $entity) {
                    if (!$entity->getArticle() || !$value) return null;
                    // Retourner le chemin complet depuis la racine web
                    return 'assets/img/articles/thumbnails/' . $entity->getArticle()->getId() . '/' . $value;
                });
            yield DateTimeField::new('createdAt', 'Ajoutée le');
        } else {
            // Pour la création/édition
            if ($pageName === Crud::PAGE_NEW) {
                yield ImageField::new('filename', 'Photo')
                    ->setUploadDir('public/assets/img/articles')
                    ->setBasePath('assets/img/articles')
                    ->setUploadedFileNamePattern('[randomhash].[extension]')
                    ->setRequired(true)
                    ->setHelp('Formats acceptés: JPG, PNG, GIF, WEBP. L\'image sera automatiquement redimensionnée en 800x800 avec création d\'une miniature 250x250');
            } else {
                // En édition, afficher l'image existante
                yield ImageField::new('filename', 'Photo actuelle')
                    ->setBasePath('/') // Racine web
                    ->formatValue(function ($value, $entity) {
                        if (!$entity->getArticle() || !$value) return null;
                        // Retourner le chemin complet depuis la racine web
                        return 'assets/img/articles/thumbnails/' . $entity->getArticle()->getId() . '/' . $value;
                    })
                    ->setHelp('Image actuellement enregistrée (miniature). Pour changer l\'image, supprimez cette photo et créez-en une nouvelle.');
            }
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Photo $photo */
        $photo = $entityInstance;
        
        if ($photo->getArticle() && $photo->getFilename()) {
            $articleId = $photo->getArticle()->getId();
            
            // Le fichier a été uploadé par EasyAdmin dans public/assets/img/articles
            $projectDir = $this->imageService->getProjectDir();
            $tempPath = $projectDir . '/public/assets/img/articles/' . $photo->getFilename();
            
            if (file_exists($tempPath)) {
                try {
                    // Créer un UploadedFile depuis le fichier temporaire
                    $uploadedFile = new UploadedFile(
                        $tempPath,
                        basename($tempPath),
                        mime_content_type($tempPath),
                        null,
                        true // Mode test pour accepter le fichier déjà sur le disque
                    );
                    
                    // Uploader et redimensionner avec ImageService
                    $newFilename = $this->imageService->uploadImage($uploadedFile, $articleId);
                    
                    // Mettre à jour le nom du fichier
                    $photo->setFilename($newFilename);
                    
                    // Supprimer le fichier temporaire
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }
                } catch (\Exception $e) {
                    // Log l'erreur (pour débug)
                    error_log('Erreur upload photo: ' . $e->getMessage());
                    throw $e;
                }
            }
        }
        
        parent::persistEntity($entityManager, $photo);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Photo $photo */
        $photo = $entityInstance;
        
        // Supprimer les fichiers physiques avant de supprimer l'entité
        if ($photo->getArticle() && $photo->getFilename()) {
            $this->imageService->deleteImage($photo->getFilename(), $photo->getArticle()->getId());
        }
        
        parent::deleteEntity($entityManager, $photo);
    }
}

