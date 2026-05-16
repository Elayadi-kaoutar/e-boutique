-- ============================================================
--  KH Boutique — Base de données complète v2
--  mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS kh_boutique
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE kh_boutique;

-- ──────────────────────────────────────────────
--  TABLE: users
-- ──────────────────────────────────────────────
DROP TABLE IF EXISTS commande_items;
DROP TABLE IF EXISTS commandes;
DROP TABLE IF EXISTS panier;
DROP TABLE IF EXISTS produits;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  nom          VARCHAR(100)  NOT NULL,
  email        VARCHAR(150)  NOT NULL UNIQUE,
  mot_de_passe VARCHAR(255)  NOT NULL,
  telephone    VARCHAR(20)   DEFAULT NULL,
  adresse      TEXT          DEFAULT NULL,
  role         ENUM('client','admin') NOT NULL DEFAULT 'client',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Compte admin (mot de passe : admin123)
INSERT INTO users (nom, email, mot_de_passe, role) VALUES
('Administrateur', 'admin@khboutique.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ──────────────────────────────────────────────
--  TABLE: categories
-- ──────────────────────────────────────────────
CREATE TABLE categories (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nom  VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categories (nom) VALUES
('Robes'), ('Chaussures'), ('Sacs'), ('Parfums'), ('Accessoires');

-- ──────────────────────────────────────────────
--  TABLE: produits
-- ──────────────────────────────────────────────
CREATE TABLE produits (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  nom          VARCHAR(200) NOT NULL,
  description  TEXT,
  prix         DECIMAL(10,2) NOT NULL,
  stock        INT NOT NULL DEFAULT 0,
  categorie_id INT,
  image_url    VARCHAR(500) DEFAULT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO produits (nom, description, prix, stock, categorie_id, image_url) VALUES
('Robe Élégante Noire',       'Robe longue en soie noire, coupe ajustée, idéale pour les soirées chic.',         89.99, 15, 1, 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=400&q=80'),
('Robe Fleurie d\'Été',       'Robe légère à imprimés floraux, parfaite pour les journées ensoleillées.',         54.99, 20, 1, 'https://images.unsplash.com/photo-1572804013309-59a88b7e92f1?w=400&q=80'),
('Robe de Cocktail Rose',     'Robe mi-longue en dentelle rose pâle, style romantique et raffiné.',               74.99, 10, 1, 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=400&q=80'),
('Escarpins Dorés',           'Talons aiguilles dorés 10 cm, fermeture à boucle, bout pointu.',                  119.99,  8, 2, 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=400&q=80'),
('Sandales Plates Beige',     'Sandales en cuir véritable, semelle anatomique, confort toute la journée.',        45.99, 25, 2, 'https://images.unsplash.com/photo-1603487742131-4160ec999306?w=400&q=80'),
('Bottes Cuir Noir',          'Bottes mi-mollet en cuir pleine fleur, doublure chaude, semelle crantée.',        149.99,  6, 2, 'https://images.unsplash.com/photo-1608256246200-53e635b5b65f?w=400&q=80'),
('Sac à Main Caramel',        'Sac porté épaule en cuir veau, fermeture magnétique, plusieurs compartiments.',   189.99,  5, 3, 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=400&q=80'),
('Pochette Soirée Dorée',     'Pochette rigide dorée ornée de strass, chaîne amovible, intérieur satiné.',        69.99, 12, 3, 'https://images.unsplash.com/photo-1591561954557-26941169b49e?w=400&q=80'),
('Sac Cabas Blanc',           'Grand sac de shopping en toile premium, anses doubles, fermeture zip.',            55.99, 18, 3, 'https://images.unsplash.com/photo-1523779105320-d1cd346ff52b?w=400&q=80'),
('Parfum Rose Orientale',     'Eau de parfum 100 ml, notes de rose, oud et ambre. Longue tenue 12 h.',           129.99,  9, 4, 'https://images.unsplash.com/photo-1588405748880-12d1d2a59f75?w=400&q=80'),
('Parfum Fleur de Jasmin',    'Eau de toilette 75 ml, bouquet floral printanier, légèreté et fraîcheur.',         89.99, 14, 4, 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=400&q=80'),
('Collier Perles Nacrées',    'Collier de perles d\'eau douce, fermoir en argent 925, longueur 45 cm.',           79.99, 20, 5, 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=400&q=80'),
('Foulard en Soie Parisien',  'Carré 90×90 cm, imprimé Liberty, 100 % soie naturelle, finitions roulottées.',    59.99, 15, 5, 'https://images.unsplash.com/photo-1601924994987-69e26d50dc26?w=400&q=80'),
('Lunettes de Soleil Cat-Eye','Monture acétate noire, verres polarisés UV400, étui cuir inclus.',                 64.99, 22, 5, 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=400&q=80');

-- ──────────────────────────────────────────────
--  TABLE: panier (persistance BDD)
-- ──────────────────────────────────────────────
CREATE TABLE panier (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  produit_id INT NOT NULL,
  quantite   INT NOT NULL DEFAULT 1,
  added_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
  UNIQUE KEY uq_user_produit (user_id, produit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
--  TABLE: commandes
-- ──────────────────────────────────────────────
CREATE TABLE commandes (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  statut          ENUM('en_attente','confirmee','expediee','livree','annulee')
                  NOT NULL DEFAULT 'en_attente',
  total           DECIMAL(10,2) NOT NULL,
  frais_livraison DECIMAL(10,2) NOT NULL DEFAULT 4.99,
  nom_livraison   VARCHAR(150) NOT NULL,
  adresse         TEXT NOT NULL,
  ville           VARCHAR(100) NOT NULL,
  code_postal     VARCHAR(20) NOT NULL,
  pays            VARCHAR(80) NOT NULL DEFAULT 'France',
  telephone       VARCHAR(30) DEFAULT NULL,
  notes           TEXT DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
--  TABLE: commande_items
-- ──────────────────────────────────────────────
CREATE TABLE commande_items (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  commande_id INT NOT NULL,
  produit_id  INT NOT NULL,
  nom_produit VARCHAR(200) NOT NULL,
  prix_unit   DECIMAL(10,2) NOT NULL,
  quantite    INT NOT NULL,
  FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  FOREIGN KEY (produit_id)  REFERENCES produits(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
