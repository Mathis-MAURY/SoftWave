<?php
// ============================================
// API – Statut de session client
// ============================================

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$response = [
    'isLoggedIn' => false,
    'clientName' => null
];

if (isset($_SESSION['client_id']) && isset($_SESSION['client_prenom'])) {
    $response['isLoggedIn'] = true;
    $response['clientName'] = $_SESSION['client_prenom'];
}

echo json_encode($response);
