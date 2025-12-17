<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskManager - <?php echo $page_title ?? 'Gestion de Tâches'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo BASE_URL; ?>/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- CSS Personnalisé -->
    <link href="<?php echo BASE_URL; ?>/src/assets/css/style.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/src/assets/css/auth.css" rel="stylesheet">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
    /* CSS de base pour éviter les problèmes d'affichage */
    body {
        margin: 0;
        padding: 0;
        font-family: 'Poppins', sans-serif;
        background: #f8f9fa;
    }
    </style>
</head>
<body>