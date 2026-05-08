<?php
/**
 * api/equipes.php — CRUD Équipes
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
                    'SELECT e.*, c.nom AS categorie_nom, c.icone,
                            CONCAT(u.prenom," ",u.nom) AS coach_nom,
                            COUNT(m.id) AS nb_membres
                     FROM equipes e
                     LEFT JOIN categories c ON c.id = e.categorie_id
                     LEFT JOIN utilisateurs u ON u.id = e.coach_id
                     LEFT JOIN membres m ON m.equipe_id = e.id
                     WHERE e.id = ?
                     GROUP BY e.id', [$id]
                );
                if (!$row) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Équipe introuvable']); exit; }
                echo json_encode(['success'=>true,'data'=>$row]);
            } else {
                $rows = dbFetchAll(
                    'SELECT e.id, e.nom, e.effectif_max, e.statut, e.annee_creation,
                            c.nom AS categorie, c.icone,
                            CONCAT(u.prenom," ",u.nom) AS coach,
                            COUNT(m.id) AS nb_membres
                     FROM equipes e
                     LEFT JOIN categories c ON c.id = e.categorie_id
                     LEFT JOIN utilisateurs u ON u.id = e.coach_id
                     LEFT JOIN membres m ON m.equipe_id = e.id
                     GROUP BY e.id
                     ORDER BY e.statut ASC, c.nom ASC, e.nom ASC'
                );
                echo json_encode(['success'=>true,'data'=>$rows]);
            }
            break;

        case 'POST':
            $d = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($d['nom']) || empty($d['categorie_id'])) {
                http_response_code(400); echo json_encode(['success'=>false,'message'=>'Nom et catégorie sont requis.']); exit;
            }
            $newId = dbInsert('equipes', [
                'nom'           => $d['nom'],
                'categorie_id'  => (int)$d['categorie_id'],
                'coach_id'      => !empty($d['coach_id']) ? (int)$d['coach_id'] : null,
                'description'   => $d['description'] ?? null,
                'effectif_max'  => (int)($d['effectif_max'] ?? 20),
                'statut'        => $d['statut'] ?? 'actif',
                'annee_creation'=> !empty($d['annee_creation']) ? (int)$d['annee_creation'] : null,
            ]);
            http_response_code(201);
            echo json_encode(['success'=>true,'message'=>'Équipe créée.','id'=>$newId]);
            break;

        case 'PUT':
            if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'ID requis.']); exit; }
            $d = json_decode(file_get_contents('php://input'), true) ?? [];
            dbUpdate('equipes', [
                'nom'           => $d['nom'],
                'categorie_id'  => (int)$d['categorie_id'],
                'coach_id'      => !empty($d['coach_id']) ? (int)$d['coach_id'] : null,
                'description'   => $d['description'] ?? null,
                'effectif_max'  => (int)($d['effectif_max'] ?? 20),
                'statut'        => $d['statut'] ?? 'actif',
                'annee_creation'=> !empty($d['annee_creation']) ? (int)$d['annee_creation'] : null,
            ], ['id' => $id]);
            echo json_encode(['success'=>true,'message'=>'Équipe mise à jour.']);
            break;

        case 'DELETE':
            if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'ID requis.']); exit; }
            // Désaffecter les membres avant suppression
            dbQuery('UPDATE membres SET equipe_id = NULL WHERE equipe_id = ?', [$id]);
            dbDelete('equipes', ['id' => $id]);
            echo json_encode(['success'=>true,'message'=>'Équipe supprimée.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Méthode non supportée.']);
    }
} catch (PDOException $e) {
    error_log('[API equipes] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Erreur serveur.']);
}
