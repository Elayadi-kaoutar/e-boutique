<?php
// ============================================================
//  KH Boutique — Panier
//  - Persistance BDD pour les utilisateurs connectés
//  - Fusion session → BDD au login (géré dans connexion.php)
// ============================================================
require_once __DIR__ . '/config/config.php';
startSession();

$pdo    = getConnexion();
$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);
// 'return' sert à rediriger vers la page précédente après ajout
$return = $_GET['return'] ?? '/';

$user = getCurrentUser();

// ── Actions ──────────────────────────────────────────────────

if ($action === 'ajouter' && $id > 0) {
    $stmt = $pdo->prepare('SELECT id, stock FROM produits WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $prod = $stmt->fetch();

    if ($prod && $prod['stock'] > 0) {
        if (!isset($_SESSION['panier'][$id])) {
            $_SESSION['panier'][$id] = ['qte' => 0];
        }
        $qte_actuelle = $_SESSION['panier'][$id]['qte'];
        if ($qte_actuelle < $prod['stock']) {
            $_SESSION['panier'][$id]['qte']++;
            if ($user) saveCartToDB($user['id']);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Produit ajouté au panier !'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Stock maximum atteint pour ce produit.'];
        }
    }
    // Redirige vers la page d'origine (index ou autre)
    header('Location: ' . SITE_URL . $return);
    exit;
}

if ($action === 'supprimer' && $id > 0) {
    unset($_SESSION['panier'][$id]);
    if ($user) saveCartToDB($user['id']);
    $_SESSION['flash'] = ['type' => 'info', 'msg' => 'Produit retiré du panier.'];
    header('Location: ' . SITE_URL . '/panier.php');
    exit;
}

if ($action === 'update' && $id > 0 && isset($_POST['qte'])) {
    $qte = (int)$_POST['qte'];
    if ($qte <= 0) {
        unset($_SESSION['panier'][$id]);
    } else {
        $_SESSION['panier'][$id] = ['qte' => $qte];
    }
    if ($user) saveCartToDB($user['id']);
    header('Location: ' . SITE_URL . '/panier.php');
    exit;
}

if ($action === 'vider') {
    $_SESSION['panier'] = [];
    if ($user) saveCartToDB($user['id']);
    $_SESSION['flash'] = ['type' => 'info', 'msg' => 'Votre panier a été vidé.'];
    header('Location: ' . SITE_URL . '/panier.php');
    exit;
}

// ── Chargement du panier ─────────────────────────────────────
$panier  = $_SESSION['panier'] ?? [];
$total   = 0.0;
$details = [];

if (!empty($panier)) {
    $ids  = implode(',', array_map('intval', array_keys($panier)));
    $rows = $pdo->query("SELECT * FROM produits WHERE id IN ($ids)")->fetchAll();
    foreach ($rows as $p) {
        $qte        = $panier[$p['id']]['qte'] ?? 1;
        $sous_total = $p['prix'] * $qte;
        $total     += $sous_total;
        $details[]  = array_merge($p, ['qte' => $qte, 'sous_total' => $sous_total]);
    }
}

$frais_livraison = ($total > 0 && $total >= 80) ? 0 : ($total > 0 ? 4.99 : 0);
$total_final     = $total + $frais_livraison;

$pageTitle = 'Mon Panier';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <h1>🛒 Mon Panier</h1>
  <a href="<?= SITE_URL ?>/" class="btn btn-outline btn-sm">← Continuer mes achats</a>
</div>

<?php if (empty($details)): ?>
  <div class="vide-state">
    <div class="vide-icon">🛍️</div>
    <h3>Votre panier est vide</h3>
    <p>Découvrez nos produits et ajoutez vos coups de cœur !</p>
    <a href="<?= SITE_URL ?>/" class="btn btn-primary">Voir la boutique</a>
  </div>

<?php else: ?>
  <div class="panier-layout">

    <!-- ── Articles ── -->
    <div class="panier-items">
      <div class="panier-header-row">
        <h2>Articles (<?= count($details) ?>)</h2>
        <a href="<?= SITE_URL ?>/panier.php?action=vider" class="btn btn-danger btn-sm"
           onclick="return confirm('Vider le panier ?')">🗑 Tout supprimer</a>
      </div>

      <?php foreach ($details as $d): ?>
        <div class="panier-item">
          <div class="panier-img">
            <?php if (!empty($d['image_url'])): ?>
              <img src="<?= e($d['image_url']) ?>" alt="<?= e($d['nom']) ?>"
                   onerror="this.style.display='none'">
            <?php else: ?>
              <div class="img-fallback">🛍️</div>
            <?php endif; ?>
          </div>
          <div class="panier-info">
            <h3><?= e($d['nom']) ?></h3>
            <p class="panier-prix-unit"><?= number_format($d['prix'], 2, ',', ' ') ?> € / unité</p>
          </div>
          <div class="panier-qte">
            <form method="POST" action="<?= SITE_URL ?>/panier.php?action=update&id=<?= $d['id'] ?>">
              <label>Qté</label>
              <div class="qte-controls">
                <button type="button" class="qte-btn" onclick="changeQte(this,-1)">−</button>
                <input type="number" name="qte" value="<?= $d['qte'] ?>"
                       min="0" max="<?= $d['stock'] ?>" class="qte-input"
                       onchange="this.form.submit()">
                <button type="button" class="qte-btn" onclick="changeQte(this,1)">+</button>
              </div>
            </form>
          </div>
          <div class="panier-sous-total">
            <strong><?= number_format($d['sous_total'], 2, ',', ' ') ?> €</strong>
          </div>
          <a href="<?= SITE_URL ?>/panier.php?action=supprimer&id=<?= $d['id'] ?>"
             class="panier-delete" title="Retirer" onclick="return confirm('Retirer cet article ?')">✕</a>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Récapitulatif ── -->
    <aside class="panier-recap">
      <h2>Récapitulatif</h2>
      <div class="recap-line">
        <span>Sous-total</span>
        <span><?= number_format($total, 2, ',', ' ') ?> €</span>
      </div>
      <div class="recap-line">
        <span>Livraison</span>
        <span>
          <?= $frais_livraison > 0
            ? number_format($frais_livraison, 2, ',', ' ') . ' €'
            : '<span class="free-shipping">Offerte 🎁</span>' ?>
        </span>
      </div>
      <?php if ($frais_livraison > 0): ?>
        <p class="livraison-hint">
          Livraison offerte dès 80,00 € d'achat
          (encore <?= number_format(80 - $total, 2, ',', ' ') ?> €)
        </p>
      <?php endif; ?>
      <hr class="recap-sep">
      <div class="recap-total">
        <span>Total</span>
        <span class="total-price"><?= number_format($total_final, 2, ',', ' ') ?> €</span>
      </div>

      <?php if ($user): ?>
        <a href="<?= SITE_URL ?>/commande.php" class="btn btn-primary btn-block">
          Commander →
        </a>
        <p class="recap-user">
          Connecté en tant que <strong><?= e($user['nom']) ?></strong>
        </p>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/auth/connexion.php" class="btn btn-primary btn-block">
          Se connecter pour commander
        </a>
        <p class="recap-hint">
          Votre panier est sauvegardé après connexion.
        </p>
      <?php endif; ?>
    </aside>
  </div>
<?php endif; ?>

<script>
function changeQte(btn, delta) {
  const input = btn.parentElement.querySelector('.qte-input');
  const newVal = parseInt(input.value) + delta;
  const min = parseInt(input.min);
  const max = parseInt(input.max);
  if (newVal >= min && newVal <= max) {
    input.value = newVal;
    input.form.submit();
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
