<?php
// ============================================================
//  KH Boutique — Configuration centrale
// ============================================================

// ── Paramètres base de données ──────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'kh_boutique');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── Paramètres site ──────────────────────────────────────────
// IMPORTANT : ne pas mettre de slash final
// En local Apache : '/kh_boutique'
// En production (domaine racine) : ''
define('SITE_NAME', 'KH Boutique');
define('SITE_URL',  '/kh_boutique');

// ── Connexion PDO (singleton) ────────────────────────────────
function getConnexion(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;">
             <h2>Erreur de connexion à la base de données</h2>
             <p>Vérifiez vos paramètres dans <code>config/config.php</code>.</p>
             </div>');
    }
    return $pdo;
}

// ── Session ──────────────────────────────────────────────────
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// ── Helpers auth ─────────────────────────────────────────────
function isLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    startSession();
    return !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'nom'   => $_SESSION['user_nom'],
        'email' => $_SESSION['user_email'],
        'role'  => $_SESSION['user_role'],
    ];
}

function redirect(string $path): void {
    header('Location: ' . SITE_URL . $path);
    exit;
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ── Helpers panier ───────────────────────────────────────────

/**
 * Compte total d'articles dans le panier (session + BDD si connecté)
 */
function cartCount(): int {
    startSession();
    if (empty($_SESSION['panier'])) return 0;
    return array_sum(array_column($_SESSION['panier'], 'qte'));
}

/**
 * Fusionne le panier SESSION → BDD après connexion.
 * Les articles en session s'ajoutent aux articles déjà en BDD.
 */
function mergeCartToDB(int $userId): void {
    if (empty($_SESSION['panier'])) return;
    $pdo = getConnexion();
    foreach ($_SESSION['panier'] as $produitId => $item) {
        $qte = (int)$item['qte'];
        if ($qte <= 0) continue;
        // Vérifie stock
        $s = $pdo->prepare('SELECT stock FROM produits WHERE id = ?');
        $s->execute([$produitId]);
        $prod = $s->fetch();
        if (!$prod) continue;
        // Upsert : si la ligne existe, additionne les quantités sans dépasser le stock
        $pdo->prepare("
            INSERT INTO panier (user_id, produit_id, quantite)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                quantite = LEAST(quantite + VALUES(quantite), ?)
        ")->execute([$userId, $produitId, $qte, $prod['stock']]);
    }
    // Recharge le panier session depuis la BDD
    loadCartFromDB($userId);
}

/**
 * Charge le panier BDD dans la session.
 */
function loadCartFromDB(int $userId): void {
    $pdo  = getConnexion();
    $rows = $pdo->prepare('SELECT produit_id, quantite FROM panier WHERE user_id = ?');
    $rows->execute([$userId]);
    $_SESSION['panier'] = [];
    foreach ($rows->fetchAll() as $r) {
        $_SESSION['panier'][$r['produit_id']] = ['qte' => $r['quantite']];
    }
}

/**
 * Sauvegarde le panier session → BDD (appelé à chaque modification si connecté).
 */
function saveCartToDB(int $userId): void {
    $pdo = getConnexion();
    // Vider l'ancienne BDD pour cet utilisateur
    $pdo->prepare('DELETE FROM panier WHERE user_id = ?')->execute([$userId]);
    foreach ($_SESSION['panier'] ?? [] as $produitId => $item) {
        $qte = (int)$item['qte'];
        if ($qte > 0) {
            $pdo->prepare('INSERT INTO panier (user_id, produit_id, quantite) VALUES (?, ?, ?)')
                ->execute([$userId, $produitId, $qte]);
        }
    }
}
