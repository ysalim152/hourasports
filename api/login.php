<?php
/**
 * api/login.php — Authentification (AJAX POST)
 * Gère les 5 rôles : admin | coach | adherent | participant | visiteur
 * Retourne : { success, message, redirect, user }
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$email    = trim($body['email']    ?? '');
$password = trim($body['password'] ?? $body['mot_de_passe'] ?? '');

// ── Rate Limiting (sécurité) ──
session_start();
$ip = $_SERVER['REMOTE_ADDR'];
$_SESSION['login_attempts'][$ip] = $_SESSION['login_attempts'][$ip] ?? ['count' => 0, 'time' => time()];

if (time() - $_SESSION['login_attempts'][$ip]['time'] > 300) { // Reset after 5 minutes
    $_SESSION['login_attempts'][$ip] = ['count' => 0, 'time' => time()];
}

if ($_SESSION['login_attempts'][$ip]['count'] >= 5) {
    http_response_code(429); // Too Many Requests
    echo json_encode(['success' => false, 'message' => 'Trop de tentatives. Veuillez réessayer dans 5 minutes.']);
    exit;
}

$_SESSION['login_attempts'][$ip]['count']++;

// ── Validation basique ──
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email et mot de passe sont requis.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format email invalide.']);
    exit;
}

// ── Rechercher l'utilisateur avec son rôle ──
$user = dbFetchOne(
    'SELECT u.id, u.nom, u.prenom, u.email, u.mot_de_passe, u.statut, u.avatar,
            r.id AS role_id, r.nom AS role, r.label AS role_label, r.niveau_acces
     FROM utilisateurs u
     JOIN roles r ON r.id = u.role_id
     WHERE u.email = ?',
    [$email]
);

if (!$user) {
    http_response_code(401);
    session_write_close(); // Ferme la session avant de terminer
    echo json_encode(['success' => false, 'message' => 'Identifiants incorrects.']);
    exit;
}

// ── Vérifier statut ──
if ($user['statut'] === 'en_attente') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Votre compte est en attente de validation par l\'administration (délai 48h).',
    ]);
    exit;
}
if (in_array($user['statut'], ['inactif', 'suspendu'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Compte ' . $user['statut'] . '. Contactez l\'administration.',
    ]);
    exit;
}

// ── Vérifier mot de passe ──
if (!password_verify($password, $user['mot_de_passe'])) {
    // Note: le message d'erreur est volontairement le même que pour un email invalide
    http_response_code(401);
    session_write_close(); // Ferme la session avant de terminer
    echo json_encode(['success' => false, 'message' => 'Identifiants incorrects.']);
    exit;
}

// ── Créer la session PHP ──
// Succès, on réinitialise le compteur de tentatives
unset($_SESSION['login_attempts'][$ip]);
loginUser($user);

// ── Mettre à jour dernière connexion ──
dbQuery('UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?', [$user['id']]);

// ── Journal d'audit ──
try {
    dbInsert('audit_log', [
        'utilisateur_id' => $user['id'],
        'action'         => 'connexion',
        'table_cible'    => 'utilisateurs',
        'id_cible'       => $user['id'],
        'details'        => json_encode(['ip' => $_SERVER['REMOTE_ADDR'] ?? '']),
        'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
} catch (Exception $e) { /* non bloquant */ }

// ── Redirection selon rôle ──
// admin (1) et coach (2) → backoffice
// adherent (3), participant (4), visiteur (5) → espace membre
$redirect = match((int)$user['role_id']) {
    1, 2    => '../admin/dashboard.html',
    3, 4    => '../espace-membre.html',
    default => '../blog.html',
};

// Vérifier si le rôle admin/coach a un profil validé
$extraInfo = [];
if ((int)$user['role_id'] === 2) {
    $profil = dbFetchOne(
        'SELECT statut_validation FROM profils_coach WHERE utilisateur_id = ?',
        [$user['id']]
    );
    $extraInfo['coach_statut'] = $profil['statut_validation'] ?? 'inconnu';
}

echo json_encode([
    'success'  => true,
    'message'  => "Bienvenue, {$user['prenom']} !",
    'redirect' => $redirect,
    'user'     => [
        'id'          => (int)$user['id'],
        'nom'         => $user['nom'],
        'prenom'      => $user['prenom'],
        'email'       => $user['email'],
        'role'        => $user['role'],
        'niveau_acces'=> (int)$user['niveau_acces'],
        'role_label'  => $user['role_label'],
        'niveau_acces'=> (int)$user['niveau_acces'],
        'avatar'      => $user['avatar'],
    ] + $extraInfo,
]);
