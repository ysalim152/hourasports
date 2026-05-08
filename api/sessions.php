<?php
/**
 * api/sessions.php — CRUD Sessions d'entraînement
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
            if ($id) {
                $row = dbFetchOne(
                    'SELECT s.*, e.nom AS equipe, CONCAT(u.prenom," ",u.nom) AS coach_nom
                     FROM sessions_entrainement s
                     LEFT JOIN equipes e ON e.id = s.equipe_id
                     LEFT JOIN utilisateurs u ON u.id = s.coach_id
                     WHERE s.id = ?', [$id]
                );
                if (!$row) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Session introuvable']); exit; }
                echo json_encode(['success'=>true,'data'=>$row]);
            } else {
                $where = '1=1';
                $params = [];
                if (!empty($_GET['mois'])) {
                    $where .= ' AND DATE_FORMAT(s.date_debut,"%Y-%m") = ?';
                    $params[] = $_GET['mois'];
                }
                if (!empty($_GET['equipe_id'])) {
                    $where .= ' AND s.equipe_id = ?';
                    $params[] = (int)$_GET['equipe_id'];
                }
                $rows = dbFetchAll(
                    "SELECT s.id, s.titre, s.type, s.lieu, s.date_debut, s.date_fin, s.statut, s.capacite,
                            e.nom AS equipe, CONCAT(u.prenom,' ',u.nom) AS coach
                     FROM sessions_entrainement s
                     LEFT JOIN equipes e ON e.id = s.equipe_id
                     LEFT JOIN utilisateurs u ON u.id = s.coach_id
                     WHERE {$where}
                     ORDER BY s.date_debut ASC", $params
                );
                echo json_encode(['success'=>true,'data'=>$rows,'total'=>count($rows)]);
            }
            break;

        case 'POST':
            $d = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($d['titre']) || empty($d['date_debut']) || empty($d['date_fin'])) {
                http_response_code(400); echo json_encode(['success'=>false,'message'=>'Titre et dates sont requis.']); exit;
            }
            if (strtotime($d['date_fin']) <= strtotime($d['date_debut'])) {
                http_response_code(400); echo json_encode(['success'=>false,'message'=>'La date de fin doit être après le début.']); exit;
            }
            $newId = dbInsert('sessions_entrainement', [
                'titre'       => $d['titre'],
                'equipe_id'   => !empty($d['equipe_id']) ? (int)$d['equipe_id'] : null,
                'coach_id'    => !empty($d['coach_id']) ? (int)$d['coach_id'] : currentUserId(),
                'type'        => $d['type'] ?? 'entrainement',
                'lieu'        => $d['lieu'] ?? null,
                'date_debut'  => $d['date_debut'],
                'date_fin'    => $d['date_fin'],
                'description' => $d['description'] ?? null,
                'capacite'    => !empty($d['capacite']) ? (int)$d['capacite'] : null,
                'statut'      => $d['statut'] ?? 'planifie',
            ]);
            http_response_code(201);
            echo json_encode(['success'=>true,'message'=>'Session créée.','id'=>$newId]);
            break;

        case 'PUT':
            if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'ID requis.']); exit; }
            $d = json_decode(file_get_contents('php://input'), true) ?? [];
            dbUpdate('sessions_entrainement', [
                'titre'       => $d['titre'],
                'equipe_id'   => !empty($d['equipe_id']) ? (int)$d['equipe_id'] : null,
                'coach_id'    => !empty($d['coach_id']) ? (int)$d['coach_id'] : null,
                'type'        => $d['type'] ?? 'entrainement',
                'lieu'        => $d['lieu'] ?? null,
                'date_debut'  => $d['date_debut'],
                'date_fin'    => $d['date_fin'],
                'description' => $d['description'] ?? null,
                'capacite'    => !empty($d['capacite']) ? (int)$d['capacite'] : null,
                'statut'      => $d['statut'] ?? 'planifie',
            ], ['id' => $id]);
            echo json_encode(['success'=>true,'message'=>'Session mise à jour.']);
            break;

        case 'DELETE':
            if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'ID requis.']); exit; }
            dbDelete('presences', ['session_id' => $id]);
            dbDelete('sessions_entrainement', ['id' => $id]);
            echo json_encode(['success'=>true,'message'=>'Session supprimée.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Méthode non supportée.']);
    }
} catch (PDOException $e) {
    error_log('[API sessions] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Erreur serveur.']);
}
