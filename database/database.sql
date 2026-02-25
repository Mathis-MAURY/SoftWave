-- ============================================
-- SoftWave - Base de données v2.0
-- Tables nommées en français
-- ============================================

CREATE DATABASE IF NOT EXISTS softwave CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE softwave;

-- ============================================
-- TABLE : categories
-- Référentiel des catégories de logiciels
-- ============================================
CREATE TABLE IF NOT EXISTS categories (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nom          VARCHAR(50)  UNIQUE NOT NULL,
    slug         VARCHAR(50)  UNIQUE NOT NULL,
    icone        VARCHAR(10)  DEFAULT NULL,
    couleur      VARCHAR(20)  DEFAULT NULL,
    cree_le      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE : produits
-- Catalogue des logiciels en vente
-- ============================================
CREATE TABLE IF NOT EXISTS produits (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id        INT           DEFAULT NULL,
    nom                 VARCHAR(100)  NOT NULL,
    slug                VARCHAR(100)  UNIQUE NOT NULL,
    description         TEXT,
    description_courte  VARCHAR(255),
    prix                DECIMAL(10,2) NOT NULL,
    prix_original       DECIMAL(10,2) DEFAULT NULL,
    badge               VARCHAR(50)   DEFAULT NULL,
    fonctionnalites     JSON          DEFAULT NULL,
    image_url           VARCHAR(255)  DEFAULT NULL,
    telechargement_url  VARCHAR(255)  DEFAULT NULL,
    version             VARCHAR(20)   DEFAULT NULL,
    stock               INT           DEFAULT -1 COMMENT '-1 = illimité (licence logicielle)',
    est_actif           TINYINT(1)    DEFAULT 1,
    cree_le             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modifie_le          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categorie (categorie_id),
    INDEX idx_actif     (est_actif),
    INDEX idx_prix      (prix),
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ============================================
-- TABLE : administrateurs
-- Comptes administrateurs du back-office
-- ============================================
CREATE TABLE IF NOT EXISTS administrateurs (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    identifiant         VARCHAR(50)  UNIQUE NOT NULL,
    email               VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe        VARCHAR(255) NOT NULL,
    derniere_connexion  DATETIME     DEFAULT NULL,
    est_actif           TINYINT(1)   DEFAULT 1,
    cree_le             TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE : tokens_connexion
-- Tokens "Se souvenir de moi" (30 jours)
-- ============================================
CREATE TABLE IF NOT EXISTS tokens_connexion (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    administrateur_id INT          NOT NULL,
    token_hash      VARCHAR(255)   NOT NULL,
    expire_le       DATETIME       NOT NULL,
    cree_le         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token   (token_hash),
    INDEX idx_expiration (expire_le),
    FOREIGN KEY (administrateur_id) REFERENCES administrateurs(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE : clients
-- Clients ayant passé commande
-- ============================================
CREATE TABLE IF NOT EXISTS clients (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    email               VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe        VARCHAR(255) DEFAULT NULL COMMENT 'NULL = compte créé par commande sans inscription',
    prenom              VARCHAR(75)  DEFAULT NULL,
    nom                 VARCHAR(75)  DEFAULT NULL,
    entreprise          VARCHAR(100) DEFAULT NULL,
    telephone           VARCHAR(20)  DEFAULT NULL,
    pays                CHAR(2)      DEFAULT 'FR',
    est_actif           TINYINT(1)   DEFAULT 1,
    cree_le             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modifie_le          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);

-- ============================================
-- TABLE : commandes
-- Commandes passées sur le site
-- ============================================
CREATE TABLE IF NOT EXISTS commandes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reference       VARCHAR(20)   UNIQUE NOT NULL,
    client_id       INT           DEFAULT NULL,
    client_nom      VARCHAR(100)  NOT NULL,
    client_email    VARCHAR(150)  NOT NULL,
    sous_total_ht   DECIMAL(10,2) NOT NULL,
    taux_tva        DECIMAL(5,2)  DEFAULT 20.00,
    montant_tva     DECIMAL(10,2) NOT NULL,
    total_ttc       DECIMAL(10,2) NOT NULL,
    statut          ENUM('en_attente','paye','annule','rembourse') DEFAULT 'en_attente',
    moyen_paiement  VARCHAR(50)   DEFAULT NULL,
    ref_paiement    VARCHAR(100)  DEFAULT NULL COMMENT 'ID transaction Stripe/PayPal',
    notes           TEXT          DEFAULT NULL,
    cree_le         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modifie_le      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_statut       (statut),
    INDEX idx_client       (client_id),
    INDEX idx_client_email (client_email),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
);

-- ============================================
-- TABLE : lignes_commande
-- Produits inclus dans une commande (1 → N)
-- ============================================
CREATE TABLE IF NOT EXISTS lignes_commande (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    commande_id     INT           NOT NULL,
    produit_id      INT           DEFAULT NULL,
    nom_produit     VARCHAR(100)  NOT NULL,
    prix_unitaire   DECIMAL(10,2) NOT NULL,
    quantite        INT           DEFAULT 1,
    cle_licence     VARCHAR(100)  DEFAULT NULL,
    INDEX idx_commande (commande_id),
    INDEX idx_produit  (produit_id),
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id)  REFERENCES produits(id)  ON DELETE SET NULL
);

-- ============================================
-- TABLE : messages_contact
-- Messages reçus via le formulaire de contact
-- ============================================
CREATE TABLE IF NOT EXISTS messages_contact (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    sujet       VARCHAR(200) DEFAULT NULL,
    message     TEXT         NOT NULL,
    adresse_ip  VARCHAR(45)  DEFAULT NULL,
    est_lu      TINYINT(1)   DEFAULT 0,
    est_repondu TINYINT(1)   DEFAULT 0,
    cree_le     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lu      (est_lu),
    INDEX idx_email   (email),
    INDEX idx_date    (cree_le)
);

-- ============================================
-- TABLE : journal_audit
-- Traçabilité des actions administrateurs
-- ============================================
CREATE TABLE IF NOT EXISTS journal_audit (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    administrateur_id INT          DEFAULT NULL,
    action            VARCHAR(100) NOT NULL COMMENT 'ex: produit.creation, commande.statut_change',
    entite            VARCHAR(50)  DEFAULT NULL COMMENT 'ex: produit, commande, message',
    entite_id         INT          DEFAULT NULL,
    detail            JSON         DEFAULT NULL,
    adresse_ip        VARCHAR(45)  DEFAULT NULL,
    cree_le           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin  (administrateur_id),
    INDEX idx_action (action),
    INDEX idx_date   (cree_le),
    FOREIGN KEY (administrateur_id) REFERENCES administrateurs(id) ON DELETE SET NULL
);

-- ============================================
-- DONNÉES DE DÉMONSTRATION
-- ============================================

-- Catégories
INSERT INTO categories (nom, slug, icone, couleur) VALUES
('Productivité', 'productivite', '📄', '#3d7fff'),
('Sécurité',     'securite',    '🔐', '#22c983'),
('Cloud',        'cloud',       '☁️', '#6c4de8'),
('Créativité',   'creativite',  '🎨', '#f5a623');

-- Produits
INSERT INTO produits (categorie_id, nom, slug, description, description_courte, prix, prix_original, badge, fonctionnalites, version) VALUES
(1, 'NovaPDF Pro', 'novapdf-pro',
 'NovaPDF Pro est la solution ultime pour créer, éditer et gérer vos documents PDF.',
 'Créez, éditez et gérez vos PDF comme un pro.', 49.99, 79.99, 'Populaire',
 '["Conversion illimitée","OCR avancé","Signature électronique","Compression intelligente","Support 24/7","Mises à jour 1 an"]', '3.2.1'),

(2, 'SecureVault', 'securevault',
 'Coffre-fort numérique avec chiffrement AES-256 et gestionnaire de mots de passe.',
 'Coffre-fort numérique avec chiffrement militaire.', 34.99, NULL, 'Nouveau',
 '["Chiffrement AES-256","Gestionnaire de mots de passe","2FA intégré","Sync cloud sécurisé","Multi-appareils","Zero-knowledge"]', '1.5.0'),

(3, 'DataSync Pro', 'datasync-pro',
 'DataSync Pro synchronise et sauvegarde automatiquement vos données multi-cloud.',
 'Synchronisation et sauvegarde automatique multi-cloud.', 59.99, 89.99, 'Bestseller',
 '["Sync temps réel","Compatible 15+ clouds","Versionning illimité","Planification avancée","Rapports détaillés","API REST"]', '2.8.3'),

(4, 'DesignFlow', 'designflow',
 'Suite créative avec templates professionnels et collaboration temps réel.',
 'Suite créative pour designers professionnels.', 79.99, 119.99, NULL,
 '["1000+ templates premium","Outils vectoriels avancés","Collaboration temps réel","Export multi-format","Banque d\'images intégrée","Plugins marketplace"]', '4.1.0');

-- Administrateur par défaut
INSERT INTO administrateurs (identifiant, email, mot_de_passe) VALUES
('admin', 'admin@softwave.fr', '$2y$12$fF6/ndDqiHjS.iyHSagb0ee8FQCDefMes5PNPUvFmbZn7w6f.gU/G');