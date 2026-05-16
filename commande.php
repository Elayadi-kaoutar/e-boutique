<?php
// ============================================================
//  KH Boutique — Page de commande (checkout)
// ============================================================
require_once __DIR__ . '/config/config.php';
startSession();

// Réservé aux utilisateurs connectés
if (!isLoggedIn()) {
    $_SESSION['flash'] = ['type' => 'info', 'msg' => 'Connectez-vous pour passer commande.'];
    redirect('/auth/connexion.php');
}

$pdo  = getConnexion();
$user = getCurrentUser();

// ── Charger le panier ────────────────────────────────────────
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

// Panier vide → retour boutique
if (empty($details)) {
    $_SESSION['flash'] = ['type' => 'info', 'msg' => 'Votre panier est vide.'];
    redirect('/');
}

$frais_livraison = $total >= 80 ? 0 : 4.99;
$total_final     = $total + $frais_livraison;

$erreur  = '';
$success = false;

// ── Traitement du formulaire ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_livraison = trim($_POST['nom_livraison'] ?? '');
    $adresse       = trim($_POST['adresse']       ?? '');
    $ville         = trim($_POST['ville']         ?? '');
    $code_postal   = trim($_POST['code_postal']   ?? '');
    $pays          = trim($_POST['pays']          ?? 'France');
    $telephone     = trim($_POST['telephone']     ?? '');
    $notes         = trim($_POST['notes']         ?? '');

    // ── Validation ───────────────────────────────────────────
    if (empty($nom_livraison) || empty($adresse) || empty($ville) || empty($code_postal)) {
        $erreur = 'Veuillez remplir tous les champs obligatoires (*).';
    } else {
        try {
            $pdo->beginTransaction();

            // 1) Vérifier le stock de chaque produit
            foreach ($details as $d) {
                $s = $pdo->prepare('SELECT stock FROM produits WHERE id = ? FOR UPDATE');
                $s->execute([$d['id']]);
                $stockDispo = (int)$s->fetchColumn();
                if ($stockDispo < $d['qte']) {
                    throw new RuntimeException(
                        'Stock insuffisant pour « ' . $d['nom'] . ' » (disponible : ' . $stockDispo . ').'
                    );
                }
            }

            // 2) Créer la commande
            $pdo->prepare("
                INSERT INTO commandes
                  (user_id, total, frais_livraison, nom_livraison, adresse, ville, code_postal, pays, telephone, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $user['id'], $total, $frais_livraison,
                $nom_livraison, $adresse, $ville, $code_postal, $pays, $telephone, $notes
            ]);
            $commandeId = (int)$pdo->lastInsertId();

            // 3) Insérer les articles + décrémenter le stock
            foreach ($details as $d) {
                $pdo->prepare("
                    INSERT INTO commande_items (commande_id, produit_id, nom_produit, prix_unit, quantite)
                    VALUES (?, ?, ?, ?, ?)
                ")->execute([$commandeId, $d['id'], $d['nom'], $d['prix'], $d['qte']]);

                $pdo->prepare('UPDATE produits SET stock = stock - ? WHERE id = ?')
                    ->execute([$d['qte'], $d['id']]);
            }

            // 4) Vider le panier session + BDD
            $_SESSION['panier'] = [];
            $pdo->prepare('DELETE FROM panier WHERE user_id = ?')->execute([$user['id']]);

            $pdo->commit();

            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => "🎉 Commande #{$commandeId} confirmée ! Merci pour votre achat.",
            ];
            redirect('/mes-commandes.php');

        } catch (RuntimeException $e) {
            $pdo->rollBack();
            $erreur = $e->getMessage();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erreur = 'Erreur lors de la commande. Veuillez réessayer.';
        }
    }
}

// Pré-remplir avec les infos du profil
$userRow = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$userRow->execute([$user['id']]);
$profil = $userRow->fetch();

$pageTitle = 'Commander';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <h1>📦 Finaliser ma commande</h1>
  <a href="<?= SITE_URL ?>/panier.php" class="btn btn-outline btn-sm">← Retour au panier</a>
</div>

<?php if ($erreur): ?>
  <div class="alert alert-error">⚠ <?= e($erreur) ?></div>
<?php endif; ?>

<div class="checkout-layout">

  <!-- ── Formulaire livraison ── -->
  <div class="checkout-form-wrap">
    <div class="card" style="max-width:100%">
      <h2>Adresse de livraison</h2>
      <form method="POST" action="" novalidate>

        <div class="form-group">
          <label for="nom_livraison">Nom complet *</label>
          <input type="text" id="nom_livraison" name="nom_livraison" required
                 placeholder="Prénom Nom"
                 value="<?= e($_POST['nom_livraison'] ?? $profil['nom'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="adresse">Adresse *</label>
          <input type="text" id="adresse" name="adresse" required
                 placeholder="Numéro et nom de rue"
                 value="<?= e($_POST['adresse'] ?? $profil['adresse'] ?? '') ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="code_postal">Code postal *</label>
            <input type="text" id="code_postal" name="code_postal" required
                   placeholder="75001"
                   value="<?= e($_POST['code_postal'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="ville">Ville *</label>
            <input type="text" id="ville" name="ville" required
                   placeholder="Paris"
                   value="<?= e($_POST['ville'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label for="pays">Pays *</label>
          <select id="pays" name="pays">
            <?php
            $paysList = ['France','Maroc','Belgique','Suisse','Luxembourg',
                         'Algérie','Tunisie','Canada','Sénégal','Côte d\'Ivoire'];
            $selectedPays = $_POST['pays'] ?? 'France';
            foreach ($paysList as $p):
            ?>
              <option value="<?= e($p) ?>" <?= $selectedPays === $p ? 'selected' : '' ?>><?= e($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="telephone">Téléphone <small>(optionnel)</small></label>
          <input type="tel" id="telephone" name="telephone"
                 placeholder="+33 6 00 00 00 00"
                 value="<?= e($_POST['telephone'] ?? $profil['telephone'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="notes">Instructions de livraison <small>(optionnel)</small></label>
          <textarea id="notes" name="notes" rows="3"
                    placeholder="Digicode, étage, instructions particulières..."><?= e($_POST['notes'] ?? '') ?></textarea>
        </div>

        <!-- ── Récap mini ── -->
        <div class="checkout-recap-mini">
          <div class="recap-line">
            <span>Sous-total</span>
            <span><?= number_format($total, 2, ',', ' ') ?> €</span>
          </div>
          <div class="recap-line">
            <span>Livraison</span>
            <span><?= $frais_livraison > 0 ? number_format($frais_livraison, 2, ',', ' ') . ' €' : '<span class="free-shipping">Offerte 🎁</span>' ?></span>
          </div>
          <hr class="recap-sep">
          <div class="recap-total">
            <span>Total à payer</span>
            <span class="total-price"><?= number_format($total_final, 2, ',', ' ') ?> €</span>
          </div>
        </div>

        <div class="paiement-notice">
          <span class="paiement-icon">🔒</span>
          <div>
            <strong>Paiement à la livraison</strong>
            <p>Vous réglerez en espèces ou par carte à la réception de votre colis.</p>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:1.5rem">
          ✅ Confirmer ma commande
        </button>
      </form>
    </div>
  </div>

  <!-- ── Résumé articles ── -->
  <aside class="checkout-summary">
    <div class="card" style="max-width:100%">
      <h2>Votre commande</h2>
      <?php foreach ($details as $d): ?>
        <div class="checkout-item">
          <div class="checkout-item-img">
            <?php if (!empty($d['image_url'])): ?>
              <img src="<?= e($d['image_url']) ?>" alt="<?= e($d['nom']) ?>"
                   onerror="this.style.display='none'">
            <?php else: ?>
              <div class="img-fallback" style="font-size:1.5rem;display:flex;align-items:center;justify-content:center;width:100%;height:100%">🛍️</div>
            <?php endif; ?>
          </div>
          <div class="checkout-item-info">
            <p class="checkout-item-nom"><?= e($d['nom']) ?></p>
            <p class="checkout-item-qte">x<?= $d['qte'] ?></p>
          </div>
          <div class="checkout-item-prix">
            <?= number_format($d['sous_total'], 2, ',', ' ') ?> €
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </aside>

</div>

<style>
.checkout-layout {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 2rem;
  align-items: flex-start;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.checkout-recap-mini {
  background: var(--light);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.2rem;
  margin: 1.5rem 0;
}
.paiement-notice {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  background: #e8f5e9;
  border: 1px solid #a5d6a7;
  border-radius: var(--radius);
  padding: 1rem 1.2rem;
}
.paiement-icon { font-size: 1.6rem; }
.paiement-notice strong { display: block; margin-bottom: .2rem; color: #1b5e20; }
.paiement-notice p { font-size: .85rem; color: #2e7d32; margin: 0; }

.checkout-item {
  display: flex;
  align-items: center;
  gap: .85rem;
  padding: .85rem 0;
  border-bottom: 1px solid var(--border);
}
.checkout-item:last-child { border-bottom: none; }
.checkout-item-img {
  width: 56px;
  height: 56px;
  border-radius: 8px;
  overflow: hidden;
  background: #f5f0e8;
  flex-shrink: 0;
}
.checkout-item-img img { width: 100%; height: 100%; object-fit: cover; }
.checkout-item-info { flex: 1; }
.checkout-item-nom { font-size: .88rem; font-weight: 600; color: var(--dark); }
.checkout-item-qte { font-size: .8rem; color: #888; }
.checkout-item-prix { font-weight: 700; font-size: .95rem; white-space: nowrap; }

@media (max-width: 900px) {
  .checkout-layout { grid-template-columns: 1fr; }
  .checkout-summary { order: -1; }
}
@media (max-width: 480px) {
  .form-row { grid-template-columns: 1fr; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
