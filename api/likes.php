<?php
/**
 * api/likes.php — Gère les "J'aime" sur les articles
 * POST   /api/likes.php?article_id=X      → Aimer un article
 * DELETE /api/likes.php?article_id=X      → Retirer son "J'aime"
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/redis.php';

header('Content-Type: application/json; charset=utf-8');

// Toutes les actions sur les likes nécessitent d'être connecté
requireAuth();

$method     = $_SERVER['REQUEST_METHOD'];
$articleId  = isset($_GET['article_id']) ? (int)$_GET['article_id'] : null;
$userId     = currentUserId();

if (!$articleId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de l\'article manquant.']);
    exit;
}

// Vérifier que l'article existe
$article = dbFetchOne('SELECT id, slug, likes_count FROM actualites WHERE id = ?', [$articleId]);
if (!$article) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Article introuvable.']);
    exit;
}

$redis = getRedis();

try {
    switch ($method) {
        case 'POST': // Aimer un article
            $existingLike = dbFetchOne(
                'SELECT id FROM actualite_likes WHERE actualite_id = ? AND user_id = ?',
                [$articleId, $userId]
            );

            if ($existingLike) {
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'Vous avez déjà aimé cet article.']);
                exit;
            }

            // Insérer le like. Le trigger `trg_likes_insert` mettra à jour le compteur.
            dbInsert('actualite_likes', [
                'actualite_id' => $articleId,
                'user_id'      => $userId
            ]);

            // Invalider le cache
            if ($redis) {
                $redis->del(["article:{$article['id']}", "article:slug:{$article['slug']}"]);
                $keys = $redis->keys('articles_list:*');
                if ($keys) $redis->del($keys);
            }

            http_response_code(201);
            echo json_encode([
                'success'     => true,
                'message'     => 'Article aimé.',
                'liked'       => true,
                'likes_count' => $article['likes_count'] + 1
            ]);
            break;

        case 'DELETE': // Retirer son "J'aime"
            // Supprimer le like. Le trigger `trg_likes_delete` mettra à jour le compteur.
            $deleted = dbDelete('actualite_likes', [
                'actualite_id' => $articleId,
                'user_id'      => $userId
            ]);

            if ($deleted > 0) {
                // Invalider le cache
                if ($redis) {
                    $redis->del(["article:{$article['id']}", "article:slug:{$article['slug']}"]);
                    $keys = $redis->keys('articles_list:*');
                    if ($keys) $redis->del($keys);
                }
            }

            echo json_encode([
                'success'     => true,
                'message'     => 'Like retiré.',
                'liked'       => false,
                'likes_count' => max(0, $article['likes_count'] - 1)
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non supportée.']);
    }
} catch (PDOException $e) {
    error_log('[API likes] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}