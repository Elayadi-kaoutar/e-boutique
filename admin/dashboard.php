<?php
require_once __DIR__ . '/../config/config.php';
startSession();

if (!isAdmin()) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Accès réservé aux administrateurs.'];
    redirect('/');
}

$pdo = getConnexion();

// ── Action : changer statut commande ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commande_id'], $_POST['statut'])) {
    $statutsValides = ['en_attente','confirmee','expediee','livree','annulee'];
    if (in_array($_POST['statut'], $statutsValides)) {
        $pdo->prepare('UPDATE commandes SET statut = ? WHERE id = ?')
            ->execute([$_POST['statut'], (int)$_POST['commande_id']]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Statut mis à jour.'];
    }
    header('Location: ' . SITE_URL . '/admin/dashboard.php#commandes');
    exit;
}

// ── Stats ────────────────────────────────────────────────────
$stats = [
    'utilisateurs' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'produits'     => $pdo->query('SELECT COUNT(*) FROM produits')->fetchColumn(),
    'categories'   => $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'stock_total'  => $pdo->query('SELECT COALESCE(SUM(stock),0) FROM produits')->fetchColumn(),
    'commandes'    => $pdo->query('SELECT COUNT(*) FROM commandes')->fetchColumn(),
    'ca'           => $pdo->query('SELECT COALESCE(SUM(total+frais_livraison),0) FROM commandes WHERE statut != "annulee"')->fetchColumn(),
];

$produits = $pdo->query("
    SELECT p.*, c.nom AS categorie
    FROM produits p
    LEFT JOIN categories c ON p.categorie_id = c.id
    ORDER BY p.created_at DESC
")->fetchAll();

$commandes = $pdo->query("
    SELECT co.*, u.nom AS client_nom, u.email AS client_email,
           COUNT(ci.id) AS nb_articles
    FROM commandes co
    JOIN users u ON u.id = co.user_id
    LEFT JOIN commande_items ci ON ci.commande_id = co.id
    GROUP BY co.id
    ORDER BY co.created_at DESC
    LIMIT 50
")->fetchAll();

$statutLabels = [
    'en_attente' => ['label' => 'En attente',  'color' => '#e67e22'],
    'confirmee'  => ['label' => 'Confirmée',   'color' => '#1976d2'],
    'expediee'   => ['label' => 'Expédiée',    'color' => '#7b1fa2'],
    'livree'     => ['label' => 'Livrée',      'color' => '#1e7e34'],
    'annulee'    => ['label' => 'Annulée',     'color' => '#c0392b'],
];

$pageTitle = 'Administration';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>⚙ Tableau de bord Admin</h1>
  <a href="<?= SITE_URL ?>/" class="btn btn-outline btn-sm">← Boutique</a>
</div>

<!-- ── Stats ── -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-num"><?= $stats['utilisateurs'] ?></div>
    <div class="stat-label">Utilisateurs</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $stats['produits'] ?></div>
    <div class="stat-label">Produits</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= number_format($stats['stock_total']) ?></div>
    <div class="stat-label">En stock</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $stats['commandes'] ?></div>
    <div class="stat-label">Commandes</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= number_format($stats['ca'], 0, ',', ' ') ?> €</div>
    <div class="stat-label">Chiffre d'affaires</div>
  </div>
</div>

<!-- ── Produits ── -->
<div class="card" style="max-width:100%;margin-bottom:2rem">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
    <h2 style="margin:0">Produits</h2>
    <a href="<?= SITE_URL ?>/admin/produit_form.php" class="btn btn-primary btn-sm">+ Ajouter</a>
  </div>
  <div style="overflow-x:auto">
    <table>
      <thead>
        <tr><th>#</th><th>Image</th><th>Nom</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($produits as $p): ?>
          <tr class="<?= $p['stock'] == 0 ? 'rupture' : ($p['stock'] <= 3 ? 'alerte' : '') ?>">
            <td><?= $p['id'] ?></td>
            <td>
              <?php if ($p['image_url']): ?>
                <img src="<?= e($p['image_url']) ?>" alt=""
                     style="width:45px;height:45px;object-fit:cover;border-radius:8px">
              <?php else: ?>
                <span style="font-size:1.8rem">🛍️</span>
              <?php endif; ?>
            </td>
            <td><strong><?= e($p['nom']) ?></strong></td>
            <td><?= e($p['categorie'] ?? '—') ?></td>
            <td><?= number_format($p['prix'], 2, ',', ' ') ?> €</td>
            <td>
              <?php if ($p['stock'] == 0): ?>
                <span style="color:#dc3545;font-weight:600">Rupture</span>
              <?php elseif ($p['stock'] <= 3): ?>
                <span style="color:#e67e22;font-weight:600"><?= $p['stock'] ?> ⚠</span>
              <?php else: ?>
                <?= $p['stock'] ?>
              <?php endif; ?>
            </td>
            <td>
              <a href="<?= SITE_URL ?>/admin/produit_form.php?id=<?= $p['id'] ?>"
                 class="btn btn-outline btn-sm">Éditer</a>
              <a href="<?= SITE_URL ?>/admin/delete_produit.php?id=<?= $p['id'] ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('Supprimer ce produit ?')">Suppr.</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Commandes ── -->
<div class="card" style="max-width:100%" id="commandes">
  <h2 style="margin-bottom:1.25rem">Commandes récentes</h2>
  <?php if (empty($commandes)): ?>
    <p style="color:#888;text-align:center;padding:2rem 0">Aucune commande pour l'instant.</p>
  <?php else: ?>
    <div style="overflow-x:auto">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Client</th><th>Date</th><th>Articles</th>
            <th>Total</th><th>Statut</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($commandes as $c):
            $st = $statutLabels[$c['statut']] ?? ['label'=>$c['statut'],'color'=>'#888'];
          ?>
            <tr>
              <td><strong>#<?= $c['id'] ?></strong></td>
              <td>
                <strong><?= e($c['client_nom']) ?></strong><br>
                <span style="font-size:.78rem;color:#888"><?= e($c['client_email']) ?></span>
              </td>
              <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
              <td><?= $c['nb_articles'] ?></td>
              <td><strong><?= number_format($c['total'] + $c['frais_livraison'], 2, ',', ' ') ?> €</strong></td>
              <td>
                <span style="background:<?= $st['color'] ?>;color:#fff;padding:.2rem .75rem;
                             border-radius:50px;font-size:.78rem;font-weight:700">
                  <?= $st['label'] ?>
                </span>
              </td>
              <td>
                <form method="POST" style="display:flex;gap:.4rem;align-items:center">
                  <input type="hidden" name="commande_id" value="<?= $c['id'] ?>">
                  <select name="statut" style="padding:.3rem .5rem;font-size:.8rem;width:auto">
                    <?php foreach ($statutLabels as $key => $sl): ?>
                      <option value="<?= $key ?>" <?= $c['statut'] === $key ? 'selected' : '' ?>>
                        <?= $sl['label'] ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-primary btn-sm">OK</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<style>
.alerte td { background: #fff8ee !important; }
.rupture td { background: #fff5f5 !important; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
