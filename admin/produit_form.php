<?php
require_once __DIR__ . '/../config/config.php';
startSession();
if (!isAdmin()) redirect('/');

$pdo = getConnexion();
$id  = (int)($_GET['id'] ?? 0);
$produit = null;

if ($id > 0) {
    $s = $pdo->prepare('SELECT * FROM produits WHERE id = ?');
    $s->execute([$id]);
    $produit = $s->fetch();
    if (!$produit) redirect('/admin/dashboard.php');
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY nom')->fetchAll();
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom         = trim($_POST['nom']         ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix        = (float)str_replace(',', '.', $_POST['prix'] ?? '0');
    $stock       = (int)($_POST['stock'] ?? 0);
    $cat_id      = (int)($_POST['categorie_id'] ?? 0);
    $image_url   = trim($_POST['image_url'] ?? '');

    if (empty($nom) || $prix <= 0) {
        $erreur = 'Le nom et un prix valide sont requis.';
    } else {
        if ($id > 0) {
            $pdo->prepare("UPDATE produits SET nom=?, description=?, prix=?, stock=?, categorie_id=?, image_url=? WHERE id=?")
                ->execute([$nom, $description, $prix, $stock, $cat_id ?: null, $image_url ?: null, $id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Produit mis à jour.'];
        } else {
            $pdo->prepare("INSERT INTO produits (nom, description, prix, stock, categorie_id, image_url) VALUES (?,?,?,?,?,?)")
                ->execute([$nom, $description, $prix, $stock, $cat_id ?: null, $image_url ?: null]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Produit ajouté.'];
        }
        redirect('/admin/dashboard.php');
    }
}

$pageTitle = $produit ? 'Éditer produit' : 'Nouveau produit';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1><?= $produit ? '✏️ Éditer' : '➕ Nouveau produit' ?></h1>
  <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn btn-outline btn-sm">← Dashboard</a>
</div>

<div class="card">
  <?php if ($erreur): ?>
    <div class="alert alert-error">⚠ <?= e($erreur) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="form-group">
      <label>Nom du produit *</label>
      <input type="text" name="nom" required value="<?= e($produit['nom'] ?? $_POST['nom'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description" rows="4"><?= e($produit['description'] ?? $_POST['description'] ?? '') ?></textarea>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div class="form-group">
        <label>Prix (€) *</label>
        <input type="number" name="prix" step="0.01" min="0" required
               value="<?= e($produit['prix'] ?? $_POST['prix'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Stock</label>
        <input type="number" name="stock" min="0"
               value="<?= e($produit['stock'] ?? $_POST['stock'] ?? '0') ?>">
      </div>
    </div>
    <div class="form-group">
      <label>Catégorie</label>
      <select name="categorie_id">
        <option value="">— Aucune —</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>"
            <?= ($produit['categorie_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
            <?= e($c['nom']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>URL image</label>
      <input type="url" name="image_url" placeholder="https://..."
             value="<?= e($produit['image_url'] ?? $_POST['image_url'] ?? '') ?>">
    </div>
    <?php if (!empty($produit['image_url'])): ?>
      <img src="<?= e($produit['image_url']) ?>" alt=""
           style="width:120px;height:120px;object-fit:cover;border-radius:8px;margin-bottom:1rem"
           onerror="this.remove()">
    <?php endif; ?>
    <button type="submit" class="btn btn-primary">
      <?= $produit ? '💾 Enregistrer' : '➕ Créer le produit' ?>
    </button>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
