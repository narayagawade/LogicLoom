<?php
require_once 'config/database.php';
require_once 'config/session.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    $sessionManager = new SessionManager($db);
    $sessionManager->destroySession();
}

header('Location: login.html');
exit();
?>