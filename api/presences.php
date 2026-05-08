<?php
/**
 * api/presences.php — CRUD Présences
 * GET  ?session_id=X             → présences d'une session
 * GET  ?utilisateur_id=X         → présences d'un membre
 * POST                           → marquer une présence
 * PUT  ?id=X                     → modifier une présence
 * POST ?action=bulk              → enregistrer plusieurs présences d'un coup
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

requireRole('coach');
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? '';

try {
    switch ($method) {

        case 'GET':
            if (!empty($_GET['session_id'])) {
                $sessId = (int)$_GET['session_id'];
                $rows = dbFetchAll(
                    'SELECT p.id, p.statut, p.note, p.created_at,
                            u.id AS utilisateur_id,
                            CONCAT(u.prenom," ",u.nom) AS nom_complet,
                            u.email, r.nom AS role
                     FROM presences p
                     JOIN utilisateurs u ON u.id = p.utilisateur_id
                     JOIN roles r ON r.id = u.role_id
                     WHERE p.session_id = ?
                     ORDER BY u.nom, u.prenom',
                    [$sessId]
                );
                // Taux de présence
                $total   = count($rows);
                $presents= count(array_filter($rows, fn($r) => $r['statut'] === 'present'));
                echo json_encode([
                    'success' => true,
                    'data'    => $rows,
                    'stats'   => [
                        'total'    => $total,
                        'presents' => $presents,
                        'absents'  => count(array_filter($rows, fn($r) => $r['statut'] === 'absent')),
                        'excuses'  => count(array_filter($rows, fn($r) => $r['statut'] === 'excuse')),
                        'retards'  => count(array_filter($rows, fn($r) => $r['statut'] === 'retard')),
                        'taux_pct' => $total > 0 ? round($presents / $total * 100) : 0,
                    ],
                ]);
            } elseif (!empty($_GET['utilisateur_id'])) {
                $userId = (int)$_GET['utilisateur_id'];
                $rows = dbFetchAll(
                    'SELECT p.id, p.statut, p.note, p.created_at,
                            s.id AS session_id, s.titre, s.type, s.date_debut, s.lieu,
                            e.nom AS equipe
                     FROM presences p
                     JOIN sessions_entrainement s ON s.id = p.session_id
                     LEFT JOIN equipes e ON e.id = s.equipe_id
                     WHERE p.utilisateur_id = ?
                     ORDER BY s.date_debut DESC',
                    [$userId]
                );
                $total   = count($rows);
                $presents= count(array_filter($rows, fn($r) => $r['statut'] === 'present'));
                echo json_encode([
                    'success' => true,
                    'data'    => $rows,
                    'stats'   => [
                        'total'    => $total,
                        'presents' => $presents,
                        'taux_pct' => $total > 0 ? round($presents / $total * 100) : 0,
                    ],
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'session_id ou utilisateur_id requis.']);
            }
            break;

        case 'POST':
            $d = json_decode(file_get_contents('php://input'), true) ?? [];

            // ── Bulk insert (toute une feuille de présence) ──
            if ($action === 'bulk') {
                $sessId   = (int)($d['session_id'] ?? 0);
                $presences= $d['presences'] ?? []; // [{utilisateur_id, statut, note?}, ...]
                if (!$sessId || empty($presences)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'session_id et presences requis.']);
                    exit;
                }
                $statuts = ['present','absent','excuse','retard'];
                $saved = 0;
                foreach ($presences as $p) {
                    $uid   = (int)($p['utilisateur_id'] ?? 0);
                    $stat  = in_array($p['statut'] ?? '', $statuts) ? $p['statut'] : 'absent';
                    $note  = $p['note'] ?? null;
                    if (!$uid) continue;
                    // Upsert
                    $existing = dbFetchOne(
                        'SELECT id FROM presences WHERE session_id = ? AND utilisateur_id = ?',
                        [$sessId, $uid]
                    );
                    if ($existing) {
                        dbUpdate('presences', ['statut' => $stat, 'note' => $note], ['id' => $existing['id']]);
                    } else {
                        dbInsert('presences', [
                            'session_id'     => $sessId,
                            'utilisateur_id' => $uid,
                            'statut'         => $stat,
                            'note'           => $note,
                        ]);
                    }
                    $saved++;
                }
                dbInsert('audit_log', [
                    'utilisateur_id' => currentUserId(),
                    'action'         => 'maj_presences',
                    'table_cible'    => 'presences',
                    'id_cible'       => $sessId,
                    'details'        => json_encode(['nb_saved' => $saved]),
                    'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
                echo json_encode(['success' => true, 'message' => "{$saved} présences enregistrées.", 'saved' => $saved]);
            } else {
                // Single presence
                $sessId  = (int)($d['session_id']     ?? 0);
                $userId  = (int)($d['utilisateur_id'] ?? 0);
                $statut  = in_array($d['statut'] ?? '', ['present','absent','excuse','retard']) ? $d['statut'] : 'absent';
                $note    = $d['note'] ?? null;
                if (!$sessId || !$userId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'session_id et utilisateur_id requis.']);
                    exit;
                }
                $existing = dbFetchOne(
                    'SELECT id FROM presences WHERE session_id = ? AND utilisateur_id = ?',
                    [$sessId, $userId]
                );
                if ($existing) {
                    dbUpdate('presences', ['statut' => $statut, 'note' => $note], ['id' => $existing['id']]);
                    echo json_encode(['success' => true, 'message' => 'Présence mise à jour.', 'id' => $existing['id']]);
                } else {
                    $newId = dbInsert('presences', [
                        'session_id'     => $sessId,
                        'utilisateur_id' => $userId,
                        'statut'         => $statut,
                        'note'           => $note,
                    ]);
                    http_response_code(201);
                    echo json_encode(['success' => true, 'message' => 'Présence enregistrée.', 'id' => $newId]);
                }
            }
            break;

        case 'PUT':
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID requis.']); exit; }
            $d = json_decode(file_get_contents('php://input'), true) ?? [];
            $statut = in_array($d['statut'] ?? '', ['present','absent','excuse','retard']) ? $d['statut'] : null;
            if (!$statut) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Statut invalide.']); exit; }
            dbUpdate('presences', ['statut' => $statut, 'note' => $d['note'] ?? null], ['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Présence modifiée.']);
            break;

        case 'DELETE':
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID requis.']); exit; }
            dbDelete('presences', ['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Présence supprimée.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non supportée.']);
    }
} catch (PDOException $e) {
    error_log('[API presences] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
