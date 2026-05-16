</main>
<footer>
  <div class="footer-inner">
    <div class="footer-brand">
      <span class="logo-kh">KH</span> Boutique
      <p>Mode &amp; Élégance — Votre boutique de luxe en ligne</p>
    </div>
    <div class="footer-links">
      <a href="<?= SITE_URL ?>/">Boutique</a>
      <a href="<?= SITE_URL ?>/panier.php">Panier</a>
      <?php if (isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>/mes-commandes.php">Mes commandes</a>
        <a href="<?= SITE_URL ?>/auth/logout.php">Déconnexion</a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/auth/connexion.php">Connexion</a>
        <a href="<?= SITE_URL ?>/auth/inscription.php">Inscription</a>
      <?php endif; ?>
    </div>
    <p class="footer-copy">© <?= date('Y') ?> <?= SITE_NAME ?> — Tous droits réservés</p>
  </div>
</footer>

<script>
  document.getElementById('navToggle').addEventListener('click', function() {
    document.getElementById('navLinks').classList.toggle('open');
  });
  setTimeout(() => {
    const f = document.querySelector('.flash');
    if (f) { f.style.opacity = '0'; setTimeout(() => f.remove(), 400); }
  }, 4000);
</script>
</body>
</html>
