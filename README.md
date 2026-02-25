# ⬡ SoftWave – Plateforme E-commerce de Logiciels

> Plateforme e-commerce complète pour la vente de logiciels avec système d'authentification double (admin/client), panier d'achat, processus de commande et gestion des produits.

## 🗂️ Structure du projet

```
softwave/
├── api/
│   └── products.php            # API REST produits
├── contact/
│   └── process.php             # Traitement formulaire de contact
├── css/
│   └── style.css               # Styles principaux
├── database/
│   ├── database.sql            # Schéma BDD complet + données de démo
│   └── migration_rename_password.sql  # Migration colonnes mot_de_passe
├── includes/
│   └── config.php              # Configuration BDD + constantes
├── js/
│   ├── cart.js                 # Gestion panier (sessionStorage)
│   └── main.js                 # JavaScript principal
├── checkout.php                # Page de paiement
├── compte.php                  # Espace client (historique commandes)
├── confirmation.php            # Page de confirmation de commande
├── dashboard.php               # Panel admin
├── index.html                  # Page d'accueil e-commerce
├── index.php                   # Version dynamique (redirection)
├── login.php                   # Connexion unifiée (admin + client)
├── logout.php                  # Déconnexion
├── register.php                # Inscription client
├── traitement.php              # Traitement des commandes
└── README.md
```

## 🚀 Installation rapide

### 1. Cloner le projet

```bash
git clone https://github.com/Mathis-MAURY/softwave.git
cd softwave
```

### 2. Configuration XAMPP

Placer le dossier dans `E:\xampp\htdocs\softwave`

### 3. Base de données MySQL

```bash
# Depuis phpMyAdmin ou MySQL CLI
mysql -u root -p

# Dans MySQL
CREATE DATABASE softwave CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE softwave;
SOURCE E:/xampp/htdocs/softwave/database/database.sql;
```

### 4. Configuration

Éditer `includes/config.php` si nécessaire (valeurs par défaut adaptées à XAMPP) :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'softwave');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_URL', 'http://localhost/softwave');
```

### 5. Accéder au site

```
http://localhost/softwave/              ← Site e-commerce
http://localhost/softwave/login.php     ← Connexion (admin ou client)
http://localhost/softwave/register.php  ← Inscription client
```

## 🔑 Identifiants par défaut

**Administrateur :**

- Email : `admin@softwave.fr`
- Mot de passe : `Admin123`

**Client de test :**

- Créer un compte via `register.php`

## 💾 Base de données

### Tables principales

| Table              | Description                                  |
| ------------------ | -------------------------------------------- |
| `administrateurs`  | Comptes admin avec mot de passe hashé        |
| `clients`          | Comptes clients avec mot de passe hashé      |
| `tokens_connexion` | Tokens "Se souvenir de moi" (Remember Me)    |
| `produits`         | Catalogue produits (nom, prix, description)  |
| `categories`       | Catégories de produits                       |
| `commandes`        | Commandes clients (référence, total, statut) |
| `lignes_commande`  | Détail des produits par commande             |

### Migration existante

Si vous avez une base avec les anciennes colonnes `mot_de_passe_hash` :

```bash
mysql -u root -p softwave < database/migration_rename_password.sql
```

## ✨ Fonctionnalités implémentées

### 🛒 E-commerce

- ✅ Catalogue produits dynamique (via API REST)
- ✅ Panier d'achat avec sessionStorage
- ✅ Page de checkout avec formulaire de facturation
- ✅ Traitement des commandes en base de données
- ✅ Page de confirmation avec référence de commande
- ✅ Calcul automatique TVA (20%)

### 👤 Authentification

- ✅ Connexion unifiée (admin + client sur même page)
- ✅ Redirection automatique (admin → dashboard, client → site)
- ✅ Inscription client avec validation
- ✅ Hachage sécurisé des mots de passe (password_hash)
- ✅ Protection CSRF (token par session)
- ✅ Sessions sécurisées (httponly, samesite)

### 📊 Dashboard Admin

- ✅ Vue d'ensemble des commandes
- ✅ Statistiques en temps réel
- ✅ Gestion des produits
- ✅ Accès sécurisé (vérification session)

### 🙍 Espace Client

- ✅ Historique des commandes
- ✅ Détails de compte
- ✅ Déconnexion sécurisée

### 📧 Contact

- ✅ Formulaire de contact sécurisé
- ✅ Validation serveur et client
- ✅ Honeypot anti-spam

## 🔐 Sécurité intégrée

- ✅ Protection CSRF (token par session)
- ✅ Validation & sanitisation des inputs
- ✅ Requêtes PDO préparées (anti-injection SQL)
- ✅ Honeypot anti-spam
- ✅ Rate limiting par IP (formulaire contact)
- ✅ Headers de sécurité
- ✅ Sessions sécurisées (httponly, samesite)
- ✅ Hachage bcrypt pour mots de passe

## 🛠️ Stack technique

| Couche          | Technologie                  |
| --------------- | ---------------------------- |
| Frontend        | HTML5 / CSS3 / JS ES6+       |
| Backend         | PHP 8.1+                     |
| Base de données | MySQL 8.0+                   |
| Sécurité        | PDO, CSRF, password_hash     |
| Fonts           | Google Fonts (Syne, DM Sans) |
| État panier     | sessionStorage               |

## 📋 Pré-requis

- PHP >= 8.1 (extensions: PDO, PDO_MySQL, mbstring, session)
- MySQL >= 8.0 ou MariaDB >= 10.6
- Apache (XAMPP recommandé pour Windows)
- Navigateur moderne avec support ES6+ et sessionStorage

## 🚧 Roadmap futures améliorations

- [ ] Intégration Stripe / PayPal pour paiements réels
- [ ] Envoi emails transactionnels (PHPMailer)
- [ ] Génération de clés de licence produits
- [ ] Téléchargement des logiciels depuis espace client
- [ ] Panel admin avancé (CRUD complet produits/clients/commandes)
- [ ] Système de coupons de réduction
- [ ] Recherche et filtres produits
- [ ] Historique de navigation produits
- [ ] Système de notation/avis clients

## 🌿 Workflow Git

```bash
# Branches recommandées
main          # Production
develop       # Développement
feature/*     # Nouvelles fonctionnalités
hotfix/*      # Corrections urgentes

# Exemple de workflow
git checkout -b feature/payment-integration
git add .
git commit -m "feat: add payment gateway integration"
git push origin feature/payment-integration
```

## 📝 Notes de développement

- Les mots de passe sont stockés avec `password_hash()` (bcrypt, coût 12)
- Le panier utilise `sessionStorage` (clé: `sw_cart`)
- Les sessions PHP expirent après 7200 secondes (2h)
- Les tokens CSRF sont régénérés à chaque session
- Les commandes sont enregistrées même sans compte client (email obligatoire)
