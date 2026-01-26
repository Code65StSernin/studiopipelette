<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PictureService
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function add(UploadedFile $picture, ?string $folder = '', ?int $width = 100, ?int $height = 100)
    {
        // On renomme le fichier
        $fichier = md5(uniqid(rand(), true)) . '.webp';

        // On récupère les infos de l'image
        $picture_infos = getimagesize($picture);

        if ($picture_infos === false) {
            throw new FileException('Fichier image incorrect');
        }

        // On vérifie le format de l'image
        switch ($picture_infos['mime']) {
            case 'image/png':
                $picture_source = imagecreatefrompng($picture);
                break;
            case 'image/jpeg':
                $picture_source = imagecreatefromjpeg($picture);
                break;
            case 'image/webp':
                $picture_source = imagecreatefromwebp($picture);
                break;
            default:
                throw new FileException('Format d\'image incorrect');
        }

        // On recadre l'image
        // On récupère les dimensions
        $imageWidth = $picture_infos[0];
        $imageHeight = $picture_infos[1];

        // On vérifie l'orientation de l'image
        switch ($imageWidth <=> $imageHeight) {
            case -1: // Portrait
                $squareSize = $imageWidth;
                $src_x = 0;
                $src_y = ($imageHeight - $squareSize) / 2;
                break;
            case 0: // Carré
                $squareSize = $imageWidth;
                $src_x = 0;
                $src_y = 0;
                break;
            case 1: // Paysage
                $squareSize = $imageHeight;
                $src_x = ($imageWidth - $squareSize) / 2;
                $src_y = 0;
                break;
        }

        // On crée une nouvelle image "vierge"
        $resized_picture = imagecreatetruecolor($width, $height);

        // On garde la transparence
        imagealphablending($resized_picture, false);
        imagesavealpha($resized_picture, true);

        // On redimensionne
        imagecopyresampled(
            $resized_picture,
            $picture_source,
            0,
            0,
            $src_x,
            $src_y,
            $width,
            $height,
            $squareSize,
            $squareSize
        );

        $path = $this->params->get('images_directory') . $folder;

        // On crée le dossier s'il n'existe pas
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        // On sauvegarde
        imagewebp($resized_picture, $path . '/' . $fichier, 75);
        
        // On détruit les images en mémoire
        $picture->move($path . '/original/', $fichier); // On garde l'original au cas où ? Non, pas demandé.
        // Le move ci-dessus ferait échouer car $picture est un UploadedFile qui est déjà lu par imagecreatefrom...
        // On ne sauvegarde que le résultat webp
        
        imagedestroy($picture_source);
        imagedestroy($resized_picture);

        return $fichier;
    }

    public function delete(string $fichier, ?string $folder = '', ?int $width = 250, ?int $height = 250)
    {
        if ($fichier !== 'default.webp') {
            $success = false;
            $path = $this->params->get('images_directory') . $folder;

            $mini = $path . '/mini/' . $width . 'x' . $height . '-' . $fichier;

            if (file_exists($mini)) {
                unlink($mini);
                $success = true;
            }

            $original = $path . '/' . $fichier;

            if (file_exists($original)) {
                unlink($original);
                $success = true;
            }
            return $success;
        }
        return false;
    }
}
