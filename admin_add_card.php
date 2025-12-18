<?php
session_start();

// 1. Security Check (Match Dashboard)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin") {
    header("Location: login.php");
    exit;
}

// 2. Database Include
if (file_exists("db.php")) {
    include "db.php";
} else {
    $conn = false; 
}

$username = isset($_SESSION['username']) ? $_SESSION['username'] : "Admin";

// 3. Logic: Fetch card count
$cardCount = 0;
if ($conn) {
    $query = "SELECT COUNT(*) AS total FROM categories";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $cardCount = $row['total'];
    }
}

// 4. Logic: Handle form submit - MODIFIED TO SHOW MESSAGE ON SAME PAGE
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && $conn) {
    $name = trim($_POST["name"]);
    $type = $_POST["type"] ?? 'mcq'; // default to mcq

    if (!empty($name)) {
        $name = mysqli_real_escape_string($conn, $name);
        $avatar = strtoupper(substr($name, 0, 1));

        $insertSql = "INSERT INTO categories (name, avatar, type) VALUES ('$name', '$avatar', '$type')";
        
        if (mysqli_query($conn, $insertSql)) {
            $success_message = "Category added successfully!";
            
            // Update card count after insert
            $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM categories");
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $cardCount = $row['total'];
            }
            
            // Clear the form field
            $_POST['name'] = '';
        } else {
            $success_message = "Error: Could not create category.";
        }
    } else {
        $success_message = "Please enter a category name.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category - LogicLoom</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
    </style>
</head>

<body class="bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-100 relative overflow-x-hidden transition-colors duration-300">

    <!-- Theme Check Script -->
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.getElementById('html-root').classList.add('dark');
        } else {
            document.getElementById('html-root').classList.remove('dark');
        }
    </script>

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

                <a href="add_card.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-emerald-800/50 dark:bg-slate-800 text-white shadow-sm border border-emerald-700/50 dark:border-slate-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span class="font-medium">Categories</span>
                </a>

                <a href="manage_questions.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-emerald-100 hover:bg-emerald-800/30 dark:hover:bg-slate-800 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Questions</span>
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

            <!-- User Profile -->
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
            
            <!-- Top Header -->
            <header class="glass-panel sticky top-0 z-10 px-6 py-4 flex justify-between items-center shadow-sm transition-colors duration-300">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h2 class="text-xl font-semibold text-slate-800 dark:text-white">Add New Category</h2>
                </div>

                <div class="flex items-center gap-6">
                    <button onclick="toggleTheme()" class="p-2 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-yellow-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-all shadow-inner">
                        <svg id="sun-icon" class="w-6 h-6 block dark:hidden transition-transform duration-500 rotate-0 dark:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg id="moon-icon" class="w-6 h-6 hidden dark:block transition-transform duration-500 -rotate-90 dark:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                    <span class="text-sm text-slate-500 dark:text-slate-400 hidden sm:block"><?php echo date("l, F jS Y"); ?></span>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-6 lg:p-10 max-w-4xl mx-auto w-full">

                <!-- Stats Row -->
                <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-md dark:shadow-sm border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Total Categories</p>
                            <h3 class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1"><?php echo $cardCount; ?></h3>
                        </div>
                        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 rounded-full text-emerald-600 dark:text-emerald-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-purple-600 to-indigo-600 p-6 rounded-2xl shadow-lg text-white flex flex-col justify-between relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10"></div>
                        <div class="relative z-10">
                            <h3 class="text-lg font-bold">Need Help?</h3>
                            <p class="text-purple-100 text-sm mt-1">Let AI suggest categories for you.</p>
                        </div>
                        <button class="mt-4 bg-white text-purple-600 py-2 px-4 rounded-lg font-semibold text-sm hover:bg-purple-50 transition shadow-sm w-fit">
                            Try AI Generator
                        </button>
                    </div>
                </div>

                <!-- Main Form Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg dark:shadow-sm border border-slate-200 dark:border-slate-700 p-8">
                    
                    <!-- Success Message - Now shown on same page -->
                    <?php if (!empty($success_message)): ?>
                        <div class="mb-6 p-4 <?php echo strpos($success_message, 'successfully') !== false ? 'bg-emerald-100 dark:bg-emerald-900/30 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200' : 'bg-red-100 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200'; ?> border rounded-xl flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span><?php echo $success_message; ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-slate-100 dark:bg-slate-700 rounded-xl">
                            <svg class="w-6 h-6 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white">Create New Card</h2>
                    </div>

                    <form method="POST">
                        <div class="mb-6">
                            <label class="block mb-2 text-sm font-medium text-slate-600 dark:text-slate-400">Category Name</label>
                            <input type="text" name="name" required placeholder="e.g. Mathematics, Science, Logic..."
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                   class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent outline-none transition-all shadow-sm">
                        </div>
                       
<!-- NEW: Category Type Selection -->
<div class="mb-6">
    <label class="block mb-2 text-sm font-medium text-slate-600 dark:text-slate-400">Category Type</label>
    <div class="grid grid-cols-2 gap-4">
        <label class="flex items-center p-4 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xl cursor-pointer hover:border-emerald-500 transition">
            <input type="radio" name="type" value="mcq" checked class="mr-3 text-emerald-600 focus:ring-emerald-500">
            <span class="font-medium">📝 MCQ Practice</span>
            <p class="text-xs text-slate-500 mt-1">Multiple choice questions</p>
        </label>
        <label class="flex items-center p-4 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xl cursor-pointer hover:border-purple-500 transition">
            <input type="radio" name="type" value="coding" class="mr-3 text-purple-600 focus:ring-purple-500">
            <span class="font-medium">💻 Pattern Coding</span>
            <p class="text-xs text-slate-500 mt-1">Write code to print patterns</p>
        </label>
    </div>
</div>

                        <div class="flex flex-col sm:flex-row gap-4">
                            <button type="submit" class="flex-1 py-3 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-600/20 transition-transform hover:-translate-y-0.5">
                                Create Card
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

    <!-- Shared Scripts -->
    <script>
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
    </script>
</body>
</html>