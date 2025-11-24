<?php
session_start();

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin") {
    header("Location: login.php");
    exit;
}

$username = isset($_SESSION['username']) ? $_SESSION['username'] : "Admin";

// 2. Database Connection
if (file_exists("db.php")) {
    include "db.php";
} else {
    $conn = new mysqli("localhost", "root", "", "logicloom");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// 3. Fetch Categories
$categories = [];
if ($conn) {
    $cat_sql = "SELECT * FROM categories";
    $result = $conn->query($cat_sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
}

// 4. Handle Form Submission
$status = ""; 
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $conn) {
    $category_id = $_POST['category'];
    $question = $_POST['question'];
    $expected_output = $_POST['expected_output'];
    $explanation = $_POST['explanation'];

    // Using Prepared Statements for Security
    $sql = "INSERT INTO pattern_questions (category_id, question, expected_output, explanation) VALUES (?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isss", $category_id, $question, $expected_output, $explanation);
        
        if ($stmt->execute()) {
            $status = "success";
            $message = "Pattern question added successfully!";
        } else {
            $status = "error";
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $status = "error";
        $message = "Database Prepare Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Pattern Question - LogicLoom</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['Fira Code', 'monospace'],
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Glassmorphism */
        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(203, 213, 225, 0.8);
        }
        .dark .glass-panel {
            background: rgba(30, 41, 59, 0.8);
            border-bottom: 1px solid rgba(51, 65, 85, 0.8);
        }
        
        /* Scrollbar */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Transitions */
        * { transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease; }

        /* Toast Animations */
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        .toast-enter { animation: slideIn 0.3s ease-out forwards; }
        .toast-exit { animation: fadeOut 0.3s ease-out forwards; }
    </style>
</head>

<body class="bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-100 relative overflow-x-hidden transition-colors duration-300">

    <!-- Theme Check -->
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.getElementById('html-root').classList.add('dark');
        } else {
            document.getElementById('html-root').classList.remove('dark');
        }
    </script>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2"></div>

    <!-- Mobile Overlay -->
    <div id="mobile-backdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-black/20 z-20 hidden lg:hidden backdrop-blur-sm transition-opacity"></div>

    <div class="flex h-screen overflow-hidden">

        <!-- ========= Sidebar ========= -->
        <aside id="sidebar" class="absolute lg:relative z-30 w-64 h-full bg-emerald-900 dark:bg-slate-950 text-white transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col shadow-2xl border-r border-transparent dark:border-slate-800">
            <!-- Logo -->
            <div class="p-6 flex items-center gap-3 border-b border-emerald-800/50 dark:border-slate-800">
                <div class="w-8 h-8 bg-emerald-400 rounded-lg flex items-center justify-center shadow-lg shadow-emerald-500/20">
                    <svg class="w-5 h-5 text-emerald-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h1 class="text-xl font-bold tracking-wide">LogicLoom</h1>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1 no-scrollbar">
                <p class="px-3 text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-2">Main</p>
                
                <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-emerald-100 hover:bg-emerald-800/30 dark:hover:bg-slate-800 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>Dashboard</span>
                </a>

                <a href="add_card.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-emerald-100 hover:bg-emerald-800/30 dark:hover:bg-slate-800 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span>Categories</span>
                </a>

                <!-- "Questions" stays active as this is a type of question -->
                <a href="manage_questions.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-emerald-800/50 dark:bg-slate-800 text-white shadow-sm border border-emerald-700/50 dark:border-slate-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-medium">Questions</span>
                </a>

                <p class="px-3 text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-2 mt-6">Support</p>

                <a href="Report_issue.pdf" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-emerald-100 hover:bg-emerald-800/30 dark:hover:bg-slate-800 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>Report Issue</span>
                </a>

                <a href="logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-emerald-100 hover:bg-red-500/20 hover:text-red-200 transition-colors mt-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span>Logout</span>
                </a>
            </nav>

            <div class="p-4 border-t border-emerald-800/50 dark:border-slate-800 bg-emerald-950/30 dark:bg-slate-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-700 dark:bg-slate-700 flex items-center justify-center text-white font-bold border-2 border-emerald-600 dark:border-slate-600">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($username); ?></p>
                        <p class="text-xs text-emerald-400">Administrator</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ========= Main Content ========= -->
        <main class="flex-1 flex flex-col h-screen overflow-y-auto">
            
            <!-- Header -->
            <header class="glass-panel sticky top-0 z-10 px-6 py-4 flex justify-between items-center shadow-sm transition-colors duration-300">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h2 class="text-xl font-semibold text-slate-800 dark:text-white">Pattern Logic Manager</h2>
                </div>

                <div class="flex items-center gap-6">
                    <button onclick="toggleTheme()" class="p-2 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-yellow-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-all shadow-inner">
                        <svg id="sun-icon" class="w-6 h-6 block dark:hidden transition-transform duration-500 rotate-0 dark:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg id="moon-icon" class="w-6 h-6 hidden dark:block transition-transform duration-500 -rotate-90 dark:rotate-0" fill="none" stroke="currentColor" viewBox="0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                    <span class="text-sm text-slate-500 dark:text-slate-400 hidden sm:block"><?php echo date("l, F jS Y"); ?></span>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-6 lg:p-10 max-w-4xl mx-auto w-full">
                
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg dark:shadow-sm border border-slate-200 dark:border-slate-700 p-8">
                    
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-xl text-orange-600 dark:text-orange-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-800 dark:text-white">Add Pattern Question</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Design pattern matching and logic problems.</p>
                        </div>
                    </div>

                    <form method="POST" class="space-y-6">

                        <!-- Category Select -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-slate-600 dark:text-slate-400">Select Category</label>
                            <select name="category" required 
                                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent outline-none transition-all shadow-sm">
                                <option value="">Choose a category...</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Question Text -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-slate-600 dark:text-slate-400">Question / Problem Statement</label>
                            <textarea name="question" required placeholder="Describe the pattern or logic problem here..."
                                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent outline-none transition-all shadow-sm min-h-[100px]"></textarea>
                        </div>

                        <!-- Expected Output (Code Editor Style) -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-slate-600 dark:text-slate-400">Correct Pattern Output</label>
                            <div class="relative">
                                <textarea name="expected_output" required placeholder="   *
  ***
 *****"
                                        class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-green-400 font-mono focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none transition-all shadow-sm min-h-[150px]"></textarea>
                                <span class="absolute top-2 right-4 text-xs text-slate-500">Monospace</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-2">Enter the exact output expected from the user's code.</p>
                        </div>

                        <!-- Explanation -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-slate-600 dark:text-slate-400">Explanation (Optional)</label>
                            <textarea name="explanation" placeholder="Explain the logic behind this pattern..."
                                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent outline-none transition-all shadow-sm min-h-[80px]"></textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-4 pt-4">
                            <button type="submit" class="flex-1 py-3 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-600/20 transition-transform hover:-translate-y-0.5">
                                Add Pattern
                            </button>
                            <button type="button" onclick="window.location.href='admin_dashboard.php'" class="flex-1 py-3 px-6 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-medium rounded-xl transition-colors">
                                Cancel
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Logic -->
    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('mobile-backdrop');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }

        // Theme Toggle
        const htmlRoot = document.getElementById('html-root');
        function toggleTheme() {
            if (htmlRoot.classList.contains('dark')) {
                htmlRoot.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                htmlRoot.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }

        // Toast Notification
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-emerald-500' : 'bg-red-500';
            const icon = type === 'success' 
                ? '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
                : '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';

            toast.className = `toast-enter flex items-center gap-3 px-4 py-3 rounded-xl text-white shadow-lg ${bgColor} min-w-[300px]`;
            toast.innerHTML = `<div>${icon}</div><div class="font-medium">${message}</div>`;
            
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('toast-enter');
                toast.classList.add('toast-exit');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Check PHP status on load
        <?php if ($status === 'success'): ?>
            showToast("<?php echo $message; ?>", "success");
        <?php elseif ($status === 'error'): ?>
            showToast("<?php echo addslashes($message); ?>", "error");
        <?php endif; ?>

    </script>
</body>
</html>