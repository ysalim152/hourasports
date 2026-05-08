<?php
/**
 * api/planning.php — CRUD Planning / Événements calendrier
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

requireRole('coach');
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    switch ($method) {
        case 'GET':
            $params = [];
            $where  = '1=1';
            if (!empty($_GET['debut']) && !empty($_GET['fin'])) {
                $where .= ' AND p.date_debut BETWEEN ? AND ?';
                $params[] = $_GET['debut'];
                $params[] = $_GET['fin'];
            }
            if (!empty($_GET['equipe_id'])) {
                $where .= ' AND (p.equipe_id = ? OR p.equipe_id IS NULL)';
                $params[] = (int)$_GET['equipe_id'];
            }
            if ($id) {
                $where = 'p.id = ?';
                $params = [$id];
            }
            $rows = dbFetchAll(
                "SELECT p.id, p.titre, p.description, p.couleur, p.date_debut, p.date_fin,
                        p.recurrence, p.lieu,
                        e.nom AS equipe, s.titre AS session_titre
                 FROM planning p
                 LEFT JOIN equipes e ON e.id = p.equipe_id
                 LEFT JOIN sessions_entrainement s ON s.id = p.session_id
                 WHERE {$where}
                 ORDER BY p.date_debut ASC", $params
            );
            $result = $id ? ($rows[0] ?? null) : $rows;
            if ($id && !$result) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Événement introuvable']); exit; }
            echo json_encode(['success'=>true,'data'=>$result]);
            break;

        case 'POST':
            $d = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($d['titre']) || empty($d['date_debut']) || empty($d['date_fin'])) {
                http_response_code(400); echo json_encode(['success'=>false,'message'=>'Titre et dates sont requis.']); exit;
            }
            $newId = dbInsert('planning', [
                'titre'       => $d['titre'],
                'description' => $d['description'] ?? null,
                'session_id'  => !empty($d['session_id']) ? (int)$d['session_id'] : null,
                'equipe_id'   => !empty($d['equipe_id']) ? (int)$d['equipe_id'] : null,
                'couleur'     => $d['couleur'] ?? '#e63946',
                'date_debut'  => $d['date_debut'],
                'date_fin'    => $d['date_fin'],
                'recurrence'  => $d['recurrence'] ?? 'aucune',
                'lieu'        => $d['lieu'] ?? null,
                'created_by'  => currentUserId(),
            ]);
            http_response_code(201);
            echo json_encode(['success'=>true,'message'=>'Événement créé.','id'=>$newId]);
            break;

        case 'PUT':
            if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'ID requis.']); exit; }
            $d = json_decode(file_get_contents('php://input'), true) ?? [];
            dbUpdate('planning', [
                'titre'       => $d['titre'],
                'description' => $d['description'] ?? null,
                'equipe_id'   => !empty($d['equipe_id']) ? (int)$d['equipe_id'] : null,
                'couleur'     => $d['couleur'] ?? '#e63946',
                'date_debut'  => $d['date_debut'],
                'date_fin'    => $d['date_fin'],
                'recurrence'  => $d['recurrence'] ?? 'aucune',
                'lieu'        => $d['lieu'] ?? null,
            ], ['id' => $id]);
            echo json_encode(['success'=>true,'message'=>'Événement mis à jour.']);
            break;

        case 'DELETE':
            if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'ID requis.']); exit; }
            dbDelete('planning', ['id' => $id]);
            echo json_encode(['success'=>true,'message'=>'Événement supprimé.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Méthode non supportée.']);
    }
} catch (PDOException $e) {
    error_log('[API planning] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Erreur serveur.']);
}
