<?php
require_once '../includes/config.php';

// Rediriger si déjà connecté
if (isAuthenticated()) {
    header('Location: /user/dashboard.php');
    exit();
}

$page_title = 'Inscription';
require_once '../includes/header.php';

// Traitement du formulaire d'inscription
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || strlen($username) < 3) {
        $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }
    
    // Vérifier si l'utilisateur existe déjà
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $errors[] = "Le nom d'utilisateur ou l'adresse email est déjà utilisé.";
        }
    }
    
    // Créer l'utilisateur
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);
            
            $success = true;
            
            // Connecter automatiquement l'utilisateur
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'user';
            
            // Rediriger après 3 secondes
            header('Refresh: 3; URL=/user/dashboard.php');
            
        } catch (PDOException $e) {
            $errors[] = "Une erreur est survenue lors de l'inscription. Veuillez réessayer.";
        }
    }
}
?>

<style>
/* CSS inline pour register.php */
.auth-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #4361ee, #3a0ca3);
    padding: 20px;
}

.auth-card {
    width: 100%;
    max-width: 450px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.auth-header {
    padding: 30px;
    text-align: center;
    background: linear-gradient(135deg, #4361ee, #3a0ca3);
    color: white;
}

.auth-header h1 {
    font-size: 1.8rem;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.auth-header p {
    opacity: 0.9;
    margin: 10px 0 0;
}

.auth-form {
    padding: 30px;
}

.auth-form .form-label {
    font-weight: 500;
    margin-bottom: 8px;
    display: block;
}

.auth-form .input-group {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dee2e6;
}

.auth-form .input-group:focus-within {
    border-color: #4361ee;
    box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
}

.auth-form .input-group-text {
    background-color: #f8f9fa;
    border: none;
    color: #6c757d;
    min-width: 45px;
    justify-content: center;
}

.auth-form .form-control {
    border: none;
    padding: 12px;
}.auth-form .btn-primary {
    padding: 12px;
    font-weight: 600;
    border-radius: 8px;
    background: linear-gradient(135deg, #4361ee, #3a0ca3);
    border: none;
    width: 100%;
}.password-strength {
    margin-top: 5px;
    height: 5px;
    border-radius: 3px;
    background: #e9ecef;
    overflow: hidden;
}

.strength-meter {
    height: 100%;
    width: 0;
    transition: width 0.3s;
}.strength-weak {
    background-color: #dc3545;
    width: 33%;
}

.strength-medium {
    background-color: #ffc107;
    width: 66%;
}

.strength-strong {
    background-color: #28a745;
    width: 100%;
}

@media (max-width: 576px) {
    .auth-card {
        margin: 20px;
    }
    .auth-header, .auth-form {
        padding: 20px;
    }
}
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1><i class="fas fa-tasks me-2"></i>TaskManager</h1>
            <p class="text-muted">Créez votre compte gratuit</p>
        </div>
        
        <?php if ($success): ?>
        <div class="alert alert-success m-4">
            <h5 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Inscription réussie !</h5>
            <p class="mb-0">Votre compte a été créé avec succès. Vous allez être redirigé vers votre tableau de bord dans quelques instants...</p>
            <div class="mt-3">
                <a href="/user/dashboard.php" class="btn btn-success">Accéder maintenant</a>
            </div>
        </div>
        <?php else: ?>
        
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger m-4">
            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Erreurs</h5>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form" id="registerForm">
            <div class="mb-3">
                <label for="username" class="form-label">Nom d'utilisateur *</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           required minlength="3" maxlength="50">
                </div>
                <div class="form-text">3 à 50 caractères</div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Adresse email *</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe *</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" 
                           required minlength="6">
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength mt-2">
                    <div class="strength-meter" id="passwordStrengthMeter"></div>
                </div>
                <div class="form-text">Minimum 6 caractères</div>
            </div><div class="mb-3"><label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span><input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           required minlength="6">
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div id="passwordMatch" class="form-text"></div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-3" id="submitBtn">
                <i class="fas fa-user-plus me-2"></i>S'inscrire
            </button>
            
            <div class="text-center">
                <p class="mb-0">Déjà inscrit ? <a href="login.php" class="text-decoration-none">Se connecter</a></p>
            </div>
        </form>
        <?php endif; ?>
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
</script>

<?php require_once '../includes/footer.php'; ?>