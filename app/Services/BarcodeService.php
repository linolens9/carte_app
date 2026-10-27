<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service de génération de codes-barres vectoriels (Code 128)
 */
class BarcodeService
{
    /**
     * Table des motifs Code 128 (107 symboles)
     * Chaque nombre représente la largeur des barres et espaces (alternance 6 éléments, somme = 11)
     */
    private const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112' // 106 = STOP
    ];

    /**
     * Génère un code-barres Code 128B au format SVG
     *
     * @param string $text Le texte à encoder (ex: C-2026-ELV-0001)
     * @param int $height Hauteur du code-barres en pixels (défaut 50)
     * @param int $barWidth Largeur de l'unité de base en pixels (défaut 2)
     * @param bool $showText Afficher le texte sous les barres
     * @param string $color Couleur des barres (#000000)
     * @return string Balise SVG complète
     */
    public static function generateSvg(
        string $text,
        int $height = 40,
        int $barWidth = 2,
        bool $showText = true,
        string $color = '#000000'
    ): string {
        $text = trim($text);
        if ($text === '') {
            $text = '0000';
        }

        // Code 128B : Code de départ B = 104
        $values = [104];
        $checksum = 104;

        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $char = $text[$i];
            $ascii = ord($char);
            $val = $ascii - 32; // Dans le jeu B, code = ASCII - 32
            if ($val < 0 || $val > 95) {
                $val = 0; // Fallback pour caractères hors jeu
            }
            $values[] = $val;
            $checksum += $val * ($i + 1);
        }

        // Clé de contrôle modulo 103
        $checkVal = $checksum % 103;
        $values[] = $checkVal;

        // Symbole d'arrêt (STOP = 106)
        $values[] = 106;

        // Construction des bandes
        $bars = '';
        foreach ($values as $v) {
            $bars .= self::PATTERNS[$v] ?? self::PATTERNS[0];
        }

        // Calcul de la largeur totale
        $totalUnits = 0;
        $barLen = strlen($bars);
        for ($i = 0; $i < $barLen; $i++) {
            $totalUnits += (int)$bars[$i];
        }

        $quietZone = 10 * $barWidth;
        $totalWidth = ($totalUnits * $barWidth) + (2 * $quietZone);
        $totalHeight = $height + ($showText ? 14 : 0);

        // Tracé SVG
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $totalWidth . ' ' . $totalHeight . '" width="' . $totalWidth . '" height="' . $totalHeight . '">';
        $svg .= '<rect width="100%" height="100%" fill="transparent"/>';

        $x = $quietZone;
        $isBar = true;
        for ($i = 0; $i < $barLen; $i++) {
            $w = (int)$bars[$i] * $barWidth;
            if ($isBar) {
                $svg .= '<rect x="' . $x . '" y="0" width="' . $w . '" height="' . $height . '" fill="' . htmlspecialchars($color) . '"/>';
            }
            $x += $w;
            $isBar = !$isBar;
        }

        if ($showText) {
            $textY = $height + 11;
            $centerX = $totalWidth / 2;
            $svg .= '<text x="' . $centerX . '" y="' . $textY . '" font-family="Courier, monospace, sans-serif" font-size="11" font-weight="bold" fill="' . htmlspecialchars($color) . '" text-anchor="middle" letter-spacing="1">' . htmlspecialchars($text) . '</text>';
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Retourne le SVG sous forme d'URI data:image/svg+xml;base64,...
     */
    public static function generateDataUri(string $text, int $height = 40, int $barWidth = 2, bool $showText = true, string $color = '#000000'): string
    {
        $svg = self::generateSvg($text, $height, $barWidth, $showText, $color);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
