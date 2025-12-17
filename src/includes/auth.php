<?php
require_once 'config.php';

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register($username, $email, $password, $confirm_password, $role = 'user') {
        // Validation des données
        $errors = [];
        
        if (empty($username) || strlen($username) < 3) {
            $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }
        
        if (strlen($password) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        }
        
        if ($password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }
        
        // Vérifier si l'utilisateur existe déjà
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $errors[] = "Le nom d'utilisateur ou l'adresse email est déjà utilisé.";
        }
        
        // S'il y a des erreurs, les retourner
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Hasher le mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insérer l'utilisateur dans la base de données
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        
        try {
            $stmt->execute([$username, $email, $hashed_password, $role]);
            $user_id = $this->pdo->lastInsertId();
            
            // Connecter automatiquement l'utilisateur
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
            
            return ['success' => true, 'user_id' => $user_id];
        } catch (PDOException $e) {
            return ['success' => false, 'errors' => ["Une erreur est survenue lors de l'inscription."]];
        }
    }
    
    /**
     * Connexion d'un utilisateur
     */
    public function login($username, $password, $remember = false) {
        $errors = [];
        
        if (empty($username) || empty($password)) {
            $errors[] = "Veuillez remplir tous les champs.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Rechercher l'utilisateur par username ou email
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'errors' => ["Nom d'utilisateur ou mot de passe incorrect."]];
        }
        
        // Vérifier le mot de passe
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'errors' => ["Nom d'utilisateur ou mot de passe incorrect."]];
        }
        
        // Mettre à jour la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Gérer "Se souvenir de moi"
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $stmt = $this->pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");$stmt->execute([$user['id'], $token, $expires]);
            
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        }
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Déconnexion
     */
    public function logout() {
        // Supprimer le token "Se souvenir de moi" si existant
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $stmt = $this->pdo->prepare("DELETE FROM remember_tokens WHERE token = ?");
            $stmt->execute([$token]);
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Détruire la session
        session_unset();
        session_destroy();
    }
    
    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function check() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Récupérer l'utilisateur courant
     */
    public function user() {
        if (!$this->check()) {
            return null;
        }
        
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    
    /**
     * Changer le mot de passe
     */
    public function changePassword($current_password, $new_password, $confirm_password) {
        $user = $this->user();
        
        if (!$user) {
            return ['success' => false, 'errors' => ["Utilisateur non connecté."]];
        }
        
        $errors = [];
        
        // Vérifier l'ancien mot de passe
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Le mot de passe actuel est incorrect.";
        }
        
        // Vérifier la longueur du nouveau mot de passe
        if (strlen($new_password) < 6) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
        }
        
        // Vérifier la confirmation
        if ($new_password !== $confirm_password) {
            $errors[] = "Les nouveaux mots de passe ne correspondent pas.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Mettre à jour le mot de passe
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        try {
            $stmt->execute([$hashed_password, $user['id']]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'errors' => ["Une erreur est survenue lors du changement de mot de passe."]];
        }
    }
    
    /**
     * Mettre à jour le profil
     */
    public function updateProfile($username, $email) {
        $user = $this->user();
        
        if (!$user) {
            return ['success' => false, 'errors' => ["Utilisateur non connecté."]];
        }
        
        $errors = [];
        
        // Validation du nom d'utilisateur
        if (empty($username) || strlen($username) < 3) {
            $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
        }
        
        // Validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide.";
        }
        
        // Vérifier si le nouveau nom d'utilisateur ou email existe déjà
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user['id']]);
        
        if ($stmt->fetch()) {
            $errors[] = "Le nom d'utilisateur ou l'adresse email est déjà utilisé.";}
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Mettre à jour le profil
        $stmt = $this->pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        
        try {
            $stmt->execute([$username, $email, $user['id']]);
            
            // Mettre à jour la session
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'errors' => ["Une erreur est survenue lors de la mise à jour du profil."]];
        }
    }
}

// Créer une instance de la classe Auth
$auth = new Auth($pdo);
?>