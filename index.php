<?php
// Démarrer la session pour vérifier la connexion
session_start();

// Rediriger vers le tableau de bord si déjà connecté
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: src/admin/dashboard.php');
    } else {
        header('Location: src/user/dashboard.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskManager - Gestion de tâches</title>
    
    <!-- Bootstrap 5 -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        /* Variables CSS */
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --radius: 12px;
        }
        
        /* Reset et styles généraux */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            line-height: 1.3;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header et navigation */
        .navbar {
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-link {
            font-weight: 500;
            color: var(--dark-color) !important;
            transition: all 0.3s;
            padding: 8px 16px !important;
            border-radius: 8px;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color) !important;
            background: rgba(67, 97, 238, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
        }
        
        /* Hero Section */
        .hero-section {
            padding: 80px 0;
            background: white;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 40%;
            height: 100%;background: linear-gradient(135deg, rgba(67, 97, 238, 0.05), rgba(58, 12, 163, 0.05));
            clip-path: polygon(100% 0, 100% 100%, 0 100%, 25% 0);
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 3.5rem;
            margin-bottom: 20px;
            color: var(--dark-color);
        }
        
        .hero-title span {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 30px;
            max-width: 600px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .hero-stats {
            display: flex;
            gap: 40px;
            margin-top: 40px;
        }
        
        .stat-item h3 {
            font-size: 2.2rem;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-item p {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Features Section */
        .features-section {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .section-title p {
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .feature-card {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            height: 100%;
            text-align: center;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .feature-icon i {
            font-size: 28px;
            color: white;
        }
        
        .feature-card h4 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .feature-card p {
            color: #666;
            font-size: 0.95rem;
        }
        
        /* Preview Section */
        .preview-section {
            padding: 80px 0;
            background: white;
        }
        
        .preview-card {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .preview-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 20px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .preview-title {
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .preview-body {padding: 30px;
        }
        
        .task-preview {
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            background: #f8f9fa;
            border-radius: 0 8px 8px 0;
            margin-bottom: 15px;
        }
        
        .task-preview.completed {
            border-left-color: var(--success-color);
            opacity: 0.8;
        }
        
        .task-preview.urgent {
            border-left-color: var(--danger-color);
        }
        
        /* CTA Section */
        .cta-section {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-align: center;
        }
        
        .cta-title {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .cta-subtitle {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .btn-light {
            background: white;
            color: var(--primary-color);
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        /* Footer */
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 60px 0 30px;
        }
        
        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .footer-links h5 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: white;
        }
        
        .footer-links ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.8rem;
            }
            
            .hero-section::before {
                width: 50%;
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .hero-stats {
                flex-wrap: wrap;
                gap: 20px;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .cta-title {
                font-size: 2rem;
            }
            
            .hero-section::before {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .hero-buttons {
                flex-direction: column;
            }
            
            .btn-primary, .btn-light {
                width: 100%;
                text-align: center;}
            
            .navbar-nav {
                text-align: center;
                padding-top: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-tasks"></i>
                TaskManager
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fonctionnalités</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#preview">Aperçu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a href="src/auth/login.php" class="btn btn-primary">Se connecter</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="hero-title">
                            Gérez vos <span>tâches</span> efficacement
                        </h1>
                        <p class="hero-subtitle">
                            TaskManager est l'application ultime pour organiser votre travail, 
                            suivre vos progrès et atteindre vos objectifs. Simple, puissant et entièrement gratuit.
                        </p>
                        <div class="hero-buttons">
                            <a href="src/auth/register.php" class="btn btn-primary">
                                <i class="fas fa-rocket me-2"></i>Commencer gratuitement
                            </a>
                            <a href="#features" class="btn btn-outline-primary">
                                <i class="fas fa-play-circle me-2"></i>Découvrir les fonctionnalités
                            </a>
                        </div>
                        <div class="hero-stats">
                            <div class="stat-item">
                                <h3>10+</h3>
                                <p>Utilisateurs satisfaits</p>
                            </div>
                            <div class="stat-item">
                                <h3>50+</h3>
                                <p>Tâches accomplies</p>
                            </div>
                            <div class="stat-item">
                                <h3>99%</h3>
                                <p>Satisfaction client</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="preview-card">
                        <div class="preview-header">
                            <div class="preview-title">
                                <i class="fas fa-tasks"></i>
                                <span>Tableau de bord - Aperçu</span>
                            </div>
                            <span class="badge bg-light text-primary">En ligne</span></div>
                        <div class="preview-body">
                            <div class="task-preview">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Réunion d'équipe</h6>
                                        <small class="text-muted">Préparer l'ordre du jour</small>
                                    </div>
                                    <span class="badge bg-warning">Aujourd'hui</span>
                                </div>
                            </div>
                            <div class="task-preview completed">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><s>Rapport mensuel</s></h6>
                                        <small class="text-muted">Analyse des performances</small>
                                    </div>
                                    <span class="badge bg-success">Terminé</span>
                                </div>
                            </div>
                            <div class="task-preview urgent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Déployer l'application</h6>
                                        <small class="text-muted">Mettre en production</small>
                                    </div>
                                    <span class="badge bg-danger">Urgent</span>
                                </div>
                            </div>
                            <div class="task-preview">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Répondre aux emails</h6>
                                        <small class="text-muted">Clients importants</small>
                                    </div>
                                    <span class="badge bg-info">Demain</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Fonctionnalités puissantes</h2>
                <p>Tout ce dont vous avez besoin pour rester organisé et productif</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h4>Gestion de tâches</h4>
                        <p>Créez, organisez et suivez vos tâches avec des priorités, dates d'échéance et étiquettes.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h4>Notifications intelligentes</h4>
                        <p>Recevez des rappels par email et notifications push pour ne jamais manquer une échéance.</p>
                    </div>
                </div><div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4>Statistiques détaillées</h4>
                        <p>Visualisez votre productivité avec des graphiques interactifs et rapports personnalisés.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Multi-utilisateurs</h4>
                        <p>Gestion des rôles administrateur/utilisateur avec des permissions adaptées à chaque profil.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4>Mobile First</h4>
                        <p>Interface responsive optimisée pour tous les appareils : mobile, tablette et desktop.</p>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Sécurité avancée</h4>
                        <p>Vos données sont protégées avec chiffrement, authentification sécurisée et sauvegardes.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section" id="contact">
        <div class="container">
            <h2 class="cta-title">Prêt à booster votre productivité ?</h2>
            <p class="cta-subtitle">
                Rejoignez des milliers d'utilisateurs qui ont déjà transformé leur façon de travailler. 
                Inscrivez-vous gratuitement et commencez dès maintenant !
            </p>
            <div class="mt-4">
                <a href="src/auth/register.php" class="btn btn-light btn-lg">
                    <i class="fas fa-rocket me-2"></i>Créer mon compte gratuit
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-logo">
                        <i class="fas fa-tasks"></i>
                        TaskManager
                    </div>
                    <p style="color: rgba(255,255,255,0.7);">
                        L'application de gestion de tâches la plus simple et efficace pour organiser votre travail et atteindre vos objectifs.
                    </p>
                    <div class="social-icons mt-4">
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-github fa-lg"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <div class="footer-links">
                        <h5>Produit</h5><ul>
                            <li><a href="#features">Fonctionnalités</a></li>
                            <li><a href="#preview">Aperçu</a></li>
                            <li><a href="#">Tarifs</a></li>
                            <li><a href="#">Documentation</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <div class="footer-links">
                        <h5>Entreprise</h5>
                        <ul>
                            <li><a href="#">À propos</a></li>
                            <li><a href="#">Blog</a></li>
                            <li><a href="#">Carrières</a></li>
                            <li><a href="#">Contact</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-4 mb-4">
                    <div class="footer-links">
                        <h5>Contact</h5>
                        <ul style="color: rgba(255,255,255,0.7);">
                            <li><i class="fas fa-envelope me-2"></i> yandexkhassiny.com</li>
                            <li><i class="fas fa-phone me-2"></i> +237 621913991</li>
                            <li><i class="fas fa-map-marker-alt me-2"></i> dang Ndere</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; 2024 TaskManager. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Smooth Scroll -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
            } else {
                navbar.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.08)';
            }
        });
        
        // Animation on scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.feature-card, .preview-card');
            
            elements.forEach(element => {
                const position = element.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.2;
                
                if (position < screenPosition) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }
            });
        }
        
        // Set initial state for animation
        document.querySelectorAll('.feature-card, .preview-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
        });
        
        window.addEventListener('scroll', animateOnScroll);
        window.addEventListener('load', animateOnScroll);
    </script>
</body>
</html>