<?php
require_once '../includes/config.php';
requireAuth();

// ==================== TRAITEMENT AVANT TOUT HTML ====================
// Gestion des actions - DOIT ÊTRE AVANT TOUTE SORTIE HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // AJOUT D'UNE TÂCHE
    if (isset($_POST['add_task'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $due_date = $_POST['due_date'] ?? '';
        $priority = $_POST['priority'] ?? 'medium';
        
        // Validation basique
        if (empty($title)) {
            $_SESSION['error'] = 'Le titre de la tâche est obligatoire';
        } else {
            try {
                // Insérer la tâche
                $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, due_date, priority) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $title, $description, $due_date, $priority]);
                
                $_SESSION['success'] = 'Tâche ajoutée avec succès!';
                header('Location: tasks.php');
                exit();
                
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Erreur lors de l\'ajout de la tâche: ' . $e->getMessage();
            }
        }
    }
    
    // MISE À JOUR D'UNE TÂCHE
    if (isset($_POST['update_task'])) {
        $task_id = (int)$_POST['task_id'];
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $due_date = $_POST['due_date'] ?? '';
        $priority = $_POST['priority'] ?? 'medium';
        $status = $_POST['status'] ?? 'pending';
        
        // Vérifier que l'utilisateur est propriétaire de la tâche
        $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$task_id, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            try {
                // Mettre à jour la tâche
                $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, due_date = ?, priority = ?, status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                $stmt->execute([$title, $description, $due_date, $priority, $status, $task_id, $_SESSION['user_id']]);
                
                $_SESSION['success'] = 'Tâche mise à jour avec succès!';
                header('Location: tasks.php');
                exit();
                
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Erreur lors de la mise à jour: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = 'Tâche non trouvée ou accès non autorisé';
        }
    }
    
    // SUPPRESSION D'UNE TÂCHE
    if (isset($_POST['delete_task'])) {
        $task_id = (int)$_POST['task_id'];
        
        // Vérifier que l'utilisateur est propriétaire de la tâche
        $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$task_id, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            try {
                // Supprimer les notifications liées
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE task_id = ?");
                $stmt->execute([$task_id]);
                
                // Supprimer la tâche
                $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
                $stmt->execute([$task_id, $_SESSION['user_id']]);
                
                $_SESSION['success'] = 'Tâche supprimée avec succès!';
                header('Location: tasks.php');
                exit();
                
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Erreur lors de la suppression: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = 'Tâche non trouvée ou accès non autorisé';
        }
    }
}

// ==================== MAINTENANT ON INCLUT LES FICHIERS HTML ====================
$page_title = 'Mes Tâches';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Afficher les messages de succès/erreur
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

// Récupérer les tâches
$status = $_GET['status'] ?? 'all';
$query = "SELECT * FROM tasks WHERE user_id = ?";
$params = [$_SESSION['user_id']];

if ($status !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status;
}

$query .= " ORDER BY 
    CASE priority
        WHEN 'high' THEN 1
        WHEN 'medium' THEN 2
        WHEN 'low' THEN 3
    END,
    due_date ASC,
    created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Mes Tâches</h1>
            <p class="text-muted">Gérez vos tâches et vos échéances</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">Liste des Tâches (<?php echo count($tasks); ?>)</h5>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <a href="?status=all" class="btn btn-sm <?php echo $status == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">Toutes</a>
                                <a href="?status=pending" class="btn btn-sm <?php echo $status == 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">En attente</a>
                                <a href="?status=in_progress" class="btn btn-sm <?php echo $status == 'in_progress' ? 'btn-primary' : 'btn-outline-primary'; ?>">En cours</a>
                                <a href="?status=completed" class="btn btn-sm <?php echo $status == 'completed' ? 'btn-primary' : 'btn-outline-primary'; ?>">Terminées</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($tasks)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucune tâche trouvée</p>
                            <a href="#addTaskForm" class="btn btn-primary">Ajouter votre première tâche</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="30%">Titre</th>
                                        <th width="15%">Priorité</th>
                                        <th width="20%">Échéance</th>
                                        <th width="15%">Statut</th>
                                        <th width="20%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>
                                    <tr class="<?php echo $task['status'] == 'completed' ? 'table-success' : ''; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                            <?php if ($task['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($task['description'], 0, 50)); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $priority_class = [
                                                'low' => 'success',
                                                'medium' => 'warning',
                                                'high' => 'danger'
                                            ][$task['priority']];
                                            ?>
                                            <span class="badge bg-<?php echo $priority_class; ?>">
                                                <?php echo ucfirst($task['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($task['due_date']): ?>
                                                <?php echo date('d/m/Y H:i', strtotime($task['due_date'])); ?>
                                                <?php 
                                                $due_timestamp = strtotime($task['due_date']);
                                                $now = time();
                                                if ($due_timestamp < $now && $task['status'] != 'completed'): 
                                                ?>
                                                    <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> En retard</small>
                                                <?php elseif (($due_timestamp - $now) < 86400 && $task['status'] != 'completed'): ?>
                                                    <br><small class="text-warning"><i class="fas fa-clock"></i> Bientôt</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Non définie</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'pending' => 'secondary',
                                                'in_progress' => 'primary',
                                                'completed' => 'success'
                                            ][$task['status']];
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php 
                                                $status_text = [
                                                    'pending' => 'En attente',
                                                    'in_progress' => 'En cours',
                                                    'completed' => 'Terminée'
                                                ][$task['status']];
                                                echo $status_text;
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary edit-task-btn"
                                                        data-task-id="<?php echo $task['id']; ?>"
                                                        data-title="<?php echo htmlspecialchars($task['title']); ?>"
                                                        data-description="<?php echo htmlspecialchars($task['description']); ?>"
                                                        data-due-date="<?php echo $task['due_date'] ? date('Y-m-d\TH:i', strtotime($task['due_date'])) : ''; ?>"
                                                        data-priority="<?php echo $task['priority']; ?>"
                                                        data-status="<?php echo $task['status']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?');">
                                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                    <button type="submit" name="delete_task" class="btn btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                
                                                <?php if ($task['status'] != 'completed'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                    <input type="hidden" name="title" value="<?php echo htmlspecialchars($task['title']); ?>">
                                                    <input type="hidden" name="description" value="<?php echo htmlspecialchars($task['description']); ?>">
                                                    <input type="hidden" name="due_date" value="<?php echo $task['due_date']; ?>">
                                                    <input type="hidden" name="priority" value="<?php echo $task['priority']; ?>">
                                                    <input type="hidden" name="status" value="completed">
                                                    <button type="submit" name="update_task" class="btn btn-outline-success" title="Marquer comme terminée">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm" id="addTaskForm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Ajouter une Tâche</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Date d'échéance</label>
                                <input type="datetime-local" class="form-control" id="due_date" name="due_date">
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Priorité</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="low">Basse</option>
                                    <option value="medium" selected>Moyenne</option>
                                    <option value="high">Haute</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" name="add_task" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Ajouter la tâche
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Statistiques rapides -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Statistiques</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="stat-number text-primary">
                                <?php 
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = 'pending'");
                                $stmt->execute([$_SESSION['user_id']]);
                                echo $stmt->fetchColumn();
                                ?>
                            </div>
                            <small class="text-muted">En attente</small>
                        </div>
                        <div class="col-4">
                            <div class="stat-number text-warning">
                                <?php 
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = 'in_progress'");
                                $stmt->execute([$_SESSION['user_id']]);
                                echo $stmt->fetchColumn();
                                ?>
                            </div>
                            <small class="text-muted">En cours</small>
                        </div>
                        <div class="col-4">
                            <div class="stat-number text-success">
                                <?php 
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = 'completed'");
                                $stmt->execute([$_SESSION['user_id']]);
                                echo $stmt->fetchColumn();
                                ?>
                            </div>
                            <small class="text-muted">Terminées</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'édition -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la Tâche</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editTaskForm">
                <div class="modal-body">
                    <input type="hidden" name="task_id" id="edit_task_id">
                    
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Titre *</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_due_date" class="form-label">Date d'échéance</label>
                            <input type="datetime-local" class="form-control" id="edit_due_date" name="due_date">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_priority" class="form-label">Priorité</label>
                            <select class="form-select" id="edit_priority" name="priority">
                                <option value="low">Basse</option>
                                <option value="medium">Moyenne</option>
                                <option value="high">Haute</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Statut</label>
                        <select class="form-select" id="edit_status" name="status">
                            <option value="pending">En attente</option>
                            <option value="in_progress">En cours</option>
                            <option value="completed">Terminée</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="update_task" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript pour gérer le modal d'édition -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Récupérer tous les boutons d'édition
    const editButtons = document.querySelectorAll('.edit-task-btn');
    const editModal = new bootstrap.Modal(document.getElementById('editTaskModal'));
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Récupérer les données depuis les attributs data
            const taskId = this.getAttribute('data-task-id');
            const title = this.getAttribute('data-title');
            const description = this.getAttribute('data-description');
            const dueDate = this.getAttribute('data-due-date');
            const priority = this.getAttribute('data-priority');
            const status = this.getAttribute('data-status');
            
            // Remplir le formulaire du modal
            document.getElementById('edit_task_id').value = taskId;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_due_date').value = dueDate;
            document.getElementById('edit_priority').value = priority;
            document.getElementById('edit_status').value = status;
            
            // Afficher le modal
            editModal.show();
        });
    });
    
    // Gérer la confirmation de suppression
    const deleteForms = document.querySelectorAll('form[onsubmit]');
    deleteForms.forEach(form => {
        const originalOnsubmit = form.onsubmit;
        form.onsubmit = function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette tâche ? Cette action est irréversible.')) {
                e.preventDefault();
                return false;
            }
            return true;
        };
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>