<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$cat_id = (int)($_GET['cat'] ?? 0);

if ($cat_id == 0) {
    die("Invalid category.");
}

// Fetch category details to check type
$cat_stmt = $conn->prepare("SELECT name, type FROM categories WHERE id = ?");
$cat_stmt->bind_param("i", $cat_id);
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
$cat = $cat_result->fetch_assoc();
$cat_stmt->close();

if (!$cat || $cat['type'] !== 'coding') {
    die("<div class='text-center p-10 text-red-600 text-2xl'>This category does not support pattern coding.<br><a href='user_dashboard.php' class='text-blue-600 underline'>← Back to Dashboard</a></div>");
}

// Count available pattern questions from the correct table: pattern_questions
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM pattern_questions WHERE category_id = ?");
$count_stmt->bind_param("i", $cat_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count = $count_result->fetch_assoc()['total'];
$count_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Language - <?php echo htmlspecialchars($cat['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #c9e9c1, #b8e0a6); 
        }
        .lang-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-3xl shadow-2xl p-10 max-w-4xl w-full text-center">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">
            ⭐ Pattern Coding Practice
        </h1>
        <p class="text-2xl text-gray-700 mb-2">
            Category: <strong class="text-green-600"><?php echo htmlspecialchars($cat['name']); ?></strong>
        </p>
        <p class="text-xl text-gray-600 mb-10">
            <?php echo $count; ?> pattern challenge<?php echo $count == 1 ? '' : 's'; ?> available
        </p>

        <?php if ($count == 0): ?>
            <div class="text-red-600 text-lg mb-8 font-semibold">
                No pattern questions have been added to this category yet.
            </div>
            <a href="category.php?id=<?php echo $cat_id; ?>" 
               class="inline-block px-8 py-4 bg-gray-600 text-white text-lg font-semibold rounded-xl hover:bg-gray-700 transition">
                ← Back to Category
            </a>
        <?php else: ?>
            <h2 class="text-3xl font-semibold text-gray-800 mb-10">
                Choose Your Programming Language
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8 mb-12">
                <?php
                $languages = [
                    'python'     => ['name' => 'Python',     'icon' => '🐍', 'color' => 'from-yellow-400 to-orange-500'],
                    'java'       => ['name' => 'Java',       'icon' => '☕', 'color' => 'from-red-500 to-orange-600'],
                    'cpp'        => ['name' => 'C++',        'icon' => '⚡', 'color' => 'from-blue-500 to-cyan-500'],
                    'c'          => ['name' => 'C',          'icon' => '🔧', 'color' => 'from-gray-600 to-gray-800'],
                    'javascript' => ['name' => 'JavaScript', 'icon' => '🟨', 'color' => 'from-yellow-300 to-yellow-500'],
                ];

                foreach ($languages as $code => $info):
                ?>
                    <a href="pattern_session_start.php?cat=<?php echo $cat_id; ?>&lang=<?php echo $code; ?>"
                       class="lang-card block p-8 bg-gradient-to-br <?php echo $info['color']; ?> text-white rounded-2xl shadow-xl transition-all duration-300">
                        <div class="text-6xl mb-4"><?php echo $info['icon']; ?></div>
                        <div class="text-2xl font-bold"><?php echo $info['name']; ?></div>
                    </a>
                <?php endforeach; ?>
            </div>

            <a href="category.php?id=<?php echo $cat_id; ?>" 
               class="text-gray-600 hover:text-gray-800 underline text-lg">
                ← Back to Category
            </a>
        <?php endif; ?>
    </div>
</body>
</html>