<?php
// ============================================
// Checkout – Page de paiement
// ============================================

require_once __DIR__ . '/includes/config.php';

// Générer token CSRF si nécessaire
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Si client connecté, pré-remplir les infos
$prenom = '';
$nom = '';
$email = '';
$entreprise = '';

if (isset($_SESSION['client_id'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT prenom, nom, email, entreprise FROM clients WHERE id = ?");
        $stmt->execute([$_SESSION['client_id']]);
        $client = $stmt->fetch();
        if ($client) {
            $prenom = $client['prenom'];
            $nom = $client['nom'];
            $email = $client['email'];
            $entreprise = $client['entreprise'] ?? '';
        }
    } catch (PDOException $e) {
        error_log('Checkout client load error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement – SoftWave</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:      #080c14;
            --surface: #0e1420;
            --border:  rgba(255,255,255,.07);
            --text:    #e8ecf4;
            --muted:   #7a8599;
            --accent:  #3d7fff;
            --green:   #22c983;
            --red:     #ff4757;
            --grad:    linear-gradient(135deg, #3d7fff, #6c4de8);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .logo {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-family: 'Syne', sans-serif;
            font-size: 1.4rem;
            margin-bottom: 1rem;
            text-decoration: none;
            color: var(--text);
        }
        .logo span:first-child {
            font-size: 1.8rem;
            filter: drop-shadow(0 0 8px rgba(61,127,255,.5));
        }
        .logo strong { font-weight: 800; color: var(--accent); }
        h1 {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: .5rem;
        }
        .subtitle {
            color: var(--muted);
            font-size: .95rem;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
        .card {
            background: rgba(255,255,255,.03);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
        }
        .card h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        label {
            display: block;
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--muted);
            margin-bottom: .45rem;
        }
        label span {
            color: var(--red);
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            background: rgba(255,255,255,.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .85rem 1rem;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: .95rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(61,127,255,.12);
        }
        input::placeholder {
            color: rgba(255,255,255,.2);
        }
        .order-summary {
            position: sticky;
            top: 2rem;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .item-info {
            flex: 1;
        }
        .item-name {
            font-weight: 600;
            margin-bottom: .2rem;
        }
        .item-meta {
            font-size: .85rem;
            color: var(--muted);
        }
        .item-price {
            font-weight: 600;
            font-size: 1rem;
        }
        .order-total {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--border);
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: .7rem;
            font-size: .95rem;
        }
        .total-row.final {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent);
            margin-top: 1rem;
        }
        .btn {
            width: 100%;
            background: var(--grad);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 1.1rem;
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 4px 20px rgba(61,127,255,.28);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(61,127,255,.4);
        }
        .btn:disabled {
            opacity: .6;
            cursor: not-allowed;
            transform: none;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            color: var(--muted);
            text-decoration: none;
            font-size: .9rem;
            margin-top: 2rem;
            transition: color .2s;
        }
        .back-link:hover {
            color: var(--text);
        }
        .empty-cart {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--muted);
        }
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: .3;
        }
        .alert {
            background: rgba(255,71,87,.1);
            border: 1px solid rgba(255,71,87,.25);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: var(--red);
            font-size: .9rem;
        }
        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .8rem;
            background: rgba(34,201,131,.08);
            border: 1px solid rgba(34,201,131,.2);
            border-radius: 10px;
            font-size: .85rem;
            color: var(--green);
            margin-top: 1rem;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="index.html" class="logo">
            <span>⬡</span>
            <span>Soft<strong>Wave</strong></span>
        </a>
        <h1>Finaliser votre commande</h1>
        <p class="subtitle">Remplissez vos informations pour procéder au paiement</p>
    </div>

    <div id="cart-content">
        <!-- Le contenu sera chargé par JavaScript -->
    </div>

    <a href="index.html" class="back-link">← Retour à la boutique</a>
</div>

<script>
// Charger le panier depuis sessionStorage
let cart = [];
try {
    cart = JSON.parse(sessionStorage.getItem('sw_cart')) || [];
} catch (e) {
    cart = [];
}

const container = document.getElementById('cart-content');

if (cart.length === 0) {
    container.innerHTML = `
        <div class="card empty-cart">
            <div class="empty-icon">🛒</div>
            <p>Votre panier est vide</p>
            <p style="font-size: .85rem; margin-top: .5rem;">Ajoutez des produits avant de procéder au paiement</p>
        </div>
    `;
} else {
    // Calculer les totaux
    const sousTotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
    const tva = sousTotal * 0.20;
    const total = sousTotal + tva;

    // Générer les items de commande
    let itemsHTML = '';
    cart.forEach(item => {
        itemsHTML += `
            <div class="order-item">
                <div class="item-info">
                    <div class="item-name">${escapeHtml(item.name)}</div>
                    <div class="item-meta">Quantité: ${item.qty} × ${formatPrice(item.price)}</div>
                </div>
                <div class="item-price">${formatPrice(item.price * item.qty)}</div>
            </div>
            <input type="hidden" name="panier[${item.id}][id]" value="${item.id}">
            <input type="hidden" name="panier[${item.id}][qty]" value="${item.qty}">
        `;
    });

    container.innerHTML = `
        <form method="POST" action="traitement.php" id="checkoutForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="grid">
                <div class="card">
                    <h2>Informations de facturation</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Prénom <span>*</span></label>
                            <input type="text" name="prenom" value="<?= htmlspecialchars($prenom) ?>" placeholder="Jean" required>
                        </div>
                        <div class="form-group">
                            <label>Nom <span>*</span></label>
                            <input type="text" name="nom" value="<?= htmlspecialchars($nom) ?>" placeholder="Dupont" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email <span>*</span></label>
                        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="jean.dupont@exemple.fr" required>
                    </div>

                    <div class="form-group">
                        <label>Entreprise (optionnel)</label>
                        <input type="text" name="entreprise" value="<?= htmlspecialchars($entreprise) ?>" placeholder="Nom de votre entreprise">
                    </div>
                </div>

                <div class="order-summary">
                    <div class="card">
                        <h2>Récapitulatif</h2>
                        ${itemsHTML}
                        
                        <div class="order-total">
                            <div class="total-row">
                                <span>Sous-total HT</span>
                                <span>${formatPrice(sousTotal)}</span>
                            </div>
                            <div class="total-row">
                                <span>TVA (20%)</span>
                                <span>${formatPrice(tva)}</span>
                            </div>
                            <div class="total-row final">
                                <span>Total TTC</span>
                                <span>${formatPrice(total)}</span>
                            </div>
                        </div>

                        <button type="submit" class="btn" id="submitBtn">
                            Confirmer la commande →
                        </button>

                        <div class="secure-badge">
                            🔒 Paiement sécurisé
                        </div>
                    </div>
                </div>
            </div>
        </form>
    `;

    // Empêcher la double soumission
    document.getElementById('checkoutForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.textContent = 'Traitement en cours…';
    });
}

function formatPrice(n) {
    return n.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' });
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>

</body>
</html>
