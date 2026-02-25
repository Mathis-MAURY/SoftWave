<?php
// ============================================
// Client – Inscription
// ============================================

require_once __DIR__ . '/includes/config.php';

// Si déjà connecté, rediriger vers l'espace client
if (isset($_SESSION['client_id'])) {
    header('Location: compte.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Token de sécurité invalide. Rechargez la page.';
    } else {
        $prenom     = trim(filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_SPECIAL_CHARS));
        $nom        = trim(filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_SPECIAL_CHARS));
        $email      = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password   = $_POST['password'] ?? '';
        $password2  = $_POST['password2'] ?? '';
        
        // Validation
        if (empty($prenom) || empty($nom) || empty($email) || empty($password)) {
            $error = 'Tous les champs sont requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Adresse email invalide.';
        } elseif (strlen($password) < 6) {
            $error = 'Le mot de passe doit contenir au moins 6 caractères.';
        } elseif ($password !== $password2) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            try {
                $db = getDB();
                
                // Vérifier si l'email existe déjà
                $stmt = $db->prepare("SELECT id FROM clients WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error = 'Un compte existe déjà avec cet email.';
                } else {
                    // Créer le compte
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        INSERT INTO clients (email, mot_de_passe, prenom, nom, est_actif)
                        VALUES (?, ?, ?, ?, 1)
                    ");
                    $stmt->execute([$email, $passwordHash, $prenom, $nom]);
                    
                    $success = 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.';
                    
                    // Redirection automatique après 2 secondes
                    header('refresh:2;url=login.php?registered=1');
                }
            } catch (PDOException $e) {
                $error = 'Erreur lors de la création du compte. Veuillez réessayer.';
                error_log('Register error: ' . $e->getMessage());
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
    <title>Inscription – SoftWave</title>
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
        .form-box { width: 100%; max-width: 450px; }
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

        .form-row {
            display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;
            margin-bottom: 1.2rem;
        }
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
        input[type="text"], input[type="email"], input[type="password"] {
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

        .btn-register {
            width: 100%; background: var(--grad); color: #fff; border: none;
            border-radius: 12px; padding: 1rem;
            font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700;
            cursor: pointer; box-shadow: 0 4px 20px rgba(61,127,255,.28);
            transition: transform .2s, box-shadow .2s, opacity .2s;
        }
        .btn-register:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(61,127,255,.4); }
        .btn-register:disabled { opacity: .6; cursor: not-allowed; }

        .hint { text-align: center; margin-top: 1.75rem; font-size: .79rem; color: var(--muted); line-height: 1.7; }
        .hint a { color: var(--accent); text-decoration: none; }
        .hint a:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            body { grid-template-columns: 1fr; }
            .left { display: none; }
            .right { padding: 2rem 1.5rem; min-height: 100vh; }
            .form-row { grid-template-columns: 1fr; }
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
        <span class="left-tag">Créez votre compte</span>
        <h1 class="left-title">Rejoignez la <em>communauté</em> SoftWave</h1>
        <p class="left-desc">Profitez d'avantages exclusifs : achats rapides, suivi de vos commandes, licences centralisées et bien plus.</p>
        <div class="features">
            <div class="feature"><div class="feature-dot">✓</div><span>Achat en un clic</span></div>
            <div class="feature"><div class="feature-dot">✓</div><span>Suivi des commandes</span></div>
            <div class="feature"><div class="feature-dot">✓</div><span>Gestion des licences</span></div>
            <div class="feature"><div class="feature-dot">✓</div><span>Offres exclusives</span></div>
        </div>
    </div>
    <div class="left-foot">
        <a href="index.html" class="left-back">← Retour au site</a>
    </div>
</div>

<!-- ── Panneau droit ── -->
<div class="right">
    <div class="form-box">

        <h1 class="form-box__title">Inscription</h1>
        <p class="form-box__sub">Créez votre compte gratuitement</p>

        <?php if ($error): ?>
            <div class="alert alert--error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert--success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" id="registerForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <div class="input-wrap">
                        <span class="input-ico">👤</span>
                        <input type="text" id="prenom" name="prenom"
                            value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                            placeholder="Jean" required autofocus autocomplete="given-name">
                    </div>
                </div>
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <div class="input-wrap">
                        <span class="input-ico">👤</span>
                        <input type="text" id="nom" name="nom"
                            value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                            placeholder="Dupont" required autocomplete="family-name">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-wrap">
                    <span class="input-ico">✉️</span>
                    <input type="email" id="email" name="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="jean.dupont@exemple.fr" required autocomplete="email">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrap">
                    <span class="input-ico">🔒</span>
                    <input type="password" id="password" name="password"
                        placeholder="••••••••" required autocomplete="new-password">
                    <button type="button" class="eye-btn" id="eyeBtn1">👁</button>
                </div>
            </div>

            <div class="form-group">
                <label for="password2">Confirmer le mot de passe</label>
                <div class="input-wrap">
                    <span class="input-ico">🔒</span>
                    <input type="password" id="password2" name="password2"
                        placeholder="••••••••" required autocomplete="new-password">
                    <button type="button" class="eye-btn" id="eyeBtn2">👁</button>
                </div>
            </div>

            <button type="submit" class="btn-register" id="registerBtn">Créer mon compte</button>
        </form>

        <p class="hint">
            Vous avez déjà un compte ?<br>
            <a href="login.php">Connectez-vous</a>
        </p>

    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('eyeBtn1')?.addEventListener('click', function () {
    const pw = document.getElementById('password');
    const hidden = pw.type === 'password';
    pw.type = hidden ? 'text' : 'password';
    this.textContent = hidden ? '🙈' : '👁';
});
document.getElementById('eyeBtn2')?.addEventListener('click', function () {
    const pw = document.getElementById('password2');
    const hidden = pw.type === 'password';
    pw.type = hidden ? 'text' : 'password';
    this.textContent = hidden ? '🙈' : '👁';
});

// Submit loading state
document.getElementById('registerForm')?.addEventListener('submit', function () {
    const btn = document.getElementById('registerBtn');
    btn.disabled = true;
    btn.textContent = 'Création du compte…';
});
</script>

</body>
</html>
