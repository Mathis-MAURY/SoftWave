<?php
// ============================================
// API Produits
// ============================================

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    $db = getDB();

    $category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_SPECIAL_CHARS);
    $slug     = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($slug) {
        $stmt = $db->prepare("SELECT * FROM products WHERE slug = ? AND is_active = 1");
        $stmt->execute([$slug]);
        $product = $stmt->fetch();
        if ($product) {
            $product['features'] = json_decode($product['features'], true);
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
        }
    } else {
        $query = "SELECT * FROM products WHERE is_active = 1";
        $params = [];
        if ($category) {
            $query .= " AND category = ?";
            $params[] = $category;
        }
        $query .= " ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        foreach ($products as &$p) {
            $p['features'] = json_decode($p['features'], true);
        }
        echo json_encode(['success' => true, 'products' => $products]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    error_log('Products API error: ' . $e->getMessage());
}
