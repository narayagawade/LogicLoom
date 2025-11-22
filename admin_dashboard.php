<?php
session_start();
// Strict Session Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin") {
    header("Location: login.php");
    exit;
}
$username = isset($_SESSION['username']) ? $_SESSION['username'] : "Admin";
?>

<!DOCTYPE html>
<html lang="en" class="light" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LogicLoom</title>
    <!-- Tailwind CSS with Dark Mode enabled via class -->
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
        
        /* Glassmorphism for Light Mode - Slightly more opaque to reduce glare */
        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(203, 213, 225, 0.8); /* slate-300 */
        }
        
        /* Glassmorphism for Dark Mode */
        .dark .glass-panel {
            background: rgba(30, 41, 59, 0.8); /* slate-800 */
            border-bottom: 1px solid rgba(51, 65, 85, 0.8);
        }

        /* Sidebar Scrollbar styling */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Smooth transitions for colors */
        * { transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease; }
    </style>
</head>

<!-- Body: Changed from bg-slate-50 to bg-slate-100 for softer light mode -->
<body class="bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-100 relative overflow-x-hidden transition-colors duration-300">

    <!-- Mobile Overlay Backdrop -->
    <div id="mobile-backdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-black/20 z-20 hidden lg:hidden backdrop-blur-sm transition-opacity"></div>

    <div class="flex h-screen overflow-hidden">

        <!-- ========= Sidebar ========= -->
        <aside id="sidebar" class="absolute lg:relative z-30 w-64 h-full bg-emerald-900 dark:bg-slate-950 text-white transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col shadow-2xl border-r border-transparent dark:border-slate-800">
            <!-- Logo Area -->
            <div class="p-6 flex items-center gap-3 border-b border-emerald-800/50 dark:border-slate-800">
                <div class="w-8 h-8 bg-emerald-400 rounded-lg flex items-center justify-center shadow-lg shadow-emerald-500/20">
                    <svg class="w-5 h-5 text-emerald-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h1 class="text-xl font-bold tracking-wide">LogicLoom</h1>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1 no-scrollbar">
                <p class="px-3 text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-2">Main</p>
                
                <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-emerald-800/50 dark:bg-slate-800 text-white shadow-sm border border-emerald-700/50 dark:border-slate-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span class="font-medium">Dashboard</span>
                </a>

                <a href="add_card.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-emerald-100 hover:bg-emerald-800/30 dark:hover:bg-slate-800 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span>Categories</span>
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
            </nav>

            <!-- User Profile Bottom -->
            <div class="p-4 border-t border-emerald-800/50 dark:border-slate-800 bg-emerald-950/30 dark:bg-slate-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-700 dark:bg-slate-700 flex items-center justify-center text-white font-bold border-2 border-emerald-600 dark:border-slate-600">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($username); ?></p>
                        <p class="text-xs text-emerald-400">Administrator</p>
                    </div>
                    <a href="logout.php" title="Logout" class="p-2 text-emerald-400 hover:text-white hover:bg-emerald-800 dark:hover:bg-slate-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </a>
                </div>
            </div>
        </aside>

        <!-- ========= Main Content ========= -->
        <main class="flex-1 flex flex-col h-screen overflow-y-auto">
            
            <!-- Top Header -->
            <header class="glass-panel sticky top-0 z-10 px-6 py-4 flex justify-between items-center shadow-sm transition-colors duration-300">
                <div class="flex items-center gap-4">
                    <!-- Mobile Menu Button -->
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h2 class="text-xl font-semibold text-slate-800 dark:text-white">Dashboard Overview</h2>
                </div>

                <div class="flex items-center gap-6">
                    <!-- Dark/Light Mode Toggle -->
                    <button onclick="toggleTheme()" class="p-2 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-yellow-300 hover:bg-slate-300 dark:hover:bg-slate-600 transition-all shadow-inner" title="Toggle Dark/Light Mode">
                        <!-- Sun Icon (Hidden in Dark Mode) -->
                        <svg id="sun-icon" class="w-6 h-6 block dark:hidden transition-transform duration-500 rotate-0 dark:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <!-- Moon Icon (Hidden in Light Mode) -->
                        <svg id="moon-icon" class="w-6 h-6 hidden dark:block transition-transform duration-500 -rotate-90 dark:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>

                    <span class="text-sm text-slate-500 dark:text-slate-400 hidden sm:block"><?php echo date("l, F jS Y"); ?></span>
                </div>
            </header>

            <div class="p-6 lg:p-8 max-w-7xl mx-auto w-full space-y-8">
                
                <!-- Welcome Banner / Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Stat 1: Stronger border (slate-200) and shadow (shadow-md) in light mode for better definition -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-md dark:shadow-sm border border-slate-200 dark:border-slate-700 flex items-center justify-between transition-colors duration-300">
                        <div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">System Status</p>
                            <h3 class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">Active</h3>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/50 rounded-full text-emerald-600 dark:text-emerald-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <!-- Stat 2 -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-md dark:shadow-sm border border-slate-200 dark:border-slate-700 flex items-center justify-between transition-colors duration-300">
                        <div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Total Categories</p>
                            <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-1">--</h3>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/50 rounded-full text-blue-600 dark:text-blue-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                    </div>
                    <!-- Stat 3 -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-md dark:shadow-sm border border-slate-200 dark:border-slate-700 flex items-center justify-between transition-colors duration-300">
                        <div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">AI Credits</p>
                            <h3 class="text-2xl font-bold text-slate-800 dark:text-white mt-1">Unlimited</h3>
                        </div>
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/50 rounded-full text-purple-600 dark:text-purple-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Action Grid -->
                <div>
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Management Tools</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                        <!-- Category Card - Increased shadow and border for light mode visibility -->
                        <div class="group bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-md dark:shadow-sm hover:shadow-xl border border-slate-200 dark:border-slate-700 transition-all duration-300 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 dark:bg-emerald-900/20 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                            <div class="relative">
                                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/50 rounded-xl flex items-center justify-center text-emerald-600 dark:text-emerald-400 mb-4 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Add Category</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 mb-6">Create new practice card modules for users.</p>
                                <a href="admin_add_card.php" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 dark:text-emerald-400 group-hover:text-emerald-700 dark:group-hover:text-emerald-300">
                                    Create Now <span class="transition-transform group-hover:translate-x-1">→</span>
                                </a>
                            </div>
                        </div>

                        <!-- Add Questions -->
                        <div class="group bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-md dark:shadow-sm hover:shadow-xl border border-slate-200 dark:border-slate-700 transition-all duration-300 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 dark:bg-blue-900/20 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                            <div class="relative">
                                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400 mb-4 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Add Questions</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 mb-6">Insert detailed questions into specific categories.</p>
                                <a href="add_question.php" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 dark:text-blue-400 group-hover:text-blue-700 dark:group-hover:text-blue-300">
                                    Insert Data <span class="transition-transform group-hover:translate-x-1">→</span>
                                </a>
                            </div>
                        </div>

                        <!-- Pattern Questions -->
                        <div class="group bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-md dark:shadow-sm hover:shadow-xl border border-slate-200 dark:border-slate-700 transition-all duration-300 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-orange-50 dark:bg-orange-900/20 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                            <div class="relative">
                                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/50 rounded-xl flex items-center justify-center text-orange-600 dark:text-orange-400 mb-4 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white">Logic Patterns</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 mb-6">Specialized logic and pattern recognition problems.</p>
                                <a href="add_patttern_question.php" class="inline-flex items-center gap-2 text-sm font-semibold text-orange-600 dark:text-orange-400 group-hover:text-orange-700 dark:group-hover:text-orange-300">
                                    Manage Patterns <span class="transition-transform group-hover:translate-x-1">→</span>
                                </a>
                            </div>
                        </div>

                        <!-- AI Auto Generator -->
                        <div class="group bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-md dark:shadow-sm hover:shadow-xl border border-purple-200 dark:border-purple-900/50 transition-all duration-300 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-50 dark:bg-purple-900/20 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                            <div class="relative">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-xl flex items-center justify-center text-purple-600 dark:text-purple-400 mb-4 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white">AI Generator</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 mb-6">Auto-generate questions using AI models.</p>
                                <a href="admin_ai_generate.php" class="inline-flex items-center gap-2 text-sm font-semibold text-purple-600 dark:text-purple-400 group-hover:text-purple-700 dark:group-hover:text-purple-300">
                                    Launch AI <span class="transition-transform group-hover:translate-x-1">→</span>
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile Sidebar Toggle
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

        // Theme Toggle Logic
        const htmlRoot = document.getElementById('html-root');
        
        // Check local storage or system preference on load
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            htmlRoot.classList.add('dark');
        } else {
            htmlRoot.classList.remove('dark');
        }

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