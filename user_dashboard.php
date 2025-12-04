<?php
session_start();
include 'db.php';

// --- Authentication Check ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}
$user_id = $_SESSION['user_id'];

// --- Backend Data Fetching ---

// 1. Fetch User & Profile
$user = $conn->query("SELECT id, full_name, email FROM users WHERE id = $user_id")->fetch_assoc();
$profileStmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$profileStmt->bind_param("i", $user_id);
$profileStmt->execute();
$profile = $profileStmt->get_result()->fetch_assoc();
$profileStmt->close();

if (!$profile) {
    header("Location: profile_setup.php");
    exit;
}

// 2. Optimized Category Fetching
$sql = "SELECT c.*, COUNT(q.id) as question_count 
        FROM categories c 
        LEFT JOIN questions q ON c.id = q.category_id 
        GROUP BY c.id 
        ORDER BY c.id ASC";
$result = $conn->query($sql);

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// 3. Logic for Time-based Greeting (UPDATED for accurate evening time)
$hour = date('H'); // Gets the hour in 24-hour format (00 to 23)

if ($hour >= 5 && $hour < 12) { 
    $greeting = "Good Morning"; // 5 AM to 11:59 AM
} elseif ($hour >= 12 && $hour < 17) { 
    $greeting = "Good Afternoon"; // 12 PM to 4:59 PM
} else {
    // This covers 5 PM (17:00) until 4:59 AM
    $greeting = "Good Evening"; 
}

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard | LogicLoom</title>
  
  <script src="https://cdn.tailwindcss.com"></script>
  
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <script src="https://unpkg.com/@phosphor-icons/web"></script>

  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    
    /* Smooth Entrance Animations */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-enter { animation: fadeInUp 0.6s ease-out forwards; opacity: 0; }
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }

    [x-cloak] { display: none !important; }
  </style>
</head>

<body class="bg-gray-50 min-h-screen relative" 
      x-data="{ 
          search: '', 
          isDropdownOpen: false,
          categories: <?php echo htmlspecialchars(json_encode($categories)); ?> 
      }">

  <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-20 -left-20 w-96 h-96 bg-green-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
    <div class="absolute top-0 -right-20 w-96 h-96 bg-teal-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob delay-2000"></div>
  </div>

  <header class="sticky top-0 z-50 w-full bg-white/80 backdrop-blur-xl border-b border-gray-200 shadow-sm transition-all duration-300">
      <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
          
          <a href="user_dashboard.php" class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 p-0.5 shadow-md group-hover:scale-105 transition-transform duration-300">
                <div class="bg-white w-full h-full rounded-[6px] overflow-hidden flex items-center justify-center">
                    <img src="img/loginlamge.png" class="w-8 h-8 object-contain" alt="Logo"/>
                </div>
            </div>
            <span class="text-xl font-bold text-gray-900 tracking-tight">LogicLoom</span>
          </a>

          <div class="hidden md:block flex-1 max-w-md mx-8">
              <div class="relative group">
                  <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-green-600 transition-colors"></i>
                  <input type="text" x-model="search" placeholder="Search topics..." 
                         class="w-full pl-10 pr-4 py-2.5 bg-gray-100 border-transparent rounded-xl focus:bg-white focus:border-green-300 focus:ring-4 focus:ring-green-100 transition-all outline-none text-sm">
              </div>
          </div>

          <div class="flex items-center gap-4">
              
              <button class="w-10 h-10 rounded-full bg-white border border-gray-200 text-gray-500 flex items-center justify-center hover:bg-gray-50 hover:text-green-600 transition-colors relative">
                  <i class="ph ph-bell text-xl"></i>
                  <span class="absolute top-2 right-2.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
              </button>

              <div class="relative">
                  <button @click="isDropdownOpen = !isDropdownOpen" @click.outside="isDropdownOpen = false" 
                          class="flex items-center gap-3 pl-1 pr-1 py-1 rounded-full border border-gray-200 bg-white hover:shadow-md transition-all duration-200">
                      <img src="<?php echo htmlspecialchars($profile['avatar'] ?: 'img/loginlamge.png'); ?>" 
                           class="w-9 h-9 rounded-full object-cover border border-gray-100" />
                      
                      <div class="hidden sm:block text-left mr-2">
                          <div class="text-xs font-bold text-gray-700 leading-none mb-0.5">
                             <?php echo htmlspecialchars($profile['nickname'] ?: explode(' ', $user['full_name'])[0]); ?>
                          </div>
                          <div class="text-[10px] text-gray-400 font-medium">Student</div>
                      </div>
                      <i class="ph ph-caret-down text-gray-400 mr-2 text-sm"></i>
                  </button>

                  <div x-show="isDropdownOpen" x-cloak
                       x-transition:enter="transition ease-out duration-200"
                       x-transition:enter-start="opacity-0 translate-y-2"
                       x-transition:enter-end="opacity-100 translate-y-0"
                       x-transition:leave="transition ease-in duration-150"
                       x-transition:leave-start="opacity-100 translate-y-0"
                       x-transition:leave-end="opacity-0 translate-y-2"
                       class="absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-[100] origin-top-right">
                      
                      <div class="px-5 py-4 border-b border-gray-50 bg-gray-50/50">
                          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Signed in as</p>
                          <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($user['email']); ?></p>
                      </div>

                      <div class="p-2">
                          <a href="profile_update.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors">
                              <i class="ph ph-user-circle text-lg"></i> My Profile
                          </a>
                          <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors">
                              <i class="ph ph-star text-lg"></i> Starred Topics
                          </a>
                          <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors">
                              <i class="ph ph-gear text-lg"></i> Settings
                          </a>
                      </div>

                      <div class="h-px bg-gray-100 my-1 mx-2"></div>
                      
                      <div class="p-2">
                          <a href="logout.php" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 transition-colors">
                              <i class="ph ph-sign-out text-lg"></i> Log Out
                          </a>
                      </div>
                  </div>
              </div>

          </div>
      </div>
  </header>

  <main class="relative z-0 max-w-7xl mx-auto px-6 py-8">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10 animate-enter delay-100">
        <div class="lg:col-span-2 flex flex-col justify-center">
            <h2 class="text-4xl font-bold text-gray-900 mb-2">
                <?php echo $greeting; ?>, <span class="text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-teal-500"><?php echo htmlspecialchars($profile['nickname'] ?: 'Student'); ?></span>!
            </h2>
            <p class="text-gray-500 text-lg">Track your progress and learn something new.</p>
            
            <div class="md:hidden mt-4">
                 <input type="text" x-model="search" placeholder="Search categories..." 
                         class="w-full pl-4 pr-4 py-3 bg-white border border-gray-200 rounded-xl shadow-sm focus:border-green-400 outline-none text-gray-700">
            </div>
        </div>

        <div class="relative group bg-gradient-to-br from-emerald-500 to-green-600 rounded-3xl p-6 text-white shadow-xl shadow-emerald-200/50 transform hover:scale-[1.01] transition-transform duration-300">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>

            <div class="flex justify-between items-start mb-6">
                <div>
                    <p class="text-emerald-100 text-sm font-medium mb-1">Learning Stats</p>
                    <h3 class="text-3xl font-bold"><?php echo count($categories); ?> <span class="text-lg font-normal opacity-80">Topics</span></h3>
                </div>
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <i class="ph ph-trend-up text-xl text-white"></i>
                </div>
            </div>

            <div class="relative pt-1">
                <div class="flex mb-2 items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-100">Overall Progress</span>
                    <span class="text-xs font-bold text-white">30%</span>
                </div>
                <div class="overflow-hidden h-2 mb-1 text-xs flex rounded-full bg-black/20">
                    <div style="width: 30%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-white rounded-full"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between mb-6 animate-enter delay-200">
        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <i class="ph ph-squares-four text-green-600"></i> Categories
        </h3>
        <span class="text-sm font-medium text-gray-500 bg-white px-3 py-1 rounded-full border border-gray-200">
            <span x-text="categories.filter(c => c.name.toLowerCase().includes(search.toLowerCase())).length"></span> Results
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-enter delay-300">
        
        <template x-for="cat in categories.filter(c => c.name.toLowerCase().includes(search.toLowerCase()))" :key="cat.id">
            
            <div class="group relative bg-white/90 backdrop-blur-sm rounded-2xl p-6 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] hover:shadow-lg hover:-translate-y-1 transition-all duration-300 border border-gray-100">
                
                <div class="flex justify-between items-start mb-4">
                    <div class="w-14 h-14 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center text-xl font-bold group-hover:bg-green-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                        <span x-text="cat.name.substring(0,2).toUpperCase()"></span>
                    </div>

                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-50 border border-gray-100 group-hover:border-green-100 transition-colors">
                        <i class="ph ph-cards text-gray-400 group-hover:text-green-500"></i>
                        <span class="text-xs font-bold text-gray-600" x-text="cat.question_count"></span>
                    </div>
                </div>

                <h4 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-green-700 transition-colors" x-text="cat.name"></h4>
                <p class="text-sm text-gray-500 line-clamp-2 mb-6">
                    Dive into <span x-text="cat.name"></span> challenges to sharpen your logical thinking.
                </p>

                <a :href="'category.php?id=' + cat.id" 
                   class="inline-flex w-full items-center justify-center gap-2 px-4 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-green-600 transition-colors shadow-lg shadow-gray-200 hover:shadow-green-200">
                   Start Practice
                   <i class="ph ph-arrow-right"></i>
                </a>
            </div>

        </template>

        <div x-show="categories.filter(c => c.name.toLowerCase().includes(search.toLowerCase())).length === 0" 
             class="col-span-full py-16 text-center">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                <i class="ph ph-magnifying-glass text-3xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">No matches found</h3>
            <p class="text-gray-500">We couldn't find any categories matching "<span x-text="search"></span>"</p>
        </div>

    </div>

    <div class="h-20"></div> </main>

</body>
</html>