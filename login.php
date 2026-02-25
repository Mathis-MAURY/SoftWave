<?php
// ============================================
// Connexion Unifiée – Admin & Client
// ============================================

require_once __DIR__ . '/includes/config.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}
if (isset($_SESSION['client_id'])) {
    header('Location: index.html');
    exit;
}

$error   = '';
$success = isset($_GET['bye']) ? 'Vous avez été déconnecté.' : '';
$success = isset($_GET['registered']) ? 'Inscription réussie ! Connectez-vous.' : $success;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Token de sécurité invalide. Rechargez la page.';
    } else {
        $identifiant = trim(filter_input(INPUT_POST, 'identifiant', FILTER_SANITIZE_SPECIAL_CHARS));
        $password    = $_POST['password'] ?? '';
        $ip          = $_SERVER['REMOTE_ADDR'];
        $key_tries   = 'login_tries_' . $ip;
        $key_lockout = 'login_lock_'  . $ip;

        if (!empty($_SESSION[$key_lockout]) && time() < $_SESSION[$key_lockout]) {
            $wait  = ceil(($_SESSION[$key_lockout] - time()) / 60);
            $error = "Trop de tentatives. Réessayez dans {$wait} min.";
        } elseif (empty($identifiant) || empty($password)) {
            $error = 'Identifiant et mot de passe requis.';
        } else {
            try {
                $db = getDB();
                $isAdmin = false;
                $user = null;
                
                // 1. Vérifier si c'est un administrateur
                $stmt = $db->prepare("SELECT id, identifiant, mot_de_passe, est_actif FROM administrateurs WHERE identifiant = ? OR email = ? LIMIT 1");
                $stmt->execute([$identifiant, $identifiant]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['mot_de_passe'])) {
                    if (!$admin['est_actif']) {
                        $error = 'Votre compte a été désactivé.';
                    } else {
                        $isAdmin = true;
                        $user = $admin;
                    }
                }
                
                // 2. Si pas admin, vérifier si c'est un client
                if (!$isAdmin && !$error) {
                    $stmt = $db->prepare("SELECT id, email, mot_de_passe, prenom, nom, est_actif FROM clients WHERE email = ? LIMIT 1");
                    $stmt->execute([$identifiant]);
                    $client = $stmt->fetch();
                    
                    if ($client && $client['mot_de_passe'] && password_verify($password, $client['mot_de_passe'])) {
                        if (!$client['est_actif']) {
                            $error = 'Votre compte a été désactivé.';
                        } else {
                            $user = $client;
                        }
                    }
                }
                
                // 3. Gérer le résultat
                if ($user && !$error) {
                    // ✅ Connexion réussie
                    session_regenerate_id(true);
                    unset($_SESSION[$key_tries], $_SESSION[$key_lockout]);
                    
                    if ($isAdmin) {
                        // Connexion admin
                        $_SESSION['admin_id'] = $user['id'];
                        $_SESSION['admin_identifiant'] = $user['identifiant'];
                        
                        // Mettre à jour dernière connexion
                        $db->prepare("UPDATE administrateurs SET derniere_connexion = NOW() WHERE id = ?")->execute([$user['id']]);
                        
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        // Connexion client
                        $_SESSION['client_id'] = $user['id'];
                        $_SESSION['client_email'] = $user['email'];
                        $_SESSION['client_prenom'] = $user['prenom'];
                        $_SESSION['client_nom'] = $user['nom'];
                        
                        header('Location: index.html');
                        exit;
                    }
                } else {
                    if (!$error) {
                        // ❌ Échec
                        $_SESSION[$key_tries] = ($_SESSION[$key_tries] ?? 0) + 1;
                        if ($_SESSION[$key_tries] >= 5) {
                            $_SESSION[$key_lockout] = time() + 300;
                            $error = 'Trop de tentatives. Compte bloqué 5 minutes.';
                        } else {
                            $left = 5 - $_SESSION[$key_tries];
                            $error = "Identifiants incorrects — {$left} tentative(s) restante(s).";
                        }
                    }
                }
            } catch (PDOException $e) {
                $error = 'Erreur serveur. Veuillez réessayer.';
                error_log('Login error: ' . $e->getMessage());
            }
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion – SoftWave</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
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
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* ── Panneau gauche ── */
        .left {
            background: linear-gradient(160deg, #0a1020, #0d1a30, #091428);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 3.5rem 4rem;
            position: relative;
            overflow: hidden;
        }
        .left::before {
            content: '';
            position: absolute;
            top: -150px; left: -100px;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(61,127,255,.13), transparent 65%);
            border-radius: 50%;
        }
        .left::after {
            content: '';
            position: absolute;
            bottom: -100px; right: -100px;
            width: 380px; height: 380px;
            background: radial-gradient(circle, rgba(108,77,232,.1), transparent 65%);
            border-radius: 50%;
        }
        .left-logo {
            display: flex; align-items: center; gap: .65rem;
            font-family: 'Syne', sans-serif; font-size: 1.4rem;
            position: relative; z-index: 1; margin-bottom: auto;
        }
        .left-logo span:first-child {
            font-size: 1.8rem;
            filter: drop-shadow(0 0 10px rgba(61,127,255,.55));
        }
        .left-logo strong { font-weight: 800; color: var(--accent); }
        .left-body {
            position: relative; z-index: 1; flex: 1;
            display: flex; flex-direction: column; justify-content: center;
        }
        .left-tag {
            display: inline-block; font-size: .72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .14em; color: var(--accent);
            border: 1px solid rgba(61,127,255,.25); background: rgba(61,127,255,.08);
            padding: .3rem .85rem; border-radius: 100px; margin-bottom: 1.5rem;
        }
        .left-title {
            font-family: 'Syne', sans-serif; font-size: 2.5rem; font-weight: 800;
            line-height: 1.1; margin-bottom: 1.2rem;
        }
        .left-title em {
            font-style: normal;
            background: var(--grad);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .left-desc {
            color: var(--muted); font-size: .93rem; line-height: 1.75;
            max-width: 340px; margin-bottom: 2.5rem;
        }
        .features { display: flex; flex-direction: column; gap: .7rem; }
        .feature {
            display: flex; align-items: center; gap: .75rem;
            font-size: .87rem; color: rgba(255,255,255,.55);
        }
        .feature-dot {
            width: 22px; height: 22px;
            background: rgba(34,201,131,.1); border: 1px solid rgba(34,201,131,.25);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            color: var(--green); font-size: .65rem; flex-shrink: 0;
        }
        .left-foot { position: relative; z-index: 1; margin-top: 3rem; }
        .left-back {
            display: inline-flex; align-items: center; gap: .4rem;
            font-size: .82rem; color: var(--muted); text-decoration: none; transition: color .2s;
        }
        .left-back:hover { color: var(--text); }

        /* ── Panneau droit ── */
        .right {
            display: flex; align-items: center; justify-content: center; padding: 3rem 2rem;
        }
        .form-box { width: 100%; max-width: 400px; }
        .form-box__title {
            font-family: 'Syne', sans-serif; font-size: 1.6rem; font-weight: 800;
            text-align: center; margin-bottom: .4rem;
        }
        .form-box__sub {
            text-align: center; font-size: .87rem; color: var(--muted); margin-bottom: 2rem;
        }
        .alert {
            border-radius: 10px; padding: .85rem 1.1rem; font-size: .87rem;
            margin-bottom: 1.5rem; display: flex; align-items: center; gap: .6rem;
        }
        .alert--error   { background: rgba(255,71,87,.08);  border: 1px solid rgba(255,71,87,.22);  color: var(--red);   }
        .alert--success { background: rgba(34,201,131,.08); border: 1px solid rgba(34,201,131,.22); color: var(--green); }

        .form-group { margin-bottom: 1.2rem; }
        label {
            display: block; font-size: .75rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .1em; color: var(--muted); margin-bottom: .45rem;
        }
        .input-wrap { position: relative; }
        .input-ico {
            position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
            color: var(--muted); pointer-events: none; font-size: .95rem;
        }
        input[type="text"], input[type="password"] {
            width: 100%; background: rgba(255,255,255,.04);
            border: 1px solid var(--border); border-radius: 12px;
            padding: .9rem 2.8rem .9rem 2.7rem; color: var(--text);
            font-family: 'DM Sans', sans-serif; font-size: .95rem;
            outline: none; transition: border-color .2s, box-shadow .2s, background .2s;
        }
        input:focus {
            border-color: var(--accent); background: rgba(61,127,255,.04);
            box-shadow: 0 0 0 3px rgba(61,127,255,.12);
        }
        input::placeholder { color: rgba(255,255,255,.18); }
        .eye-btn {
            position: absolute; right: .9rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--muted);
            cursor: pointer; font-size: .9rem; padding: .2rem; transition: color .2s;
        }
        .eye-btn:hover { color: var(--text); }

        /* ── Case "Se souvenir de moi" ── */
        .remember-row {
            display: flex; align-items: center; gap: .75rem;
            margin-bottom: 1.5rem; cursor: pointer;
        }
        .remember-row input[type="checkbox"] { display: none; }
        .checkbox-box {
            width: 20px; height: 20px;
            border: 1.5px solid var(--border); border-radius: 5px;
            background: rgba(255,255,255,.04); flex-shrink: 0;
            position: relative; transition: all .2s;
        }
        .remember-row input:checked ~ .checkbox-box {
            background: var(--grad); border-color: transparent;
        }
        .remember-row input:checked ~ .checkbox-box::after {
            content: '✓'; position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: .75rem; font-weight: 700;
        }
        .remember-label { font-size: .87rem; color: var(--muted); user-select: none; }
        .remember-label span { color: var(--text); font-weight: 500; }

        .btn-login {
            width: 100%; background: var(--grad); color: #fff; border: none;
            border-radius: 12px; padding: 1rem;
            font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700;
            cursor: pointer; box-shadow: 0 4px 20px rgba(61,127,255,.28);
            transition: transform .2s, box-shadow .2s, opacity .2s;
        }
        .btn-login:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(61,127,255,.4); }
        .btn-login:disabled { opacity: .6; cursor: not-allowed; }

        .hint { text-align: center; margin-top: 1.75rem; font-size: .79rem; color: var(--muted); line-height: 1.7; }
        .hint a { color: var(--accent); }

        @media (max-width: 768px) {
            body { grid-template-columns: 1fr; }
            .left { display: none; }
            .right { padding: 2rem 1.5rem; min-height: 100vh; }
        }
    </style>
</head>
<body>

<!-- ── Panneau gauche ── -->
<div class="left">
    <div class="left-logo">
        <span>⬡</span>
        <span>Soft<strong>Wave</strong></span>
    </div>
    <div class="left-body">
        <span class="left-tag">Connexion</span>
        <h1 class="left-title">Accédez à votre<br><em>espace</em> SoftWave</h1>
        <p class="left-desc">Connectez-vous pour accéder à votre compte client ou à l'espace d'administration.</p>
        <div class="features">
            <div class="feature"><div class="feature-dot">✓</div><span>Connexion sécurisée</span></div>
            <div class="feature"><div class="feature-dot">✓</div><span>Accès rapide</span></div>
            <div class="feature"><div class="feature-dot">✓</div><span>Gestion centralisée</span></div>
            <div class="feature"><div class="feature-dot">✓</div><span>Support dédié</span></div>
        </div>
    </div>
    <div class="left-foot">
        <a href="index.html" class="left-back">← Retour au site</a>
    </div>
</div>

<!-- ── Panneau droit ── -->
<div class="right">
    <div class="form-box">

        <h1 class="form-box__title">Connexion</h1>
        <p class="form-box__sub">Email ou identifiant</p>

        <?php if ($error): ?>
            <div class="alert alert--error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert--success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="form-group">
                <label for="identifiant">Email ou identifiant</label>
                <div class="input-wrap">
                    <span class="input-ico">👤</span>
                    <input type="text" id="identifiant" name="identifiant"
                        value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>"
                        placeholder="admin ou email@exemple.fr" required autofocus autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrap">
                    <span class="input-ico">🔒</span>
                    <input type="password" id="password" name="password"
                        placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="eye-btn" id="eyeBtn">👁</button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">Se connecter</button>
        </form>

        <p class="hint">
            Pas encore de compte ?<br>
            <a href="register.php">Créez un compte gratuitement</a>
        </p>

    </div>
</div>

<script>
document.getElementById('eyeBtn')?.addEventListener('click', function () {
    const pw = document.getElementById('password');
    const hidden = pw.type === 'password';
    pw.type = hidden ? 'text' : 'password';
    this.textContent = hidden ? '🙈' : '👁';
});
document.getElementById('loginForm')?.addEventListener('submit', function () {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.textContent = 'Connexion en cours…';
});
</script>

</body>
</html>