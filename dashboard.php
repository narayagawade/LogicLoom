<?php
require_once 'config/database.php';
require_once 'config/session.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed");
}

$sessionManager = new SessionManager($db);
$user = $sessionManager->validateSession();

if (!$user) {
    header('Location: login.html');
    exit();
}

$is_admin = $sessionManager->isAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogicLoom Dashboard</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #ECFAE5 0%, #DDF6D2 35%, #CAE8BD 70%, #B0DB9C 100%);
            min-height: 100vh;
        }
        .dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        .welcome {
            font-size: 24px;
            color: #2d3748;
            margin: 0;
        }
        .user-info {
            color: #718096;
        }
        .logout-btn {
            background: #B0DB9C;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .feature-icon {
            font-size: 48px;
            color: #B0DB9C;
            margin-bottom: 15px;
        }
        .admin-badge {
            background: #ff6b6b;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <div>
                <h1 class="welcome">Welcome to LogicLoom, <?php echo htmlspecialchars($user['username']); ?>!</h1>
                <p class="user-info">Email: <?php echo htmlspecialchars($user['email']); ?>
                <?php if ($is_admin): ?>
                    <span class="admin-badge">ADMIN</span>
                <?php endif; ?>
                </p>
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🧩</div>
                <h3>Pattern Solving</h3>
                <p>Master coding patterns through interactive challenges</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📝</div>
                <h3>MCQ Tests</h3>
                <p>Test your knowledge with multiple choice questions</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3>AI Interviews</h3>
                <p>Practice with AI-powered mock interviews</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Progress Tracking</h3>
                <p>Monitor your learning journey with detailed analytics</p>
            </div>
        </div>
    </div>
</body>
</html>