<?php
require_once '../includes/config.php';
requireAdmin();

$page_title = 'Statistiques';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Statistiques générales
$stats = [];

// Nombre total d'utilisateurs
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $stmt->fetch()['total'];

// Nombre d'utilisateurs par rôle
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$stats['users_by_role'] = $stmt->fetchAll();

// Nombre total de tâches
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tasks");
$stats['total_tasks'] = $stmt->fetch()['total'];

// Tâches par statut
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status");
$stats['tasks_by_status'] = $stmt->fetchAll();

// Tâches créées par mois (12 derniers mois)
$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM tasks 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");
$stats['tasks_by_month'] = $stmt->fetchAll();

// Utilisateurs inscrits par mois
$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");
$stats['users_by_month'] = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Statistiques</h1>
            <p class="text-muted">Analyse des données de l'application</p>
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
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tâches Totales
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['total_tasks']; ?>
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
                        <div class="col mr-2"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Taux d'achèvement
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        <?php
                                        $completed = 0;
                                        foreach ($stats['tasks_by_status'] as $status) {
                                            if ($status['status'] == 'completed') {
                                                $completed = $status['count'];
                                                break;
                                            }
                                        }
                                        echo $stats['total_tasks'] > 0 ? round(($completed / $stats['total_tasks']) * 100) : 0;
                                        ?>%
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" style="width: <?php echo $stats['total_tasks'] > 0 ? round(($completed / $stats['total_tasks']) * 100) : 0; ?>%"></div>
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
                                Administrateurs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $admins = 0;
                                foreach ($stats['users_by_role'] as $role) {
                                    if ($role['role'] == 'admin') {
                                        $admins = $role['count'];
                                        break;
                                    }
                                }
                                echo $admins;
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques et tableaux -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Évolution des tâches (12 derniers mois)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="tasksChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4"><div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tâches par Statut</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Utilisateurs par Rôle</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Rôle</th>
                                    <th>Nombre</th>
                                    <th>Pourcentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['users_by_role'] as $role): ?>
                                <tr>
                                    <td><?php echo $role['role'] == 'admin' ? 'Administrateur' : 'Utilisateur'; ?></td>
                                    <td><?php echo $role['count']; ?></td>
                                    <td>
                                        <?php echo round(($role['count'] / $stats['total_users']) * 100, 1); ?>%
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Inscriptions par Mois</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Mois</th>
                                    <th>Nouveaux Utilisateurs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['users_by_month'] as $month): ?>
                                <tr>
                                    <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                    <td><?php echo $month['count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Préparer les données pour les graphiques
const tasksByMonth = <?php echo json_encode($stats['tasks_by_month']); ?>;
const tasksByStatus = <?php echo json_encode($stats['tasks_by_status']); ?>;

// Graphique d'évolution des tâches
const tasksCtx = document.getElementById('tasksChart').getContext('2d');
const tasksChart = new Chart(tasksCtx, {
    type: 'line',
    data: {
        labels: tasksByMonth.map(item => item.month),
        datasets: [{
            label: 'Tâches créées',data: tasksByMonth.map(item => item.count),
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Graphique camembert des statuts
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'pie',
    data: {
        labels: tasksByStatus.map(item => {
            const statusMap = {
                'pending': 'En attente',
                'in_progress': 'En cours',
                'completed': 'Terminé'
            };
            return statusMap[item.status] || item.status;
        }),
        datasets: [{
            data: tasksByStatus.map(item => item.count),
            backgroundColor: [
                'rgba(108, 117, 125, 0.7)',    // pending
                'rgba(13, 110, 253, 0.7)',     // in_progress
                'rgba(25, 135, 84, 0.7)'       // completed
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>