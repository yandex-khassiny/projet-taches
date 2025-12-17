<?php
require_once '../includes/config.php';
requireAdmin();

$page_title = 'Tableau de bord Administrateur';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Récupérer les statistiques pour le dashboard
$stats = [];

// Nombre total d'utilisateurs
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$stats['total_users'] = $stmt->fetchColumn();

// Nombre d'utilisateurs ce mois-ci
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$stats['users_this_month'] = $stmt->fetchColumn();

// Nombre total de tâches
$stmt = $pdo->query("SELECT COUNT(*) FROM tasks");
$stats['total_tasks'] = $stmt->fetchColumn();

// Tâches créées aujourd'hui
$stmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE DATE(created_at) = CURDATE()");
$stats['tasks_today'] = $stmt->fetchColumn();

// Tâches en retard
$stmt = $pdo->query("SELECT COUNT(*) FROM tasks WHERE due_date < NOW() AND status != 'completed'");
$stats['overdue_tasks'] = $stmt->fetchColumn();

// Tâches par statut
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status");
$tasks_by_status = $stmt->fetchAll();
$stats['tasks_by_status'] = [];
foreach ($tasks_by_status as $row) {
    $stats['tasks_by_status'][$row['status']] = $row['count'];
}

// Derniers utilisateurs inscrits
$stmt = $pdo->query("SELECT username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

// Dernières tâches créées
$stmt = $pdo->query("SELECT t.title, t.status, t.due_date, u.username FROM tasks t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 10");
$recent_tasks = $stmt->fetchAll();

// Calculer le taux d'achèvement
$completion_rate = $stats['total_tasks'] > 0 ? 
    round(($stats['tasks_by_status']['completed'] ?? 0) / $stats['total_tasks'] * 100) : 0;
?>

<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Tableau de bord Administrateur</h1>
            <p class="text-muted">Bienvenue, <?php echo htmlspecialchars($_SESSION['username']); ?> ! Voici un aperçu de votre application.</p>
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
                                Utilisateurs Totaux
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['total_users']; ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span class="text-success mr-2">
                                    <i class="fas fa-arrow-up"></i> <?php echo $stats['users_this_month']; ?>
                                </span>
                                <span>ce mois</span>
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
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center"><div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tâches Totales
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['total_tasks']; ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span class="text-success mr-2">
                                    <i class="fas fa-tasks"></i> <?php echo $stats['tasks_today']; ?>
                                </span>
                                <span>aujourd'hui</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Taux d'achèvement
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        <?php echo $completion_rate; ?>%
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" style="width: <?php echo $completion_rate; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Tâches en retard
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['overdue_tasks']; ?>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span class="text-danger mr-2">
                                    <i class="fas fa-exclamation-triangle"></i> Attention
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="row"><!-- Derniers utilisateurs -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Derniers utilisateurs inscrits</h6>
                    <a href="users.php" class="btn btn-sm btn-primary">Voir tout</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="recentUsersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nom d'utilisateur</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Inscription</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-warning' : 'bg-info'; ?>">
                                            <?php echo $user['role'] == 'admin' ? 'Admin' : 'User'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dernières tâches -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Dernières tâches créées</h6>
                    <a href="#" class="btn btn-sm btn-primary" onclick="loadRecentTasks()">Actualiser</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="recentTasksTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tâche</th>
                                    <th>Utilisateur</th>
                                    <th>Statut</th>
                                    <th>Échéance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_tasks as $task): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['username']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'pending' => 'secondary',
                                            'in_progress' => 'primary',
                                            'completed' => 'success'][$task['status']];
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo $task['status'] == 'in_progress' ? 'En cours' : ucfirst($task['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($task['due_date']): ?>
                                            <?php echo date('d/m/Y', strtotime($task['due_date'])); ?>
                                            <?php if (strtotime($task['due_date']) < time() && $task['status'] != 'completed'): ?>
                                                <br><small class="text-danger"><i class="fas fa-exclamation-circle"></i> En retard</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Non définie</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Activité des tâches (30 derniers jours)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Répartition des tâches</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="tasksPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Graphique d'activité
const activityCtx = document.getElementById('activityChart').getContext('2d');
const activityChart = new Chart(activityCtx, {
    type: 'line',
    data: {
        labels: ['Jour 1', 'Jour 2', 'Jour 3', 'Jour 4', 'Jour 5', 'Jour 6', 'Jour 7'],
        datasets: [{
            label: 'Tâches créées',
            data: [12, 19, 8, 15, 12, 17, 20],
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.4
        }, {
            label: 'Tâches terminées',
            data: [7, 12, 6, 9, 10, 13, 15],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Nombre de tâches'
                }
            }
        }
    }
});

// Graphique camembert des tâches
const pieCtx = document.getElementById('tasksPieChart').getContext('2d');const tasksPieChart = new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: ['En attente', 'En cours', 'Terminé'],
        datasets: [{
            data: [
                <?php echo $stats['tasks_by_status']['pending'] ?? 0; ?>,
                <?php echo $stats['tasks_by_status']['in_progress'] ?? 0; ?>,
                <?php echo $stats['tasks_by_status']['completed'] ?? 0; ?>
            ],
            backgroundColor: [
                'rgba(108, 117, 125, 0.7)',
                'rgba(13, 110, 253, 0.7)',
                'rgba(25, 135, 84, 0.7)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += context.raw + ' tâches';
                        return label;
                    }
                }
            }
        }
    }
});

// Fonction pour charger les tâches récentes
function loadRecentTasks() {
    fetch('/api/admin/recent-tasks.php')
        .then(response => response.json())
        .then(data => {
            // Mettre à jour le tableau
            console.log('Tâches récentes chargées:', data);
        })
        .catch(error => console.error('Erreur:', error));
}

// Mettre à jour les notifications toutes les minutes
setInterval(() => {
    fetch('/api/notifications/count.php')
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                document.getElementById('notificationCount').textContent = data.count;
                document.getElementById('notificationCount').classList.remove('d-none');
            }
        });
}, 60000);
</script>

<?php require_once '../includes/footer.php'; ?>