<?php
/**
 * includes/footer.php
 */
$rootPath = $rootPath ?? '../';
?>
<!-- FOOTER -->
<footer class="footer">
  <div class="footer-grid">
    <div class="footer-brand">
      <a href="<?= $rootPath ?>public/blog.html" class="nav-logo" style="margin-bottom:1rem;display:inline-flex">
        <div class="logo-icon">🏆</div>
        AS<span>Club</span>
      </a>
      <p class="footer-desc">Club sportif multidisciplinaire fondé en 2010. Nous cultivons l'excellence, la passion du sport et l'esprit d'équipe pour tous les niveaux.</p>
      <div class="footer-social">
        <a href="#" class="social-btn" title="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="#" class="social-btn" title="Instagram"><i class="fab fa-instagram"></i></a>
        <a href="#" class="social-btn" title="Twitter/X"><i class="fab fa-x-twitter"></i></a>
        <a href="#" class="social-btn" title="YouTube"><i class="fab fa-youtube"></i></a>
      </div>
    </div>
    <div class="footer-col">
      <h5>Navigation</h5>
      <ul class="footer-links">
        <li><a href="<?= $rootPath ?>public/blog.html">Accueil</a></li>
        <li><a href="<?= $rootPath ?>public/activites.html">Activités</a></li>
        <li><a href="<?= $rootPath ?>public/apropos.html">À Propos</a></li>
        <li><a href="<?= $rootPath ?>public/contact.html">Contact</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h5>Compte</h5>
      <ul class="footer-links">
        <li><a href="<?= $rootPath ?>public/auth/login.html">Connexion</a></li>
        <li><a href="<?= $rootPath ?>public/auth/inscrire.html">Inscription</a></li>
        <li><a href="<?= $rootPath ?>public/admin/dashboard.html">Espace Admin</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h5>Contact</h5>
      <ul class="footer-links">
        <li><a href="mailto:contact@association.dz">contact@association.dz</a></li>
        <li><a href="tel:+213555123456">+213 555 123 456</a></li>
        <li><a href="#">12 Rue du Sport, Blida</a></li>
        <li><a href="#">Lun–Sam: 08h–20h</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© <?= date('Y') ?> Association Sportive Club — Tous droits réservés</span>
    <span>Développé avec ❤️ en PHP 8 + MariaDB</span>
  </div>
</footer>

<!-- Scripts -->
<script src="<?= $rootPath ?>assets/js/app.js"></script>
</body>
</html>
