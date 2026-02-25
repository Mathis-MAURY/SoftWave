<?php
// ============================================
// Point d'entrée PHP – génère le token CSRF
// et injecte les données dynamiques
// ============================================

require_once __DIR__ . '/../includes/config.php';

// Générer token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Récupérer les produits depuis la BDD
$produits = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug FROM produits p LEFT JOIN categories c ON c.id = p.categorie_id WHERE p.est_actif = 1 ORDER BY p.cree_le DESC");
    $produits = $stmt->fetchAll();
    foreach ($produits as &$p) {
        $p['fonctionnalites'] = json_decode($p['fonctionnalites'], true) ?? [];
    }
} catch (PDOException $e) {
    // Fallback : produits statiques dans index.html
    error_log('Index products error: ' . $e->getMessage());
}

// Inclure le HTML et injecter les données
ob_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SoftWave – Logiciels professionnels pour booster votre productivité.">
    <title>SoftWave – Logiciels Professionnels</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php /* Le contenu est identique à index.html, mais avec PHP dynamique */ ?>

<script>
// Injection CSRF dans le formulaire
document.addEventListener('DOMContentLoaded', () => {
    const csrfEl = document.getElementById('csrfToken');
    if (csrfEl) csrfEl.value = '<?= htmlspecialchars($csrfToken) ?>';
});
</script>

<?php
// Injecter les produits depuis la BDD
if (!empty($produits)) {
    echo '<script>';
    echo 'window.SOFTWAVE_PRODUITS = ' . json_encode($produits) . ';';
    echo '</script>';
}
?>
<script src="js/main.js"></script>
</body>
</html>
<?php
$html = ob_get_clean();
echo $html;