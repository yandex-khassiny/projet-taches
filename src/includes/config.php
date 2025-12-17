<?php
// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// CONFIGURATION INTELLIGENTE
// ============================================

// Détecter si on est sur Render (sur Internet) ou en local
$isOnInternet = false;

// Méthode 1 : Vérifier si c'est Render
if (isset($_SERVER['RENDER'])) {
    $isOnInternet = true;
}

// Méthode 2 : Vérifier l'URL
if (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'onrender.com') !== false) {
    $isOnInternet = true;
}

// Méthode 3 : Vérifier l'adresse IP
if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    // Pas localhost, probablement sur Internet
    $isOnInternet = true;
}

if ($isOnInternet) {
    // ============================================
    // CONFIGURATION POUR INTERNET (RENDER)
    // ============================================
    
    // URL de votre site sur Internet (à changer plus tard)
    define('BASE_URL', 'https://votre-app.onrender.com');
    
    // Base de données SQLite (la plus simple pour débuter)
    $dbPath = '/tmp/task_manager.db';
    
    try {
        $pdo = new PDO("sqlite:" . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer les tables si elles n'existent pas
        createTablesIfNeeded($pdo);
        
    } catch (PDOException $e) {
        die("Erreur SQLite : " . $e->getMessage());
    }
    
} else {
    // ============================================
    // CONFIGURATION POUR LOCAL (XAMPP)
    // ============================================
    
    define('BASE_URL', 'http://localhost/projet-taches');
    
    // Configuration MySQL pour XAMPP
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'task_manager');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $e) {
        die("Erreur MySQL : " . $e->getMessage() . 
            "<br>Vérifie que :<br>" .
            "1. XAMPP est démarré<br>" .
            "2. MySQL est activé<br>" .
            "3. La base 'task_manager' existe");
    }
}

// ============================================
// FONCTIONS UTILES
// ============================================

function createTablesIfNeeded($pdo) {
    // Table des utilisateurs
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP,
        updated_at TIMESTAMP
    )");
    
    // Table des tâches
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        due_date TIMESTAMP,
        priority TEXT DEFAULT 'medium',
        status TEXT DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Table des notifications
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        task_id INTEGER,
        message TEXT NOT NULL,
        is_read INTEGER DEFAULT 0,
        scheduled_time TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
    )");
}

function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAuth() {
    if (!isAuthenticated()) {
        $login_url = BASE_URL . '/src/auth/login.php';
        if (!headers_sent()) {
            header('Location: ' . $login_url);
        } else {
            echo '<script>window.location.href="' . $login_url . '";</script>';
        }
        exit();
    }
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        $dashboard_url = BASE_URL . '/src/user/dashboard.php';
        if (!headers_sent()) {
            header('Location: ' . $dashboard_url);
        } else {
            echo '<script>window.location.href="' . $dashboard_url . '";</script>';
        }
        exit();
    }
}

// En production, cacher les erreurs
if ($isOnInternet) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    // En local, montrer les erreurs
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
?>