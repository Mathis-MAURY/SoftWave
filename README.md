# ⬡ SoftWave – Site Vitrine de Logiciels

> Site vitrine professionnel pour la vente de logiciels, avec formulaire de contact sécurisé et base pour l'e-commerce.

## 🗂️ Structure du projet

```
SITE_RESEAUX/
├── api/
│   └── products.php        # API REST produits
├── contact/
│   └── process.php         # Traitement formulaire de contact
├── css/
│   └── style.css           # Styles principaux
├── includes/
│   └── config.php          # Configuration BDD + constantes
├── js/
│   └── main.js             # JavaScript principal
├── .gitignore
├── database.sql            # Schéma BDD + données de démo
├── index.html              # Version statique
├── index.php               # Version dynamique PHP (recommandée)
└── README.md
```

## 🚀 Installation rapide

### 1. Cloner le projet

```bash
git init
git clone https://github.com/Mathis-MAURY/softwave.git
cd softwave
```

### 2. Base de données MySQL

```bash
mysql -u root -p < database.sql
```

### 3. Configuration

```bash
# Éditer includes/config.php avec vos paramètres
```

```php
// includes/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'softwave');
define('DB_USER', 'votre_user');
define('DB_PASS', 'votre_password');
define('SITE_URL', 'http://localhost/Site_Reseaux');
```

### 4. Accéder au site (XAMPP)

```
http://localhost/Site_Reseaux/index.php        ← Site principal
```

## 🔐 Sécurité intégrée

- ✅ Protection CSRF (token par session)
- ✅ Validation & sanitisation des inputs (filter_input)
- ✅ Requêtes PDO préparées (anti-injection SQL)
- ✅ Honeypot anti-spam
- ✅ Rate limiting par IP
- ✅ Headers de sécurité
- ✅ Sessions sécurisées (httponly, samesite)

## 🛒 Roadmap E-commerce

La table `orders` est déjà créée en BDD. Prochaines étapes :

- [ ] Intégration Stripe / PayPal
- [ ] Génération de clés de licence
- [ ] Espace client (téléchargements)
- [ ] Panel admin (gestion produits, commandes, contacts)
- [ ] Emails transactionnels (PHPMailer)
- [ ] Système de coupons de réduction

## 🌿 Workflow Git recommandé

```bash
# Branches
main          # Production
develop       # Développement
feature/*     # Nouvelles fonctionnalités
hotfix/*      # Corrections urgentes

# Exemple
git checkout -b feature/stripe-integration
git commit -m "feat: add Stripe payment integration"
git push origin feature/stripe-integration
```

## 🛠️ Stack technique

| Couche   | Technologie                  |
| -------- | ---------------------------- |
| Frontend | HTML5 / CSS3 / JS ES6+       |
| Backend  | PHP 8.1+                     |
| Base BDD | MySQL 8.0+                   |
| Sécurité | PDO, CSRF, bcrypt            |
| Fonts    | Google Fonts (Syne, DM Sans) |

## 📋 Pré-requis

- PHP >= 8.1 (extensions: PDO, PDO_MySQL, mbstring)
- MySQL >= 8.0 ou MariaDB >= 10.6
- Apache ou Nginx
- Optionnel: Composer (pour PHPMailer, etc.)
