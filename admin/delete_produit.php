<?php
require_once __DIR__ . '/../config/config.php';
startSession();
if (!isAdmin()) redirect('/');

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $pdo = getConnexion();
    $pdo->prepare('DELETE FROM produits WHERE id = ?')->execute([$id]);
    $_SESSION['flash'] = ['type' => 'info', 'msg' => 'Produit supprimé.'];
}
redirect('/admin/dashboard.php');
