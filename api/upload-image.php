<?php
/**
 * api/upload-image.php — Gère l'upload, la compression et le redimensionnement des images.
 * POST /api/upload-image.php
 *   - Body: multipart/form-data
 *   - Field: "image"
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

// Seuls les coachs et admins peuvent uploader des images
requireRole('coach');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Aucun fichier image fourni.']);
    exit;
}

$file = $_FILES['image'];

// --- 1. Validation du fichier ---

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5 MB

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload. Code: ' . $file['error']]);
    exit;
}

if (!in_array($file['type'], $allowedMimes)) {
    http_response_code(415); // Unsupported Media Type
    echo json_encode(['success' => false, 'message' => 'Format de fichier non supporté. Uniquement JPG, PNG, WebP.']);
    exit;
}

if ($file['size'] > $maxSize) {
    http_response_code(413); // Payload Too Large
    echo json_encode(['success' => false, 'message' => 'Le fichier est trop volumineux (max 5MB).']);
    exit;
}

// --- 2. Préparation des chemins ---

$uploadDir = __DIR__ . '/../public/uploads/actualites/';
$uploadUrl = '/public/uploads/actualites/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$originalName = pathinfo($file['name'], PATHINFO_FILENAME);
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$safeFilename = preg_replace('/[^a-z0-9_-]/i', '-', $originalName);
$uniqueId = uniqid();
$baseFilename = date('Y-m-d') . '-' . $safeFilename . '-' . $uniqueId;

$paths = [
    'original'  => $uploadDir . $baseFilename . '.' . $extension,
    'main'      => $uploadDir . $baseFilename . '.jpg',
    'webp'      => $uploadDir . $baseFilename . '.webp',
    'thumbnail' => $uploadDir . $baseFilename . '-thumb.jpg',
];

$urls = [
    'main'      => $uploadUrl . $baseFilename . '.jpg',
    'webp'      => $uploadUrl . $baseFilename . '.webp',
    'thumbnail' => $uploadUrl . $baseFilename . '-thumb.jpg',
];

// Déplacer le fichier uploadé
if (!move_uploaded_file($file['tmp_name'], $paths['original'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible de déplacer le fichier uploadé.']);
    exit;
}

// --- 3. Traitement de l'image (avec GD) ---

try {
    // Charger l'image originale
    $sourceImage = match ($file['type']) {
        'image/jpeg' => imagecreatefromjpeg($paths['original']),
        'image/png' => imagecreatefrompng($paths['original']),
        'image/webp' => imagecreatefromwebp($paths['original']),
        default => false,
    };

    if (!$sourceImage) {
        throw new Exception("Impossible de charger l'image source.");
    }

    // Conserver la transparence pour les PNG
    imagealphablending($sourceImage, false);
    imagesavealpha($sourceImage, true);

    // --- a) Créer l'image principale (1200px de large, compressée) ---
    $mainWidth = 1200;
    list($originalWidth, $originalHeight) = getimagesize($paths['original']);
    $mainImage = imagescale($sourceImage, $mainWidth);
    imagejpeg($mainImage, $paths['main'], 80); // Compression à 80%

    // --- b) Créer la version WebP ---
    imagewebp($mainImage, $paths['webp'], 80);

    // --- c) Créer la miniature (600x400, rognée au centre) ---
    $thumbWidth = 600;
    $thumbHeight = 400;
    $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);

    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);
    $sourceRatio = $sourceWidth / $sourceHeight;
    $thumbRatio = $thumbWidth / $thumbHeight;

    if ($sourceRatio > $thumbRatio) { // Image plus large que la miniature
        $src_w = (int)($sourceHeight * $thumbRatio);
        $src_h = $sourceHeight;
        $src_x = (int)(($sourceWidth - $src_w) / 2);
        $src_y = 0;
    } else { // Image plus haute
        $src_w = $sourceWidth;
        $src_h = (int)($sourceWidth / $thumbRatio);
        $src_x = 0;
        $src_y = (int)(($sourceHeight - $src_h) / 2);
    }

    imagecopyresampled($thumbImage, $sourceImage, 0, 0, $src_x, $src_y, $thumbWidth, $thumbHeight, $src_w, $src_h);
    imagejpeg($thumbImage, $paths['thumbnail'], 75);

    // Récupérer la hauteur de l'image principale redimensionnée
    $mainHeight = (int) (($mainWidth / $originalWidth) * $originalHeight);

    // Libérer la mémoire
    imagedestroy($sourceImage);
    imagedestroy($mainImage);
    imagedestroy($thumbImage);

    // Supprimer l'original
    unlink($paths['original']);

    // --- 4. Réponse JSON ---
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Image uploadée et traitée avec succès.',
        'data' => [
            'image'     => $urls['main'],
            'image_webp'=> $urls['webp'],
            'thumbnail' => $urls['thumbnail'],
            'width'     => $mainWidth,
            'height'    => $mainHeight,
            'size_original' => $file['size'],
            'size_compressed' => filesize($paths['main']),
        ]
    ]);

} catch (Exception $e) {
    error_log('[API upload-image] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors du traitement de l\'image.']);
}

?>