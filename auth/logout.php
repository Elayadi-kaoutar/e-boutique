<?php
require_once __DIR__ . '/../config/config.php';
startSession();

// ── Sauvegarde du panier en BDD avant déconnexion ───────────
// Le panier est déjà en BDD grâce aux saves en temps réel,
// mais on s'assure d'une dernière synchro propre.
if (isLoggedIn()) {
    saveCartToDB((int)$_SESSION['user_id']);
}

// ── Destruction de la session ────────────────────────────────
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// ── Flash après redémarrage de session ──────────────────────
session_start();
$_SESSION['flash'] = [
    'type' => 'info',
    'msg'  => 'Vous avez été déconnecté(e). À bientôt !',
];

header('Location: ' . SITE_URL . '/');
exit;
