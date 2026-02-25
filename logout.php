<?php
// ============================================
// Admin – Déconnexion
// ============================================

require_once __DIR__ . '/../includes/config.php';

// Supprimer le token "Se souvenir de moi" en BDD + cookie
if (isset($_COOKIE['sw_remember'])) {
    try {
        $db        = getDB();
        $tokenHash = hash('sha256', $_COOKIE['sw_remember']);
        $db->prepare("DELETE FROM tokens_connexion WHERE token_hash = ?")->execute([$tokenHash]);
    } catch (PDOException $e) {
        error_log('Logout token cleanup: ' . $e->getMessage());
    }
    // Expirer le cookie
    setcookie('sw_remember', '', time() - 3600, '/', '', false, true);
}

// Détruire la session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: login.php?bye=1');
exit;