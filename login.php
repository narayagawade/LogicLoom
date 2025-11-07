<?php
header('Content-Type: application/json');
require_once 'config/database.php';
require_once 'config/security.php';
require_once 'config/session.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get and sanitize input
    $email = Security::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate input
    if (empty($email) || empty($password)) {
        throw new Exception('Email and password are required');
    }
    
    if (!Security::validateEmail($email)) {
        throw new Exception('Invalid email format');
    }
    
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Check if it's admin login
    if ($email === 'admin@logicloom.com') {
        $query = "SELECT * FROM admins WHERE email = :email";
        $is_admin = true;
    } else {
        $query = "SELECT * FROM users WHERE email = :email AND is_active = TRUE";
        $is_admin = false;
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password
        if (Security::verifyPassword($password, $user['password_hash'])) {
            // Create session
            $sessionManager = new SessionManager($db);
            $sessionManager->createSession($user['id'], $is_admin);
            
            $response['success'] = true;
            $response['message'] = 'Login successful!';
            $response['redirect'] = $is_admin ? 'admin_dashboard.php' : 'dashboard.php';
            $response['is_admin'] = $is_admin;
        } else {
            throw new Exception('Invalid email or password');
        }
    } else {
        throw new Exception('Invalid email or password');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $response['message'] = 'Database error occurred. Please try again.';
}

echo json_encode($response);
?>