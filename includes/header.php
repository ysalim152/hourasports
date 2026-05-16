<?php
/**
 * includes/header.php
 * Variables attendues avant l'include:
 *   $pageTitle  = "Titre de la page"
 *   $activePage = "blog|activites|apropos|contact|admin"
 *   $isAdmin    = true|false (optionnel)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle  = $pageTitle  ?? 'Association Sportive';
$activePage = $activePage ?? '';
$isAdmin    = $isAdmin    ?? false;
$rootPath   = $rootPath   ?? '../';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Association Sportive — Excellence, passion et esprit d'équipe.">
  <title><?= htmlspecialchars($pageTitle) ?> — Association Sportive</title>
  <link rel="icon" href="<?= $rootPath ?>assets/favicon.ico" type="image/x-icon">
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Style -->
  <link rel="stylesheet" href="<?= $rootPath ?>assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="<?= $rootPath ?>public/blog.html" class="nav-logo">
    <div class="logo-icon">🏆</div>
    AS<span>Club</span>
  </a>

  <ul class="nav-links" id="navLinks">
    <li><a href="<?= $rootPath ?>public/blog.html" class="<?= $activePage==='blog'?'active':'' ?>">Accueil</a></li>
    <li><a href="<?= $rootPath ?>public/activites.html" class="<?= $activePage==='activites'?'active':'' ?>">Activités</a></li>
    <li><a href="<?= $rootPath ?>public/apropos.html" class="<?= $activePage==='apropos'?'active':'' ?>">À Propos</a></li>
    <li><a href="<?= $rootPath ?>public/contact.html" class="<?= $activePage==='contact'?'active':'' ?>">Contact</a></li>
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php
            $userPrenom = htmlspecialchars($_SESSION['user_prenom'] ?? 'Membre');
            $userRole = $_SESSION['user_role'] ?? 'visiteur';
            $espaceUrl = ($userRole === 'admin' || $userRole === 'coach')
                ? "{$rootPath}public/admin/profil.html"
                : "{$rootPath}public/espace-membre.html";
        ?>
        <li class="nav-item-dropdown">
            <a href="#" 
               class="nav-link-dropdown-toggle" 
               id="user-menu-button-php" 
               aria-haspopup="true" 
               aria-expanded="false" 
               aria-controls="user-menu-php">
                👤 Bonjour, <?= $userPrenom ?> <i class="fas fa-chevron-down" style="font-size: 0.7em; margin-left: 0.3rem;"></i>
            </a>
            <ul class="dropdown-menu" id="user-menu-php" role="menu" aria-labelledby="user-menu-button-php">
                <li role="none"><a href="<?= $espaceUrl ?>" role="menuitem"><i class="fas fa-user-circle" style="width:14px"></i> Mon Profil</a></li>
                <?php if ($userRole === 'admin' || $userRole === 'coach'): ?>
                    <li role="none"><a href="<?= $rootPath ?>public/admin/dashboard.html" role="menuitem"><i class="fas fa-tachometer-alt" style="width:14px"></i> Tableau de bord</a></li>
                <?php endif; ?>
                <li role="none"><hr class="dropdown-divider" role="separator"></li>
                <li role="none">
                    <a href="<?= $rootPath ?>public/auth/logout.php" role="menuitem" style="color: var(--danger);">
                        <i class="fas fa-sign-out-alt" style="width:14px"></i> Déconnexion
                    </a>
                </li>
            </ul>
        </li>
    <?php else: ?>
        <li><a href="<?= $rootPath ?>public/auth/login.html" class="nav-cta">Connexion</a></li>
    <?php endif; ?>
  </ul>

  <button class="hamburger" id="hamburger" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
</nav>
