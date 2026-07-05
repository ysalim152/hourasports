<?php
/**
 * api/membres.php — CRUD Membres (admin/coach)
 * Gère adhérents (role_id=3) ET participants (role_id=4)
 * GET    /api/membres.php              → liste
 * GET    /api/membres.php?id=X         → un membre
 * POST   /api/membres.php              → créer
 * PUT    /api/membres.php?id=X         → modifier
 * DELETE /api/membres.php?id=X         → supprimer
 * GET    /api/membres.php?action=stats → statistiques
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

requireRole('coach');
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id'])     ? (int)$_GET['id']     : null;
$action = $_GET['action'] ?? '';

try {

    // ── STATS ──
    if ($action === 'stats' && $method === 'GET') {
        $stats = [
            'total'        => (int)(dbFetchOne('SELECT COUNT(*) c FROM utilisateurs WHERE role_id IN (3,4) AND statut="actif"')['c'] ?? 0),
            'adherents'    => (int)(dbFetchOne('SELECT COUNT(*) c FROM utilisateurs WHERE role_id=3 AND statut="actif"')['c'] ?? 0),
            'participants' => (int)(dbFetchOne('SELECT COUNT(*) c FROM utilisateurs WHERE role_id=4 AND statut="actif"')['c'] ?? 0),
            'cotisations_ok' => (int)(dbFetchOne('SELECT COUNT(*) c FROM membres WHERE cotisation_payee="oui"')['c'] ?? 0),
            'cotisations_nok'=> (int)(dbFetchOne('SELECT COUNT(*) c FROM membres WHERE cotisation_payee="non"')['c'] ?? 0),
            'en_attente'   => (int)(dbFetchOne('SELECT COUNT(*) c FROM utilisateurs WHERE statut="en_attente"')['c'] ?? 0),
            'par_discipline' => dbFetchAll(
                'SELECT c.nom, c.icone, COUNT(pd.id) AS total
                 FROM categories c
                 LEFT JOIN participant_disciplines pd ON pd.categorie_id = c.id AND pd.statut="actif"
                 GROUP BY c.id ORDER BY total DESC'
            ),
        ];
        echo json_encode(['success' => true, 'data' => $stats]);
        exit;
    }

    switch ($method) {

        // ── LIST / ONE ──
        case 'GET':
            if ($id) {
                $row = dbFetchOne(
                    'SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.sexe,
                            u.date_naissance, u.ville, u.statut, u.created_at,
                            r.nom AS role, r.label AS role_label,
                            m.id AS membre_id, m.numero_licence, m.date_adhesion,
                            m.formule_cotisation, m.cotisation_payee, m.montant_cotisation,
                            m.groupe_sanguin, m.condition_medicale,
                            m.contact_urgence_nom, m.contact_urgence_tel, m.notes,
                            e.nom AS equipe, c.nom AS categorie
                     FROM utilisateurs u
                     JOIN roles r ON r.id = u.role_id
                     LEFT JOIN membres m ON m.utilisateur_id = u.id
                     LEFT JOIN equipes e ON e.id = m.equipe_id
                     LEFT JOIN categories c ON c.id = e.categorie_id
                     WHERE u.id = ? AND u.role_id IN (3,4)',
                    [$id]
                );
                if (!$row) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Membre introuvable.']);
                    exit;
                }
                // Disciplines si participant
                if ($row['role'] === 'participant') {
                    $row['disciplines'] = dbFetchAll(
                        'SELECT c.id, c.nom, c.icone, pd.niveau, pd.date_inscription, pd.statut
                         FROM participant_disciplines pd
                         JOIN categories c ON c.id = pd.categorie_id
                         WHERE pd.utilisateur_id = ?',
                        [$id]
                    );
                }
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                $search  = trim($_GET['q']      ?? '');
                $role    = trim($_GET['role']    ?? '');
                $statut  = trim($_GET['statut']  ?? '');
                $cotis   = trim($_GET['cotisation'] ?? '');
                $equipe  = trim($_GET['equipe']  ?? '');
                $page    = max(1, (int)($_GET['page']    ?? 1));
                $perPage = max(5, min(100, (int)($_GET['per_page'] ?? 25)));
                $offset  = ($page - 1) * $perPage;

                $where  = ['u.role_id IN (3,4)'];
                $params = [];

                if ($search) {
                    $where[]  = '(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR m.numero_licence LIKE ?)';
                    $like     = "%{$search}%";
                    $params   = array_merge($params, [$like,$like,$like,$like]);
                }
                if ($role)   { $where[] = 'r.nom = ?';       $params[] = $role; }
                if ($statut) { $where[] = 'u.statut = ?';    $params[] = $statut; }
                if ($cotis)  { $where[] = 'm.cotisation_payee = ?'; $params[] = $cotis; }
                if ($equipe) { $where[] = 'e.nom = ?';       $params[] = $equipe; }

                $whereSQL = 'WHERE ' . implode(' AND ', $where);

                $total = (int)(dbFetchOne(
                    "SELECT COUNT(*) c
                     FROM utilisateurs u
                     JOIN roles r ON r.id = u.role_id
                     LEFT JOIN membres m ON m.utilisateur_id = u.id
                     LEFT JOIN equipes e ON e.id = m.equipe_id
                     {$whereSQL}",
                    $params
                )['c'] ?? 0);

                $rows = dbFetchAll(
                    "SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.ville, u.statut, u.created_at,
                            r.nom AS role, r.label AS role_label, r.couleur AS role_couleur, r.icone AS role_icone,
                            m.numero_licence, m.date_adhesion, m.formule_cotisation,
                            m.cotisation_payee, m.montant_cotisation,
                            e.nom AS equipe
                     FROM utilisateurs u
                     JOIN roles r ON r.id = u.role_id
                     LEFT JOIN membres m ON m.utilisateur_id = u.id
                     LEFT JOIN equipes e ON e.id = m.equipe_id
                     {$whereSQL}
                     ORDER BY u.created_at DESC
                     LIMIT {$perPage} OFFSET {$offset}",
                    $params
                );

                echo json_encode([
                    'success'   => true,
                    'data'      => $rows,
                    'total'     => $total,
                    'page'      => $page,
                    'per_page'  => $perPage,
                    'pages'     => (int)ceil($total / $perPage),
                ]);
            }
            break;

        // ── CREATE ──
        case 'POST':
            requireRole('admin');
            $d = json_decode(file_get_contents('php://input'), true) ?? [];

            if (empty($d['nom']) || empty($d['prenom']) || empty($d['email'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nom, prénom et email sont requis.']);
                exit;
            }
            if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Email invalide.']);
                exit;
            }
            if (dbFetchOne('SELECT id FROM utilisateurs WHERE email = ?', [$d['email']])) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Email déjà utilisé.']);
                exit;
            }

            $roleId  = in_array((int)($d['role_id'] ?? 4), [3,4]) ? (int)$d['role_id'] : 4;
            $pwd     = $d['mot_de_passe'] ?? bin2hex(random_bytes(6));
            $userId  = dbInsert('utilisateurs', [
                'nom'           => $d['nom'],
                'prenom'        => $d['prenom'],
                'email'         => $d['email'],
                'mot_de_passe'  => password_hash($pwd, PASSWORD_BCRYPT),
                'telephone'     => $d['telephone'] ?? null,
                'date_naissance'=> $d['date_naissance'] ?? null,
                'sexe'          => $d['sexe'] ?? null,
                'ville'         => $d['ville'] ?? null,
                'role_id'       => $roleId,
                'statut'        => 'actif',
            ]);

            dbInsert('membres', [
                'utilisateur_id'      => $userId,
                'equipe_id'           => !empty($d['equipe_id']) ? (int)$d['equipe_id'] : null,
                'numero_licence'      => $d['numero_licence'] ?? 'LIC-'.date('Y').'-'.str_pad((string)$userId, 4, '0', STR_PAD_LEFT),
                'date_adhesion'       => $d['date_adhesion'] ?? date('Y-m-d'),
                'formule_cotisation'  => $d['formule_cotisation'] ?? null,
                'cotisation_payee'    => $d['cotisation_payee'] ?? 'non',
                'montant_cotisation'  => !empty($d['montant_cotisation']) ? (float)$d['montant_cotisation'] : null,
                'groupe_sanguin'      => $d['groupe_sanguin'] ?? null,
                'contact_urgence_nom' => $d['contact_urgence_nom'] ?? null,
                'contact_urgence_tel' => $d['contact_urgence_tel'] ?? null,
                'notes'               => $d['notes'] ?? null,
            ]);

            dbInsert('audit_log', [
                'utilisateur_id' => currentUserId(),
                'action'         => 'creation_membre',
                'table_cible'    => 'utilisateurs',
                'id_cible'       => $userId,
                'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Membre créé.', 'id' => $userId]);
            break;

        // ── UPDATE ──
        case 'PUT':
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID requis.']); exit; }
            // Only admins should be able to update member profiles.
            // Coaches might have read-only access or access to a subset of fields,
            // but full PUT access is a high privilege.
            requireRole('admin');
            $d = json_decode(file_get_contents('php://input'), true) ?? [];

            dbUpdate('utilisateurs', [
                'nom'           => $d['nom'],
                'prenom'        => $d['prenom'],
                'telephone'     => $d['telephone'] ?? null,
                'sexe'          => $d['sexe'] ?? null,
                'ville'         => $d['ville'] ?? null,
                'statut'        => $d['statut'] ?? 'actif',
            ], ['id' => $id]);

            // Vérifier si une entrée membres existe
            $existsMembre = dbFetchOne('SELECT id FROM membres WHERE utilisateur_id = ?', [$id]);
            if ($existsMembre) {
                dbUpdate('membres', [
                    'equipe_id'           => !empty($d['equipe_id']) ? (int)$d['equipe_id'] : null,
                    'formule_cotisation'  => $d['formule_cotisation'] ?? null,
                    'cotisation_payee'    => $d['cotisation_payee'] ?? 'non',
                    'groupe_sanguin'      => $d['groupe_sanguin'] ?? null,
                    'contact_urgence_nom' => $d['contact_urgence_nom'] ?? null,
                    'contact_urgence_tel' => $d['contact_urgence_tel'] ?? null,
                    'notes'               => $d['notes'] ?? null,
                ], ['utilisateur_id' => $id]);
            }

            dbInsert('audit_log', [
                'utilisateur_id' => currentUserId(),
                'action'         => 'modification_membre',
                'table_cible'    => 'utilisateurs',
                'id_cible'       => $id,
                'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            echo json_encode(['success' => true, 'message' => 'Membre mis à jour.']);
            break;

        // ── DELETE ──
        case 'DELETE':
            requireRole('admin');
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID requis.']); exit; }

            // Sauvegarder pour log avant suppression
            $userToDelete = dbFetchOne('SELECT nom, prenom, email FROM utilisateurs WHERE id = ?', [$id]);
            dbDelete('utilisateurs', ['id' => $id]); // cascade FK

            dbInsert('audit_log', [
                'utilisateur_id' => currentUserId(),
                'action'         => 'suppression_membre',
                'table_cible'    => 'utilisateurs',
                'id_cible'       => $id,
                'details'        => json_encode($userToDelete),
                'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            echo json_encode(['success' => true, 'message' => 'Membre supprimé.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non supportée.']);
    }

} catch (PDOException $e) {
    error_log('[API membres] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
