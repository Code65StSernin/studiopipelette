<?php

namespace App\Controller\Caisse;

use App\Entity\Photo;
use App\Form\PhotoType;
use App\Repository\PhotoRepository;
use App\Service\ImageService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse/photos')]
class PhotoController extends AbstractController
{
    public function __construct(private ImageService $imageService)
    {
    }

    #[Route('/', name: 'app_caisse_photo_index', methods: ['GET'])]
    public function index(PhotoRepository $photoRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $photoRepository->createQueryBuilder('p')
            ->leftJoin('p.article', 'a')
            ->addSelect('a')
            ->orderBy('p.createdAt', 'DESC');

        if ($q) {
            $queryBuilder
                ->where('p.filename LIKE :q OR a.nom LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('caisse/photo/index.html.twig', [
            'photos' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_photo_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $photo = new Photo();
        $form = $this->createForm(PhotoType::class, $photo, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();

            if ($uploadedFile && $photo->getArticle()) {
                try {
                    $this->handleFileUpload($photo, $uploadedFile);
                    
                    $entityManager->persist($photo);
                    $entityManager->flush();

                    $this->addFlash('success', 'La photo a bien été ajoutée.');
                    return $this->redirectToRoute('app_caisse_photo_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload : ' . $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Veuillez sélectionner un fichier et un article.');
            }
        }

        return $this->render('caisse/photo/new.html.twig', [
            'photo' => $photo,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_photo_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Photo $photo, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PhotoType::class, $photo, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();

            if ($uploadedFile && $photo->getArticle()) {
                // Supprimer l'ancien fichier
                $this->deleteFile($photo);
                
                // Uploader le nouveau
                try {
                    $this->handleFileUpload($photo, $uploadedFile);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload : ' . $e->getMessage());
                    return $this->render('caisse/photo/edit.html.twig', [
                        'photo' => $photo,
                        'form' => $form->createView(),
                    ]);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'La photo a bien été modifiée.');
            return $this->redirectToRoute('app_caisse_photo_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/photo/edit.html.twig', [
            'photo' => $photo,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_photo_delete', methods: ['POST'])]
    public function delete(Request $request, Photo $photo, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$photo->getId(), $request->request->get('_token'))) {
            $this->deleteFile($photo);
            $entityManager->remove($photo);
            $entityManager->flush();
            $this->addFlash('success', 'La photo a bien été supprimée.');
        }

        return $this->redirectToRoute('app_caisse_photo_index', [], Response::HTTP_SEE_OTHER);
    }

    private function handleFileUpload(Photo $photo, $uploadedFile): void
    {
        $articleId = $photo->getArticle()->getId();
        $projectDir = $this->imageService->getProjectDir();
        
        $mimeType = $uploadedFile->getMimeType();
        $isVideo = strpos($mimeType, 'video/') === 0;
        
        if ($isVideo) {
            // Vérifier la taille du fichier vidéo (max 5Mo)
            $fileSize = $uploadedFile->getSize();
            $maxSize = 5 * 1024 * 1024; // 5Mo en octets
            
            if ($fileSize > $maxSize) {
                throw new \Exception('La vidéo est trop volumineuse. Taille maximale autorisée : 5Mo.');
            }
            
            // Gérer l'upload de vidéo
            $videoDir = $projectDir . '/public/assets/videos/articles/' . $articleId;
            if (!is_dir($videoDir)) {
                mkdir($videoDir, 0755, true);
            }
            
            $newFilename = uniqid() . '_' . $uploadedFile->getClientOriginalName();
            
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
    }

    private function deleteFile(Photo $photo): void
    {
        if ($photo->getArticle() && $photo->getFilename()) {
            $articleId = $photo->getArticle()->getId();
            $projectDir = $this->imageService->getProjectDir();
            
            if ($photo->getType() === 'video') { // Check type property or method isVideo if exists
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
    }
}
