<?php
// ============================================
// Admin – Dashboard
// ============================================

require_once __DIR__ . '/../includes/config.php';

// Guard : rediriger si non connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier que le compte existe toujours
try {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, identifiant FROM administrateurs WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    if (!$admin) { session_destroy(); header('Location: login.php'); exit; }
} catch (PDOException $e) {
    session_destroy(); header('Location: login.php'); exit;
}

// Récupérer les stats
try {
    $stats = [
        'produits' => $db->query("SELECT COUNT(*) FROM produits WHERE est_actif = 1")->fetchColumn(),
        'messages' => $db->query("SELECT COUNT(*) FROM messages_contact")->fetchColumn(),
        'non_lus'  => $db->query("SELECT COUNT(*) FROM messages_contact WHERE est_lu = 0")->fetchColumn(),
        'commandes'=> $db->query("SELECT COUNT(*) FROM commandes")->fetchColumn(),
        'revenus'  => $db->query("SELECT COALESCE(SUM(total_ttc), 0) FROM commandes WHERE statut = 'paye'")->fetchColumn(),
    ];
    $lastMessages = $db->query("SELECT * FROM messages_contact ORDER BY cree_le DESC LIMIT 6")->fetchAll();
    $lastCommandes = $db->query("SELECT o.*, c.prenom, c.nom FROM commandes o LEFT JOIN clients c ON c.id = o.client_id ORDER BY o.cree_le DESC LIMIT 6")->fetchAll();
} catch (PDOException $e) {
    $stats = ['produits'=>0,'messages'=>0,'non_lus'=>0,'commandes'=>0,'revenus'=>0];
    $lastMessages = []; $lastCommandes = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Admin SoftWave</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #080c14; --surface: #0e1420; --surf2: #141c2c;
            --border: rgba(255,255,255,.07); --text: #e8ecf4; --muted: #7a8599;
            --accent: #3d7fff; --green: #22c983; --amber: #f5a623; --red: #ff4757;
            --purple: #6c4de8; --grad: linear-gradient(135deg,#3d7fff,#6c4de8);
            --sidebar: 230px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; font-size: 14px; }
        a { color: inherit; text-decoration: none; }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar);
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0;
            z-index: 10; padding: 1.5rem 1rem;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .s-logo {
            display: flex; align-items: center; gap: .6rem;
            font-family: 'Syne', sans-serif; font-size: 1.25rem;
            padding: .25rem .5rem 1.25rem;
            border-bottom: 1px solid var(--border); margin-bottom: 1.25rem;
        }
        .s-logo span:first-child { font-size: 1.5rem; filter: drop-shadow(0 0 8px rgba(61,127,255,.5)); }
        .s-logo strong { color: var(--accent); }
        .s-nav { display: flex; flex-direction: column; gap: .2rem; flex: 1; min-height: 0; }
        .s-link {
            display: flex; align-items: center; gap: .7rem;
            padding: .65rem .9rem; border-radius: 9px;
            color: var(--muted); font-size: .88rem; font-weight: 500;
            transition: all .2s; position: relative;
        }
        .s-link:hover { background: rgba(255,255,255,.04); color: var(--text); }
        .s-link.active { background: rgba(61,127,255,.12); color: var(--accent); font-weight: 600; }
        .s-badge {
            margin-left: auto; background: var(--red); color: #fff;
            font-size: .68rem; font-weight: 700; padding: .15rem .4rem;
            border-radius: 100px;
        }
        .s-foot {
            border-top: 1px solid var(--border); padding-top: 1rem;
            display: flex; align-items: center; gap: .7rem; margin-bottom: .75rem;
            flex-shrink: 0;
        }
        .s-avatar {
            width: 34px; height: 34px; background: var(--grad);
            border-radius: 8px; display: grid; place-items: center;
            font-family: 'Syne', sans-serif; font-weight: 800; font-size: .8rem; flex-shrink: 0;
        }
        .s-foot-info { flex: 1; overflow: hidden; }
        .s-foot-info strong { display: block; font-size: .85rem; }
        .s-foot-info span { display: block; font-size: .73rem; color: var(--muted); }
        /* Bouton déconnexion bien visible */
        .s-logout-btn {
            display: flex; align-items: center; justify-content: center; gap: .6rem;
            width: 100%; padding: .7rem 1rem;
            background: rgba(255,71,87,.08);
            border: 1px solid rgba(255,71,87,.2);
            border-radius: 10px;
            color: #ff4757;
            font-size: .87rem; font-weight: 600;
            text-decoration: none;
            transition: all .2s;
            flex-shrink: 0;
        }
        .s-logout-btn:hover {
            background: rgba(255,71,87,.15);
            border-color: rgba(255,71,87,.4);
            transform: translateY(-1px);
        }

        /* ── MAIN ── */
        .main { margin-left: var(--sidebar); flex: 1; padding: 2rem 2.5rem; }

        /* ── TOPBAR ── */
        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 2.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border);
        }
        .topbar h1 { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 800; }
        .topbar p { color: var(--muted); font-size: .85rem; margin-top: .15rem; }
        .btn-ghost {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .5rem 1.1rem; border: 1px solid var(--border); border-radius: 9px;
            font-size: .85rem; font-weight: 600; color: var(--muted); transition: all .2s;
        }
        .btn-ghost:hover { color: var(--text); border-color: rgba(255,255,255,.15); }

        /* ── STATS ── */
        .stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 2rem; }
        .stat {
            background: linear-gradient(145deg,rgba(255,255,255,.04),rgba(255,255,255,.01));
            border: 1px solid var(--border); border-radius: 14px;
            padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 1rem;
            transition: border-color .2s;
        }
        .stat:hover { border-color: rgba(255,255,255,.12); }
        .stat-icon {
            width: 44px; height: 44px; border-radius: 10px;
            display: grid; place-items: center; font-size: 1.2rem; flex-shrink: 0;
        }
        .stat-val { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 800; }
        .stat-lbl { font-size: .78rem; color: var(--muted); margin-top: .1rem; }
        .stat-lbl .badge-inline {
            display: inline-block; background: var(--red); color:#fff;
            font-size: .65rem; font-weight: 700; padding: .1rem .35rem; border-radius: 4px; margin-left: .3rem;
        }

        /* ── GRID ── */
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .card {
            background: linear-gradient(145deg,rgba(255,255,255,.04),rgba(255,255,255,.01));
            border: 1px solid var(--border); border-radius: 16px; overflow: hidden;
        }
        .card-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--border);
        }
        .card-head h2 { font-family: 'Syne', sans-serif; font-size: .95rem; font-weight: 700; }
        .card-head a { font-size: .8rem; color: var(--accent); }
        .card-head a:hover { opacity: .7; }

        /* ── TABLE ── */
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: rgba(255,255,255,.02); }
        th {
            padding: .75rem 1.2rem; text-align: left;
            font-size: .72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .08em; color: var(--muted); white-space: nowrap;
        }
        td {
            padding: .85rem 1.2rem; border-bottom: 1px solid var(--border);
            font-size: .86rem; vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: rgba(255,255,255,.02); }
        td small { color: var(--muted); font-size: .78rem; display: block; }
        .tr-unread td { border-left: 3px solid var(--accent); background: rgba(61,127,255,.04); }

        /* ── STATUS ── */
        .status {
            display: inline-block; font-size: .73rem; font-weight: 700;
            padding: .22rem .55rem; border-radius: 100px; text-transform: capitalize;
        }
        .s-paid      { background: rgba(34,201,131,.12); color: var(--green); }
        .s-pending   { background: rgba(245,166,35,.12); color: var(--amber); }
        .s-cancelled { background: rgba(255,71,87,.12);  color: var(--red); }
        .s-refunded  { background: rgba(122,133,153,.12); color: var(--muted); }

        .btn-sm {
            display: inline-block; font-size: .76rem; font-weight: 600;
            padding: .28rem .65rem; border-radius: 6px; border: 1px solid var(--border);
            background: rgba(255,255,255,.04); color: var(--text); transition: all .2s;
        }
        .btn-sm:hover { border-color: var(--accent); color: var(--accent); }
        code { font-size: .78rem; background: rgba(255,255,255,.06); padding: .15rem .4rem; border-radius: 5px; }
        .empty { padding: 2.5rem; text-align: center; color: var(--muted); }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="s-logo">
        <span>⬡</span>
        <span>Soft<strong>Wave</strong></span>
    </div>
    <nav class="s-nav">
        <a href="dashboard.php" class="s-link active">⊞ &nbsp;Dashboard</a>
        <a href="#" class="s-link">📦 &nbsp;Produits</a>
        <a href="#" class="s-link">
            ✉️ &nbsp;Messages
            <?php if ($stats['non_lus'] > 0): ?>
                <span class="s-badge"><?= $stats['non_lus'] ?></span>
            <?php endif; ?>
        </a>
        <a href="#" class="s-link">🛒 &nbsp;Commandes</a>
    </nav>
    <div class="s-foot">
        <div class="s-avatar"><?= strtoupper(substr($admin['identifiant'], 0, 2)) ?></div>
        <div class="s-foot-info">
            <strong><?= htmlspecialchars($admin['identifiant']) ?></strong>
            <span>Administrateur</span>
        </div>
    </div>
    <a href="logout.php" class="s-logout-btn" onclick="return confirm('Se déconnecter ?')">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Déconnexion
    </a>
</aside>

<!-- MAIN -->
<main class="main">

    <header class="topbar">
        <div>
            <h1>Tableau de bord</h1>
            <p>Bienvenue, <?= htmlspecialchars($admin['identifiant']) ?> 👋</p>
        </div>
        <a href="../softwave/index.html" class="btn-ghost" target="_blank">↗ Voir le site</a>
    </header>

    <!-- STATS -->
    <div class="stats">
        <div class="stat">
            <div class="stat-icon" style="background:rgba(61,127,255,.1);color:#3d7fff">📦</div>
            <div>
                <div class="stat-val"><?= $stats['produits'] ?></div>
                <div class="stat-lbl">Logiciels actifs</div>
            </div>
        </div>
        <div class="stat">
            <div class="stat-icon" style="background:rgba(34,201,131,.1);color:#22c983">✉️</div>
            <div>
                <div class="stat-val"><?= $stats['messages'] ?></div>
                <div class="stat-lbl">
                    Messages reçus
                    <?php if ($stats['non_lus']): ?>
                        <span class="badge-inline"><?= $stats['non_lus'] ?> non lus</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="stat">
            <div class="stat-icon" style="background:rgba(245,166,35,.1);color:#f5a623">🛒</div>
            <div>
                <div class="stat-val"><?= $stats['commandes'] ?></div>
                <div class="stat-lbl">Commandes</div>
            </div>
        </div>
        <div class="stat">
            <div class="stat-icon" style="background:rgba(108,77,232,.1);color:#6c4de8">💶</div>
            <div>
                <div class="stat-val"><?= number_format($stats['revenus'], 2, ',', ' ') ?> €</div>
                <div class="stat-lbl">Revenus encaissés</div>
            </div>
        </div>
    </div>

    <!-- TABLEAUX -->
    <div class="grid2">

        <!-- Derniers messages -->
        <div class="card">
            <div class="card-head">
                <h2>Derniers messages</h2>
                <a href="#">Voir tout →</a>
            </div>
            <?php if (empty($lastMessages)): ?>
                <p class="empty">Aucun message pour l'instant.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr><th>Expéditeur</th><th>Sujet</th><th>Date</th></tr>
                </thead>
                <tbody>
                <?php foreach ($lastMessages as $c): ?>
                <tr class="<?= !$c['est_lu'] ? 'tr-unread' : '' ?>">
                    <td>
                        <strong><?= htmlspecialchars($c['nom']) ?></strong>
                        <small><?= htmlspecialchars($c['email']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($c['sujet'] ?? '—') ?></td>
                    <td><?= date('d/m H:i', strtotime($c['cree_le'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Dernières commandes -->
        <div class="card">
            <div class="card-head">
                <h2>Dernières commandes</h2>
                <a href="#">Voir tout →</a>
            </div>
            <?php if (empty($lastCommandes)): ?>
                <p class="empty">Aucune commande. L'e-commerce alimentera cette section.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr><th>Réf.</th><th>Client</th><th>Montant</th><th>Statut</th></tr>
                </thead>
                <tbody>
                <?php foreach ($lastCommandes as $o): ?>
                <tr>
                    <td><code><?= htmlspecialchars($o['reference']) ?></code></td>
                    <td><?= htmlspecialchars($o['client_nom']) ?></td>
                    <td><?= number_format($o['total_ttc'], 2, ',', ' ') ?> €</td>
                    <td>
                        <span class="status s-<?= $o['statut'] ?>">
                            <?= ucfirst($o['statut']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div>
</main>

</body>
</html>