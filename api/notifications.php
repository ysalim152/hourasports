<?php
/**
 * api/notifications.php
 * GET  /api/notifications.php          → mes notifications
 * PUT  /api/notifications.php?id=X     → marquer lu
 * PUT  /api/notifications.php?action=all_read → tout marquer lu
 * DELETE /api/notifications.php?id=X   → supprimer
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

requireAuth();
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? '';
$userId = currentUserId();

try {
    switch ($method) {
        case 'GET':
            $rows = dbFetchAll(
                'SELECT id, type, titre, message, lien, lu, created_at
                 FROM notifications WHERE utilisateur_id = ?
                 ORDER BY created_at DESC LIMIT 20',
                [$userId]
            );
            $nbNonLues = (int)(dbFetchOne(
                'SELECT COUNT(*) c FROM notifications WHERE utilisateur_id = ? AND lu = 0',
                [$userId]
            )['c'] ?? 0);
            echo json_encode(['success' => true, 'data' => $rows, 'non_lues' => $nbNonLues]);
            break;

        case 'PUT':
            if ($action === 'all_read') {
                dbQuery('UPDATE notifications SET lu = 1 WHERE utilisateur_id = ?', [$userId]);
                echo json_encode(['success' => true, 'message' => 'Toutes notifications lues.']);
            } elseif ($id) {
                dbUpdate('notifications', ['lu' => 1], ['id' => $id, 'utilisateur_id' => $userId]);
                echo json_encode(['success' => true, 'message' => 'Notification lue.']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requis.']);
            }
            break;

        case 'DELETE':
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID requis.']); exit; }
            dbDelete('notifications', ['id' => $id, 'utilisateur_id' => $userId]);
            echo json_encode(['success' => true, 'message' => 'Notification supprimée.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non supportée.']);
    }
} catch (PDOException $e) {
    error_log('[API notifications] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
