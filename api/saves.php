<?php
/**
 * api/saves.php — Gère les articles sauvegardés (favoris)
 * POST   /api/saves.php?article_id=X      → Sauvegarder un article
 * DELETE /api/saves.php?article_id=X      → Retirer un article des favoris
 * GET    /api/saves.php                   → Lister mes articles sauvegardés
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Toutes les actions sur les favoris nécessitent d'être connecté
requireAuth();

$method     = $_SERVER['REQUEST_METHOD'];
$articleId  = isset($_GET['article_id']) ? (int)$_GET['article_id'] : null;
$userId     = currentUserId();

try {
    switch ($method) {
        case 'GET': // Lister les articles sauvegardés par l'utilisateur
            $saves = dbFetchAll(
                'SELECT a.id, a.titre, a.slug, a.extrait, a.image_thumbnail AS image, s.saved_at
                 FROM actualite_saves s
                 JOIN actualites a ON s.actualite_id = a.id
                 WHERE s.user_id = ?
                 ORDER BY s.saved_at DESC',
                [$userId]
            );
            echo json_encode(['success' => true, 'data' => $saves]);
            break;

        case 'POST': // Sauvegarder un article
            if (!$articleId) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID de l\'article manquant.']); exit; }

            $existingSave = dbFetchOne(
                'SELECT id FROM actualite_saves WHERE actualite_id = ? AND user_id = ?',
                [$articleId, $userId]
            );

            if ($existingSave) {
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'Article déjà sauvegardé.']);
                exit;
            }

            dbInsert('actualite_saves', [
                'actualite_id' => $articleId,
                'user_id'      => $userId
            ]);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Article sauvegardé.',
                'saved'   => true
            ]);
            break;

        case 'DELETE': // Retirer un article des favoris
            if (!$articleId) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID de l\'article manquant.']); exit; }

            dbDelete('actualite_saves', [
                'actualite_id' => $articleId,
                'user_id'      => $userId
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Article retiré des favoris.',
                'saved'   => false
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non supportée.']);
    }
} catch (PDOException $e) {
    error_log('[API saves] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}