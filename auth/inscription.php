<?php
require_once __DIR__ . '/../config/config.php';
startSession();

if (isLoggedIn()) redirect('/');

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom   = trim($_POST['nom']         ?? '');
    $email = trim($_POST['email']       ?? '');
    $mdp   = $_POST['mot_de_passe']      ?? '';
    $mdp2  = $_POST['mot_de_passe_conf'] ?? '';

    if (empty($nom) || empty($email) || empty($mdp) || empty($mdp2)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } elseif (mb_strlen($nom) < 2 || mb_strlen($nom) > 100) {
        $erreur = 'Le nom doit contenir entre 2 et 100 caractères.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse e-mail invalide.';
    } elseif (mb_strlen($mdp) < 6) {
        $erreur = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($mdp !== $mdp2) {
        $erreur = 'Les deux mots de passe ne correspondent pas.';
    } else {
        try {
            $pdo   = getConnexion();
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            if ($check->fetch()) {
                $erreur = 'Cette adresse e-mail est déjà utilisée.';
            } else {
                $hash = password_hash($mdp, PASSWORD_BCRYPT);
                $pdo->prepare('INSERT INTO users (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)')
                    ->execute([$nom, $email, $hash, 'client']);
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'msg'  => 'Compte créé ! Vous pouvez maintenant vous connecter.',
                ];
                redirect('/auth/connexion.php');
            }
        } catch (PDOException $e) {
            $erreur = 'Erreur serveur lors de l\'inscription. Réessayez.';
        }
    }
}

$pageTitle = 'Inscription';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-logo">✨</div>
      <h2>Créer un compte</h2>
      <p>Rejoignez KH Boutique</p>
    </div>

    <?php if ($erreur): ?>
      <div class="alert alert-error">⚠ <?= e($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
      <div class="form-group">
        <label for="nom">Nom complet</label>
        <input type="text" id="nom" name="nom" placeholder="Votre nom"
               value="<?= e($_POST['nom'] ?? '') ?>" required minlength="2" autocomplete="name">
      </div>
      <div class="form-group">
        <label for="email">Adresse e-mail</label>
        <input type="email" id="email" name="email" placeholder="votre@email.com"
               value="<?= e($_POST['email'] ?? '') ?>" required autocomplete="email">
      </div>
      <div class="form-group">
        <label for="mot_de_passe">Mot de passe <small>(min. 6 caractères)</small></label>
        <div class="input-eye">
          <input type="password" id="mot_de_passe" name="mot_de_passe"
                 placeholder="••••••••" required minlength="6" autocomplete="new-password">
          <button type="button" class="eye-btn" onclick="togglePwd('mot_de_passe', this)">👁</button>
        </div>
      </div>
      <div class="form-group">
        <label for="mot_de_passe_conf">Confirmer le mot de passe</label>
        <div class="input-eye">
          <input type="password" id="mot_de_passe_conf" name="mot_de_passe_conf"
                 placeholder="••••••••" required autocomplete="new-password">
          <button type="button" class="eye-btn" onclick="togglePwd('mot_de_passe_conf', this)">👁</button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
    </form>

    <div class="auth-footer">
      Déjà un compte ?
      <a href="<?= SITE_URL ?>/auth/connexion.php">Se connecter</a>
    </div>
  </div>
</div>

<script>
function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}
document.querySelector('form').addEventListener('submit', function(e) {
  const p1 = document.getElementById('mot_de_passe').value;
  const p2 = document.getElementById('mot_de_passe_conf').value;
  if (p1 !== p2) { e.preventDefault(); alert('Les mots de passe ne correspondent pas.'); }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
