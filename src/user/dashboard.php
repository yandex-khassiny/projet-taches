<?php
require_once '../includes/config.php';
requireAuth();

$page_title = 'Tableau de bord';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$user_id = $_SESSION['user_id'];

// Récupérer les statistiques pour l'utilisateur
$stats = [];

// Tâches totales
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats['total_tasks'] = $stmt->fetchColumn();

// Tâches aujourd'hui
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND DATE(created_at) = CURDATE()");
$stmt->execute([$user_id]);
$stats['tasks_today'] = $stmt->fetchColumn();

// Tâches en retard
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND due_date < NOW() AND status != 'completed'");
$stmt->execute([$user_id]);
$stats['overdue_tasks'] = $stmt->fetchColumn();

// Tâches à faire aujourd'hui
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND DATE(due_date) = CURDATE() AND status != 'completed'");
$stmt->execute([$user_id]);
$stats['due_today'] = $stmt->fetchColumn();

// Tâches par statut
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks WHERE user_id = ? GROUP BY status");
$stmt->execute([$user_id]);
$tasks_by_status = $stmt->fetchAll();
$stats['tasks_by_status'] = [];
foreach ($tasks_by_status as $row) {
    $stats['tasks_by_status'][$row['status']] = $row['count'];
}

// Dernières tâches
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_tasks = $stmt->fetchAll();

// Tâches à venir (7 prochains jours)
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? AND due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) ORDER BY due_date ASC LIMIT 10");
$stmt->execute([$user_id]);
$upcoming_tasks = $stmt->fetchAll();

// Notifications non lues
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->execute([$user_id]);
$stats['unread_notifications'] = $stmt->fetchColumn();

// Calculer le taux d'achèvement
$completion_rate = $stats['total_tasks'] > 0 ? 
    round(($stats['tasks_by_status']['completed'] ?? 0) / $stats['total_tasks'] * 100) : 0;
?>

<style>
/* ===========================================
   CSS POUR LE DASHBOARD UTILISATEUR
   =========================================== */

/* Variables CSS */
:root {
    --primary-color: #4361ee;
    --primary-light: #eef2ff;
    --secondary-color: #3a0ca3;
    --accent-color: #4cc9f0;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --info-color: #3b82f6;
    --light-color: #f8fafc;
    --dark-color: #1e293b;
    --gray-color: #64748b;
    --border-color: #e2e8f0;
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
    --radius: 12px;
    --radius-sm: 8px;
}

/* Styles de base pour le dashboard */
.dashboard-container {
    background: linear-gradient(135deg, #f5f7fb 0%, #e4edf5 100%);
    min-height: 100vh;
    padding: 20px;
}

.main-content {
    margin-left: 250px;
    padding: 20px;
    transition: all 0.3s ease;
}

/* En-tête */
.dashboard-header {
    background: white;
    border-radius: var(--radius);
    padding: 25px 30px;
    margin-bottom: 25px;
    box-shadow: var(--shadow-md);
    border-left: 5px solid var(--primary-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    animation: slideIn 0.5s ease-out;
}

.header-content h1 {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 5px;
}

.header-content p {
    color: var(--gray-color);
    font-size: 1rem;
    margin: 0;
}

.btn-add-task {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));color: white;
    border: none;
    padding: 12px 25px;
    border-radius: var(--radius-sm);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
}

.btn-add-task:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
}

/* Grille de statistiques */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: var(--radius);
    padding: 25px;
    box-shadow: var(--shadow-md);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s;
    border-top: 4px solid;
    animation: fadeIn 0.5s ease-out;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.stat-card.total {
    border-top-color: var(--primary-color);
}

.stat-card.completed {
    border-top-color: var(--success-color);
}

.stat-card.overdue {
    border-top-color: var(--danger-color);
}

.stat-card.today {
    border-top-color: var(--info-color);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.stat-card.total .stat-icon {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
}

.stat-card.completed .stat-icon {
    background: linear-gradient(135deg, var(--success-color), #059669);
}

.stat-card.overdue .stat-icon {
    background: linear-gradient(135deg, var(--danger-color), #dc2626);
}

.stat-card.today .stat-icon {
    background: linear-gradient(135deg, var(--info-color), #2563eb);
}

.stat-content h3 {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0 0 5px 0;
    color: var(--dark-color);
}

.stat-content p {
    color: var(--gray-color);
    margin: 0;
    font-size: 0.95rem;
}

.stat-meta {
    margin-top: 8px;
    font-size: 0.85rem;
    color: var(--gray-color);
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Grille de contenu */
.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 30px;
}

@media (max-width: 992px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

/* Cartes */
.dashboard-card {
    background: white;
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    margin-bottom: 25px;
    animation: slideIn 0.6s ease-out;
}

.card-header {
    padding: 20px 25px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
}

.card-header h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--dark-color);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-header .btn-sm {
    padding: 8px 16px;
    font-size: 0.9rem;
}

.card-body {
    padding: 25px;
}

/* Liste des tâches */
.task-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.task-item {
    padding: 18px 0;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s;
}

.task-item:last-child {
    border-bottom: none;
}

.task-item:hover {
    background: var(--light-color);
    padding-left: 10px;
    padding-right: 10px;
    border-radius: var(--radius-sm);
}

.task-info {
    flex: 1;
}

.task-title {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.05rem;
}

.task-title i {
    color: var(--gray-color);
    font-size: 1rem;
}

.task-description {
    color: var(--gray-color);
    font-size: 0.9rem;
    margin-bottom: 10px;
    line-height: 1.5;
}

.task-meta {
    display: flex;
    gap: 15px;
    font-size: 0.85rem;
    color: var(--gray-color);
}.task-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: capitalize;
}

.status-pending { background: #f1f5f9; color: #64748b; }
.status-in_progress { background: #dbeafe; color: #1d4ed8; }
.status-completed { background: #d1fae5; color: #047857; }

.task-priority {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: capitalize;
}

.priority-low { background: #d1fae5; color: #047857; }
.priority-medium { background: #fef3c7; color: #d97706; }
.priority-high { background: #fee2e2; color: #dc2626; }

.task-date {
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Timeline pour tâches à venir */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--border-color);
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    top: 8px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 3px solid white;
    z-index: 1;
}

.timeline-marker.high { background: var(--danger-color); }
.timeline-marker.medium { background: var(--warning-color); }
.timeline-marker.low { background: var(--success-color); }

.timeline-content {
    padding: 15px;
    background: var(--light-color);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
}

.timeline-title {
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--dark-color);
}

.timeline-date {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    font-size: 0.85rem;
    color: var(--gray-color);
}

/* Actions rapides */
.quick-actions-grid {
    display: grid;
    gap: 15px;
}

.quick-action {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px;
    background: white;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-sm);
    text-decoration: none;
    color: var(--dark-color);
    transition: all 0.3s;
    cursor: pointer;
}

.quick-action:hover {
    background: var(--primary-light);
    border-color: var(--primary-color);
    transform: translateX(5px);
}

.quick-action i {
    width: 45px;
    height: 45px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
}

.quick-action.new-task i { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
.quick-action.today i { background: linear-gradient(135deg, var(--warning-color), #f59e0b); }
.quick-action.overdue i { background: linear-gradient(135deg, var(--danger-color), #ef4444); }
.quick-action.notifications i { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.quick-action.profile i { background: linear-gradient(135deg, var(--success-color), #10b981); }

.quick-action-content {
    flex: 1;
}

.quick-action-title {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 3px;
}

.quick-action-desc {
    font-size: 0.85rem;
    color: var(--gray-color);
}

/* Graphique */
.chart-container {
    background: white;
    border-radius: var(--radius);
    padding: 25px;
    box-shadow: var(--shadow-md);
    margin-bottom: 30px;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.chart-header h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--dark-color);
    margin: 0;
}

.chart-placeholder {
    height: 300px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
}

.chart-placeholder i {
    font-size: 3rem;
    color: #cbd5e1;
}/* Statistiques détaillées */
.stats-details {
    background: white;
    border-radius: var(--radius);
    padding: 25px;
    box-shadow: var(--shadow-md);
}

.stats-grid-small {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 15px;
}

.stat-box {
    text-align: center;
    padding: 20px;
    background: var(--light-color);
    border-radius: var(--radius-sm);
    transition: all 0.3s;
}

.stat-box:hover {
    background: white;
    box-shadow: var(--shadow-sm);
}

.stat-number {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.stat-number.pending { color: #64748b; }
.stat-number.in_progress { color: #1d4ed8; }
.stat-number.completed { color: #047857; }
.stat-number.priority { color: #7c3aed; }

.stat-label {
    font-size: 0.9rem;
    color: var(--gray-color);
}

/* Progression */
.progress-section {
    margin-top: 20px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    font-size: 0.9rem;
    color: var(--dark-color);
}

.progress {
    height: 10px;
    border-radius: 5px;
    background: #e2e8f0;
    overflow: hidden;
}

.progress-bar {
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    border-radius: 5px;
}

/* Responsive */
@media (max-width: 1200px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
        padding-top: 80px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 15px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .btn-add-task {
        align-self: stretch;
        justify-content: center;
    }
    
    .content-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .card-header .btn-sm {
        align-self: stretch;
        text-align: center;
    }
    
    .task-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .task-meta {
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .stats-grid-small {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .stat-icon {
        margin: 0 auto;
    }
    
    .quick-action {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .quick-action i {
        margin: 0 auto;
    }
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.fade-in {
    animation: fadeIn 0.5s ease-out;
}

.slide-in {
    animation: slideIn 0.3s ease-out;
}

/* Badge notification */
.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: var(--danger-color);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 600;
}

/* État vide */
.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-state i {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 20px;
}

.empty-state h4 {
    font-size: 1.3rem;
    color: var(--dark-color);
    margin-bottom: 10px;
}

.empty-state p {
    color: var(--gray-color);
    margin-bottom: 20px;
}
</style><div class="dashboard-container">
    <div class="main-content">
        <!-- En-tête -->
        <div class="dashboard-header">
            <div class="header-content">
                <h1>Bonjour, <?php echo htmlspecialchars($_SESSION['username']); ?> ! 👋</h1>
                <p>Voici un aperçu de vos tâches et activités</p>
            </div>
            <a href="tasks.php?action=add" class="btn-add-task">
                <i class="fas fa-plus"></i>
                Nouvelle Tâche
            </a>
        </div>

        <!-- Cartes de statistiques -->
        <div class="stats-grid">
            <div class="stat-card total fade-in">
                <div class="stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_tasks']; ?></h3>
                    <p>Tâches Total</p>
                    <div class="stat-meta">
                        <i class="fas fa-calendar-day text-success"></i>
                        <span><?php echo $stats['tasks_today']; ?> ajoutées aujourd'hui</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card completed fade-in" style="animation-delay: 0.1s">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['tasks_by_status']['completed'] ?? 0; ?></h3>
                    <p>Tâches Terminées</p>
                    <div class="stat-meta">
                        <i class="fas fa-percentage text-success"></i>
                        <span><?php echo $completion_rate; ?>% taux d'achèvement</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card overdue fade-in" style="animation-delay: 0.2s">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['overdue_tasks']; ?></h3>
                    <p>Tâches en Retard</p>
                    <div class="stat-meta">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                        <span>Action requise</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card today fade-in" style="animation-delay: 0.3s">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['due_today']; ?></h3>
                    <p>À faire Aujourd'hui</p>
                    <div class="stat-meta">
                        <i class="fas fa-calendar-alt text-info"></i>
                        <span>Priorité du jour</span>
                    </div>
                </div>
            </div>
        </div>
<!-- Grille de contenu -->
        <div class="content-grid">
            <!-- Colonne gauche: Dernières tâches -->
            <div>
                <div class="dashboard-card slide-in">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Dernières tâches</h3>
                        <a href="tasks.php" class="btn btn-primary btn-sm">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_tasks)): ?>
                            <div class="empty-state">
                                <i class="fas fa-tasks"></i>
                                <h4>Aucune tâche créée</h4>
                                <p>Commencez par créer votre première tâche</p>
                                <a href="tasks.php?action=add" class="btn btn-primary">Créer une tâche</a>
                            </div>
                        <?php else: ?>
                            <ul class="task-list">
                                <?php foreach ($recent_tasks as $task): ?>
                                <li class="task-item">
                                    <div class="task-info">
                                        <div class="task-title">
                                            <i class="far fa-file-alt"></i>
                                            <span><?php echo htmlspecialchars($task['title']); ?></span>
                                        </div>
                                        <?php if ($task['description']): ?>
                                        <div class="task-description">
                                            <?php echo substr(htmlspecialchars($task['description']), 0, 100); ?>
                                            <?php if (strlen($task['description']) > 100): ?>...<?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="task-meta">
                                            <div class="task-date">
                                                <i class="far fa-calendar"></i>
                                                <span><?php echo date('d/m/Y', strtotime($task['created_at'])); ?></span>
                                            </div>
                                            <span class="task-status status-<?php echo $task['status']; ?>">
                                                <?php echo $task['status'] == 'in_progress' ? 'En cours' : ucfirst($task['status']); ?>
                                            </span>
                                            <span class="task-priority priority-<?php echo $task['priority']; ?>">
                                                <?php echo ucfirst($task['priority']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <a href="tasks.php?action=view&id=<?php echo $task['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Colonne droite: Tâches à venir -->
            <div>
                <div class="dashboard-card slide-in" style="animation-delay: 0.1s">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-alt"></i> Tâches à venir (7 jours)</h3>
                        <a href="tasks.php?filter=upcoming" class="btn btn-primary btn-sm">Voir tout</a></div>
                    <div class="card-body">
                        <?php if (empty($upcoming_tasks)): ?>
                            <div class="empty-state">
                                <i class="far fa-calendar"></i>
                                <h4>Aucune tâche à venir</h4>
                                <p>Vous n'avez pas de tâches programmées pour la semaine prochaine</p>
                            </div>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach ($upcoming_tasks as $task): 
                                    $days_diff = floor((strtotime($task['due_date']) - time()) / (60 * 60 * 24));
                                ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker <?php echo $task['priority']; ?>"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">
                                            <a href="tasks.php?action=view&id=<?php echo $task['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($task['title']); ?>
                                            </a>
                                        </div>
                                        <?php if ($task['description']): ?>
                                        <p class="small text-muted mb-2">
                                            <?php echo substr(htmlspecialchars($task['description']), 0, 80); ?>
                                            <?php if (strlen($task['description']) > 80): ?>...<?php endif; ?>
                                        </p>
                                        <?php endif; ?>
                                        <div class="timeline-date">
                                            <span>
                                                <i class="far fa-clock me-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($task['due_date'])); ?>
                                            </span>
                                            <span class="<?php echo $days_diff == 0 ? 'text-warning' : ($days_diff == 1 ? 'text-info' : 'text-muted'); ?>">
                                                <?php 
                                                if ($days_diff == 0) {
                                                    echo '<strong>Aujourd\'hui</strong>';
                                                } elseif ($days_diff == 1) {
                                                    echo 'Demain';
                                                } elseif ($days_diff > 0) {
                                                    echo "Dans $days_diff jours";
                                                }
                                                ?>
                                            </span>
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

        <!-- Graphique et Actions rapides -->
        <div class="content-grid">
            <!-- Graphique de productivité -->
            <div>
                <div class="chart-container slide-in" style="animation-delay: 0.2s">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-line"></i> Votre productivité (7 derniers jours)</h3>
                    </div><div class="chart-placeholder">
                        <div style="text-align: center;">
                            <i class="fas fa-chart-bar"></i>
                            <p style="color: #94a3b8; margin-top: 15px;">Graphique de productivité</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div>
                <div class="dashboard-card slide-in" style="animation-delay: 0.3s">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> Actions rapides</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions-grid">
                            <a href="tasks.php?action=add" class="quick-action new-task">
                                <i class="fas fa-plus-circle"></i>
                                <div class="quick-action-content">
                                    <div class="quick-action-title">Nouvelle tâche</div>
                                    <div class="quick-action-desc">Ajouter une nouvelle tâche</div>
                                </div>
                            </a>
                            
                            <a href="tasks.php?filter=today" class="quick-action today">
                                <i class="fas fa-calendar-day"></i>
                                <div class="quick-action-content">
                                    <div class="quick-action-title">Tâches du jour</div>
                                    <div class="quick-action-desc">Voir les tâches d'aujourd'hui</div>
                                </div>
                            </a>
                            
                            <a href="tasks.php?filter=overdue" class="quick-action overdue">
                                <i class="fas fa-exclamation-circle"></i>
                                <div class="quick-action-content">
                                    <div class="quick-action-title">Tâches en retard</div>
                                    <div class="quick-action-desc">Gérer les tâches en retard</div>
                                </div>
                            </a>
                            
                            <a href="notifications.php" class="quick-action notifications position-relative">
                                <i class="fas fa-bell"></i>
                                <?php if ($stats['unread_notifications'] > 0): ?>
                                <div class="notification-badge">
                                    <?php echo $stats['unread_notifications']; ?>
                                </div>
                                <?php endif; ?>
                                <div class="quick-action-content">
                                    <div class="quick-action-title">Notifications</div>
                                    <div class="quick-action-desc">
                                        <?php echo $stats['unread_notifications']; ?> non lue<?php echo $stats['unread_notifications'] > 1 ? 's' : ''; ?>
                                    </div>
                                </div>
                            </a>
                            
                            <a href="profile.php" class="quick-action profile">
                                <i class="fas fa-user-circle"></i>
                                <div class="quick-action-content">
                                    <div class="quick-action-title">Mon profil</div>
                                    <div class="quick-action-desc">Gérer mon compte</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div><!-- Statistiques détaillées -->
                <div class="stats-details slide-in" style="animation-delay: 0.4s">
                    <h4 style="color: var(--dark-color); margin-bottom: 20px; font-weight: 600;">
                        <i class="fas fa-chart-pie me-2"></i> Statistiques détaillées
                    </h4>
                    <div class="stats-grid-small">
                        <div class="stat-box">
                            <div class="stat-number pending"><?php echo $stats['tasks_by_status']['pending'] ?? 0; ?></div>
                            <div class="stat-label">En attente</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number in_progress"><?php echo $stats['tasks_by_status']['in_progress'] ?? 0; ?></div>
                            <div class="stat-label">En cours</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number completed"><?php echo $stats['tasks_by_status']['completed'] ?? 0; ?></div>
                            <div class="stat-label">Terminées</div>
                        </div>
                        <div class="stat-box">
                            <?php
                            $total_priority = ($stats['tasks_by_status']['pending'] ?? 0) + 
                                            ($stats['tasks_by_status']['in_progress'] ?? 0);
                            ?>
                            <div class="stat-number priority"><?php echo $total_priority; ?></div>
                            <div class="stat-label">À prioriser</div>
                        </div>
                    </div>
                    
                    <!-- Barre de progression -->
                    <div class="progress-section">
                        <div class="progress-label">
                            <span>Progression globale</span>
                            <span><?php echo $completion_rate; ?>%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $completion_rate; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Animation et interactions
document.addEventListener('DOMContentLoaded', function() {
    // Animation des cartes au chargement
    const cards = document.querySelectorAll('.stat-card, .dashboard-card, .chart-container, .stats-details');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.5s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });
    
    // Mettre à jour les données en temps réel
    function updateDashboardData() {
        // Simuler une mise à jour des données
        const notificationBadge = document.querySelector('.notification-badge');
        if (notificationBadge) {
            const currentCount = parseInt(notificationBadge.textContent);
            // Simuler de nouvelles notifications (exemple)
            if (Math.random() > 0.8) {
                const newCount = currentCount + 1;
                notificationBadge.textContent = newCount;
                notificationBadge.parentElement.querySelector('.quick-action-desc').textContent = 
                    newCount + ' non lue' + (newCount > 1 ? 's' : '');
                
                // Notification toast
                showNotification('Nouvelle notification', 'Vous avez une nouvelle notification');
            }}
    }
    
    // Afficher une notification toast
    function showNotification(title, message) {
        const toast = document.createElement('div');
        toast.className = 'position-fixed top-0 end-0 p-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = 
            <div class="toast show" role="alert">
                <div class="toast-header" style="background: linear-gradient(135deg, #4361ee, #3a0ca3); color: white;">
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        ;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
    // Actualiser les données toutes les 30 secondes
    setInterval(updateDashboardData, 30000);
    
    // Effet de survol pour les boutons d'actions rapides
    const quickActions = document.querySelectorAll('.quick-action');
    quickActions.forEach(action => {
        action.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px) scale(1.02)';
        });
        
        action.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0) scale(1)';
        });
    });
    
    // Effet de survol pour les cartes de statistiques
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 15px 35px rgba(0, 0, 0, 0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.boxShadow = 'var(--shadow-md)';
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>