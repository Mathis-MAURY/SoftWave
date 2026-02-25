<?php
// ============================================
// Espace Client – Mon Compte
// ============================================

require_once __DIR__ . '/includes/config.php';

// Vérifier que le client est connecté
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}

$clientId = $_SESSION['client_id'];

// Récupérer les informations du client
try {
    $db = getDB();
    
    // Informations client
    $stmt = $db->prepare("SELECT * FROM clients WHERE id = ? LIMIT 1");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();
    
    if (!$client) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // Commandes du client
    $stmt = $db->prepare("
        SELECT c.*, 
               (SELECT COUNT(*) FROM lignes_commande WHERE commande_id = c.id) as nb_produits
        FROM commandes c
        WHERE c.client_id = ?
        ORDER BY c.cree_le DESC
        LIMIT 10
    ");
    $stmt->execute([$clientId]);
    $commandes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Client account error: ' . $e->getMessage());
    $error = 'Erreur lors du chargement des données.';
}

$statusLabels = [
    'en_attente' => ['En attente', 'orange'],
    'paye'       => ['Payée', 'green'],
    'annule'     => ['Annulée', 'red'],
    'rembourse'  => ['Remboursée', 'blue']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte – SoftWave</title>
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
            --orange:  #f5a623;
            --blue:    #3d7fff;
            --grad:    linear-gradient(135deg, #3d7fff, #6c4de8);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Header ── */
        .header {
            background: rgba(14,20,32,.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            padding: 1.2rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: .65rem;
            font-family: 'Syne', sans-serif;
            font-size: 1.3rem;
            text-decoration: none;
            color: var(--text);
        }
        .logo span:first-child {
            font-size: 1.6rem;
            filter: drop-shadow(0 0 8px rgba(61,127,255,.5));
        }
        .logo strong { font-weight: 800; color: var(--accent); }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .btn {
            padding: .7rem 1.3rem;
            border-radius: 10px;
            font-size: .87rem;
            font-weight: 600;
            text-decoration: none;
            transition: all .2s;
            border: none;
            cursor: pointer;
        }
        .btn--ghost {
            background: rgba(255,255,255,.04);
            color: var(--text);
            border: 1px solid var(--border);
        }
        .btn--ghost:hover {
            background: rgba(255,255,255,.08);
            border-color: var(--accent);
        }
        .btn--danger {
            background: rgba(255,71,87,.1);
            color: var(--red);
            border: 1px solid rgba(255,71,87,.2);
        }
        .btn--danger:hover {
            background: rgba(255,71,87,.15);
        }

        /* ── Container ── */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        /* ── Page Title ── */
        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: .5rem;
        }
        .page-subtitle {
            color: var(--muted);
            font-size: .95rem;
            margin-bottom: 2.5rem;
        }

        /* ── Cards Grid ── */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        .card {
            background: rgba(255,255,255,.03);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.8rem;
            transition: border-color .2s, transform .2s;
        }
        .card:hover {
            border-color: rgba(61,127,255,.3);
            transform: translateY(-2px);
        }
        .card-icon {
            font-size: 2rem;
            margin-bottom: .8rem;
        }
        .card-title {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--muted);
            font-weight: 600;
            margin-bottom: .4rem;
        }
        .card-value {
            font-family: 'Syne', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text);
        }

        /* ── Section ── */
        .section {
            margin-bottom: 3rem;
        }
        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        /* ── Info Box ── */
        .info-box {
            background: rgba(255,255,255,.03);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: .9rem 0;
            border-bottom: 1px solid var(--border);
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: var(--muted);
            font-size: .87rem;
        }
        .info-value {
            color: var(--text);
            font-weight: 500;
        }

        /* ── Table ── */
        .table-wrapper {
            background: rgba(255,255,255,.03);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: rgba(255,255,255,.02);
            text-align: left;
            padding: 1rem 1.5rem;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            font-weight: 600;
            border-bottom: 1px solid var(--border);
        }
        td {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--border);
            font-size: .9rem;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:hover {
            background: rgba(255,255,255,.02);
        }
        .badge {
            display: inline-block;
            padding: .35rem .8rem;
            border-radius: 8px;
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .badge--orange { background: rgba(245,166,35,.15); color: var(--orange); }
        .badge--green  { background: rgba(34,201,131,.15); color: var(--green); }
        .badge--red    { background: rgba(255,71,87,.15); color: var(--red); }
        .badge--blue   { background: rgba(61,127,255,.15); color: var(--blue); }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--muted);
        }
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: .5;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            .container {
                padding: 2rem 1.5rem;
            }
            .cards-grid {
                grid-template-columns: 1fr;
            }
            table {
                font-size: .8rem;
            }
            th, td {
                padding: .8rem;
            }
        }
    </style>
</head>
<body>

<!-- ── Header ── -->
<header class="header">
    <div class="header-content">
        <a href="index.html" class="logo">
            <span>⬡</span>
            <span>Soft<strong>Wave</strong></span>
        </a>
        <div class="header-actions">
            <span style="color: var(--muted); font-size: .87rem;">
                Bonjour, <strong style="color: var(--text);"><?= htmlspecialchars($client['prenom']) ?></strong>
            </span>
            <a href="index.html" class="btn btn--ghost">← Boutique</a>
            <a href="client-logout.php" class="btn btn--danger">Déconnexion</a>
        </div>
    </div>
</header>

<!-- ── Main Content ── -->
<div class="container">

    <h1 class="page-title">Mon Compte</h1>
    <p class="page-subtitle">Gérez vos informations et consultez vos commandes</p>

    <!-- ── Stats Cards ── -->
    <div class="cards-grid">
        <div class="card">
            <div class="card-icon">📦</div>
            <div class="card-title">Commandes</div>
            <div class="card-value"><?= count($commandes) ?></div>
        </div>
        <div class="card">
            <div class="card-icon">💰</div>
            <div class="card-title">Total dépensé</div>
            <div class="card-value">
                <?php
                $total = 0;
                foreach ($commandes as $cmd) {
                    if ($cmd['statut'] === 'paye') {
                        $total += $cmd['total_ttc'];
                    }
                }
                echo number_format($total, 2, ',', ' ') . ' €';
                ?>
            </div>
        </div>
        <div class="card">
            <div class="card-icon">✓</div>
            <div class="card-title">Compte actif</div>
            <div class="card-value"><?= $client['est_actif'] ? 'Oui' : 'Non' ?></div>
        </div>
    </div>

    <!-- ── Informations personnelles ── -->
    <div class="section">
        <h2 class="section-title">Informations personnelles</h2>
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Email</span>
                <span class="info-value"><?= htmlspecialchars($client['email']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Nom complet</span>
                <span class="info-value"><?= htmlspecialchars($client['prenom'] . ' ' . $client['nom']) ?></span>
            </div>
            <?php if ($client['entreprise']): ?>
            <div class="info-row">
                <span class="info-label">Entreprise</span>
                <span class="info-value"><?= htmlspecialchars($client['entreprise']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($client['telephone']): ?>
            <div class="info-row">
                <span class="info-label">Téléphone</span>
                <span class="info-value"><?= htmlspecialchars($client['telephone']) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">Pays</span>
                <span class="info-value"><?= htmlspecialchars($client['pays']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Membre depuis</span>
                <span class="info-value"><?= date('d/m/Y', strtotime($client['cree_le'])) ?></span>
            </div>
        </div>
    </div>

    <!-- ── Historique des commandes ── -->
    <div class="section">
        <h2 class="section-title">Mes commandes</h2>
        <?php if (empty($commandes)): ?>
            <div class="table-wrapper">
                <div class="empty-state">
                    <div class="empty-state-icon">🛒</div>
                    <p>Vous n'avez pas encore passé de commande.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Date</th>
                            <th>Produits</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $cmd): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($cmd['reference']) ?></strong></td>
                                <td><?= date('d/m/Y', strtotime($cmd['cree_le'])) ?></td>
                                <td><?= $cmd['nb_produits'] ?> produit(s)</td>
                                <td><strong><?= number_format($cmd['total_ttc'], 2, ',', ' ') ?> €</strong></td>
                                <td>
                                    <?php
                                    $status = $statusLabels[$cmd['statut']] ?? ['Inconnu', 'gray'];
                                    echo '<span class="badge badge--' . $status[1] . '">' . $status[0] . '</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
