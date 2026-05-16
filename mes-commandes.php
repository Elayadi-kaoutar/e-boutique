<?php
require_once __DIR__ . '/config/config.php';
startSession();

if (!isLoggedIn()) {
    $_SESSION['flash'] = ['type' => 'info', 'msg' => 'Connectez-vous pour voir vos commandes.'];
    redirect('/auth/connexion.php');
}

$pdo  = getConnexion();
$user = getCurrentUser();

$commandes = $pdo->prepare("
    SELECT c.*,
           COUNT(ci.id) AS nb_articles
    FROM commandes c
    LEFT JOIN commande_items ci ON ci.commande_id = c.id
    WHERE c.user_id = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$commandes->execute([$user['id']]);
$commandes = $commandes->fetchAll();

// Détail d'une commande si demandé
$detail    = null;
$detailId  = (int)($_GET['id'] ?? 0);
if ($detailId > 0) {
    $cStmt = $pdo->prepare('SELECT * FROM commandes WHERE id = ? AND user_id = ?');
    $cStmt->execute([$detailId, $user['id']]);
    $detail = $cStmt->fetch();
    if ($detail) {
        $iStmt = $pdo->prepare('SELECT * FROM commande_items WHERE commande_id = ?');
        $iStmt->execute([$detailId]);
        $detail['items'] = $iStmt->fetchAll();
    }
}

$statutLabels = [
    'en_attente' => ['label' => 'En attente',  'color' => '#e67e22'],
    'confirmee'  => ['label' => 'Confirmée',   'color' => '#1976d2'],
    'expediee'   => ['label' => 'Expédiée',    'color' => '#7b1fa2'],
    'livree'     => ['label' => 'Livrée ✓',    'color' => '#1e7e34'],
    'annulee'    => ['label' => 'Annulée',      'color' => '#c0392b'],
];

$pageTitle = 'Mes commandes';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <h1>📋 Mes commandes</h1>
  <a href="<?= SITE_URL ?>/" class="btn btn-outline btn-sm">← Boutique</a>
</div>

<?php if ($detail): ?>
  <!-- ── Détail commande ── -->
  <div class="card" style="max-width:720px;margin-bottom:2rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
      <h2 style="margin:0">Commande #<?= $detail['id'] ?></h2>
      <a href="<?= SITE_URL ?>/mes-commandes.php" class="btn btn-outline btn-sm">← Retour</a>
    </div>
    <p style="color:#888;font-size:.88rem;margin-bottom:1.5rem">
      Passée le <?= date('d/m/Y à H:i', strtotime($detail['created_at'])) ?>
    </p>

    <?php $st = $statutLabels[$detail['statut']] ?? ['label'=>$detail['statut'],'color'=>'#888']; ?>
    <p style="margin-bottom:1.25rem">
      Statut :
      <span style="background:<?= $st['color'] ?>;color:#fff;padding:.25rem .85rem;border-radius:50px;font-size:.82rem;font-weight:700">
        <?= $st['label'] ?>
      </span>
    </p>

    <h3 style="font-size:1rem;margin-bottom:.75rem">Articles</h3>
    <?php foreach ($detail['items'] as $it): ?>
      <div style="display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid var(--border);font-size:.9rem">
        <span><?= e($it['nom_produit']) ?> × <?= $it['quantite'] ?></span>
        <strong><?= number_format($it['prix_unit'] * $it['quantite'], 2, ',', ' ') ?> €</strong>
      </div>
    <?php endforeach; ?>

    <div style="margin-top:1rem">
      <div class="recap-line"><span>Sous-total</span><span><?= number_format($detail['total'], 2, ',', ' ') ?> €</span></div>
      <div class="recap-line">
        <span>Livraison</span>
        <span><?= $detail['frais_livraison'] > 0 ? number_format($detail['frais_livraison'], 2, ',', ' ') . ' €' : '<span class="free-shipping">Offerte</span>' ?></span>
      </div>
      <div class="recap-total" style="margin-top:.5rem">
        <span>Total</span>
        <span class="total-price"><?= number_format($detail['total'] + $detail['frais_livraison'], 2, ',', ' ') ?> €</span>
      </div>
    </div>

    <hr class="recap-sep" style="margin:1.25rem 0">
    <h3 style="font-size:1rem;margin-bottom:.6rem">Adresse de livraison</h3>
    <p style="font-size:.9rem;line-height:1.7;color:#555">
      <?= e($detail['nom_livraison']) ?><br>
      <?= e($detail['adresse']) ?><br>
      <?= e($detail['code_postal']) ?> <?= e($detail['ville']) ?><br>
      <?= e($detail['pays']) ?>
      <?php if ($detail['telephone']): ?>
        <br><?= e($detail['telephone']) ?>
      <?php endif; ?>
    </p>
    <?php if ($detail['notes']): ?>
      <p style="font-size:.85rem;color:#888;margin-top:.5rem"><em><?= e($detail['notes']) ?></em></p>
    <?php endif; ?>
  </div>

<?php elseif (empty($commandes)): ?>
  <div class="vide-state">
    <div class="vide-icon">📦</div>
    <h3>Aucune commande pour l'instant</h3>
    <p>Faites vos premiers achats sur KH Boutique !</p>
    <a href="<?= SITE_URL ?>/" class="btn btn-primary">Voir la boutique</a>
  </div>

<?php else: ?>
  <div class="card" style="max-width:100%">
    <div style="overflow-x:auto">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Articles</th>
            <th>Total</th>
            <th>Statut</th>
            <th>Détail</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($commandes as $c):
            $st = $statutLabels[$c['statut']] ?? ['label'=>$c['statut'],'color'=>'#888'];
          ?>
            <tr>
              <td><strong>#<?= $c['id'] ?></strong></td>
              <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
              <td><?= $c['nb_articles'] ?> article<?= $c['nb_articles'] > 1 ? 's' : '' ?></td>
              <td><strong><?= number_format($c['total'] + $c['frais_livraison'], 2, ',', ' ') ?> €</strong></td>
              <td>
                <span style="background:<?= $st['color'] ?>;color:#fff;padding:.2rem .75rem;
                             border-radius:50px;font-size:.78rem;font-weight:700;white-space:nowrap">
                  <?= $st['label'] ?>
                </span>
              </td>
              <td>
                <a href="<?= SITE_URL ?>/mes-commandes.php?id=<?= $c['id'] ?>"
                   class="btn btn-outline btn-sm">Voir</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
