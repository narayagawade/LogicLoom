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
    $username = Security::sanitizeInput($_POST['username'] ?? '');
    $email = Security::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $mobile = Security::sanitizeInput($_POST['mobile'] ?? '');
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($mobile)) {
        throw new Exception('All fields are required');
    }
    
    if (!Security::validateEmail($email)) {
        throw new Exception('Invalid email format');
    }
    
    if (!Security::validateMobile($mobile)) {
        throw new Exception('Invalid mobile number');
    }
    
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }
    
    // Check if terms are accepted
    if (!isset($_POST['agreeTerms']) || $_POST['agreeTerms'] !== 'on') {
        throw new Exception('You must agree to the terms and conditions');
    }
    
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Check if user already exists
    $query = "SELECT id FROM users WHERE email = :email OR username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        throw new Exception('User with this email or username already exists');
    }
    
    // Hash password
    $password_hash = Security::hashPassword($password);
    
    // Insert new user
    $query = "INSERT INTO users (username, email, password_hash, mobile) 
              VALUES (:username, :email, :password_hash, :mobile)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password_hash", $password_hash);
    $stmt->bindParam(":mobile", $mobile);
    
    if ($stmt->execute()) {
        $user_id = $db->lastInsertId();
        
        // Create session
        $sessionManager = new SessionManager($db);
        $sessionManager->createSession($user_id);
        
        $response['success'] = true;
        $response['message'] = 'Registration successful!';
        $response['redirect'] = 'dashboard.php';
    } else {
        throw new Exception('Registration failed. Please try again.');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $response['message'] = 'Database error occurred. Please try again.';
}

echo json_encode($response);
?>