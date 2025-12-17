<?php
require_once '../includes/config.php';
requireAuth();

$page_title = 'Mon Profil';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$user_id = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Variables pour les messages
$success = '';
$error = '';

// Gestion de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        $errors = [];
        
        // Validation
        if (empty($username) || strlen($username) < 3) {
            $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }
        
        // Vérifier si le nouveau nom d'utilisateur ou email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        
        if ($stmt->fetch()) {
            $errors[] = "Le nom d'utilisateur ou l'adresse email est déjà utilisé.";
        }
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            
            if ($stmt->execute([$username, $email, $user_id])) {
                // Mettre à jour la session
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                
                $success = "Profil mis à jour avec succès.";
                $user['username'] = $username;
                $user['email'] = $email;
            } else {
                $error = "Une erreur est survenue lors de la mise à jour du profil.";
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $errors = [];
        
        // Vérifier le mot de passe actuel
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Le mot de passe actuel est incorrect.";
        }
        
        // Validation du nouveau mot de passe
        if (strlen($new_password) < 6) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "Les nouveaux mots de passe ne correspondent pas.";
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashed_password, $user_id])) {
                $success = "Mot de passe changé avec succès.";
            } else {
                $error = "Une erreur est survenue lors du changement de mot de passe.";
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
}

// Récupérer les statistiques du profil
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN due_date < NOW() AND status != 'completed' THEN 1 ELSE 0 END) as overdue_tasks
    FROM tasks 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Calculer le taux d'achèvement
$completion_rate = $stats['total_tasks'] > 0 ? 
    round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0;

// Récupérer les 5 dernières activités
$stmt = $pdo->prepare("
    SELECT 
        'task_created' as type,
        title,
        created_at as date
    FROM tasks 
    WHERE user_id = ?
    
    UNION ALL
    
    SELECT 
        'task_completed' as type,
        title,
        updated_at as date
    FROM tasks 
    WHERE user_id = ? AND status = 'completed'
    
    ORDER BY date DESC 
    LIMIT 5
");
$stmt->execute([$user_id, $user_id]);
$activities = $stmt->fetchAll();
?>

<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Mon Profil</h1>
            <p class="text-muted">Gérez vos informations personnelles et vos paramètres</p>
        </div>
    </div>

    <!-- Messages d'alerte -->
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Colonne gauche: Informations du profil -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informations du profil</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required minlength="3">
                                <div class="form-text">Minimum 3 caractères</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Adresse email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rôle</label>
                                <input type="text" class="form-control" value="<?php echo $user['role'] == 'admin' ? 'Administrateur' : 'Utilisateur'; ?>" readonly>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date d'inscription</label>
                                <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
<!-- Changement de mot de passe -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Changer le mot de passe</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6" oninput="checkPasswordStrength(this.value)">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordStrength" class="mt-2"></div>
                            <div class="form-text">Minimum 6 caractères</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatch" class="mt-2"></div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Colonne droite: Statistiques et activités -->
        <div class="col-lg-4">
            <!-- Photo de profil -->
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <i class="fas fa-user-circle fa-7x text-primary"></i>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h5>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#avatarModal">
                            <i class="fas fa-camera me-1"></i>Changer la photo
                        </button>
                    </div>
                </div>
            </div>

<!-- Statistiques du profil -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Vos statistiques</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="display-6 text-primary"><?php echo $stats['total_tasks']; ?></div>
                            <small class="text-muted">Tâches totales</small>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="display-6 text-success"><?php echo $stats['completed_tasks']; ?></div>
                            <small class="text-muted">Terminées</small>
                        </div>
                        <div class="col-6">
                            <div class="display-6 text-warning"><?php echo $completion_rate; ?>%</div>
                            <small class="text-muted">Taux d'achèvement</small>
                        </div>
                        <div class="col-6">
                            <div class="display-6 text-danger"><?php echo $stats['overdue_tasks']; ?></div>
                            <small class="text-muted">En retard</small>
                        </div>
                    </div>
                    
                    <div class="progress mt-3" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $completion_rate; ?>%"></div>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted">Progression globale</small>
                    </div>
                </div>
            </div>

            <!-- Dernières activités -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Activités récentes</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($activities)): ?>
                        <p class="text-muted text-center mb-0">Aucune activité récente</p>
                    <?php else: ?>
                        <div class="activity-feed">
                            <?php foreach ($activities as $activity): ?>
                            <div class="activity-item mb-3">
                                <div class="activity-icon">
                                    <i class="fas <?php echo $activity['type'] == 'task_completed' ? 'fa-check-circle text-success' : 'fa-plus-circle text-primary'; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?php echo $activity['type'] == 'task_completed' ? 'Tâche terminée' : 'Nouvelle tâche'; ?>
                                    </div>
                                    <div class="activity-text">
                                        <?php echo htmlspecialchars($activity['title']); ?>
                                    </div>
                                    <div class="activity-time text-muted">
                                        <small><?php echo date('d/m/Y H:i', strtotime($activity['date'])); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Paramètres avancés -->
    <div class="row mt-4">

    <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Paramètres avancés</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                <label class="form-check-label" for="emailNotifications">
                                    Notifications par email
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="darkMode">
                                <label class="form-check-label" for="darkMode">
                                    Mode sombre
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                                <label class="form-check-label" for="autoRefresh">
                                    Actualisation automatique
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button class="btn btn-outline-primary" onclick="saveSettings()">
                            <i class="fas fa-save me-2"></i>Enregistrer les paramètres
                        </button>
                        <button class="btn btn-outline-danger ms-2" onclick="resetSettings()">
                            <i class="fas fa-undo me-2"></i>Réinitialiser
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section de compte (danger) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-danger shadow">
                <div class="card-header bg-danger text-white py-3">
                    <h6 class="m-0 font-weight-bold">Zone de danger</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Ces actions sont irréversibles. Veuillez être certain de ce que vous faites.
                    </p>
                    
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            <i class="fas fa-user-slash me-2"></i>Supprimer mon compte
                        </button>
                        <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#exportDataModal">
                            <i class="fas fa-download me-2"></i>Exporter mes données
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/config.php';
requireAuth();

$page_title = 'Mon Profil';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$user_id = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Variables pour les messages
$success = '';
$error = '';

// Gestion de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        $errors = [];
        
        // Validation
        if (empty($username) || strlen($username) < 3) {
            $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }
        
        // Vérifier si le nouveau nom d'utilisateur ou email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        
        if ($stmt->fetch()) {
            $errors[] = "Le nom d'utilisateur ou l'adresse email est déjà utilisé.";
        }
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            
            if ($stmt->execute([$username, $email, $user_id])) {
                // Mettre à jour la session
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                
                $success = "Profil mis à jour avec succès.";
                $user['username'] = $username;
                $user['email'] = $email;
            } else {
                $error = "Une erreur est survenue lors de la mise à jour du profil.";
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $errors = [];
        
        // Vérifier le mot de passe actuel
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Le mot de passe actuel est incorrect.";
        }
        
        // Validation du nouveau mot de passe
        if (strlen($new_password) < 6) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "Les nouveaux mots de passe ne correspondent pas.";
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashed_password, $user_id])) {
                $success = "Mot de passe changé avec succès.";
            } else {
                $error = "Une erreur est survenue lors du changement de mot de passe.";
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
}

// Récupérer les statistiques du profil
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN due_date < NOW() AND status != 'completed' THEN 1 ELSE 0 END) as overdue_tasks
    FROM tasks 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Calculer le taux d'achèvement
$completion_rate = $stats['total_tasks'] > 0 ? 
    round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0;

// Récupérer les 5 dernières activités
$stmt = $pdo->prepare("
    SELECT 
        'task_created' as type,
        title,
        created_at as date
    FROM tasks 
    WHERE user_id = ?
    
    UNION ALL
    
    SELECT 
        'task_completed' as type,
        title,
        updated_at as date
    FROM tasks 
    WHERE user_id = ? AND status = 'completed'
    
    ORDER BY date DESC 
    LIMIT 5
");
$stmt->execute([$user_id, $user_id]);
$activities = $stmt->fetchAll();
?>

<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Mon Profil</h1>
            <p class="text-muted">Gérez vos informations personnelles et vos paramètres</p>
        </div>
    </div>

    <!-- Messages d'alerte -->
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Colonne gauche: Informations du profil -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informations du profil</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required minlength="3">
                                <div class="form-text">Minimum 3 caractères</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Adresse email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rôle</label>
                                <input type="text" class="form-control" value="<?php echo $user['role'] == 'admin' ? 'Administrateur' : 'Utilisateur'; ?>" readonly>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date d'inscription</label>
                                <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
<!-- Changement de mot de passe -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Changer le mot de passe</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6" oninput="checkPasswordStrength(this.value)">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordStrength" class="mt-2"></div>
                            <div class="form-text">Minimum 6 caractères</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatch" class="mt-2"></div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Colonne droite: Statistiques et activités -->
        <div class="col-lg-4">
            <!-- Photo de profil -->
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <i class="fas fa-user-circle fa-7x text-primary"></i>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h5>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#avatarModal">
                            <i class="fas fa-camera me-1"></i>Changer la photo
                        </button>
                    </div>
                </div>
            </div>
<!-- Statistiques du profil -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Vos statistiques</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="display-6 text-primary"><?php echo $stats['total_tasks']; ?></div>
                            <small class="text-muted">Tâches totales</small>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="display-6 text-success"><?php echo $stats['completed_tasks']; ?></div>
                            <small class="text-muted">Terminées</small>
                        </div>
                        <div class="col-6">
                            <div class="display-6 text-warning"><?php echo $completion_rate; ?>%</div>
                            <small class="text-muted">Taux d'achèvement</small>
                        </div>
                        <div class="col-6">
                            <div class="display-6 text-danger"><?php echo $stats['overdue_tasks']; ?></div>
                            <small class="text-muted">En retard</small>
                        </div>
                    </div>
                    
                    <div class="progress mt-3" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $completion_rate; ?>%"></div>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted">Progression globale</small>
                    </div>
                </div>
            </div>

            <!-- Dernières activités -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Activités récentes</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($activities)): ?>
                        <p class="text-muted text-center mb-0">Aucune activité récente</p>
                    <?php else: ?>
                        <div class="activity-feed">
                            <?php foreach ($activities as $activity): ?>
                            <div class="activity-item mb-3">
                                <div class="activity-icon">
                                    <i class="fas <?php echo $activity['type'] == 'task_completed' ? 'fa-check-circle text-success' : 'fa-plus-circle text-primary'; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?php echo $activity['type'] == 'task_completed' ? 'Tâche terminée' : 'Nouvelle tâche'; ?>
                                    </div>
                                    <div class="activity-text">
                                        <?php echo htmlspecialchars($activity['title']); ?>
                                    </div>
                                    <div class="activity-time text-muted">
                                        <small><?php echo date('d/m/Y H:i', strtotime($activity['date'])); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Paramètres avancés -->
    <div class="row mt-4">

    <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Paramètres avancés</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                <label class="form-check-label" for="emailNotifications">
                                    Notifications par email
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="darkMode">
                                <label class="form-check-label" for="darkMode">
                                    Mode sombre
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                                <label class="form-check-label" for="autoRefresh">
                                    Actualisation automatique
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button class="btn btn-outline-primary" onclick="saveSettings()">
                            <i class="fas fa-save me-2"></i>Enregistrer les paramètres
                        </button>
                        <button class="btn btn-outline-danger ms-2" onclick="resetSettings()">
                            <i class="fas fa-undo me-2"></i>Réinitialiser
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section de compte (danger) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-danger shadow">
                <div class="card-header bg-danger text-white py-3">
                    <h6 class="m-0 font-weight-bold">Zone de danger</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Ces actions sont irréversibles. Veuillez être certain de ce que vous faites.
                    </p>
                    
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            <i class="fas fa-user-slash me-2"></i>Supprimer mon compte
                        </button>
                        <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#exportDataModal">
                            <i class="fas fa-download me-2"></i>Exporter mes données
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour la photo de profil -->
<div class="modal fade" id="avatarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Changer la photo de profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="avatar-preview mb-3">
                        <i class="fas fa-user-circle fa-6x text-primary" id="avatarPreview"></i>
                    </div>
                    <input type="file" id="avatarInput" class="form-control" accept="image/*">
                    <div class="form-text mt-2">Formats acceptés: JPG, PNG, GIF (max 2MB)</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="uploadAvatar()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression de compte -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Supprimer mon compte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Attention !</h6>
                    <p class="mb-0">
                        Cette action est irréversible. Toutes vos données seront définitivement supprimées, y compris toutes vos tâches et activités.
                    </p>
                </div>
                
                <div class="mb-3">
                    <label for="confirmDelete" class="form-label">
                        Tapez "SUPPRIMER" pour confirmer
                    </label>
                    <input type="text" class="form-control" id="confirmDelete" placeholder="SUPPRIMER">
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmBackup">
                    <label class="form-check-label" for="confirmBackup">
                        J'ai sauvegardé toutes mes données importantes
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" onclick="deleteAccount()" id="deleteAccountBtn" disabled>
                    <i class="fas fa-trash me-2"></i>Supprimer définitivement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'export des données -->
<div class="modal fade" id="exportDataModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Exporter mes données</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-4">Sélectionnez les données que vous souhaitez exporter :</p>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="exportTasks" checked>
                        <label class="form-check-label" for="exportTasks">
                            Toutes mes tâches
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="exportProfile" checked>
                        <label class="form-check-label" for="exportProfile">
                            Informations du profil
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="exportActivities">
                        <label class="form-check-label" for="exportActivities">
                            Historique d'activités
                        </label>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="exportFormat" class="form-label">Format d'export :</label>
                    <select class="form-select" id="exportFormat">
                        <option value="json">JSON</option>
                        <option value="csv">CSV</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="exportData()">
                    <i class="fas fa-download me-2"></i>Exporter
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.profile-avatar {
    position: relative;
    display: inline-block;
}

.avatar-upload {
    position: absolute;
    bottom: 0;
    right: 0;
    background: white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    cursor: pointer;
}

.activity-feed .activity-item {
    display: flex;
    align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.activity-feed .activity-item:last-child {
    border-bottom: none;
}

.activity-feed .activity-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    flex-shrink: 0;
}

.activity-feed .activity-content {
    flex: 1;
}

.activity-feed .activity-title {
    font-weight: 600;
    font-size: 0.9rem;
}

.activity-feed .activity-text {
    font-size: 0.85rem;
    color: #666;
    margin: 2px 0;
}

.activity-feed .activity-time {
    font-size: 0.75rem;
}
</style>

<script>
// Toggle password visibility
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

// Check password strength
function checkPasswordStrength(password) {
    const strengthDiv = document.getElementById('passwordStrength');
    let strength = 0;
    let message = '';
    let color = 'danger';
    
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;

    switch (strength) {
        case 0:
        case 1:
            message = 'Faible';
            color = 'danger';
            break;
        case 2:
            message = 'Moyen';
            color = 'warning';
            break;
        case 3:
            message = 'Fort';
            color = 'success';
            break;
        case 4:
            message = 'Très fort';
            color = 'success';
            break;
    }
    
    strengthDiv.innerHTML = 
        <div class="progress" style="height: 5px;">
            <div class="progress-bar bg-${color}" style="width: ${strength * 25}%"></div>
        </div>
        <small class="text-${color}">${message}</small>
    ;
}

// Check password match
document.getElementById('confirm_password').addEventListener('input', function() {
    const matchDiv = document.getElementById('passwordMatch');
    const password = document.getElementById('new_password').value;
    const confirm = this.value;
    
    if (confirm === '') {
        matchDiv.innerHTML = '';
    } else if (password === confirm) {
        matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>Les mots de passe correspondent</small>';
    } else {
        matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Les mots de passe ne correspondent pas</small>';
    }
});

// Avatar preview
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const avatarPreview = document.getElementById('avatarPreview');
            avatarPreview.className = '';
            avatarPreview.style.backgroundImage = url(${e.target.result});
            avatarPreview.style.width = '96px';
            avatarPreview.style.height = '96px';
            avatarPreview.style.borderRadius = '50%';
            avatarPreview.style.backgroundSize = 'cover';
            avatarPreview.style.backgroundPosition = 'center';
        };
        reader.readAsDataURL(file);
    }
});

// Upload avatar
function uploadAvatar() {
    const fileInput = document.getElementById('avatarInput');
    const file = fileInput.files[0];
    
    if (!file) {
        alert('Veuillez sélectionner une image');
        return;
    }
    
    // Ici, vous ajouteriez le code pour uploader l'image vers le serveur
    alert('Fonctionnalité d\'upload à implémenter');
    const modal = bootstrap.Modal.getInstance(document.getElementById('avatarModal'));
    modal.hide();
}

// Delete account confirmation
document.getElementById('confirmDelete').addEventListener('input', function() {
    const deleteBtn = document.getElementById('deleteAccountBtn');
    const backupCheck = document.getElementById('confirmBackup');
    deleteBtn.disabled = !(this.value === 'SUPPRIMER' && backupCheck.checked);
});

// Delete account
function deleteAccount() {
    if (confirm('Êtes-vous ABSOLUMENT certain de vouloir supprimer votre compte ? Cette action est IRREVERSIBLE !')) {
        // Ici, vous ajouteriez le code pour supprimer le compte
        fetch('/api/user/delete-account.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ confirm: true })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/auth/logout.php';
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}

// Export data
function exportData() {

Yandex Khassiny, [14/12/2025 23:38]
const format = document.getElementById('exportFormat').value;
    const tasks = document.getElementById('exportTasks').checked;
    const profile = document.getElementById('exportProfile').checked;
    const activities = document.getElementById('exportActivities').checked;
    
    // Ici, vous ajouteriez le code pour exporter les données
    alert(Export des données en format ${format} à implémenter);
    const modal = bootstrap.Modal.getInstance(document.getElementById('exportDataModal'));
    modal.hide();
}

// Save settings
function saveSettings() {
    const settings = {
        emailNotifications: document.getElementById('emailNotifications').checked,
        darkMode: document.getElementById('darkMode').checked,
        autoRefresh: document.getElementById('autoRefresh').checked
    };
    
    localStorage.setItem('userSettings', JSON.stringify(settings));
    alert('Paramètres enregistrés !');
}

// Load settings on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedSettings = localStorage.getItem('userSettings');
    if (savedSettings) {
        const settings = JSON.parse(savedSettings);
        document.getElementById('emailNotifications').checked = settings.emailNotifications;
        document.getElementById('darkMode').checked = settings.darkMode;
        document.getElementById('autoRefresh').checked = settings.autoRefresh;
    }
});

// Reset settings
function resetSettings() {
    if (confirm('Réinitialiser tous les paramètres ?')) {
        localStorage.removeItem('userSettings');
        document.getElementById('emailNotifications').checked = true;
        document.getElementById('darkMode').checked = false;
        document.getElementById('autoRefresh').checked = true;
        alert('Paramètres réinitialisés !');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>