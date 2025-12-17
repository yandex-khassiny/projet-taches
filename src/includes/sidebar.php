<?php if (isAuthenticated()): ?>
<?php
// Récupérer la page actuelle
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar-wrapper">
    <!-- Sidebar pour desktop -->
    <aside class="sidebar d-none d-lg-block">
        <div class="sidebar-header">
            <h3 class="sidebar-logo">
                <i class="fas fa-tasks me-2"></i>
                TaskManager
            </h3>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="user-details">
                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                    <small class="user-role"><?php echo isAdmin() ? 'Administrateur' : 'Utilisateur'; ?></small>
                </div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <?php if (isAdmin()): ?>
                    <!-- Menu Admin -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/src/admin/dashboard.php">
                            <i class="fas fa-chart-line me-2"></i>
                            Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/src/admin/users.php">
                            <i class="fas fa-users me-2"></i>
                            Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'statistics.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/src/admin/statistics.php">
                            <i class="fas fa-chart-pie me-2"></i>
                            Statistiques
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Menu Utilisateur -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/src/user/dashboard.php">
                            <i class="fas fa-home me-2"></i>
                            Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'tasks.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/src/user/tasks.php">
                            <i class="fas fa-tasks me-2"></i>
                            Mes Tâches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/src/user/notifications.php">
                            <i class="fas fa-bell me-2"></i>
                            Notifications
                            <span class="badge bg-danger notification-count" id="notificationCount">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/src/user/profile.php"><i class="fas fa-user-circle me-2"></i>
                            Profil
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Déconnexion -->
                <li class="nav-item logout-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/src/auth/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        Déconnexion
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Menu mobile (hamburger) -->
    <div class="mobile-header d-lg-none">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <h3 class="mobile-logo">
            <i class="fas fa-tasks me-2"></i>
            TaskManager
        </h3>
        <div class="mobile-user">
            <span class="badge bg-primary"><?php echo isAdmin() ? 'admin' : 'user'; ?></span>
        </div>
    </div>

    <!-- Sidebar pour mobile -->
    <aside class="mobile-sidebar d-lg-none">
        <div class="mobile-sidebar-header">
            <button class="close-sidebar">
                <i class="fas fa-times"></i>
            </button>
            <div class="mobile-user-info">
                <div class="mobile-user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                    <small><?php echo isAdmin() ? 'Administrateur' : 'Utilisateur'; ?></small>
                </div>
            </div>
        </div>
        
        <nav class="mobile-sidebar-nav">
            <ul>
                <?php if (isAdmin()): ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/src/admin/dashboard.php" 
                           class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line"></i> Tableau de bord
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/src/admin/users.php" 
                           class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> Utilisateurs
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/src/admin/statistics.php" 
                           class="<?php echo $current_page == 'statistics.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-pie"></i> Statistiques
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/src/user/dashboard.php" 
                           class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Tableau de bord
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/src/user/tasks.php" 
                           class="<?php echo $current_page == 'tasks.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tasks"></i> Mes Tâches
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/src/user/notifications.php" 
                           class="<?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                            <i class="fas fa-bell"></i> Notifications</a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/src/user/profile.php" 
                           class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-circle"></i> Profil
                        </a>
                    </li>
                <?php endif; ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/src/auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
</div>

<div class="main-content <?php echo isAuthenticated() ? '' : 'no-sidebar'; ?>">
<?php endif; ?>