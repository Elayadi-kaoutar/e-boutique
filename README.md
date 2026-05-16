# KH Boutique — Guide d'installation & déploiement

## Structure du projet

```
kh_boutique/
├── admin/
│   ├── dashboard.php       # Tableau de bord admin
│   ├── produit_form.php    # Ajouter / éditer un produit
│   └── delete_produit.php  # Supprimer un produit
├── assets/
│   └── css/
│       └── style.css
├── auth/
│   ├── connexion.php
│   ├── inscription.php
│   └── logout.php
├── config/
│   └── config.php          # ← À configurer en premier
├── includes/
│   ├── header.php
│   └── footer.php
├── index.php               # Boutique (page d'accueil)
├── panier.php              # Panier
├── commande.php            # Tunnel de commande
├── mes-commandes.php       # Historique client
├── database.sql            # Schéma + données de démo
├── .htaccess
└── README.md
```

## Installation locale (XAMPP / WAMP / LAMP)

### 1. Copier le projet
Placez le dossier `kh_boutique/` dans votre répertoire web :
- XAMPP : `C:/xampp/htdocs/kh_boutique/`
- Linux  : `/var/www/html/kh_boutique/`

### 2. Créer la base de données
```bash
mysql -u root -p < database.sql
```
Ou importez `database.sql` via **phpMyAdmin**.

### 3. Configurer `config/config.php`
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'kh_boutique');
define('DB_USER', 'root');
define('DB_PASS', '');            // votre mot de passe MySQL
define('SITE_URL', '/kh_boutique'); // chemin relatif Apache
```

### 4. Tester
Accédez à : `http://localhost/kh_boutique/`

**Compte admin par défaut :**
- Email : `admin@khboutique.com`
- Mot de passe : `admin123`

---

## Déploiement en production (hébergement mutualisé / VPS)

### 1. Adapter `config.php`
Si le site est à la **racine du domaine** (ex. `https://khboutique.com/`) :
```php
define('SITE_URL', '');  // chaîne vide
```
Si dans un **sous-dossier** (ex. `https://monhote.com/boutique/`) :
```php
define('SITE_URL', '/boutique');
```

### 2. Modifier les credentials BDD
```php
define('DB_HOST', 'localhost');   // souvent 'localhost' chez l'hébergeur
define('DB_NAME', 'votre_bdd');
define('DB_USER', 'votre_user');
define('DB_PASS', 'votre_mdp');
```

### 3. Importer la base via phpMyAdmin
Allez dans phpMyAdmin de votre hébergeur et importez `database.sql`.

### 4. Désactiver l'affichage des erreurs PHP
Dans `config.php` ou `.htaccess` (déjà configuré) :
```php
ini_set('display_errors', 0);
error_reporting(0);
```

### 5. HTTPS
Activez SSL via votre hébergeur (Let's Encrypt gratuit).
Mettez à jour `.htaccess` pour forcer HTTPS :
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
```

---

## Fonctionnalités

| Fonctionnalité | Statut |
|---|---|
| Catalogue produits avec filtres | ✅ |
| Panier session + persistance BDD | ✅ |
| Fusion panier session→BDD à la connexion | ✅ |
| Inscription / Connexion sécurisée (bcrypt) | ✅ |
| Tunnel de commande complet | ✅ |
| Paiement à la livraison | ✅ |
| Historique des commandes client | ✅ |
| Dashboard admin avec stats | ✅ |
| Gestion produits (CRUD) | ✅ |
| Gestion statuts commandes | ✅ |
| Design responsive mobile | ✅ |
| Protection CSRF (sessions régénérées) | ✅ |

---

## Compte par défaut

| Rôle | Email | Mot de passe |
|---|---|---|
| Admin | admin@khboutique.com | admin123 |

> ⚠️ Changez ce mot de passe dès la mise en production via phpMyAdmin :
> ```sql
> UPDATE users SET mot_de_passe = '$2y$10$...' WHERE email = 'admin@khboutique.com';
> ```
> Générez le hash avec `password_hash('VotreNouveauMdp', PASSWORD_BCRYPT)` dans un script PHP.
