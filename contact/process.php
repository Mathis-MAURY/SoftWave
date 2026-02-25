<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Nettoyage des données
$nom     = trim(filter_input(INPUT_POST, 'name',    FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$email   = trim(filter_input(INPUT_POST, 'email',   FILTER_SANITIZE_EMAIL) ?? '');
$sujet   = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$ip      = $_SERVER['REMOTE_ADDR'];

// Validation
$errors = [];
if (mb_strlen($nom) < 2)                          $errors[] = 'Le nom est trop court';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = 'Email invalide';
if (mb_strlen($message) < 20)                     $errors[] = 'Message trop court (20 car. min)';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO messages_contact (nom, email, sujet, message, adresse_ip, est_lu, est_repondu, cree_le) 
        VALUES (?, ?, ?, ?, ?, 0, 0, NOW())
    ");
    
    $stmt->execute([$nom, $email, $sujet, $message, $ip]);

    echo json_encode([
        'success' => true,
        'message' => 'Message envoyé avec succès ! Nous vous répondrons sous 24h.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur. Veuillez réessayer.']);
    error_log('Contact form error: ' . $e->getMessage());
}