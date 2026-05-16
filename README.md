# 🛍️ KH Boutique — Application e-commerce PHP/MySQL

> Application e-commerce complète déployée sur stack **LAMP** (Linux · Apache · MySQL · PHP).  
> Catalogue produits, gestion du panier, tunnel de commande, espace client et back-office admin.

---

## 📋 Table des matières

- [Stack technique](#-stack-technique)
- [Fonctionnalités](#-fonctionnalités)
- [Structure du projet](#-structure-du-projet)
- [Schéma de la base de données](#-schéma-de-la-base-de-données)
- [Installation locale](#-installation-locale-xampp--wamp--lamp)
- [Déploiement en production (VPS / hébergement mutualisé)](#-déploiement-en-production-vps--hébergement-mutualisé)
- [Configuration](#️-configuration-configconfigphp)
- [Comptes par défaut](#-comptes-par-défaut)
- [Sécurité](#-sécurité)
- [Contribuer](#-contribuer)
- [Licence](#-licence)

---

## 🧱 Stack technique

| Couche | Technologie |
|---|---|
| **OS** | Linux (Ubuntu 20.04+ recommandé) |
| **Serveur web** | Apache 2.4+ avec `mod_rewrite` |
| **Langage** | PHP 8.0+ |
| **Base de données** | MySQL 8.0+ / MariaDB 10.5+ |
| **Front-end** | HTML5 · CSS3 (vanilla, responsive) |
| **Sécurité mots de passe** | `password_hash()` / `password_verify()` — bcrypt |

---

## ✅ Fonctionnalités

### Côté client
| Fonctionnalité | Statut |
|---|---|
| Catalogue produits avec filtres par catégorie | ✅ |
| Panier en session (invité) + persistance BDD (connecté) | ✅ |
| Fusion automatique panier session → BDD à la connexion | ✅ |
| Inscription / Connexion sécurisée (bcrypt) | ✅ |
| Tunnel de commande (adresse, récapitulatif, validation) | ✅ |
| Paiement à la livraison | ✅ |
| Historique des commandes client | ✅ |
| Design responsive (mobile-first) | ✅ |

### Côté administrateur
| Fonctionnalité | Statut |
|---|---|
| Dashboard avec statistiques clés | ✅ |
| Gestion des produits — CRUD complet | ✅ |
| Gestion des statuts de commandes | ✅ |
| Accès protégé par rôle (`admin`) | ✅ |

---

## 📁 Structure du projet

```
kh_boutique/
├── admin/
│   ├── dashboard.php          # Tableau de bord administrateur
│   ├── produit_form.php       # Ajouter / éditer un produit
│   └── delete_produit.php     # Supprimer un produit
├── assets/
│   └── css/
│       └── style.css          # Feuille de styles globale
├── auth/
│   ├── connexion.php          # Formulaire de connexion
│   ├── inscription.php        # Formulaire d'inscription
│   └── logout.php             # Déconnexion + destruction de session
├── config/
│   └── config.php             # ← Paramètres BDD & URL du site (à éditer)
├── includes/
│   ├── header.php             # En-tête global (nav, session)
│   └── footer.php             # Pied de page global
├── index.php                  # Page d'accueil — catalogue boutique
├── panier.php                 # Gestion du panier
├── commande.php               # Tunnel de commande
├── mes-commandes.php          # Historique des commandes (espace client)
├── database.sql               # Schéma BDD + données de démonstration
├── .htaccess                  # Réécriture d'URL + sécurité Apache
└── README.md
```

---

## 🗄️ Schéma de la base de données

```
users              categories          produits
──────────         ──────────          ────────────────────
id (PK)            id (PK)             id (PK)
nom                nom                 nom
email                                  description
mot_de_passe                           prix
telephone          panier              stock
adresse            ──────────          categorie_id (FK)
role               id (PK)             image_url
created_at         user_id (FK)        created_at
                   produit_id (FK)
                   quantite            commandes
                   added_at            ──────────────────
                                       id (PK)
commande_items                         user_id (FK)
─────────────────                      statut
id (PK)                                total
commande_id (FK)                       frais_livraison
produit_id (FK)                        adresse livraison
nom_produit                            created_at
prix_unit
quantite
```

**Catégories de démo incluses :** Robes · Chaussures · Sacs · Parfums · Accessoires  
**Produits de démo :** 14 articles avec images Unsplash

---

## 🚀 Installation locale (XAMPP / WAMP / LAMP)

### Prérequis

- Apache 2.4+ avec `mod_rewrite` activé
- PHP 8.0+
- MySQL 8.0+ ou MariaDB 10.5+
- phpMyAdmin *(optionnel, pour l'import BDD)*

### Étape 1 — Cloner le dépôt

```bash
git clone https://github.com/Elayadi-kaoutar/e-boutique.git kh_boutique
```

Placez le dossier dans votre répertoire web :

```
# XAMPP (Windows)
C:/xampp/htdocs/kh_boutique/

# LAMP / WAMP (Linux)
/var/www/html/kh_boutique/
```

### Étape 2 — Créer la base de données

Via la ligne de commande :

```bash
mysql -u root -p < kh_boutique/database.sql
```

Ou via **phpMyAdmin** : créez une base `kh_boutique` puis importez `database.sql`.

### Étape 3 — Configurer `config/config.php`

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'kh_boutique');
define('DB_USER', 'root');
define('DB_PASS', '');               // votre mot de passe MySQL
define('SITE_URL', '/kh_boutique');  // chemin relatif si en sous-dossier
```

### Étape 4 — Activer `mod_rewrite` (si besoin)

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Vérifiez que `AllowOverride All` est bien activé dans votre VirtualHost.

### Étape 5 — Tester

Ouvrez votre navigateur :

```
http://localhost/kh_boutique/
```

---

## 🌐 Déploiement en production (VPS / hébergement mutualisé)

### 1. Transférer les fichiers

```bash
# Via rsync (VPS)
rsync -avz --exclude '.git' kh_boutique/ user@votre-serveur:/var/www/html/kh_boutique/

# Ou via FTP pour un hébergement mutualisé (FileZilla, etc.)
```

### 2. Adapter `config/config.php`

**Site à la racine du domaine** (`https://khboutique.com/`) :

```php
define('SITE_URL', '');   // chaîne vide
```

**Site dans un sous-dossier** (`https://monhote.com/boutique/`) :

```php
define('SITE_URL', '/boutique');
```

**Credentials BDD de production :**

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'votre_bdd_prod');
define('DB_USER', 'votre_user_prod');
define('DB_PASS', 'mot_de_passe_fort');
```

### 3. Importer la base via phpMyAdmin

Dans l'interface phpMyAdmin de votre hébergeur, créez la base de données puis importez `database.sql`.

### 4. Désactiver l'affichage des erreurs PHP

Ajoutez dans `config/config.php` :

```php
ini_set('display_errors', 0);
error_reporting(0);
```

### 5. Forcer HTTPS

Le fichier `.htaccess` inclus prend déjà en charge la réécriture.  
Ajoutez ces lignes en haut pour forcer le HTTPS :

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
```

> 💡 **Let's Encrypt** est gratuit — demandez un certificat SSL via le panneau de votre hébergeur ou avec `certbot` sur un VPS.

### 6. Permissions des fichiers

```bash
# Fichiers PHP/HTML
find /var/www/html/kh_boutique -type f -name "*.php" -exec chmod 644 {} \;

# Dossiers
find /var/www/html/kh_boutique -type d -exec chmod 755 {} \;

# Propriétaire Apache
sudo chown -R www-data:www-data /var/www/html/kh_boutique/
```

---

## ⚙️ Configuration `config/config.php`

| Constante | Description | Exemple |
|---|---|---|
| `DB_HOST` | Hôte MySQL | `localhost` |
| `DB_NAME` | Nom de la base | `kh_boutique` |
| `DB_USER` | Utilisateur MySQL | `root` |
| `DB_PASS` | Mot de passe MySQL | `MonMotDePasse` |
| `SITE_URL` | Préfixe URL (sous-dossier ou vide) | `/kh_boutique` ou `""` |

---

## 👤 Comptes par défaut

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@khboutique.com` | `admin123` |

> ⚠️ **Changez absolument ce mot de passe avant toute mise en production.**
>
> Générez un nouveau hash bcrypt dans un script PHP temporaire :
> ```php
> echo password_hash('VotreNouveauMotDePasse', PASSWORD_BCRYPT);
> ```
> Puis mettez à jour via phpMyAdmin :
> ```sql
> UPDATE users
> SET mot_de_passe = '$2y$10$VOTRE_HASH_ICI'
> WHERE email = 'admin@khboutique.com';
> ```

---

## 🔒 Sécurité

- **Mots de passe** hachés avec `bcrypt` via `password_hash()` / `password_verify()`
- **Protection CSRF** : régénération de l'ID de session après connexion
- **Préparation des requêtes SQL** : PDO avec requêtes préparées
- **Séparation des rôles** : vérification `role = 'admin'` sur chaque page d'administration
- **`.htaccess`** : accès direct aux dossiers `config/`, `includes/`, `auth/` bloqué
- **Erreurs PHP** masquées en production (voir [section déploiement](#4-désactiver-laffichage-des-erreurs-php))

---

## 🤝 Contribuer

1. Forkez le dépôt
2. Créez une branche : `git checkout -b feature/ma-fonctionnalite`
3. Committez : `git commit -m "feat: ajout de ma fonctionnalité"`
4. Poussez : `git push origin feature/ma-fonctionnalite`
5. Ouvrez une **Pull Request**

---

## 📄 Licence

Ce projet est distribué sans licence explicite. Contactez l'auteur pour toute réutilisation commerciale.

---

<p align="center">
  Développé par <a href="https://github.com/Elayadi-kaoutar">Elayadi Kaoutar</a>
</p>
