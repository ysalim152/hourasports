<?php
/**
 * api/parametres.php — Lecture/écriture des paramètres système
 * GET  /api/parametres.php          → tous les paramètres
 * GET  /api/parametres.php?cle=X    → un paramètre
 * PUT  /api/parametres.php          → mettre à jour (body JSON {cle:valeur,...})
 * POST /api/parametres.php          → créer ou mettre à jour un paramètre
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

requireRole('admin');
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

// Paramètres interdits à l'édition via API (sécurité)
const LOCKED_KEYS = ['code_admin', 'code_coach'];

try {
    switch ($method) {
        case 'GET':
            $cle = trim($_GET['cle'] ?? '');
            if ($cle) {
                $row = dbFetchOne('SELECT cle, valeur, description, updated_at FROM parametres WHERE cle = ?', [$cle]);
                if (!$row) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Paramètre introuvable.']); exit; }
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                $rows = dbFetchAll('SELECT cle, valeur, description, updated_at FROM parametres ORDER BY cle');
                // Masquer les codes secrets
                foreach ($rows as &$r) {
                    if (in_array($r['cle'], LOCKED_KEYS)) $r['valeur'] = '••••••••';
                }
                echo json_encode(['success' => true, 'data' => $rows]);
            }
            break;

        case 'PUT':
        case 'POST':
            $d = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($d)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Corps vide.']); exit; }

            $updated = 0;
            foreach ($d as $cle => $valeur) {
                $cle = trim($cle);
                if (empty($cle) || in_array($cle, LOCKED_KEYS)) continue;
                $existing = dbFetchOne('SELECT cle FROM parametres WHERE cle = ?', [$cle]);
                if ($existing) {
                    dbUpdate('parametres', ['valeur' => (string)$valeur], ['cle' => $cle]);
                } else {
                    try {
                        dbQuery('INSERT INTO parametres (cle, valeur) VALUES (?, ?)', [$cle, (string)$valeur]);
                    } catch (PDOException $e) { /* doublon ignoré */ }
                }
                $updated++;
            }
            dbInsert('audit_log', [
                'utilisateur_id' => currentUserId(),
                'action'         => 'maj_parametres',
                'table_cible'    => 'parametres',
                'details'        => json_encode(['keys_updated' => $updated]),
                'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            echo json_encode(['success' => true, 'message' => "{$updated} paramètre(s) mis à jour."]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non supportée.']);
    }
} catch (PDOException $e) {
    error_log('[API parametres] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
