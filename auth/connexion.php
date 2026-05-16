<?php
require_once __DIR__ . '/../config/config.php';
startSession();

if (isLoggedIn()) redirect('/');

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($mdp)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse e-mail invalide.';
    } else {
        try {
            $pdo  = getConnexion();
            $stmt = $pdo->prepare('SELECT id, nom, email, mot_de_passe, role FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($mdp, $user['mot_de_passe'])) {
                session_regenerate_id(true);

                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_nom']   = $user['nom'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];

                // ── Fusion panier session → BDD ──────────────────────
                // Les articles choisis AVANT connexion sont conservés
                mergeCartToDB($user['id']);

                $_SESSION['flash'] = [
                    'type' => 'success',
                    'msg'  => 'Bienvenue, ' . $user['nom'] . ' ! Vous êtes connecté(e).',
                ];

                redirect('/');
            } else {
                $erreur = 'E-mail ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $erreur = 'Erreur serveur. Veuillez réessayer.';
        }
    }
}

$pageTitle = 'Connexion';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-logo">👜</div>
      <h2>Connexion</h2>
      <p>Bienvenue sur KH Boutique</p>
    </div>

    <?php if ($erreur): ?>
      <div class="alert alert-error">⚠ <?= e($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
      <div class="form-group">
        <label for="email">Adresse e-mail</label>
        <input type="email" id="email" name="email"
               placeholder="votre@email.com"
               value="<?= e($_POST['email'] ?? '') ?>"
               required autocomplete="email">
      </div>
      <div class="form-group">
        <label for="mot_de_passe">Mot de passe</label>
        <div class="input-eye">
          <input type="password" id="mot_de_passe" name="mot_de_passe"
                 placeholder="••••••••" required autocomplete="current-password">
          <button type="button" class="eye-btn" onclick="togglePwd('mot_de_passe', this)">👁</button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
    </form>

    <div class="auth-footer">
      Pas encore de compte ?
      <a href="<?= SITE_URL ?>/auth/inscription.php">S'inscrire gratuitement</a>
    </div>
  </div>
</div>

<script>
function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
