<?php
/**
 * api/register.php — Inscription (AJAX POST)
 * Gère les 5 rôles : admin | coach | adherent | participant | visiteur
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$d = json_decode(file_get_contents('php://input'), true) ?? [];

// ── Champs communs ──
$nom      = trim($d['nom']      ?? '');
$prenom   = trim($d['prenom']   ?? '');
$email    = trim($d['email']    ?? '');
$password = trim($d['mot_de_passe'] ?? '');
$confirm  = trim($d['confirm_password'] ?? '');
$role     = trim($d['role']     ?? 'visiteur');
$tel      = trim($d['telephone'] ?? '');
$ville    = trim($d['ville']    ?? '');
$dob      = $d['date_naissance'] ?? null;
$sexe     = $d['sexe'] ?? null;

// ── Validation commune ──
$errors = [];
if (strlen($nom)    < 2) $errors[] = 'Le nom est requis (min. 2 caractères).';
if (strlen($prenom) < 2) $errors[] = 'Le prénom est requis (min. 2 caractères).';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
if (strlen($password) < 8) $errors[] = 'Mot de passe trop court (min. 8 caractères).';
if ($password !== $confirm) $errors[] = 'Les mots de passe ne correspondent pas.';

// ── Mapping rôle → role_id ──
$roleMap = ['admin' => 1, 'coach' => 2, 'adherent' => 3, 'participant' => 4, 'visiteur' => 5];
if (!array_key_exists($role, $roleMap)) $errors[] = 'Rôle invalide.';
$roleId = $roleMap[$role] ?? 5;

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── Vérifier codes secrets (admin/coach) ──
if ($role === 'admin') {
    $codeEntre = trim($d['code_admin'] ?? '');
    $codeDB = dbFetchOne(
        'SELECT id, usage_max, usage_count FROM codes_invitation
         WHERE code = ? AND role_id = 1 AND actif = 1
           AND (expire_at IS NULL OR expire_at > NOW())',
        [$codeEntre]
    );
    if (!$codeDB) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Code d\'invitation administrateur invalide ou expiré.']);
        exit;
    }
    if ($codeDB['usage_max'] && $codeDB['usage_count'] >= $codeDB['usage_max']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Ce code a atteint son nombre d\'utilisations maximum.']);
        exit;
    }
}
if ($role === 'coach') {
    $codeEntre = trim($d['code_coach'] ?? '');
    $codeDB = dbFetchOne(
        'SELECT id, usage_max, usage_count FROM codes_invitation
         WHERE code = ? AND role_id = 2 AND actif = 1
           AND (expire_at IS NULL OR expire_at > NOW())',
        [$codeEntre]
    );
    if (!$codeDB) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Code coach invalide ou expiré.']);
        exit;
    }
}

// ── Email unique ──
if (dbFetchOne('SELECT id FROM utilisateurs WHERE email = ?', [$email])) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé.']);
    exit;
}

// ── Statut selon rôle ──
// admin → en_attente (validation manuelle), autres → actif
$statut = ($role === 'admin') ? 'en_attente' : 'actif';

// ── Hash mot de passe ──
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    getPDO()->beginTransaction();

    // ── Créer l'utilisateur ──
    $userId = dbInsert('utilisateurs', [
        'nom'            => $nom,
        'prenom'         => $prenom,
        'email'          => $email,
        'mot_de_passe'   => $hash,
        'telephone'      => $tel ?: null,
        'date_naissance' => $dob ?: null,
        'sexe'           => in_array($sexe, ['M','F','Autre']) ? $sexe : null,
        'ville'          => $ville ?: null,
        'role_id'        => $roleId,
        'statut'         => $statut,
    ]);

    // ── Profil spécifique selon rôle ──
    switch ($role) {

        case 'admin':
            // Profil coach réutilisé pour stocker la fonction + justification
            dbInsert('profils_coach', [
                'utilisateur_id'      => $userId,
                'fonction'            => $d['fonction']      ?? null,
                'bio'                 => $d['justification'] ?? null,
                'statut_validation'   => 'en_attente',
            ]);
            // Incrémenter usage code
            if (!empty($codeDB)) {
                dbQuery('UPDATE codes_invitation SET usage_count = usage_count + 1 WHERE id = ?', [$codeDB['id']]);
            }
            break;

        case 'coach':
            $specs = $d['specialites'] ?? [];
            $profilId = dbInsert('profils_coach', [
                'utilisateur_id'    => $userId,
                'diplome'           => $d['diplome']      ?? null,
                'experience_ans'    => $d['experience']   ?? null,
                'bio'               => $d['bio']          ?? null,
                'disponibilites'    => !empty($d['disponibilites']) ? json_encode($d['disponibilites']) : null,
                'statut_validation' => 'valide', // coach avec code valide immédiatement
            ]);
            // Spécialités
            if (!empty($specs)) {
                foreach ($specs as $catId) {
                    $cat = dbFetchOne('SELECT id FROM categories WHERE id = ?', [(int)$catId]);
                    if ($cat) {
                        try {
                            dbInsert('coach_specialites', ['coach_id' => $profilId, 'categorie_id' => (int)$catId]);
                        } catch (Exception $e) { /* doublon ignoré */ }
                    }
                }
            }
            if (!empty($codeDB)) {
                dbQuery('UPDATE codes_invitation SET usage_count = usage_count + 1 WHERE id = ?', [$codeDB['id']]);
            }
            break;

        case 'adherent':
            $formule = in_array($d['formule_cotisation'] ?? '', ['mensuel','semestriel','annuel'])
                       ? $d['formule_cotisation'] : null;
            $montants = ['mensuel' => 500.00, 'semestriel' => 2500.00, 'annuel' => 4500.00];
            dbInsert('membres', [
                'utilisateur_id'      => $userId,
                'numero_licence'      => 'LIC-'.date('Y').'-'.str_pad((string)$userId, 4, '0', STR_PAD_LEFT),
                'date_adhesion'       => date('Y-m-d'),
                'formule_cotisation'  => $formule,
                'cotisation_payee'    => 'non',
                'montant_cotisation'  => $formule ? ($montants[$formule] ?? null) : null,
                'groupe_sanguin'      => $d['groupe_sanguin']    ?? null,
                'condition_medicale'  => $d['condition_medicale'] ?? null,
                'contact_urgence_nom' => $d['urgence_nom']       ?? null,
                'contact_urgence_tel' => $d['urgence_tel']       ?? null,
            ]);
            break;

        case 'participant':
            // Créer l'entrée membre de base
            $membreId = dbInsert('membres', [
                'utilisateur_id'   => $userId,
                'numero_licence'   => 'LIC-'.date('Y').'-'.str_pad((string)$userId, 4, '0', STR_PAD_LEFT),
                'date_adhesion'    => date('Y-m-d'),
                'cotisation_payee' => 'non',
                'groupe_sanguin'   => $d['groupe_sanguin'] ?? null,
                'contact_urgence_tel' => $d['urgence_tel'] ?? null,
            ]);
            // Disciplines choisies
            $disciplines = $d['disciplines'] ?? [];
            $niveaux     = $d['niveaux']     ?? [];
            foreach ($disciplines as $catId) {
                $cat = dbFetchOne('SELECT id FROM categories WHERE id = ?', [(int)$catId]);
                if ($cat) {
                    $niveau = in_array($niveaux[$catId] ?? '', ['debutant','intermediaire','avance','competiteur'])
                              ? $niveaux[$catId] : 'debutant';
                    try {
                        dbInsert('participant_disciplines', [
                            'utilisateur_id' => $userId,
                            'categorie_id'   => (int)$catId,
                            'niveau'         => $niveau,
                        ]);
                    } catch (Exception $e) { /* doublon ignoré */ }
                }
            }
            break;

        case 'visiteur':
            // Pas de profil supplémentaire pour un visiteur
            break;
    }

    // ── Notification admin (nouveaux inscrits) ──
    $admins = dbFetchAll('SELECT id FROM utilisateurs WHERE role_id = 1 AND statut = "actif"');
    foreach ($admins as $admin) {
        try {
            dbInsert('notifications', [
                'utilisateur_id' => $admin['id'],
                'type'           => 'inscription',
                'titre'          => 'Nouvelle inscription',
                'message'        => "{$prenom} {$nom} ({$role}) vient de s'inscrire.",
                'lien'           => '../admin/membres.html',
            ]);
        } catch (Exception $e) { /* non bloquant */ }
    }

    // ── Journal d'audit ──
    try {
        dbInsert('audit_log', [
            'utilisateur_id' => $userId,
            'action'         => 'inscription',
            'table_cible'    => 'utilisateurs',
            'id_cible'       => $userId,
            'details'        => json_encode(['role' => $role]),
            'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Exception $e) { /* non bloquant */ }

    getPDO()->commit();

    // ── Messages de succès selon rôle ──
    $messages = [
        'admin'       => 'Votre demande de compte administrateur a été soumise. Validation sous 48h ouvrables.',
        'coach'       => 'Compte Coach créé ! Vous pouvez vous connecter dès maintenant.',
        'adherent'    => 'Adhésion enregistrée ! Rendez-vous au secrétariat pour finaliser le paiement.',
        'participant' => 'Inscription confirmée ! Consultez le planning pour vos prochaines sessions.',
        'visiteur'    => 'Compte créé ! Bienvenue sur le site de l\'association.',
    ];

    $redirects = [
        'admin'       => 'login.html',
        'coach'       => 'login.html',
        'adherent'    => 'login.html',
        'participant' => 'login.html',
        'visiteur'    => 'login.html',
    ];

    http_response_code(201);
    echo json_encode([
        'success'  => true,
        'message'  => $messages[$role] ?? 'Compte créé avec succès !',
        'redirect' => $redirects[$role] ?? 'login.html',
        'role'     => $role,
        'statut'   => $statut,
    ]);

} catch (PDOException $e) {
    getPDO()->rollBack();
    error_log('[Register] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur. Veuillez réessayer.']);
}
