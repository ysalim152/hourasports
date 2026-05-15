<?php
/**
 * api/comments.php — Gère les commentaires sur les articles
 * GET    /api/comments.php?article_id=X      → Lister les commentaires d'un article (imbriqués)
 * POST   /api/comments.php                   → Poster un commentaire ou une réponse
 * PUT    /api/comments.php?id=X&action=Y     → Modérer un commentaire (admin/coach)
 * DELETE /api/comments.php?id=X              → Supprimer un commentaire (admin/coach)
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Construit une arborescence de commentaires à partir d'une liste à plat.
 * @param array $comments Liste de commentaires de la base de données.
 * @return array Arborescence de commentaires.
 */
function buildCommentTree(array $comments): array
{
    $commentMap = [];
    foreach ($comments as $comment) {
        $commentMap[$comment['id']] = $comment;
        $commentMap[$comment['id']]['children'] = [];
    }

    $tree = [];
    foreach ($commentMap as $id => &$comment) {
        if ($comment['comment_parent_id']) {
            if (isset($commentMap[$comment['comment_parent_id']])) {
                $commentMap[$comment['comment_parent_id']]['children'][] = &$comment;
            }
        } else {
            $tree[] = &$comment;
        }
    }
    return $tree;
}

try {
    switch ($method) {
        case 'GET':
            $articleId = isset($_GET['article_id']) ? (int)$_GET['article_id'] : null;
            if (!$articleId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de l\'article manquant.']);
                exit;
            }

            $comments = dbFetchAll(
                "SELECT c.id, c.contenu, c.created_at, c.comment_parent_id,
                        u.id AS user_id, CONCAT(u.prenom, ' ', u.nom) AS user_name, u.avatar
                 FROM actualite_commentaires c
                 JOIN utilisateurs u ON c.user_id = u.id
                 WHERE c.actualite_id = ? AND c.statut = 'approuve'
                 ORDER BY c.created_at ASC",
                [$articleId]
            );

            $commentTree = buildCommentTree($comments);

            echo json_encode(['success' => true, 'data' => $commentTree]);
            break;

        case 'POST':
            requireAuth(); // Seuls les utilisateurs connectés peuvent commenter

            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $articleId = isset($body['article_id']) ? (int)$body['article_id'] : null;
            $contenu = trim($body['contenu'] ?? '');
            $parentId = isset($body['parent_id']) ? (int)$body['parent_id'] : null;
            $userId = currentUserId();

            if (!$articleId || empty($contenu)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Données manquantes.']);
                exit;
            }

            // Pour cet exemple, on approuve automatiquement les commentaires.
            // En production, vous pourriez vouloir 'en_attente' et un système de modération.
            $statut = 'approuve';

            $newId = dbInsert('actualite_commentaires', [
                'actualite_id' => $articleId,
                'user_id' => $userId,
                'contenu' => $contenu,
                'comment_parent_id' => $parentId,
                'statut' => $statut,
            ]);

            // Le trigger `trg_comments_insert` mettra à jour le compteur sur la table `actualites`

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Commentaire ajouté.', 'id' => $newId]);
            break;

        case 'PUT':
            requireRole('coach'); // Seuls les coachs/admins peuvent modérer

            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            $action = $_GET['action'] ?? '';

            if (!$id || !in_array($action, ['approuve', 'rejete', 'spam'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Action ou ID invalide.']);
                exit;
            }

            dbUpdate('actualite_commentaires', ['statut' => $action], ['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Statut du commentaire mis à jour.']);
            break;

        case 'DELETE':
            requireRole('coach');

            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID manquant.']);
                exit;
            }

            // Grâce à `ON DELETE CASCADE` dans la BDD, la suppression d'un commentaire
            // supprimera automatiquement toutes ses réponses.
            dbDelete('actualite_commentaires', ['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Commentaire et réponses supprimés.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non supportée.']);
    }
} catch (PDOException $e) {
    error_log('[API comments] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}

?>