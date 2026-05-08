<?php
/**
 * includes/auth_check.php
 * Inclure en haut de chaque page protégée.
 * Usage:
 *   require_once __DIR__ . '/../includes/auth_check.php';
 *   requireAuth();         // redirige si non connecté
 *   requireRole('admin');  // redirige si mauvais rôle
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

/**
 * Vérifie que l'utilisateur est connecté.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Retourne le rôle de l'utilisateur connecté.
 */
function currentRole(): string {
    return $_SESSION['user_role'] ?? 'visiteur';
}

/**
 * Retourne l'ID de l'utilisateur connecté.
 */
function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Redirige vers login si non authentifié.
 */
function requireAuth(string $redirect = '/public/auth/login.html'): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Vérifie que l'utilisateur a le rôle requis.
 */
function requireRole(string $role, string $redirect = '/public/index.html'): void {
    requireAuth();
    $hierarchy = ['visiteur' => 0, 'membre' => 1, 'coach' => 2, 'admin' => 3];
    $current = $hierarchy[currentRole()] ?? 0;
    $required = $hierarchy[$role] ?? 99;
    if ($current < $required) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Définit la session utilisateur après connexion réussie.
 */
function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_nom']   = $user['nom'];
    $_SESSION['user_prenom']= $user['prenom'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'] ?? 'membre';
    $_SESSION['logged_at']  = time();
}

/**
 * Déconnecte l'utilisateur.
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Retourne une réponse JSON standardisée.
 */
function jsonResponse(bool $success, string $message = '', array $data = [], int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

/**
 * Vérifie le token CSRF.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
/**
 * Vérifie si l'utilisateur a un niveau d'accès suffisant.
 * admin=4, coach=3, adherent=2, participant=1, visiteur=0
 */
function hasAccess(int $minNiveau): bool {
    $niveaux = ['admin' => 4, 'coach' => 3, 'adherent' => 2, 'participant' => 1, 'visiteur' => 0];
    return ($niveaux[currentRole()] ?? 0) >= $minNiveau;
}

/**
 * Retourne true si l'utilisateur a exactement ce rôle.
 */
function hasRole(string $role): bool {
    return currentRole() === $role;
}

/**
 * Retourne les infos de session complètes.
 */
function currentUser(): array {
    if (!isLoggedIn()) return [];
    return [
        'id'     => $_SESSION['user_id']     ?? null,
        'nom'    => $_SESSION['user_nom']    ?? '',
        'prenom' => $_SESSION['user_prenom'] ?? '',
        'email'  => $_SESSION['user_email']  ?? '',
        'role'   => $_SESSION['user_role']   ?? 'visiteur',
    ];
}
