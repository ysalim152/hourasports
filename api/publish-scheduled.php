<?php
/**
 * api/cron/publish-scheduled.php
 * Endpoint sécurisé destiné à être appelé par un cron job pour publier les articles programmés.
 *
 * USAGE:
 * GET /api/cron/publish-scheduled.php?token=VOTRE_TOKEN_SECRET
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// --- 1. Sécurité : Vérification du Token ---

// Récupérez ce token depuis une variable d'environnement ou un fichier de configuration sécurisé.
// NE LE METTEZ PAS EN DUR ICI EN PRODUCTION.
$secretToken = getenv('CRON_SECRET_TOKEN') ?: 'CHANGER_CE_TOKEN_SECRET';

$providedToken = $_GET['token'] ?? '';

if (empty($providedToken) || !hash_equals($secretToken, $providedToken)) {
    http_response_code(403);
    // Message d'erreur volontairement vague pour la sécurité.
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit;
}

// --- 2. Exécution de la procédure stockée ---

try {
    $pdo = getPDO();

    // On récupère les IDs des articles qui VONT être publiés pour le log
    $articlesToPublish = dbFetchAll(
        "SELECT id FROM actualites
         WHERE statut = 'brouillon'
           AND scheduled_at IS NOT NULL
           AND scheduled_at <= NOW()"
    );
    $publishedIds = array_column($articlesToPublish, 'id');

    // Appel de la procédure qui fait la mise à jour
    $stmt = $pdo->query('CALL proc_publier_articles_programmes()');
    $stmt->closeCursor();

    http_response_code(200);
    echo json_encode([
        'success'            => true,
        'message'            => count($publishedIds) . ' article(s) publié(s) avec succès.',
        'articles_published' => count($publishedIds),
        'published_ids'      => $publishedIds,
    ]);

} catch (PDOException $e) {
    error_log('[CRON] Erreur lors de la publication programmée: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur lors de l\'exécution du cron.']);
}