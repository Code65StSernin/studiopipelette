<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageService
{
    private string $projectDir;
    private string $uploadDir;
    private string $thumbnailDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->uploadDir = $projectDir . '/public/assets/img/articles';
        $this->thumbnailDir = $projectDir . '/public/assets/img/articles/thumbnails';
        
        // Créer les répertoires s'ils n'existent pas
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
        if (!is_dir($this->thumbnailDir)) {
            mkdir($this->thumbnailDir, 0777, true);
        }
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    /**
     * Upload et redimensionne une image
     * @param UploadedFile $file
     * @param int $articleId ID de l'article
     * @return string Nom du fichier uploadé
     */
    public function uploadImage(UploadedFile $file, int $articleId): string
    {
        // Créer les répertoires pour cet article s'ils n'existent pas
        $articleDir = $this->uploadDir . '/' . $articleId;
        $articleThumbnailDir = $this->thumbnailDir . '/' . $articleId;
        
        if (!is_dir($articleDir)) {
            mkdir($articleDir, 0777, true);
        }
        if (!is_dir($articleThumbnailDir)) {
            mkdir($articleThumbnailDir, 0777, true);
        }
        
        // Générer un nom unique
        $filename = uniqid() . '_' . time() . '.' . $file->guessExtension();
        
        // Charger l'image source
        $sourceImage = $this->loadImage($file->getPathname(), $file->getMimeType());
        
        if (!$sourceImage) {
            throw new \Exception('Format d\'image non supporté');
        }
        
        // Redimensionner à 800x800
        $resizedImage = $this->resizeImage($sourceImage, 800, 800);
        
        // Sauvegarder l'image redimensionnée
        $this->saveImage($resizedImage, $articleDir . '/' . $filename);
        
        // Créer la miniature 250x250
        $thumbnail = $this->resizeImage($sourceImage, 250, 250);
        $this->saveImage($thumbnail, $articleThumbnailDir . '/' . $filename);
        
        // Libérer la mémoire
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        imagedestroy($thumbnail);
        
        return $filename;
    }

    /**
     * Charge une image depuis un fichier
     */
    private function loadImage(string $path, ?string $mimeType): \GdImage|false
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };
    }

    /**
     * Redimensionne une image en carré parfait avec crop au centre
     * L'image est redimensionnée puis croppée pour obtenir exactement maxWidth x maxHeight
     */
    private function resizeImage(\GdImage $source, int $maxWidth, int $maxHeight): \GdImage
    {
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        
        // Calculer le ratio pour que le côté le PLUS PETIT fasse la taille cible
        // max() garantit que l'image remplit complètement les dimensions (on crop ensuite)
        $ratio = max($maxWidth / $srcWidth, $maxHeight / $srcHeight);
        $resizedWidth = (int)($srcWidth * $ratio);
        $resizedHeight = (int)($srcHeight * $ratio);
        
        // Créer une image temporaire redimensionnée
        $temp = imagecreatetruecolor($resizedWidth, $resizedHeight);
        
        // Préserver la transparence pour PNG
        imagealphablending($temp, false);
        imagesavealpha($temp, true);
        $transparent = imagecolorallocatealpha($temp, 255, 255, 255, 127);
        imagefilledrectangle($temp, 0, 0, $resizedWidth, $resizedHeight, $transparent);
        
        // Redimensionner
        imagecopyresampled(
            $temp, $source,
            0, 0, 0, 0,
            $resizedWidth, $resizedHeight,
            $srcWidth, $srcHeight
        );
        
        // Créer l'image finale carrée
        $final = imagecreatetruecolor($maxWidth, $maxHeight);
        
        // Préserver la transparence pour PNG
        imagealphablending($final, false);
        imagesavealpha($final, true);
        $transparent2 = imagecolorallocatealpha($final, 255, 255, 255, 127);
        imagefilledrectangle($final, 0, 0, $maxWidth, $maxHeight, $transparent2);
        
        // Calculer la position pour centrer le crop
        $cropX = (int)(($resizedWidth - $maxWidth) / 2);
        $cropY = (int)(($resizedHeight - $maxHeight) / 2);
        
        // Copier la partie centrale
        imagecopy(
            $final, $temp,
            0, 0,
            $cropX, $cropY,
            $maxWidth, $maxHeight
        );
        
        // Libérer la mémoire de l'image temporaire
        imagedestroy($temp);
        
        return $final;
    }

    /**
     * Sauvegarde une image
     */
    private function saveImage(\GdImage $image, string $path): void
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        match ($extension) {
            'jpg', 'jpeg' => imagejpeg($image, $path, 90),
            'png' => imagepng($image, $path, 9),
            'gif' => imagegif($image, $path),
            'webp' => imagewebp($image, $path, 90),
            default => imagejpeg($image, $path, 90),
        };
    }

    /**
     * Supprime une image et sa miniature
     * @param string $filename
     * @param int $articleId ID de l'article
     */
    public function deleteImage(string $filename, int $articleId): bool
    {
        $deleted = true;
        
        $imagePath = $this->uploadDir . '/' . $articleId . '/' . $filename;
        if (file_exists($imagePath)) {
            $deleted = unlink($imagePath) && $deleted;
        }
        
        $thumbnailPath = $this->thumbnailDir . '/' . $articleId . '/' . $filename;
        if (file_exists($thumbnailPath)) {
            $deleted = unlink($thumbnailPath) && $deleted;
        }
        
        // Supprimer les répertoires s'ils sont vides
        $articleDir = $this->uploadDir . '/' . $articleId;
        $articleThumbnailDir = $this->thumbnailDir . '/' . $articleId;
        
        if (is_dir($articleDir) && count(scandir($articleDir)) === 2) { // . et ..
            rmdir($articleDir);
        }
        if (is_dir($articleThumbnailDir) && count(scandir($articleThumbnailDir)) === 2) {
            rmdir($articleThumbnailDir);
        }
        
        return $deleted;
    }

    /**
     * Retourne le chemin public d'une image
     * @param string $filename
     * @param int $articleId ID de l'article
     */
    public function getImagePath(string $filename, int $articleId): string
    {
        return '/assets/img/articles/' . $articleId . '/' . $filename;
    }

    /**
     * Retourne le chemin public d'une miniature
     * @param string $filename
     * @param int $articleId ID de l'article
     */
    public function getThumbnailPath(string $filename, int $articleId): string
    {
        return '/assets/img/articles/thumbnails/' . $articleId . '/' . $filename;
    }
}

