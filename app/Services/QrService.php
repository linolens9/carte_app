<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service de génération de QR Code pur PHP (Vectoriel SVG & Data URI)
 * Implémente la spécification ISO/IEC 18004 (QR Code 2005) en mode Byte (ISO-8859-1 / UTF-8)
 * Niveau de correction L ou M, sans aucune dépendance externe ni connexion internet.
 */
class QrService
{
    // Reed-Solomon Galois Field 256 tables
    private static array $exp = [];
    private static array $log = [];
    private static bool $gfInit = false;

    private static function initGF(): void
    {
        if (self::$gfInit) {
            return;
        }
        self::$exp = array_fill(0, 512, 0);
        self::$log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11d; // Polynôme primitif x^8 + x^4 + x^3 + x^2 + 1
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];
        }
        self::$gfInit = true;
    }

    private static function gMul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }
        return self::$exp[self::$log[$a] + self::$log[$b]];
    }

    private static function rsPoly(int $n): array
    {
        $poly = [1];
        for ($i = 0; $i < $n; $i++) {
            $next = array_fill(0, count($poly) + 1, 0);
            $factor = self::$exp[$i];
            for ($j = 0; $j < count($poly); $j++) {
                $next[$j] ^= self::gMul($poly[$j], $factor);
                $next[$j + 1] ^= $poly[$j];
            }
            $poly = $next;
        }
        return array_reverse($poly);
    }

    private static function rsEncode(array $data, int $ecCount): array
    {
        self::initGF();
        $poly = self::rsPoly($ecCount);
        $res = array_pad($data, count($data) + $ecCount, 0);
        $dataLen = count($data);

        for ($i = 0; $i < $dataLen; $i++) {
            $coef = $res[$i];
            if ($coef !== 0) {
                for ($j = 0; $j <= $ecCount; $j++) {
                    $res[$i + $j] ^= self::gMul($poly[$j], $coef);
                }
            }
        }
        return array_slice($res, $dataLen);
    }

    /**
     * Versions QR supportées (1 à 10) avec capacité Byte niveau M
     */
    private const SPECS = [
        1  => ['size' => 21, 'data' => 16,  'ec' => 10, 'blocks' => 1, 'align' => []],
        2  => ['size' => 25, 'data' => 28,  'ec' => 16, 'blocks' => 1, 'align' => [6, 18]],
        3  => ['size' => 29, 'data' => 44,  'ec' => 26, 'blocks' => 1, 'align' => [6, 22]],
        4  => ['size' => 33, 'data' => 64,  'ec' => 36, 'blocks' => 2, 'align' => [6, 26]],
        5  => ['size' => 37, 'data' => 86,  'ec' => 48, 'blocks' => 2, 'align' => [6, 30]],
        6  => ['size' => 41, 'data' => 108, 'ec' => 64, 'blocks' => 4, 'align' => [6, 34]],
        7  => ['size' => 45, 'data' => 124, 'ec' => 72, 'blocks' => 4, 'align' => [6, 22, 38]],
        8  => ['size' => 49, 'data' => 154, 'ec' => 88, 'blocks' => 4, 'align' => [6, 24, 42]],
        9  => ['size' => 53, 'data' => 182, 'ec' => 110, 'blocks' => 5, 'align' => [6, 26, 46]],
        10 => ['size' => 57, 'data' => 216, 'ec' => 130, 'blocks' => 5, 'align' => [6, 28, 50]],
    ];

    /**
     * Génère la matrice binaire du QR code
     */
    public static function createMatrix(string $text): array
    {
        $bytes = array_values(unpack('C*', $text));
        $dataLen = count($bytes);

        // Détermination de la version minimale nécessaire
        $ver = 0;
        foreach (self::SPECS as $v => $spec) {
            // Byte mode: 4 bits mode + 8 bits char count + 8*dataLen bits
            $bitsNeeded = 4 + 8 + ($dataLen * 8);
            if ($bitsNeeded <= ($spec['data'] * 8)) {
                $ver = $v;
                break;
            }
        }

        if ($ver === 0) {
            $ver = 10; // Cap
            $bytes = array_slice($bytes, 0, 200);
            $dataLen = count($bytes);
        }

        $spec = self::SPECS[$ver];
        $totalDataBytes = $spec['data'];
        $ecBytesTotal = $spec['ec'];
        $blocksCount = $spec['blocks'];

        // Bit stream
        $bitStream = '0100'; // Byte mode
        $bitStream .= sprintf('%08b', $dataLen);
        foreach ($bytes as $b) {
            $bitStream .= sprintf('%08b', $b);
        }

        // Terminaison (jusqu'à 4 zéros)
        $maxBits = $totalDataBytes * 8;
        $remBits = $maxBits - strlen($bitStream);
        if ($remBits > 0) {
            $bitStream .= str_repeat('0', min(4, $remBits));
        }

        // Alignement sur 8 bits
        while (strlen($bitStream) % 8 !== 0) {
            $bitStream .= '0';
        }

        // Remplissage avec octets alternés 11101100 (236) et 00010001 (17)
        $padBytes = [0xEC, 0x11];
        $padIdx = 0;
        while (strlen($bitStream) < $maxBits) {
            $bitStream .= sprintf('%08b', $padBytes[$padIdx]);
            $padIdx = ($padIdx + 1) % 2;
        }

        // Découpage en octets de données
        $dataBytes = [];
        for ($i = 0; $i < strlen($bitStream); $i += 8) {
            $dataBytes[] = bindec(substr($bitStream, $i, 8));
        }

        // Partitionnement en blocs & calcul RS pour chaque bloc
        $dataPerBlock = (int)($totalDataBytes / $blocksCount);
        $ecPerBlock = (int)($ecBytesTotal / $blocksCount);

        $blockData = [];
        $blockEc = [];
        for ($b = 0; $b < $blocksCount; $b++) {
            $start = $b * $dataPerBlock;
            $slice = array_slice($dataBytes, $start, $dataPerBlock);
            $blockData[] = $slice;
            $blockEc[] = self::rsEncode($slice, $ecPerBlock);
        }

        // Entrelacement (interleaving)
        $finalCodewords = [];
        for ($i = 0; $i < $dataPerBlock; $i++) {
            for ($b = 0; $b < $blocksCount; $b++) {
                if (isset($blockData[$b][$i])) {
                    $finalCodewords[] = $blockData[$b][$i];
                }
            }
        }
        for ($i = 0; $i < $ecPerBlock; $i++) {
            for ($b = 0; $b < $blocksCount; $b++) {
                if (isset($blockEc[$b][$i])) {
                    $finalCodewords[] = $blockEc[$b][$i];
                }
            }
        }

        // Bit stream final
        $finalBits = '';
        foreach ($finalCodewords as $cw) {
            $finalBits .= sprintf('%08b', $cw);
        }

        // Création de la matrice
        $size = $spec['size'];
        $matrix = array_fill(0, $size, array_fill(0, $size, null));

        // Motifs de recherche (Finder Patterns 7x7) aux 3 coins
        self::placeFinder($matrix, 0, 0);
        self::placeFinder($matrix, $size - 7, 0);
        self::placeFinder($matrix, 0, $size - 7);

        // Motifs d'alignement
        $align = $spec['align'];
        $alignCount = count($align);
        for ($r = 0; $r < $alignCount; $r++) {
            for ($c = 0; $c < $alignCount; $c++) {
                $ar = $align[$r];
                $ac = $align[$c];
                // Ignorer si chevauche un motif de recherche
                if (($ar < 9 && $ac < 9) || ($ar < 9 && $ac > $size - 9) || ($ar > $size - 9 && $ac < 9)) {
                    continue;
                }
                self::placeAlignment($matrix, $ar - 2, $ac - 2);
            }
        }

        // Lignes de synchronisation (Timing Patterns)
        for ($i = 8; $i < $size - 8; $i++) {
            $val = ($i % 2 === 0) ? 1 : 0;
            if ($matrix[6][$i] === null) {
                $matrix[6][$i] = $val;
            }
            if ($matrix[$i][6] === null) {
                $matrix[$i][6] = $val;
            }
        }

        // Module noir fixe
        $matrix[$size - 8][8] = 1;

        // Réservation des zones d'information de format (15 bits)
        for ($i = 0; $i < 9; $i++) {
            if ($matrix[8][$i] === null) $matrix[8][$i] = 0;
            if ($matrix[$i][8] === null) $matrix[$i][8] = 0;
        }
        for ($i = $size - 8; $i < $size; $i++) {
            if ($matrix[8][$i] === null) $matrix[8][$i] = 0;
            if ($matrix[$i][8] === null) $matrix[$i][8] = 0;
        }

        // Placement des données en zigzag (de droite à gauche, par colonnes de 2)
        $bitIdx = 0;
        $numBits = strlen($finalBits);
        $dir = -1; // Vers le haut
        $x = $size - 1;

        while ($x > 0) {
            if ($x === 6) {
                $x--; // Sauter la colonne de synchronisation
            }
            $y = ($dir === -1) ? ($size - 1) : 0;

            for ($step = 0; $step < $size; $step++) {
                for ($col = 0; $col < 2; $col++) {
                    $cx = $x - $col;
                    if ($matrix[$y][$cx] === null) {
                        $bit = ($bitIdx < $numBits) ? (int)$finalBits[$bitIdx] : 0;
                        $bitIdx++;
                        // Application du masque 0 : (row + col) % 2 == 0
                        $mask = (($y + $cx) % 2 === 0);
                        $matrix[$y][$cx] = $mask ? ($bit ^ 1) : $bit;
                    }
                }
                $y += $dir;
            }
            $dir = -$dir;
            $x -= 2;
        }

        // Information de format pour Niveau M + Masque 0 (Format Bits: 101010000010010)
        // Code BCH 15 bits précalculé avec XOR masque 101010000010010
        // Pour M (00) et Masque 0 (000), le format code est 101010000010010
        $fmtBits = '101010000010010';
        // Placement horizontal
        $idx = 0;
        for ($i = 0; $i < 9; $i++) {
            if ($i !== 6) {
                $matrix[8][$i] = (int)$fmtBits[$idx++];
            }
        }
        for ($i = $size - 7; $i < $size; $i++) {
            $matrix[8][$i] = (int)$fmtBits[$idx++];
        }

        // Placement vertical
        $idx = 0;
        for ($i = $size - 1; $i >= $size - 7; $i--) {
            $matrix[$i][8] = (int)$fmtBits[$idx++];
        }
        $matrix[8][8] = (int)$fmtBits[$idx++];
        $matrix[7][8] = (int)$fmtBits[$idx++];
        for ($i = 5; $i >= 0; $i--) {
            $matrix[$i][8] = (int)$fmtBits[$idx++];
        }

        return $matrix;
    }

    private static function placeFinder(array &$m, int $x, int $y): void
    {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                $isBorder = ($r === 0 || $r === 6 || $c === 0 || $c === 6);
                $isCenter = ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
                $m[$y + $r][$x + $c] = ($isBorder || $isCenter) ? 1 : 0;
            }
        }
        // Séparateur blanc
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $px = $x + $c;
                $py = $y + $r;
                if ($px >= 0 && $px < count($m) && $py >= 0 && $py < count($m)) {
                    if ($r === -1 || $r === 7 || $c === -1 || $c === 7) {
                        $m[$py][$px] = 0;
                    }
                }
            }
        }
    }

    private static function placeAlignment(array &$m, int $x, int $y): void
    {
        for ($r = 0; $r < 5; $r++) {
            for ($c = 0; $c < 5; $c++) {
                $isBorder = ($r === 0 || $r === 4 || $c === 0 || $c === 4);
                $isCenter = ($r === 2 && $c === 2);
                $m[$y + $r][$x + $c] = ($isBorder || $isCenter) ? 1 : 0;
            }
        }
    }

    /**
     * Génère un QR code au format SVG
     */
    public static function generateSvg(string $text, int $sizePx = 140, string $color = '#111827', string $bg = '#ffffff'): string
    {
        $matrix = self::createMatrix($text);
        $count = count($matrix);
        $quietZone = 2; // Zone de marge
        $fullSize = $count + ($quietZone * 2);

        $path = '';
        for ($r = 0; $r < $count; $r++) {
            for ($c = 0; $c < $count; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $x = $c + $quietZone;
                    $y = $r + $quietZone;
                    $path .= "M{$x},{$y}h1v1h-1z ";
                }
            }
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $fullSize . ' ' . $fullSize . '" width="' . $sizePx . '" height="' . $sizePx . '" shape-rendering="crispEdges">';
        if ($bg !== 'transparent') {
            $svg .= '<rect width="100%" height="100%" fill="' . htmlspecialchars($bg) . '"/>';
        }
        $svg .= '<path d="' . trim($path) . '" fill="' . htmlspecialchars($color) . '"/>';
        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Génère une URL Data URI pour le QR code
     */
    public static function generateDataUri(string $text, int $sizePx = 140, string $color = '#111827', string $bg = '#ffffff'): string
    {
        $svg = self::generateSvg($text, $sizePx, $color, $bg);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
