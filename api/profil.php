<?php
/**
 * api/profil.php — Mise à jour profil utilisateur connecté
 * GET  /api/profil.php         → données du profil connecté
 * PUT  /api/profil.php         → mettre à jour infos personnelles
 * PUT  /api/profil.php?action=password → changer mot de passe
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

requireAuth();
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = trim($_GET['action'] ?? '');
$userId = currentUserId();

try {
    switch ($method) {
        case 'GET':
            $user = dbFetchOne(
                'SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.sexe,
                        u.date_naissance, u.ville, u.avatar, u.statut, u.created_at,
                        r.nom AS role, r.label AS role_label, r.couleur AS role_couleur,
                        m.numero_licence, m.date_adhesion, m.formule_cotisation,
                        m.cotisation_payee, m.groupe_sanguin
                 FROM utilisateurs u
                 JOIN roles r ON r.id = u.role_id
                 LEFT JOIN membres m ON m.utilisateur_id = u.id
                 WHERE u.id = ?',
                [$userId]
            );
            if (!$user) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Utilisateur introuvable.']); exit; }

            // Disciplines (si participant)
            if ($user['role'] === 'participant') {
                $user['disciplines'] = dbFetchAll(
                    'SELECT c.nom, c.icone, pd.niveau FROM participant_disciplines pd
                     JOIN categories c ON c.id = pd.categorie_id
                     WHERE pd.utilisateur_id = ? AND pd.statut = "actif"',
                    [$userId]
                );
            }
            // Stats présences
            $user['stats_presences'] = dbFetchOne(
                'SELECT COUNT(*) AS total,
                        SUM(statut="present") AS presents,
                        SUM(statut="absent") AS absents
                 FROM presences WHERE utilisateur_id = ?',
                [$userId]
            );
            echo json_encode(['success'=>true,'data'=>$user]);
            break;

        case 'PUT':
            $d = json_decode(file_get_contents('php://input'), true) ?? [];

            if ($action === 'password') {
                $current = trim($d['mot_de_passe_actuel'] ?? '');
                $new     = trim($d['nouveau_mot_de_passe'] ?? '');
                $confirm = trim($d['confirmer_mot_de_passe'] ?? '');

                if (empty($current) || empty($new)) {
                    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Champs requis.']); exit;
                }
                if (strlen($new) < 8) {
                    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Minimum 8 caractères.']); exit;
                }
                if ($new !== $confirm) {
                    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Les mots de passe ne correspondent pas.']); exit;
                }

                $hash = dbFetchOne('SELECT mot_de_passe FROM utilisateurs WHERE id = ?', [$userId])['mot_de_passe'] ?? '';
                if (!password_verify($current, $hash)) {
                    http_response_code(403); echo json_encode(['success'=>false,'message'=>'Mot de passe actuel incorrect.']); exit;
                }

                dbUpdate('utilisateurs', ['mot_de_passe' => password_hash($new, PASSWORD_BCRYPT, ['cost'=>12])], ['id' => $userId]);
                dbInsert('audit_log', ['utilisateur_id'=>$userId,'action'=>'changement_mdp','table_cible'=>'utilisateurs','id_cible'=>$userId,'ip_address'=>$_SERVER['REMOTE_ADDR']??null]);
                echo json_encode(['success'=>true,'message'=>'Mot de passe mis à jour.']);
            } else {
                // Update general profile
                $updateData = [];
                $allowed = ['nom','prenom','telephone','ville','sexe','date_naissance'];
                foreach ($allowed as $field) {
                    if (array_key_exists($field, $d)) $updateData[$field] = $d[$field] ?: null;
                }
                if (!empty($updateData)) {
                    dbUpdate('utilisateurs', $updateData, ['id' => $userId]);
                }

                // Update membre-specific fields
                $membreData = [];
                $allowedMembre = ['groupe_sanguin','contact_urgence_nom','contact_urgence_tel','condition_medicale'];
                foreach ($allowedMembre as $field) {
                    if (array_key_exists($field, $d)) $membreData[$field] = $d[$field] ?: null;
                }
                if (!empty($membreData)) {
                    $membre = dbFetchOne('SELECT id FROM membres WHERE utilisateur_id = ?', [$userId]);
                    if ($membre) dbUpdate('membres', $membreData, ['utilisateur_id' => $userId]);
                }

                dbInsert('audit_log', ['utilisateur_id'=>$userId,'action'=>'maj_profil','table_cible'=>'utilisateurs','id_cible'=>$userId,'ip_address'=>$_SERVER['REMOTE_ADDR']??null]);
                echo json_encode(['success'=>true,'message'=>'Profil mis à jour.']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Méthode non supportée.']);
    }
} catch (PDOException $e) {
    error_log('[API profil] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Erreur serveur.']);
}
