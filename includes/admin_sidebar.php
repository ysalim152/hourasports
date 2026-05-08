<!-- ============================================================
     includes/admin_sidebar.php
     Sidebar admin unifiée — inclure dans chaque page admin
     Variable requise : $activePage (ex: 'dashboard', 'membres'...)
     ============================================================ -->
<?php $activePage = $activePage ?? ''; ?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">⚙️ Backoffice</div>
  <nav class="sidebar-nav">

    <div class="sidebar-section">Principal</div>
    <a href="dashboard.html"  class="sidebar-link <?= $activePage==='dashboard'  ?'active':'' ?>"><span class="icon">📊</span> Dashboard</a>
    <a href="../blog.html"    class="sidebar-link"><span class="icon">🌐</span> Voir le Site</a>

    <div class="sidebar-section">Gestion</div>
    <a href="membres.html"    class="sidebar-link <?= $activePage==='membres'    ?'active':'' ?>"><span class="icon">👥</span> Membres</a>
    <a href="equipes.html"    class="sidebar-link <?= $activePage==='equipes'    ?'active':'' ?>"><span class="icon">🏆</span> Équipes</a>
    <a href="sessions.html"   class="sidebar-link <?= $activePage==='sessions'   ?'active':'' ?>"><span class="icon">🎯</span> Sessions</a>
    <a href="presences.html"  class="sidebar-link <?= $activePage==='presences'  ?'active':'' ?>"><span class="icon">✅</span> Présences</a>
    <a href="planning.html"   class="sidebar-link <?= $activePage==='planning'   ?'active':'' ?>"><span class="icon">📅</span> Planning</a>

    <div class="sidebar-section">Contenu</div>
    <a href="actualites.html" class="sidebar-link <?= $activePage==='actualites' ?'active':'' ?>"><span class="icon">📰</span> Actualités</a>
    <a href="messages.html"   class="sidebar-link <?= $activePage==='messages'   ?'active':'' ?>">
      <span class="icon">📬</span> Messages
      <span class="badge-count" id="msgBadge" style="display:none">0</span>
    </a>

    <div class="sidebar-section">Compte</div>
    <a href="profil.html"     class="sidebar-link <?= $activePage==='profil'     ?'active':'' ?>"><span class="icon">👤</span> Mon Profil</a>
    <a href="parametres.html" class="sidebar-link <?= $activePage==='parametres' ?'active':'' ?>"><span class="icon">⚙️</span> Paramètres</a>
    <a href="../auth/logout.php" class="sidebar-link" style="color:var(--danger);margin-top:.5rem"><span class="icon">🚪</span> Déconnexion</a>

  </nav>
</aside>
