<?php
// ============================================
// API CSRF Token Generator
// ============================================

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Générer un nouveau token CSRF si nécessaire
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode([
    'success' => true,
    'token' => $_SESSION['csrf_token']
]);
