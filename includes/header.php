<?php
require_once __DIR__ . '/../config/config.php';
startSession();
$user      = getCurrentUser();
$cartCount = cartCount();
$page      = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="flash flash-<?= e($_SESSION['flash']['type']) ?>">
    <?= e($_SESSION['flash']['msg']) ?>
    <button class="flash-close" onclick="this.parentElement.remove()">✕</button>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<nav>
  <a href="<?= SITE_URL ?>/" class="logo">
    <span class="logo-kh">KH</span> Boutique
  </a>
  <button class="nav-toggle" id="navToggle" aria-label="Menu">☰</button>
  <div class="nav-links" id="navLinks">
    <a href="<?= SITE_URL ?>/" class="<?= $page === 'index.php' ? 'active' : '' ?>">Boutique</a>
    <a href="<?= SITE_URL ?>/panier.php" class="nav-panier <?= $page === 'panier.php' ? 'active' : '' ?>">
      Panier
      <?php if ($cartCount > 0): ?>
        <span class="badge"><?= $cartCount ?></span>
      <?php endif; ?>
    </a>
    <?php if ($user): ?>
      <?php if ($user['role'] === 'admin'): ?>
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="nav-admin">⚙ Admin</a>
      <?php endif; ?>
      <a href="<?= SITE_URL ?>/mes-commandes.php" style="color:rgba(255,255,255,.75);font-size:.88rem;font-weight:500">Mes commandes</a>
      <div class="nav-user">
        <span class="user-greeting">👤 <?= e($user['nom']) ?></span>
        <a href="<?= SITE_URL ?>/auth/logout.php" class="btn btn-outline btn-sm">Déconnexion</a>
      </div>
    <?php else: ?>
      <a href="<?= SITE_URL ?>/auth/connexion.php" class="btn btn-outline btn-sm">Connexion</a>
      <a href="<?= SITE_URL ?>/auth/inscription.php" class="btn btn-primary btn-sm">S'inscrire</a>
    <?php endif; ?>
  </div>
</nav>
<main>
