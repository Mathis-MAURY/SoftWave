<?php
// ============================================
// API Produits
// Tables : produits + categories
// ============================================

require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

try {
    $db = getDB();

    $categorie = filter_input(INPUT_GET, 'categorie', FILTER_SANITIZE_SPECIAL_CHARS);
    $slug      = filter_input(INPUT_GET, 'slug',      FILTER_SANITIZE_SPECIAL_CHARS);

    if ($slug) {
        $stmt = $db->prepare("
            SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug, c.icone AS categorie_icone
            FROM produits p
            LEFT JOIN categories c ON c.id = p.categorie_id
            WHERE p.slug = ? AND p.est_actif = 1
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        $produit = $stmt->fetch();

        if ($produit) {
            $produit['fonctionnalites'] = json_decode($produit['fonctionnalites'], true);
            echo json_encode(['success' => true, 'produit' => $produit]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
        }
    } else {
        $query  = "
            SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug, c.icone AS categorie_icone
            FROM produits p
            LEFT JOIN categories c ON c.id = p.categorie_id
            WHERE p.est_actif = 1
        ";
        $params = [];

        if ($categorie) {
            $query   .= " AND c.slug = ?";
            $params[] = $categorie;
        }

        $query .= " ORDER BY p.cree_le DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $produits = $stmt->fetchAll();

        foreach ($produits as &$p) {
            $p['fonctionnalites'] = json_decode($p['fonctionnalites'], true);
        }

        echo json_encode(['success' => true, 'produits' => $produits]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    error_log('API produits error: ' . $e->getMessage());
}