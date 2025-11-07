<?php
class SessionManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        
        // Start session securely
        if (session_status() == PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }
    }
    
    // Create session for user
    public function createSession($user_id, $is_admin = false) {
        $session_token = Security::generateToken();
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $query = "INSERT INTO user_sessions (user_id, session_token, expires_at) 
                  VALUES (:user_id, :session_token, :expires_at)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":session_token", $session_token);
        $stmt->bindParam(":expires_at", $expires_at);
        
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['session_token'] = $session_token;
            $_SESSION['is_admin'] = $is_admin;
            $_SESSION['logged_in'] = true;
            
            // Set secure cookie
            Security::setSecureCookie('remember_token', $session_token, time() + (30 * 24 * 60 * 60));
            
            return true;
        }
        return false;
    }
    
    // Validate session
    public function validateSession() {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            // Check if session exists in database and is not expired
            $query = "SELECT us.*, u.username, u.email 
                      FROM user_sessions us 
                      JOIN users u ON us.user_id = u.id 
                      WHERE us.session_token = :session_token 
                      AND us.expires_at > NOW() 
                      AND u.is_active = TRUE";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":session_token", $_SESSION['session_token']);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        
        // Check remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            return $this->validateRememberToken($_COOKIE['remember_token']);
        }
        
        $this->destroySession();
        return false;
    }
    
    // Validate remember token
    private function validateRememberToken($token) {
        $query = "SELECT us.*, u.username, u.email 
                  FROM user_sessions us 
                  JOIN users u ON us.user_id = u.id 
                  WHERE us.session_token = :session_token 
                  AND us.expires_at > NOW() 
                  AND u.is_active = TRUE";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":session_token", $token);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['session_token'] = $user['session_token'];
            $_SESSION['logged_in'] = true;
            $_SESSION['is_admin'] = false;
            
            return $user;
        }
        
        return false;
    }
    
    // Destroy session
    public function destroySession() {
        if (isset($_SESSION['session_token'])) {
            // Remove from database
            $query = "DELETE FROM user_sessions WHERE session_token = :session_token";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":session_token", $_SESSION['session_token']);
            $stmt->execute();
        }
        
        // Clear session data
        $_SESSION = array();
        
        // Clear cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
    
    // Check if user is admin
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
}
?>