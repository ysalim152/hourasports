<?php
/**
 * api/contact.php — Formulaire contact public + gestion messages admin
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = trim($_GET['action'] ?? '');

try {
    /* ── ENVOI PUBLIC (POST sans auth) ── */
    if ($method === 'POST' && !isLoggedIn()) {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $nom     = trim($d['nom']     ?? '');
        $email   = trim($d['email']   ?? '');
        $sujet   = trim($d['sujet']   ?? '');
        $message = trim($d['message'] ?? '');

        if (strlen($nom) < 2) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'Nom invalide (min. 2 caractères).']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'Email invalide.']);
            exit;
        }
        if (strlen($message) < 20) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'Message trop court (min. 20 caractères).']);
            exit;
        }

        // Anti-spam : 1 message / 5 minutes par IP
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $rateLimitMinutes = (int)getParam('contact_rate_limit_min', '5');
        $recent = dbFetchOne(
            "SELECT id FROM contacts WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL {$rateLimitMinutes} MINUTE)",
            [$ip]
        );
        if ($recent) {
            http_response_code(429);
            echo json_encode(['success'=>false,'message'=>'Veuillez patienter avant de renvoyer un message.']);
            exit;
        }

        $newId = dbInsert('contacts', [
            'nom'        => htmlspecialchars($nom,     ENT_QUOTES, 'UTF-8'),
            'email'      => $email,
            'sujet'      => htmlspecialchars($sujet,   ENT_QUOTES, 'UTF-8'),
            'message'    => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            'statut'     => 'nouveau',
            'ip_address' => $ip,
        ]);

        // Notifier les admins
        try {
            $admins = dbFetchAll('SELECT id FROM utilisateurs WHERE role_id = 1 AND statut = "actif"');
            foreach ($admins as $admin) {
                dbInsert('notifications', [
                    'utilisateur_id' => $admin['id'],
                    'type'           => 'message_nouveau',
                    'titre'          => 'Nouveau message de contact',
                    'message'        => "{$nom} : " . mb_substr($message, 0, 60) . '…',
                    'lien'           => '../admin/messages.html',
                ]);
            }
        } catch (Exception $e) { /* non bloquant */ }

        http_response_code(201);
        echo json_encode(['success'=>true,'message'=>'Message envoyé ! Nous vous répondrons sous 24h.','id'=>$newId]);
        exit;
    }

    /* ── ROUTES ADMIN (auth requise) ── */
    requireRole('coach');

    switch ($method) {

        case 'GET':
            if ($id) {
                $row = dbFetchOne('SELECT * FROM contacts WHERE id = ?', [$id]);
                if (!$row) {
                    http_response_code(404);
                    echo json_encode(['success'=>false,'message'=>'Message introuvable.']);
                    exit;
                }
                if ($row['statut'] === 'nouveau') {
                    dbUpdate('contacts', ['statut'=>'lu'], ['id'=>$id]);
                    $row['statut'] = 'lu';
                }
                echo json_encode(['success'=>true,'data'=>$row]);
            } else {
                $statut = trim($_GET['statut'] ?? '');
                $q      = trim($_GET['q']      ?? '');
                $where  = []; $params = [];
                if ($statut && $statut !== 'all') { $where[]='statut = ?'; $params[]=$statut; }
                if ($q) {
                    $where[] = '(nom LIKE ? OR email LIKE ? OR sujet LIKE ? OR message LIKE ?)';
                    $like = "%{$q}%";
                    $params = array_merge($params, [$like,$like,$like,$like]);
                }
                $w = $where ? 'WHERE '.implode(' AND ',$where) : '';
                $rows = dbFetchAll(
                    "SELECT id,nom,email,sujet,message,statut,reponse,created_at
                     FROM contacts {$w} ORDER BY created_at DESC",
                    $params
                );
                $stats = [
                    'nouveau' => dbCount('contacts',['statut'=>'nouveau']),
                    'lu'      => dbCount('contacts',['statut'=>'lu']),
                    'repondu' => dbCount('contacts',['statut'=>'repondu']),
                    'total'   => dbCount('contacts'),
                ];
                echo json_encode(['success'=>true,'data'=>$rows,'stats'=>$stats]);
            }
            break;

        case 'PUT':
            if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'ID requis.']); exit; }
            $d = json_decode(file_get_contents('php://input'), true) ?? [];

            if ($action === 'reponse') {
                $reponse = trim($d['reponse'] ?? '');
                if (empty($reponse)) {
                    http_response_code(400);
                    echo json_encode(['success'=>false,'message'=>'La réponse est vide.']);
                    exit;
                }
                dbUpdate('contacts', ['statut'=>'repondu','reponse'=>$reponse], ['id'=>$id]);
                dbInsert('audit_log', [
                    'utilisateur_id' => currentUserId(),
                    'action'         => 'reponse_contact',
                    'table_cible'    => 'contacts',
                    'id_cible'       => $id,
                    'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
                echo json_encode(['success'=>true,'message'=>'Réponse envoyée.']);
            } else {
                $nouveauStatut = $d['statut'] ?? '';
                if (!in_array($nouveauStatut, ['nouveau','lu','repondu','archive'])) {
                    http_response_code(400);
                    echo json_encode(['success'=>false,'message'=>'Statut invalide.']);
                    exit;
                }
                dbUpdate('contacts', ['statut'=>$nouveauStatut], ['id'=>$id]);
                echo json_encode(['success'=>true,'message'=>'Statut mis à jour.']);
            }
            break;

        case 'DELETE':
            requireRole('admin');
            if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'ID requis.']); exit; }
            dbDelete('contacts', ['id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'Message supprimé.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Méthode non supportée.']);
    }

} catch (PDOException $e) {
    error_log('[API contact] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Erreur serveur.']);
}
