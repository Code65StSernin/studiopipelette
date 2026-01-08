<?php

namespace App\Controller\Admin;

use App\Entity\Photo;
use App\Entity\Article;
use App\Service\ImageService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
        
        yield ChoiceField::new('type', 'Type')
            ->setChoices([
                'Image' => 'image',
                'Vidéo' => 'video'
            ])
            ->setRequired(true)
            ->setHelp('Sélectionnez le type de média : image ou vidéo');
        
        if ($pageName === Crud::PAGE_INDEX) {
            yield TextField::new('filename', 'Fichier');
            yield ImageField::new('filename', 'Aperçu')
                ->setBasePath('/') // Racine web
                ->formatValue(function ($value, $entity) {
                    if (!$entity->getArticle() || !$value) return null;
                    if ($entity->isVideo()) {
                        return null; // Pas d'aperçu pour les vidéos dans la liste
                    }
                    // Retourner le chemin complet depuis la racine web
                    return 'assets/img/articles/thumbnails/' . $entity->getArticle()->getId() . '/' . $value;
                });
            yield DateTimeField::new('createdAt', 'Ajoutée le');
        } else {
            // Pour la création/édition
            if ($pageName === Crud::PAGE_NEW) {
                // Utiliser Field avec FileType pour accepter images et vidéos
                yield Field::new('filename', 'Fichier (Image ou Vidéo)')
                    ->setFormType(FileType::class)
                    ->setRequired(true)
                    ->setHelp('Images: JPG, PNG, GIF, WEBP (redimensionnées automatiquement). Vidéos: MP4, WEBM, OGG (max 5Mo, pas de redimensionnement).')
                    ->setFormTypeOptions([
                        'attr' => [
                            'accept' => 'image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/ogg',
                        ],
                        'constraints' => [
                            new \Symfony\Component\Validator\Constraints\File([
                                'maxSize' => '5M',
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/png',
                                    'image/gif',
                                    'image/webp',
                                    'video/mp4',
                                    'video/webm',
                                    'video/ogg',
                                ],
                                'mimeTypesMessage' => 'Veuillez uploader une image (JPG, PNG, GIF, WEBP) ou une vidéo (MP4, WEBM, OGG) de maximum 5Mo.',
                            ])
                        ],
                    ])
                    ->onlyOnForms();
            } else {
                // En édition, afficher le média existant
                yield ImageField::new('filename', 'Média actuel')
                    ->setBasePath('/') // Racine web
                    ->formatValue(function ($value, $entity) {
                        if (!$entity->getArticle() || !$value) return null;
                        if ($entity->isVideo()) {
                            return 'assets/videos/articles/' . $entity->getArticle()->getId() . '/' . $value;
                        }
                        // Retourner le chemin complet depuis la racine web
                        return 'assets/img/articles/thumbnails/' . $entity->getArticle()->getId() . '/' . $value;
                    })
                    ->setHelp('Média actuellement enregistré. Pour changer, supprimez ce média et créez-en un nouveau.');
            }
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Photo $photo */
        $photo = $entityInstance;
        
        // Récupérer le fichier uploadé depuis la requête
        $request = $this->getContext()->getRequest();
        $uploadedFile = null;
        
        // Chercher le fichier dans la structure de la requête EasyAdmin
        $allFiles = $request->files->all();
        foreach ($allFiles as $formName => $formData) {
            if (is_array($formData) && isset($formData['filename']) && $formData['filename'] instanceof UploadedFile) {
                $uploadedFile = $formData['filename'];
                break;
            }
        }
        
        if ($uploadedFile instanceof UploadedFile && $photo->getArticle()) {
            $articleId = $photo->getArticle()->getId();
            $projectDir = $this->imageService->getProjectDir();
            
            try {
                $mimeType = $uploadedFile->getMimeType();
                $isVideo = strpos($mimeType, 'video/') === 0;
                
                if ($isVideo) {
                    // Vérifier la taille du fichier vidéo (max 5Mo)
                    $fileSize = $uploadedFile->getSize();
                    $maxSize = 5 * 1024 * 1024; // 5Mo en octets
                    
                    if ($fileSize > $maxSize) {
                        throw new \Exception('La vidéo est trop volumineuse. Taille maximale autorisée : 5Mo. Taille actuelle : ' . round($fileSize / 1024 / 1024, 2) . 'Mo.');
                    }
                    
                    // Gérer l'upload de vidéo
                    $videoDir = $projectDir . '/public/assets/videos/articles/' . $articleId;
                    if (!is_dir($videoDir)) {
                        mkdir($videoDir, 0755, true);
                    }
                    
                    $newFilename = uniqid() . '_' . $uploadedFile->getClientOriginalName();
                    $destination = $videoDir . '/' . $newFilename;
                    
                    // Déplacer le fichier vidéo
                    $uploadedFile->move($videoDir, $newFilename);
                    
                    // Mettre à jour le type et le nom du fichier
                    $photo->setType('video');
                    $photo->setFilename($newFilename);
                } else {
                    // Uploader et redimensionner l'image avec ImageService
                    $newFilename = $this->imageService->uploadImage($uploadedFile, $articleId);
                    $photo->setType('image');
                    $photo->setFilename($newFilename);
                }
            } catch (\Exception $e) {
                // Log l'erreur (pour débug)
                error_log('Erreur upload média: ' . $e->getMessage());
                throw $e;
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
            $articleId = $photo->getArticle()->getId();
            $projectDir = $this->imageService->getProjectDir();
            
            if ($photo->isVideo()) {
                // Supprimer la vidéo
                $videoPath = $projectDir . '/public/assets/videos/articles/' . $articleId . '/' . $photo->getFilename();
                if (file_exists($videoPath)) {
                    unlink($videoPath);
                }
            } else {
                // Supprimer l'image (et sa miniature)
                $this->imageService->deleteImage($photo->getFilename(), $articleId);
            }
        }
        
        parent::deleteEntity($entityManager, $photo);
    }
}

