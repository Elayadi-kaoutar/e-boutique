<?php
require_once __DIR__ . '/config/config.php';
startSession();

$pdo = getConnexion();

// ── Filtres ──────────────────────────────────────────────────
$q    = trim($_GET['q']    ?? '');
$cat  = (int)($_GET['cat'] ?? 0);
$sort = $_GET['sort'] ?? 'recent';

$params = [];
$sql = "
  SELECT p.*, c.nom AS categorie
  FROM produits p
  LEFT JOIN categories c ON p.categorie_id = c.id
  WHERE 1=1
";

if ($q !== '') {
    $sql .= " AND (p.nom LIKE ? OR p.description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($cat > 0) {
    $sql .= " AND p.categorie_id = ?";
    $params[] = $cat;
}

$sql .= match($sort) {
    'prix_asc'  => ' ORDER BY p.prix ASC',
    'prix_desc' => ' ORDER BY p.prix DESC',
    default     => ' ORDER BY p.created_at DESC',
};

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY nom')->fetchAll();

$pageTitle = 'Boutique';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Hero ── -->
<section class="hero">
  <div class="hero-content">
    <p class="hero-sub">Collection Printemps 2025</p>
    <h1 class="hero-title">Mode &amp; Élégance</h1>
    <p class="hero-desc">Découvrez notre sélection exclusive de robes, chaussures, sacs et parfums de luxe.</p>
    <a href="#produits" class="btn btn-primary btn-lg">Découvrir la collection</a>
  </div>
</section>

<!-- ── Filtres ── -->
<section class="filtres-section" id="produits">
  <form method="GET" action="<?= SITE_URL ?>/" class="filtres-form">
    <input type="text" name="q" placeholder="🔍 Rechercher un produit..."
           value="<?= e($q) ?>" class="filtres-search">
    <select name="cat" class="filtres-select">
      <option value="0">Toutes les catégories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>>
          <?= e($c['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="sort" class="filtres-select">
      <option value="recent"   <?= $sort === 'recent'    ? 'selected' : '' ?>>Plus récents</option>
      <option value="prix_asc" <?= $sort === 'prix_asc'  ? 'selected' : '' ?>>Prix ↑</option>
      <option value="prix_desc"<?= $sort === 'prix_desc' ? 'selected' : '' ?>>Prix ↓</option>
    </select>
    <button type="submit" class="btn btn-primary">Filtrer</button>
    <?php if ($q || $cat): ?>
      <a href="<?= SITE_URL ?>/" class="btn btn-outline">Réinitialiser</a>
    <?php endif; ?>
  </form>
</section>

<!-- ── Grille produits ── -->
<section class="produits-section">
  <?php if (empty($produits)): ?>
    <div class="vide-state">
      <div class="vide-icon">🛍️</div>
      <h3>Aucun produit trouvé</h3>
      <p>Essayez d'autres mots-clés ou catégories.</p>
      <a href="<?= SITE_URL ?>/" class="btn btn-primary">Voir tout</a>
    </div>
  <?php else: ?>
    <p class="produits-count">
      <?= count($produits) ?> produit<?= count($produits) > 1 ? 's' : '' ?> trouvé<?= count($produits) > 1 ? 's' : '' ?>
    </p>
    <div class="produits-grid">
      <?php foreach ($produits as $p): ?>
        <article class="card-produit">
          <div class="produit-img-wrap">
            <?php if (!empty($p['image_url'])): ?>
              <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['nom']) ?>"
                   class="produit-img" loading="lazy"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="produit-img-placeholder" style="display:none">🛍️</div>
            <?php else: ?>
              <div class="produit-img-placeholder">🛍️</div>
            <?php endif; ?>
            <span class="cat-badge"><?= e($p['categorie'] ?? 'Autre') ?></span>
            <?php if ($p['stock'] <= 0): ?>
              <span class="badge-rupture">Rupture</span>
            <?php elseif ($p['stock'] <= 5): ?>
              <span class="badge-limited">Dernier stock</span>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <h3 class="produit-nom"><?= e($p['nom']) ?></h3>
            <p class="produit-desc"><?= e(mb_substr($p['description'] ?? '', 0, 75)) ?>…</p>
            <div class="card-footer">
              <span class="prix"><?= number_format($p['prix'], 2, ',', ' ') ?> €</span>
              <?php if ($p['stock'] > 0): ?>
                <!-- URL correcte : SITE_URL + chemin absolu depuis la racine du projet -->
                <a href="<?= SITE_URL ?>/panier.php?action=ajouter&id=<?= $p['id'] ?>&return=<?= urlencode('/') ?>"
                   class="btn btn-primary btn-sm btn-cart">+ Panier</a>
              <?php else: ?>
                <span class="btn btn-disabled btn-sm">Indisponible</span>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
