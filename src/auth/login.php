<?php
// login.php - VERSION FINALE CORRIGÉE
// DÉMARRER LA SESSION AU TOUT DÉBUT
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';

// Déjà connecté ? Rediriger
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/src/user/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyer et valider les entrées
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // NE PAS trim() le mot de passe !
    
    // Debug: logger ce qui est reçu
    error_log("Login attempt - Username: '$username', Password length: " . strlen($password));
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        try {
            // CORRECTION: Vérifier d'abord par username, puis par email séparément
            // Méthode 1: Chercher par username exact
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            // Méthode 2: Si non trouvé, chercher par email
            if (!$user) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$username]); // $username contient peut-être un email
                $user = $stmt->fetch();
            }
            
            if ($user) {
                // Debug: information sur l'utilisateur trouvé
                error_log("User found: " . $user['username'] . ", ID: " . $user['id']);
                error_log("Stored hash (first 30): " . substr($user['password'], 0, 30));
                
                // CORRECTION IMPORTANTE: Vérifier le mot de passe
                // Note: NE PAS modifier le mot de passe (pas de trim(), pas de htmlspecialchars())
                $password_verified = password_verify($password, $user['password']);
                
                error_log("Password verification result: " . ($password_verified ? "SUCCESS" : "FAILED"));
                
                if ($password_verified) {
                    // CONNEXION RÉUSSIE
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    $_SESSION['email'] = $user['email'];
                    
                    // Régénérer l'ID de session pour la sécurité
                    session_regenerate_id(true);
                    
                    // Debug
                    error_log("Login SUCCESS for user: " . $user['username']);
                    error_log("Session created: user_id=" . $_SESSION['user_id']);
                    
                    // Redirection IMMÉDIATE
                    $redirect_url = BASE_URL . '/src/user/dashboard.php';
                    if (isset($user['role']) && $user['role'] === 'admin') {
                        $redirect_url = BASE_URL . '/src/admin/dashboard.php';
                    }
                    
                    // Essayer header() d'abord
                    if (!headers_sent()) {
                        header('Location: ' . $redirect_url);
                        exit();
                    } else {
                        // Fallback JavaScript
                        echo '<script>window.location.href="' . $redirect_url . '";</script>';
                        exit();
                    }
                    
                } else {
                    $error = 'Mot de passe incorrect';
                    error_log("Login FAILED: password mismatch for user: " . $user['username']);
                }} else {
                $error = 'Utilisateur non trouvé';
                error_log("Login FAILED: user not found: " . $username);
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion à la base de données';
            error_log("Database error in login: " . $e->getMessage());
        }
    }
}

// Si on arrive ici, afficher le formulaire
$page_title = 'Connexion';
require_once '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1><i class="fas fa-tasks me-2"></i>TaskManager</h1>
            <p class="text-muted">Connectez-vous à votre compte</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger m-4">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Erreur :</strong> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form" id="loginForm" autocomplete="on">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fas fa-user me-1"></i>Nom d'utilisateur ou Email
                </label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                       required autocomplete="username" autofocus>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-1"></i>Mot de passe
                </label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" 
                           required autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Se souvenir de moi</label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-3" id="loginButton">
                <i class="fas fa-sign-in-alt me-2"></i>Se connecter
            </button>
            
            <div class="text-center">
                <p class="mb-0">Pas encore de compte ? 
                    <a href="<?php echo BASE_URL; ?>/src/auth/register.php" class="text-decoration-none">
                        S'inscrire maintenant
                    </a>
                </p>
            </div>
        </form>
        
        <!-- Section de test/debug -->
        <div class="mt-4 p-3 border rounded bg-light">
            <h6 class="mb-2"><i class="fas fa-bug me-1"></i>Test & Debug</h6>
            <small class="text-muted d-block mb-2">Utilisez ce formulaire pour tester</small>
            
            <?php
            // Afficher la liste des utilisateurs pour test
            try {
                $stmt = $pdo->query("SELECT username, email FROM users LIMIT 5");
                $test_users = $stmt->fetchAll();
                
                if ($test_users) {
                    echo "<div class='mb-2'><strong>Utilisateurs existants :</strong><br>";
                    foreach ($test_users as $u) {
                        echo "- " . htmlspecialchars($u['username']) . " (" . htmlspecialchars($u['email']) . ")<br>";
                    }
                    echo "</div>";}
            } catch (Exception $e) {
                // Ignorer les erreurs en production
            }
            ?>
            
            <form method="POST" class="mt-2">
                <input type="hidden" name="username" value="test">
                <input type="hidden" name="password" value="test123">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                    <i class="fas fa-bolt me-1"></i>Test rapide (test/test123)
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Ajouter un peu de JavaScript pour aider
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        alert('Veuillez remplir tous les champs');
        e.preventDefault();
        return;
    }
    
    // Montrer un indicateur de chargement
    const btn = document.getElementById('loginButton');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Connexion en cours...';
    btn.disabled = true;
});
</script>

<?php 
require_once '../includes/footer.php';
?>