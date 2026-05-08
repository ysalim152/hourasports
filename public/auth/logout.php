<?php
/**
 * public/auth/logout.php
 * Déconnecte l'utilisateur et redirige vers login.
 */
require_once __DIR__ . '/../../includes/auth_check.php';

logoutUser();

header('Location: login.html');
exit;
