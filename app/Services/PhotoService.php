<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Service de traitement et d'optimisation des photos d'identité
 * Format portrait 3:4 (600x800 px), compression JPEG 85%, suppression métadonnées
 */
class PhotoService
{
    private string $uploadDir;
    private int $targetWidth = 600;
    private int $targetHeight = 800;
    private int $jpegQuality = 85;

    public function __construct()
    {
        $this->uploadDir = dirname(__DIR__, 2) . '/public/uploads/photos';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Traite une photo depuis un flux Base64 (Webcam ou Cropper.js)
     */
    public function processBase64(string $base64Data): string
    {
        // Enlever le préfixe data:image/...;base64,
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        }

        $decodedData = base64_decode($base64Data);
        if ($decodedData === false) {
            throw new RuntimeException("Données d'image Base64 corrompues.");
        }

        return $this->optimizeAndSave($decodedData);
    }

    /**
     * Traite un fichier image téléversé ($_FILES)
     */
    public function processUploadedFile(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            throw new RuntimeException("Erreur de téléversement du fichier.");
        }

        // Vérification MIME
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowed, true)) {
            throw new RuntimeException("Format d'image non supporté ($mime). Formats acceptés : JPEG, PNG, WebP.");
        }

        $rawData = file_get_contents($file['tmp_name']);
        if ($rawData === false) {
            throw new RuntimeException("Impossible de lire le fichier téléversé.");
        }

        return $this->optimizeAndSave($rawData);
    }

    /**
     * Redimensionne, recadre au ratio 3:4 et compresse l'image
     */
    private function optimizeAndSave(string $imageData): string
    {
        $srcImage = @imagecreatefromstring($imageData);
        if (!$srcImage) {
            throw new RuntimeException("Impossible de décoder les données de l'image.");
        }

        $srcWidth = imagesx($srcImage);
        $srcHeight = imagesy($srcImage);

        // Calcul du recadrage centré au ratio 3:4 (0.75)
        $targetRatio = $this->targetWidth / $this->targetHeight;
        $srcRatio = $srcWidth / $srcHeight;

        if ($srcRatio > $targetRatio) {
            // L'image source est plus large : on recadre en largeur
            $cropWidth = (int)round($srcHeight * $targetRatio);
            $cropHeight = $srcHeight;
            $cropX = (int)round(($srcWidth - $cropWidth) / 2);
            $cropY = 0;
        } else {
            // L'image source est plus haute : on recadre en hauteur
            $cropWidth = $srcWidth;
            $cropHeight = (int)round($srcWidth / $targetRatio);
            $cropX = 0;
            $cropY = (int)round(($srcHeight - $cropHeight) / 2);
        }

        // Création de la toile finale 600x800
        $dstImage = imagecreatetruecolor($this->targetWidth, $this->targetHeight);

        // Fond blanc propre
        $white = imagecolorallocate($dstImage, 255, 255, 255);
        imagefilledrectangle($dstImage, 0, 0, $this->targetWidth, $this->targetHeight, $white);

        // Échantillonnage haute qualité
        imagecopyresampled(
            $dstImage,
            $srcImage,
            0, 0,
            $cropX, $cropY,
            $this->targetWidth, $this->targetHeight,
            $cropWidth, $cropHeight
        );

        // Génération d'un nom de fichier sécurisé et aléatoire
        $filename = bin2hex(random_bytes(16)) . '.jpg';
        $destination = $this->uploadDir . '/' . $filename;

        // Sauvegarde en JPEG haute fidélité sans métadonnées EXIF
        imagejpeg($dstImage, $destination, $this->jpegQuality);

        return 'uploads/photos/' . $filename;
    }
}
