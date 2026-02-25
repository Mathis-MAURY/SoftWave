<?php
// ============================================
// Confirmation de commande
// ============================================

require_once __DIR__ . '/includes/config.php';

// Vérifier qu'on vient bien d'une commande réussie
if (!isset($_SESSION['order_success'])) {
    header('Location: index.html');
    exit;
}

$order = $_SESSION['order_success'];
unset($_SESSION['order_success']); // Supprimer pour éviter les doublons
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande confirmée – SoftWave</title>
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
            --grad:    linear-gradient(135deg, #3d7fff, #6c4de8);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .container {
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .logo {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-family: 'Syne', sans-serif;
            font-size: 1.3rem;
            margin-bottom: 2rem;
            text-decoration: none;
            color: var(--text);
        }
        .logo span:first-child {
            font-size: 1.6rem;
            filter: drop-shadow(0 0 8px rgba(61,127,255,.5));
        }
        .logo strong { font-weight: 800; color: var(--accent); }
        .card {
            background: rgba(255,255,255,.03);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 3rem 2rem;
            margin-bottom: 1.5rem;
        }
        .success-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            animation: bounce 0.6s ease-out;
        }
        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        h1 {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--green);
            margin-bottom: .8rem;
        }
        .subtitle {
            color: var(--muted);
            font-size: .95rem;
            margin-bottom: 2rem;
        }
        .order-info {
            background: rgba(255,255,255,.02);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .8rem 0;
            border-bottom: 1px solid var(--border);
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: var(--muted);
            font-size: .9rem;
        }
        .info-value {
            font-weight: 600;
            font-size: 1rem;
        }
        .info-value.highlight {
            color: var(--green);
            font-size: 1.2rem;
        }
        .message {
            background: rgba(34,201,131,.08);
            border: 1px solid rgba(34,201,131,.2);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            color: var(--green);
            font-size: .9rem;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background: var(--grad);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 1rem 2.5rem;
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 4px 20px rgba(61,127,255,.28);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(61,127,255,.4);
        }
        .secondary-link {
            display: inline-block;
            margin-top: 1rem;
            color: var(--muted);
            text-decoration: none;
            font-size: .9rem;
            transition: color .2s;
        }
        .secondary-link:hover {
            color: var(--text);
        }
    </style>
</head>
<body>

<div class="container">
    <a href="index.html" class="logo">
        <span>⬡</span>
        <span>Soft<strong>Wave</strong></span>
    </a>

    <div class="card">
        <div class="success-icon">✅</div>
        <h1>Commande confirmée !</h1>
        <p class="subtitle">Merci pour votre achat. Votre commande a été enregistrée avec succès.</p>

        <div class="order-info">
            <div class="info-row">
                <span class="info-label">Référence</span>
                <span class="info-value"><?= htmlspecialchars($order['reference']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email</span>
                <span class="info-value"><?= htmlspecialchars($order['email']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Montant payé</span>
                <span class="info-value highlight"><?= number_format($order['total_ttc'], 2, ',', ' ') ?> € TTC</span>
            </div>
        </div>

        <div class="message">
            📧 Un email de confirmation a été envoyé à votre adresse.<br>
            Vous recevrez vos licences dans les plus brefs délais.
        </div>

        <a href="index.html" class="btn">Retour à la boutique</a>
        
        <?php if (isset($_SESSION['client_id'])): ?>
            <a href="compte.php" class="secondary-link">Voir mes commandes →</a>
        <?php endif; ?>
    </div>
</div>

<script>
// Vider le panier après confirmation
try {
    sessionStorage.removeItem('sw_cart');
} catch (e) {
    console.log('Could not clear cart');
}
</script>

</body>
</html>
