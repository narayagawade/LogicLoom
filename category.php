<?php
session_start();
include 'db.php';

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    // Ensure redirect goes to a secure login page, using .php extension as often standard
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get Category ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect back to dashboard if ID is missing or invalid
    header("Location: user_dashboard.php");
    exit;
}
$cat_id = (int)$_GET['id'];

// Fetch category info using prepared statements for safety
$catStmt = $conn->prepare("SELECT id, name FROM categories WHERE id = ?");
$catStmt->bind_param("i", $cat_id);
$catStmt->execute();
$catResult = $catStmt->get_result();
$cat = $catResult->fetch_assoc();
$catStmt->close();

if (!$cat) {
    // If category not found, show error or redirect
    header("Location: user_dashboard.php");
    exit;
}

// Fetch total number of questions for this category
$qCountStmt = $conn->prepare("SELECT COUNT(*) AS q_count FROM questions WHERE category_id = ?");
$qCountStmt->bind_param("i", $cat_id);
$qCountStmt->execute();
$qCountResult = $qCountStmt->get_result();
$qCount = $qCountResult->fetch_assoc()['q_count'];
$qCountStmt->close();

// Default test size
$test_size = min(20, $qCount); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?php echo htmlspecialchars($cat['name']); ?> Practice | LogicLoom</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            /* Match the dashboard gradient */
            background: linear-gradient(135deg, #f0fff4 0%, #a7e29e 100%);
        }
    </style>
</head>

<body class="min-h-screen p-6 sm:p-10">

    <div class="max-w-xl mx-auto py-10">

        <a href="user_dashboard.php" class="flex items-center gap-2 text-gray-600 hover:text-green-700 transition mb-8">
            <i class="ph ph-arrow-left text-lg"></i>
            <span class="font-medium">Back to Dashboard</span>
        </a>

        <div class="bg-white/95 backdrop-blur-sm p-8 sm:p-10 rounded-3xl shadow-2xl border border-gray-100 mb-8">
            
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-green-500 to-teal-600 text-white flex items-center justify-center text-3xl font-bold shadow-md">
                    <?php echo strtoupper(substr($cat['name'], 0, 1)); ?>
                </div>
                <div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight mb-1">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </h1>
                    <p class="text-sm font-medium text-green-700 bg-green-100 inline-block px-3 py-1 rounded-full">
                        <?php echo $qCount; ?> Questions Available
                    </p>
                </div>
            </div>

            <p class="text-gray-600 mt-6 border-t border-gray-100 pt-6">
                Welcome to the practice area for **<?php echo htmlspecialchars($cat['name']); ?>**. Select an option below to start a new practice session or review your past attempts.
            </p>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <a href="start_test.php?cat=<?php echo $cat['id']; ?>"
                class="block group bg-white rounded-2xl p-6 border-2 border-transparent hover:border-green-400 shadow-md hover:shadow-xl transition transform hover:-translate-y-1 duration-300">
                
                <div class="flex items-center justify-between">
                    <div class="w-14 h-14 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-2xl mb-3 group-hover:bg-green-600 group-hover:text-white transition-colors">
                        <i class="ph ph-pencil-line"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-500"><?php echo $test_size; ?> Qs per test</span>
                </div>

                <h3 class="text-xl font-bold text-gray-900 mb-2 mt-2">Start Practice</h3>
                <p class="text-sm text-gray-500">
                    Challenge yourself with a new set of questions. Perfect for quick revision.
                </p>
                <button class="mt-4 text-green-600 group-hover:text-green-800 font-semibold flex items-center gap-1 transition-colors">
                    Begin Now <i class="ph ph-arrow-right-circle text-xl"></i>
                </button>
            </a>

            <a href="past_history.php?cat=<?php echo $cat['id']; ?>"
                class="block group bg-white rounded-2xl p-6 border-2 border-transparent hover:border-indigo-400 shadow-md hover:shadow-xl transition transform hover:-translate-y-1 duration-300">
                
                <div class="w-14 h-14 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl mb-3 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    <i class="ph ph-book-open-text"></i>
                </div>

                <h3 class="text-xl font-bold text-gray-900 mb-2 mt-2">View Past History</h3>
                <p class="text-sm text-gray-500">
                    See your scores, review answers, and track your progress over time.
                </p>
                <button class="mt-4 text-indigo-600 group-hover:text-indigo-800 font-semibold flex items-center gap-1 transition-colors">
                    Review Attempts <i class="ph ph-arrow-right-circle text-xl"></i>
                </button>
            </a>

        </div>
    </div>

</body>
</html>