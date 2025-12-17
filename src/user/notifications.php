<?php
require_once '../includes/config.php';
requireAuth();

$page_title = 'Notifications';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$user_id = $_SESSION['user_id'];

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_all_read'])) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $success = "Toutes les notifications ont été marquées comme lues.";
    } elseif (isset($_POST['clear_all'])) {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $success = "Toutes les notifications ont été supprimées.";
    } elseif (isset($_POST['mark_read'])) {
        $notification_id = $_POST['notification_id'];
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
    } elseif (isset($_POST['delete_notification'])) {
        $notification_id = $_POST['notification_id'];
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
    }
}

// Filtres
$filter = $_GET['filter'] ?? 'all';
$query = "SELECT n.*, t.title as task_title 
          FROM notifications n 
          LEFT JOIN tasks t ON n.task_id = t.id 
          WHERE n.user_id = ?";

$params = [$user_id];

switch ($filter) {
    case 'unread':
        $query .= " AND n.is_read = FALSE";
        break;
    case 'read':
        $query .= " AND n.is_read = TRUE";
        break;
    case 'reminders':
        $query .= " AND n.type = 'reminder'";
        break;
    case 'system':
        $query .= " AND n.type = 'system'";
        break;
    case 'updates':
        $query .= " AND n.type = 'update'";
        break;
}

$query .= " ORDER BY n.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// Compter les notifications non lues
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetchColumn();
?>

<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">Notifications</h1>
                    <p class="text-muted">Restez informé de vos activités et rappels</p>
                </div>
                <div class="text-end">
                    <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger me-2"><?php echo $unread_count; ?> non lue(s)</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages d'alerte -->
    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Filtres et actions -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="btn-group" role="group">
                <a href="?filter=all" class="btn btn-sm <?php echo $filter == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Toutes
                </a>
                <a href="?filter=unread" class="btn btn-sm <?php echo $filter == 'unread' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Non lues
                    <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger ms-1"><?php echo $unread_count; ?></span>

Yandex Khassiny, [14/12/2025 22:55]
<?php endif; ?>
                </a>
                <a href="?filter=read" class="btn btn-sm <?php echo $filter == 'read' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Lues
                </a>
                <a href="?filter=reminders" class="btn btn-sm <?php echo $filter == 'reminders' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Rappels
                </a>
                <a href="?filter=system" class="btn btn-sm <?php echo $filter == 'system' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Système
                </a>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <form method="POST" class="d-inline">
                <button type="submit" name="mark_all_read" class="btn btn-sm btn-success" 
                        <?php echo empty($notifications) ? 'disabled' : ''; ?>>
                    <i class="fas fa-check-double me-1"></i>Tout marquer comme lu
                </button>
            </form>
            <form method="POST" class="d-inline ms-2" onsubmit="return confirm('Supprimer toutes les notifications ?')">
                <button type="submit" name="clear_all" class="btn btn-sm btn-danger" 
                        <?php echo empty($notifications) ? 'disabled' : ''; ?>>
                    <i class="fas fa-trash me-1"></i>Tout supprimer
                </button>
            </form>
        </div>
    </div>

    <!-- Liste des notifications -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Liste des notifications</h6>
        </div>
        <div class="card-body">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-5">
                    <i class="far fa-bell fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Aucune notification</p>
                    <p class="text-muted small">Vous serez notifié ici de vos rappels et activités</p>
                </div>
            <?php else: ?>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?> mb-3 p-3 border rounded">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-start" style="flex: 1;">
                                <div class="notification-icon me-3">
                                    <?php
                                    $icon = '';
                                    $icon_class = '';
                                    switch ($notification['type']) {
                                        case 'reminder':
                                            $icon = 'fa-clock';
                                            $icon_class = 'text-warning';
                                            break;
                                        case 'system':
                                            $icon = 'fa-cog';
                                            $icon_class = 'text-info';
                                            break;
                                        case 'update':
                                            $icon = 'fa-sync-alt';
                                            $icon_class = 'text-primary';
                                            break;
                                        default:
                                            $icon = 'fa-bell';
                                            $icon_class = 'text-secondary';
                                    }
                                    ?>
                                    <i class="fas <?php echo $icon; ?> fa-lg <?php echo $icon_class; ?>"></i>

Yandex Khassiny, [14/12/2025 22:55]
</div>
                                <div style="flex: 1;">
                                    <div class="notification-content">
                                        <p class="mb-1 <?php echo !$notification['is_read'] ? 'fw-bold' : ''; ?>">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                        
                                        <?php if ($notification['task_title']): ?>
                                        <div class="task-reference mt-2">
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-tasks me-1"></i>
                                                <?php echo htmlspecialchars($notification['task_title']); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="notification-meta mt-2">
                                            <small class="text-muted">
                                                <i class="far fa-clock me-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                                                
                                                <?php if ($notification['scheduled_time']): ?>
                                                    <span class="ms-3">
                                                        <i class="fas fa-calendar-alt me-1"></i>
                                                        Programmé: <?php echo date('d/m/Y H:i', strtotime($notification['scheduled_time'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($notification['type'] == 'reminder'): ?>
                                                    <span class="ms-3">
                                                        <i class="fas fa-exclamation-circle me-1"></i>
                                                        Rappel
                                                    </span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="notification-actions">
                                <?php if (!$notification['is_read']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" name="mark_read" class="btn btn-sm btn-outline-success" title="Marquer comme lu">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <form method="POST" class="d-inline ms-1" 
                                      onsubmit="return confirm('Supprimer cette notification ?')">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger" title="Supprimer">

Yandex Khassiny, [14/12/2025 22:55]
<i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Paramètres de notifications -->
    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Paramètres de notifications</h6>
                </div>
                <div class="card-body">
                    <form id="notificationSettings">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                <label class="form-check-label" for="emailNotifications">
                                    Notifications par email
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Recevoir des emails pour les rappels et notifications importantes
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="pushNotifications" checked>
                                <label class="form-check-label" for="pushNotifications">
                                    Notifications push
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Recevoir des notifications dans votre navigateur
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="taskReminders" checked>
                                <label class="form-check-label" for="taskReminders">
                                    Rappels de tâches
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Recevoir des rappels pour les tâches à échéance
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="taskUpdates">
                                <label class="form-check-label" for="taskUpdates">
                                    Mises à jour de tâches
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Être notifié des changements sur vos tâches
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reminderTime" class="form-label">Heure des rappels quotidiens</label>
                            <select class="form-select" id="reminderTime">
                                <option value="08:00">08:00</option>
                                <option value="09:00" selected>09:00</option>

Yandex Khassiny, [14/12/2025 22:55]
<option value="10:00">10:00</option>
                                <option value="17:00">17:00</option>
                                <option value="18:00">18:00</option>
                            </select>
                            <small class="form-text text-muted">
                                Heure à laquelle vous recevrez le récapitulatif quotidien
                            </small>
                        </div>
                        
                        <div class="mt-4">
                            <button type="button" class="btn btn-primary" onclick="saveNotificationSettings()">
                                <i class="fas fa-save me-2"></i>Enregistrer les paramètres
                            </button>
                            <button type="button" class="btn btn-outline-secondary ms-2" onclick="testNotification()">
                                <i class="fas fa-bell me-2"></i>Tester la notification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Gestion des rappels</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Configurez des rappels automatiques pour vos tâches importantes
                    </p>
                    
                    <form id="reminderForm">
                        <div class="mb-3">
                            <label for="reminderTask" class="form-label">Tâche à rappeler</label>
                            <select class="form-select" id="reminderTask">
                                <option value="">Sélectionnez une tâche</option>
                                <?php
                                $stmt = $pdo->prepare("SELECT id, title FROM tasks WHERE user_id = ? AND status != 'completed' ORDER BY title");
                                $stmt->execute([$user_id]);
                                $tasks = $stmt->fetchAll();
                                foreach ($tasks as $task) {
                                    echo "<option value=\"{$task['id']}\">" . htmlspecialchars($task['title']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reminderDate" class="form-label">Date du rappel</label>
                            <input type="date" class="form-control" id="reminderDate" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="reminderTimeInput" class="form-label">Heure du rappel</label>
                            <input type="time" class="form-control" id="reminderTimeInput" value="09:00">
                        </div>
                        
                        <div class="mb-3">
                            <label for="reminderMessage" class="form-label">Message personnalisé</label>
                            <textarea class="form-control" id="reminderMessage" rows="3" 
                                      placeholder="Ex: N'oubliez pas de terminer cette tâche importante"></textarea>
                        </div><div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="repeatReminder">
                                <label class="form-check-label" for="repeatReminder">
                                    Répéter ce rappel
                                </label>
                            </div>
                        </div>
                        
                        <div id="repeatOptions" class="mb-3" style="display: none;">
                            <label class="form-label">Répétition</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <select class="form-select" id="repeatFrequency">
                                        <option value="daily">Quotidien</option>
                                        <option value="weekly">Hebdomadaire</option>
                                        <option value="monthly">Mensuel</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="number" class="form-control" id="repeatDuration" min="1" max="30" value="7" placeholder="Durée (jours)">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="button" class="btn btn-success" onclick="createReminder()">
                                <i class="fas fa-plus-circle me-2"></i>Créer un rappel
                            </button>
                            <button type="button" class="btn btn-outline-secondary ms-2" onclick="clearReminderForm()">
                                <i class="fas fa-times me-2"></i>Effacer
                            </button>
                        </div>
                    </form>
                    
                    <!-- Liste des rappels programmés -->
                    <div class="mt-4 pt-4 border-top">
                        <h6 class="mb-3">Rappels programmés</h6>
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT n.*, t.title 
                            FROM notifications n 
                            LEFT JOIN tasks t ON n.task_id = t.id 
                            WHERE n.user_id = ? 
                            AND n.type = 'reminder' 
                            AND n.scheduled_time > NOW() 
                            ORDER BY n.scheduled_time ASC
                            LIMIT 5
                        ");
                        $stmt->execute([$user_id]);
                        $scheduled_reminders = $stmt->fetchAll();
                        
                        if (empty($scheduled_reminders)) {
                            echo '<p class="text-muted small">Aucun rappel programmé</p>';
                        } else {
                            echo '<div class="list-group list-group-flush">';
                            foreach ($scheduled_reminders as $reminder) {
                                echo '<div class="list-group-item list-group-item-action border-0 px-0 py-2">';
                                echo '<div class="d-flex justify-content-between align-items-center">';
                                echo '<small class="text-primary">' . htmlspecialchars($reminder['title'] ?? 'Sans titre') . '</small><br>';
                                echo '<small class="text-muted">' . htmlspecialchars($reminder['message']) . '</small>';
                                echo '</div>';
                                echo '<div class="text-end">';
                                echo '<small class="text-muted">' . date('d/m H:i', strtotime($reminder['scheduled_time'])) . '</small>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.notification-item.unread {
    background-color: #f0f8ff;
    border-left: 4px solid #4361ee !important;
}

.notification-item.read {
    background-color: #f8f9fa;
    opacity: 0.9;
}

.notification-item {
    transition: all 0.3s;
}

.notification-item:hover {
    transform: translateX(5px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-actions .btn {
    opacity: 0.7;
    transition: opacity 0.3s;
}

.notification-item:hover .notification-actions .btn {
    opacity: 1;
}
</style>

<script>
// Gestion des paramètres de répétition
document.getElementById('repeatReminder').addEventListener('change', function() {
    document.getElementById('repeatOptions').style.display = this.checked ? 'block' : 'none';
});

// Charger les paramètres de notifications sauvegardés
document.addEventListener('DOMContentLoaded', function() {
    const savedSettings = localStorage.getItem('notificationSettings');
    if (savedSettings) {
        const settings = JSON.parse(savedSettings);
        document.getElementById('emailNotifications').checked = settings.emailNotifications || true;
        document.getElementById('pushNotifications').checked = settings.pushNotifications || true;
        document.getElementById('taskReminders').checked = settings.taskReminders || true;
        document.getElementById('taskUpdates').checked = settings.taskUpdates || false;
        document.getElementById('reminderTime').value = settings.reminderTime || '09:00';
    }
    
    // Demander la permission pour les notifications
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
});

// Sauvegarder les paramètres de notifications
function saveNotificationSettings() {
    const settings = {
        emailNotifications: document.getElementById('emailNotifications').checked,
        pushNotifications: document.getElementById('pushNotifications').checked,
        taskReminders: document.getElementById('taskReminders').checked,
        taskUpdates: document.getElementById('taskUpdates').checked,
        reminderTime: document.getElementById('reminderTime').value
    };
    
    localStorage.setItem('notificationSettings', JSON.stringify(settings));
    
    // Sauvegarder sur le serveur
    fetch('/api/user/notification-settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Paramètres enregistrés avec succès !', 'success');
        } else {
            showAlert('Erreur lors de l\'enregistrement', 'danger');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion', 'danger');
    });
}

Yandex Khassiny, [14/12/2025 23:10]
// Tester les notifications
function testNotification() {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('TaskManager - Test de notification', {
            body: 'Ceci est un test de notification. Vos paramètres fonctionnent correctement !',
            icon: '/assets/images/notification-icon.png'
        });
    } else if (Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                new Notification('TaskManager - Test de notification', {
                    body: 'Ceci est un test de notification. Vos paramètres fonctionnent correctement !',
                    icon: '/assets/images/notification-icon.png'
                });
            }
        });
    } else {
        alert('Les notifications sont désactivées. Veuillez les autoriser dans les paramètres de votre navigateur.');
    }
}

// Créer un rappel
function createReminder() {
    const taskId = document.getElementById('reminderTask').value;
    const date = document.getElementById('reminderDate').value;
    const time = document.getElementById('reminderTimeInput').value;
    const message = document.getElementById('reminderMessage').value;
    const repeat = document.getElementById('repeatReminder').checked;
    const frequency = document.getElementById('repeatFrequency').value;
    const duration = document.getElementById('repeatDuration').value;
    
    if (!taskId  !date  !time) {
        showAlert('Veuillez remplir tous les champs obligatoires', 'warning');
        return;
    }
    
    const scheduledTime = date + ' ' + time + ':00';
    
    const reminderData = {
        task_id: taskId,
        message: message || 'Rappel de tâche',
        scheduled_time: scheduledTime,
        repeat: repeat,
        frequency: frequency,
        duration: duration
    };
    
    fetch('/api/notifications/create-reminder.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(reminderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Rappel créé avec succès !', 'success');
            clearReminderForm();
            // Recharger la page pour voir le nouveau rappel
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('Erreur: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion', 'danger');
    });
}

// Effacer le formulaire de rappel
function clearReminderForm() {
    document.getElementById('reminderTask').value = '';
    document.getElementById('reminderDate').value = '';
    document.getElementById('reminderTimeInput').value = '09:00';
    document.getElementById('reminderMessage').value = '';
    document.getElementById('repeatReminder').checked = false;
    document.getElementById('repeatOptions').style.display = 'none';
}

// Afficher une alerte
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = alert alert-${type} alert-dismissible fade show;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = 
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    ;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Vérifier les notifications en temps réel
setInterval(() => {
    fetch('/api/notifications/check.php')
        .then(response => response.json())
        .then(data => {

Yandex Khassiny, [14/12/2025 23:10]
if (data.has_new) {
                // Afficher une notification push
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification('Nouvelle notification', {
                        body: data.message,
                        icon: '/assets/images/notification-icon.png'
                    });
                }
                
                // Mettre à jour le compteur
                document.querySelectorAll('.notification-count').forEach(el => {
                    el.textContent = data.count;
                    el.classList.remove('d-none');
                });
                
                // Recharger la liste si on est sur la page des notifications
                if (window.location.pathname.includes('notifications.php')) {
                    location.reload();
                }
            }
        });
}, 30000); // Toutes les 30 secondes

// Marquer une notification comme lue au clic
document.addEventListener('click', function(e) {
    if (e.target.closest('.notification-item')) {
        const notificationItem = e.target.closest('.notification-item');
        if (notificationItem.classList.contains('unread')) {
            const notificationId = notificationItem.querySelector('input[name="notification_id"]')?.value;
            if (notificationId) {
                fetch('/api/notifications/mark-read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: notificationId })
                });
            }
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
                        
                        