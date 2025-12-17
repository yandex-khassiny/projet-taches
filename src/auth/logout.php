<?php
// logout.php
require_once '../includes/config.php';

// Détruire toutes les variables de session
$_SESSION = array();

// Si vous voulez détruire le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
$login_url = BASE_URL . '/src/auth/login.php';
if (!headers_sent()) {
    header('Location: ' . $login_url);
} else {
    echo '<script>window.location.href="' . $login_url . '";</script>';
}
exit();
?>