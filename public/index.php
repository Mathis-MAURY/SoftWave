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
$products = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC");
    $products = $stmt->fetchAll();
    foreach ($products as &$p) {
        $p['features'] = json_decode($p['features'], true) ?? [];
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
if (!empty($products)) {
    echo '<script>';
    echo 'window.SOFTWAVE_PRODUCTS = ' . json_encode($products) . ';';
    echo '</script>';
}
?>
<script src="js/main.js"></script>
</body>
</html>
<?php
$html = ob_get_clean();
echo $html;
