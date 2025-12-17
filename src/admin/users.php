<?php
require_once '../includes/config.php';
requireAdmin();

// ==================== TRAITEMENT DES FORMULAIRES (DOIT ÊTRE AVANT TOUT HTML) ====================

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DEBUG: Afficher ce qui est reçu
    error_log("POST reçu: " . print_r($_POST, true));
    
    // AJOUT D'UTILISATEUR
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        
        // Validation
        $errors = [];
        
        if (strlen($username) < 3) {
            $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }
        
        if (strlen($password) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        }
        
        // Vérifier si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Le nom d'utilisateur ou l'adresse email est déjà utilisé.";
        }
        
        if (empty($errors)) {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password, $role]);
                
                $_SESSION['success'] = "Utilisateur ajouté avec succès.";
                header("Location: users.php");
                exit();
                
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur: " . $e->getMessage();
                header("Location: users.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
            header("Location: users.php");
            exit();
        }
    }
    
    // MISE À JOUR D'UTILISATEUR
    if (isset($_POST['update_user'])) {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        
        // Validation
        $errors = [];
        
        if ($user_id == $_SESSION['user_id']) {
            $errors[] = "Vous ne pouvez pas modifier votre propre compte ici. Utilisez votre profil.";
        }
        
        if (strlen($username) < 3) {
            $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }
        
        // Vérifier si le nouvel email existe déjà pour un autre utilisateur
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = "Cette adresse email est déjà utilisée par un autre utilisateur.";
        }
        
        // Vérifier si le nouvel username existe déjà pour un autre utilisateur
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = "Ce nom d'utilisateur est déjà utilisé par un autre utilisateur.";
        }
        
        if (empty($errors)) {
            try {
                // Préparer la requête de mise à jour
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$username, $email, $hashed_password, $role, $user_id]);
                    }
                } else {
                    // Si pas de nouveau mot de passe, ne pas changer le mot de passe actuel
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$username, $email, $role, $user_id]);
                }
                
                if (empty($errors)) {
                    $_SESSION['success'] = "Utilisateur mis à jour avec succès.";
                    header("Location: users.php");
                    exit();
                } else {
                    $_SESSION['error'] = implode("<br>", $errors);
                    header("Location: users.php");
                    exit();
                }
                
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la mise à jour: " . $e->getMessage();
                header("Location: users.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
            header("Location: users.php");
            exit();
        }
    }
    
    // SUPPRESSION D'UTILISATEUR
    if (isset($_POST['delete_user'])) {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        // Empêcher la suppression de soi-même
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte.";
            header("Location: users.php");
            exit();
        } else {
            try {
                // Commencer une transaction
                $pdo->beginTransaction();
                
                // Supprimer les notifications de l'utilisateur
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Supprimer les tâches de l'utilisateur
                $stmt = $pdo->prepare("DELETE FROM tasks WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Supprimer l'utilisateur
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $pdo->commit();
                
                $_SESSION['success'] = "Utilisateur supprimé avec succès.";
                header("Location: users.php");
                exit();
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
                header("Location: users.php");
                exit();
            }
        }
    }
    
    // MISE À JOUR DU RÔLE (via le formulaire dans le tableau)
    if (isset($_POST['update_role'])) {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $new_role = $_POST['role'] ?? 'user';
        
        // Empêcher de changer son propre rôle
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error'] = "Vous ne pouvez pas modifier votre propre rôle.";
            header("Location: users.php");
            exit();
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_role, $user_id]);
                
                $_SESSION['success'] = "Rôle de l'utilisateur mis à jour avec succès.";
                header("Location: users.php");
                exit();
                
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la mise à jour du rôle: " . $e->getMessage();
                header("Location: users.php");
                exit();
            }
        }
    }
}

// ==================== PARTIE AFFICHAGE HTML ====================

$page_title = 'Gestion des Utilisateurs';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Variables pour la pagination et la recherche
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Construire la requête avec recherche et pagination
$query = "SELECT * FROM users WHERE 1=1";
$count_query = "SELECT COUNT(*) FROM users WHERE 1=1";
$params = [];
$count_params = [];

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR email LIKE ?)";
    $count_query .= " AND (username LIKE ? OR email LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term]);
    $count_params = array_merge($count_params, [$search_term, $search_term]);
}

$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Exécuter la requête
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Compter le nombre total d'utilisateurs
$stmt_count = $pdo->prepare($count_query);
$stmt_count->execute($count_params);
$total_users = $stmt_count->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Afficher les messages de succès/erreur de session
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>' . htmlspecialchars($_SESSION['success']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>' . htmlspecialchars($_SESSION['error']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['error']);
}
?>

<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Gestion des Utilisateurs</h1>
            <p class="text-muted">Gérez les utilisateurs de l'application</p>
        </div>
    </div>

    <!-- Cartes de statistiques -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Utilisateurs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $total_users; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <?php
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $admin_count = $stmt->fetchColumn();
            ?>
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Administrateurs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $admin_count; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <?php
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
            $user_count = $stmt->fetchColumn();
            ?>
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Utilisateurs Standards
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $user_count; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <?php
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
            $today_count = $stmt->fetchColumn();
            ?>
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Inscriptions Aujourd'hui
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $today_count; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barre de recherche et bouton d'ajout -->
    <div class="row mb-4">
        <div class="col-md-8">
            <form method="GET" class="d-flex">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Rechercher un utilisateur..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (!empty($search)): ?>
                    <a href="users.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Effacer
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus me-2"></i>Ajouter un Utilisateur
            </button>
        </div>
    </div>

    <!-- Tableau des utilisateurs -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Liste des Utilisateurs</h6>
            <span class="badge bg-primary"><?php echo $total_users; ?> utilisateur(s)</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="usersTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom d'utilisateur</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Inscription</th>
                            <th>Dernière connexion</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Aucun utilisateur trouvé</p>
                                <?php if (!empty($search)): ?>
                                <a href="users.php" class="btn btn-primary">Voir tous les utilisateurs</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge bg-info ms-2">Vous</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Changer le rôle de cet utilisateur ?')">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="role" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()" 
                                                <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                            <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Utilisateur</option>
                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                                        </select>
                                        <input type="hidden" name="update_role">
                                    </form>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    if (isset($user['last_login']) && !empty($user['last_login'])): 
                                    ?>
                                        <?php echo date('d/m/Y H:i', strtotime($user['last_login'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Jamais</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <!-- Voir les détails -->
                                        <button type="button" class="btn btn-outline-primary view-user-btn"
                                                data-user-id="<?php echo $user['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                data-role="<?php echo $user['role']; ?>"
                                                data-created="<?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>"
                                                data-last-login="<?php echo isset($user['last_login']) && !empty($user['last_login']) ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais'; ?>"
                                                title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <!-- Modifier -->
                                        <button type="button" class="btn btn-outline-warning edit-user-btn"
                                                data-user-id="<?php echo $user['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                data-role="<?php echo $user['role']; ?>"
                                                title="Modifier"
                                                <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <!-- Supprimer -->
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible et supprimera toutes ses tâches et notifications.');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-outline-danger"
                                                    <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal d'ajout d'utilisateur -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un Utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="addUserForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur *</label>
                        <input type="text" class="form-control" id="username" name="username" required minlength="3">
                        <div class="form-text">Minimum 3 caractères</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="form-text">Doit être une adresse email valide</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe *</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        <div class="form-text">Minimum 6 caractères</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Rôle</label>
                        <select class="form-select" id="role" name="role">
                            <option value="user">Utilisateur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal d'édition d'utilisateur -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier l'Utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editUserForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Nom d'utilisateur *</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required minlength="3">
                        <div class="form-text">Minimum 3 caractères</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                        <div class="form-text">Doit être une adresse email valide</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Rôle</label>
                        <select class="form-select" id="edit_role" name="role">
                            <option value="user">Utilisateur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                        <div class="form-text">Laissez vide pour ne pas changer le mot de passe</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de détails utilisateur -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de l'Utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Nom d'utilisateur:</strong>
                    <p id="view_username" class="mb-2"></p>
                </div>
                
                <div class="mb-3">
                    <strong>Email:</strong>
                    <p id="view_email" class="mb-2"></p>
                </div>
                
                <div class="mb-3">
                    <strong>Rôle:</strong>
                    <p id="view_role" class="mb-2"></p>
                </div>
                
                <div class="mb-3">
                    <strong>Date d'inscription:</strong>
                    <p id="view_created" class="mb-2"></p>
                </div>
                
                <div class="mb-3">
                    <strong>Dernière connexion:</strong>
                    <p id="view_last_login" class="mb-2"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé - initialisation des boutons');
    
    // Initialiser Bootstrap modals
    const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
    const editUserModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    const viewUserModal = new bootstrap.Modal(document.getElementById('viewUserModal'));
    
    // Gestion du bouton "Ajouter un utilisateur" (déjà présent dans la barre d'outils)
    const addButton = document.querySelector('button[data-bs-target="#addUserModal"]');
    if (addButton) {
        addButton.addEventListener('click', function(e) {
            console.log('Bouton ajouter cliqué');
            addUserModal.show();
        });
    }
    
    // Gestion de la vue des détails
    const viewButtons = document.querySelectorAll('.view-user-btn');
    console.log('Boutons view trouvés:', viewButtons.length);
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            console.log('Bouton view cliqué');
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            const email = this.getAttribute('data-email');
            const role = this.getAttribute('data-role');
            const created = this.getAttribute('data-created');
            const lastLogin = this.getAttribute('data-last-login');
            
            // Remplir les informations de base
            document.getElementById('view_username').textContent = username;
            document.getElementById('view_email').textContent = email;
            document.getElementById('view_role').textContent = role === 'admin' ? 'Administrateur' : 'Utilisateur';
            document.getElementById('view_created').textContent = created;
            document.getElementById('view_last_login').textContent = lastLogin;
            
            // Afficher le modal
            viewUserModal.show();
        });
    });
    
    // Gestion de l'édition
    const editButtons = document.querySelectorAll('.edit-user-btn');
    console.log('Boutons edit trouvés:', editButtons.length);
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            console.log('Bouton edit cliqué');
            
            if (this.disabled) {
                console.log('Bouton désactivé');
                return;
            }
            
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            const email = this.getAttribute('data-email');
            const role = this.getAttribute('data-role');
            
            console.log('Données récupérées:', {userId, username, email, role});
            
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_password').value = '';
            
            editUserModal.show();
        });
    });
    
    // Validation des formulaires
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Le nom d\'utilisateur doit contenir au moins 3 caractères.');
                return false;
            }
            
            if (!validateEmail(email)) {
                e.preventDefault();
                alert('Veuillez entrer une adresse email valide.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                return false;
            }
            
            return true;
        });
    }
    
    const editUserForm = document.getElementById('editUserForm');
    if (editUserForm) {
        editUserForm.addEventListener('submit', function(e) {
            const username = document.getElementById('edit_username').value.trim();
            const email = document.getElementById('edit_email').value.trim();
            const password = document.getElementById('edit_password').value;
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Le nom d\'utilisateur doit contenir au moins 3 caractères.');
                return false;
            }
            
            if (!validateEmail(email)) {
                e.preventDefault();
                alert('Veuillez entrer une adresse email valide.');
                return false;
            }
            
            if (password && password.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                return false;
            }
            
            return true;
        });
    }
    
    // Fonction de validation d'email
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Confirmation pour la suppression
    const deleteForms = document.querySelectorAll('form[onsubmit]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
                e.preventDefault();
            }
        });
    });
    
    // Debug: Afficher tous les boutons avec leurs attributs data
    console.log('=== DEBUG INFO ===');
    console.log('Edit buttons:');
    editButtons.forEach((btn, idx) => {
        console.log(`Button ${idx}:`, {
            id: btn.getAttribute('data-user-id'),
            username: btn.getAttribute('data-username'),
            disabled: btn.disabled
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>