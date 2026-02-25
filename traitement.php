<?php
// ============================================
// Traitement de commande e-commerce
// Tables : clients, commandes, lignes_commande
// ============================================

require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['error'] = 'Token de sécurité invalide. Veuillez réessayer.';
    header('Location: checkout.php');
    exit;
}

// ── Données client ────────────────────────────────
$prenom     = trim(filter_input(INPUT_POST, 'prenom',     FILTER_SANITIZE_SPECIAL_CHARS));
$nom        = trim(filter_input(INPUT_POST, 'nom',        FILTER_SANITIZE_SPECIAL_CHARS));
$email      = trim(filter_input(INPUT_POST, 'email',      FILTER_SANITIZE_EMAIL));
$entreprise = trim(filter_input(INPUT_POST, 'entreprise', FILTER_SANITIZE_SPECIAL_CHARS));

// ── Panier depuis formulaire POST ─────────────────
$panier = $_POST['panier'] ?? [];

// ── Validation ───────────────────────────────────
$errors = [];
if (empty($prenom) || mb_strlen($prenom) < 2) $errors[] = 'Prénom invalide';
if (empty($nom)    || mb_strlen($nom)    < 2) $errors[] = 'Nom invalide';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide';
if (empty($panier) || !is_array($panier))       $errors[] = 'Panier vide';

if (!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    header('Location: checkout.php');
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    // ── 1. Vérifier chaque produit en BDD ─────────
    // On récupère le prix réel depuis produits (jamais faire confiance au client)
    $lignes        = [];
    $sous_total_ht = 0.0;

    foreach ($panier as $produit_id => $data) {
        $id  = (int)($data['id']  ?? $produit_id);
        $qty = max(1, (int)($data['qty'] ?? 1));

        $stmt = $db->prepare("SELECT id, nom, prix FROM produits WHERE id = ? AND est_actif = 1");
        $stmt->execute([$id]);
        $produit = $stmt->fetch();

        if (!$produit) {
            $db->rollBack();
            $_SESSION['error'] = "Produit #$id introuvable ou inactif";
            header('Location: checkout.php');
            exit;
        }

        $lignes[]       = ['produit' => $produit, 'qty' => $qty];
        $sous_total_ht += (float)$produit['prix'] * $qty;
    }

    // ── 2. Calcul TVA 20% ─────────────────────────
    $taux_tva    = 20.00;
    $montant_tva = round($sous_total_ht * 0.20, 2);
    $total_ttc   = round($sous_total_ht + $montant_tva, 2);

    // ── 3. Upsert client ──────────────────────────
    $stmt = $db->prepare("SELECT id FROM clients WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $client_existant = $stmt->fetch();

    if ($client_existant) {
        $client_id = (int)$client_existant['id'];
    } else {
        $db->prepare("INSERT INTO clients (email, prenom, nom, entreprise) VALUES (?, ?, ?, ?)")
           ->execute([$email, $prenom, $nom, $entreprise ?: null]);
        $client_id = (int)$db->lastInsertId();
    }

    // ── 4. Créer la commande ───────────────────────
    $reference = 'SW-' . date('Y') . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT);

    $db->prepare("
        INSERT INTO commandes
            (reference, client_id, client_nom, client_email,
             sous_total_ht, taux_tva, montant_tva, total_ttc,
             statut, moyen_paiement)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en_attente', 'carte')
    ")->execute([
        $reference, $client_id,
        "$prenom $nom", $email,
        $sous_total_ht, $taux_tva, $montant_tva, $total_ttc,
    ]);
    $commande_id = (int)$db->lastInsertId();

    // ── 5. Insérer les lignes de commande ──────────
    $stmt_ligne = $db->prepare("
        INSERT INTO lignes_commande
            (commande_id, produit_id, nom_produit, prix_unitaire, quantite)
        VALUES (?, ?, ?, ?, ?)
    ");
    foreach ($lignes as $l) {
        $stmt_ligne->execute([
            $commande_id,
            $l['produit']['id'],
            $l['produit']['nom'],     // snapshot du nom au moment de l'achat
            $l['produit']['prix'],    // snapshot du prix au moment de l'achat
            $l['qty'],
        ]);
    }

    $db->commit();

    // Renouveler le token CSRF
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Stocker les infos de la commande pour la page de confirmation
    $_SESSION['order_success'] = [
        'reference' => $reference,
        'total_ttc' => $total_ttc,
        'email'     => $email,
    ];

    // Rediriger vers la page de confirmation
    header('Location: confirmation.php');
    exit;

} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    $_SESSION['error'] = 'Erreur serveur. Veuillez réessayer.';
    error_log('Commande error: ' . $e->getMessage());
    header('Location: checkout.php');
    exit;
}