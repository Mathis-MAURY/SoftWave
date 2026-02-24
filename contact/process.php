<?php
// ============================================
// Traitement du formulaire de contact
// ============================================

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Vérifier méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier token CSRF
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
    exit;
}

// Rate limiting simple (1 message par 60s par IP)
$ip = $_SERVER['REMOTE_ADDR'];
$rate_key = 'contact_' . $ip;
if (isset($_SESSION[$rate_key]) && (time() - $_SESSION[$rate_key]) < 60) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Trop de messages. Attendez 1 minute.']);
    exit;
}

// Validation et sanitisation
$name    = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
$email   = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$subject = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_SPECIAL_CHARS));
$message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS));
$honey   = trim($_POST['website'] ?? ''); // Honeypot anti-spam

// Honeypot check
if (!empty($honey)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Spam détecté']);
    exit;
}

// Validation
$errors = [];
if (empty($name) || mb_strlen($name) < 2)    $errors[] = 'Le nom doit faire au moins 2 caractères';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide';
if (empty($subject) || mb_strlen($subject) < 5) $errors[] = 'Le sujet doit faire au moins 5 caractères';
if (empty($message) || mb_strlen($message) < 20) $errors[] = 'Le message doit faire au moins 20 caractères';
if (mb_strlen($message) > 5000) $errors[] = 'Message trop long (5000 caractères max)';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $db = getDB();

    // Enregistrer en BDD
    $stmt = $db->prepare("INSERT INTO contacts (name, email, subject, message, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $subject, $message, $ip]);

    // Rate limit
    $_SESSION[$rate_key] = time();
    // Renouveler CSRF
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Email de notification (optionnel, nécessite PHPMailer)
    // sendContactEmail($name, $email, $subject, $message);

    echo json_encode([
        'success' => true,
        'message' => 'Message envoyé avec succès ! Nous vous répondrons sous 24h.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur. Veuillez réessayer.']);
    error_log('Contact form error: ' . $e->getMessage());
}
