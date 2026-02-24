-- ============================================
-- SoftWave - Base de données
-- ============================================

CREATE DATABASE IF NOT EXISTS softwave CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE softwave;

-- Table des logiciels
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2),
    category VARCHAR(50),
    badge VARCHAR(50),
    features JSON,
    image_url VARCHAR(255),
    download_url VARCHAR(255),
    version VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des contacts
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    ip_address VARCHAR(45),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des commandes (base e-commerce)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_ref VARCHAR(20) UNIQUE NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    product_id INT,
    product_name VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','paid','cancelled','refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    license_key VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Table des utilisateurs admin
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Données de démonstration
INSERT INTO products (name, slug, description, short_description, price, original_price, category, badge, features, version) VALUES
(
    'NovaPDF Pro',
    'novapdf-pro',
    'NovaPDF Pro est la solution ultime pour créer, éditer et gérer vos documents PDF. Avec une interface intuitive et des fonctionnalités avancées, transformez votre flux de travail documentaire.',
    'Créez, éditez et gérez vos PDF comme un pro.',
    49.99,
    79.99,
    'Productivité',
    'Populaire',
    '["Conversion illimitée", "OCR avancé", "Signature électronique", "Compression intelligente", "Support 24/7", "Mises à jour 1 an"]',
    '3.2.1'
),
(
    'SecureVault',
    'securevault',
    'SecureVault est votre coffre-fort numérique personnel. Chiffrement AES-256, synchronisation cloud sécurisée et gestionnaire de mots de passe intégré pour protéger toutes vos données sensibles.',
    'Coffre-fort numérique avec chiffrement militaire.',
    34.99,
    NULL,
    'Sécurité',
    'Nouveau',
    '["Chiffrement AES-256", "Gestionnaire de mots de passe", "2FA intégré", "Sync cloud sécurisé", "Multi-appareils", "Zero-knowledge"]',
    '1.5.0'
),
(
    'DataSync Pro',
    'datasync-pro',
    'DataSync Pro synchronise et sauvegarde automatiquement vos données entre tous vos appareils et services cloud. Ne perdez plus jamais un fichier important.',
    'Synchronisation et sauvegarde automatique multi-cloud.',
    59.99,
    89.99,
    'Cloud',
    'Bestseller',
    '["Sync temps réel", "Compatible 15+ clouds", "Versionning illimité", "Planification avancée", "Rapports détaillés", "API REST"]',
    '2.8.3'
),
(
    'DesignFlow',
    'designflow',
    'DesignFlow révolutionne votre processus de création graphique. Templates professionnels, outils vectoriels avancés et collaboration en temps réel pour des créations qui captivent.',
    'Suite créative pour designers professionnels.',
    79.99,
    119.99,
    'Créativité',
    NULL,
    '["1000+ templates premium", "Outils vectoriels avancés", "Collaboration temps réel", "Export multi-format", "Banque d\'images intégrée", "Plugins marketplace"]',
    '4.1.0'
);

-- Admin par défaut (mot de passe : Admin1234!)
INSERT INTO admins (username, email, password_hash) VALUES
('admin', 'admin@softwave.fr', '$2y$12$8k7GBLLxTiF/gnvjQJHrYeWQxMQBW0UXbNf8WXo5vPDJkN3.fCNcG');
