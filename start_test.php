<?php
session_start();
include 'db.php'; // must set $conn (mysqli)

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// Helper: get integer from request safely
function ireq($key, $default = null) {
    return (isset($_REQUEST[$key]) && is_numeric($_REQUEST[$key])) ? (int)$_REQUEST[$key] : $default;
}

// Dynamic bind for IN clause
function dynamic_bind_param($stmt, $params) {
    if (empty($params)) return;
    $types = str_repeat('i', count($params));
    $bind_names = [$types];
    foreach ($params as $param) {
        $bind_names[] = $param;
    }
    $refs = [];
    foreach ($bind_names as $k => $v) {
        $refs[$k] = &$bind_names[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

// --- START NEW TEST ---
if (isset($_GET['cat']) && is_numeric($_GET['cat'])) {
    $cat = (int)$_GET['cat'];

    // Clear any old test session first (important!)
    unset(
        $_SESSION['test_in_progress'],
        $_SESSION['test_questions'],
        $_SESSION['test_answers'],
        $_SESSION['test_category'],
        $_SESSION['test_started_at']
    );

    // Fetch up to 20 random MCQ questions (pattern_code empty)
    $stmt = $conn->prepare("SELECT id FROM questions WHERE category_id = ? AND (pattern_code IS NULL OR pattern_code = '') ORDER BY RAND() LIMIT 20");
    $stmt->bind_param("i", $cat);
    $stmt->execute();
    $res = $stmt->get_result();

    $question_ids = [];
    while ($row = $res->fetch_assoc()) {
        $question_ids[] = (int)$row['id'];
    }
    $stmt->close();

    if (empty($question_ids)) {
        echo "<div class='min-h-screen bg-gradient-to-br from-green-50 to-green-100 flex items-center justify-center p-6'>
                <div class='bg-white p-10 rounded-2xl shadow-xl text-center'>
                    <p class='text-xl text-red-600 font-semibold mb-4'>No questions found for this category.</p>
                    <a href='user_dashboard.php' class='text-blue-600 underline hover:text-blue-800'>← Back to Dashboard</a>
                </div>
              </div>";
        exit;
    }

    // Initialize new test session
    $_SESSION['test_in_progress'] = true;
    $_SESSION['test_category']     = $cat;
    $_SESSION['test_questions']   = $question_ids;           // ← THIS WAS MISSING!
    $_SESSION['test_answers']     = array_fill(0, count($question_ids), 0); // 0 = unanswered
    $_SESSION['test_started_at']  = time();

    // Redirect to first question
    header("Location: start_test.php?index=0");
    exit;
}

// --- CHECK IF TEST IS IN PROGRESS ---
if (!isset($_SESSION['test_in_progress']) || !isset($_SESSION['test_questions']) || empty($_SESSION['test_questions'])) {
    echo "<div class='min-h-screen bg-gradient-to-br from-green-50 to-green-100 flex items-center justify-center p-6'>
            <div class='bg-white p-10 rounded-2xl shadow-xl text-center'>
                <p class='text-xl text-gray-800 font-semibold mb-4'>No test in progress.</p>
                <p class='text-gray-600 mb-6'>Choose a category and start a new test.</p>
                <a href='user_dashboard.php' class='text-blue-600 underline hover:text-blue-800'>← Back to Dashboard</a>
            </div>
          </div>";
    exit;
}

// Load session data
$questions      = $_SESSION['test_questions'];
$answers        = $_SESSION['test_answers'];
$total          = count($questions);
$category_id    = $_SESSION['test_category'];
$started_at     = $_SESSION['test_started_at'];

// --- HANDLE POST ACTIONS (answer save, navigation, finish) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $index   = ireq('index', 0);
    $action  = $_POST['action'] ?? 'next';
    $chosen  = isset($_POST['chosen']) && is_numeric($_POST['chosen']) ? (int)$_POST['chosen'] : 0;

    // Save current answer
    if ($index >= 0 && $index < $total) {
        $answers[$index] = $chosen;
        $_SESSION['test_answers'] = $answers;
    }

    // Handle navigation
    if ($action === 'prev') {
        $next_index = max(0, $index - 1);
        header("Location: start_test.php?index={$next_index}");
        exit;
    } elseif ($action === 'skip' || $action === 'next') {
        $next_index = min($total - 1, $index + 1);
        header("Location: start_test.php?index={$next_index}");
        exit;
    } elseif ($action === 'finish') {
        // Calculate score
        $placeholders = implode(',', array_fill(0, $total, '?'));
        $stmt = $conn->prepare("SELECT id, answer FROM questions WHERE id IN ($placeholders)");
        dynamic_bind_param($stmt, $questions);
        $stmt->execute();
        $res = $stmt->get_result();

        $correct_map = [];
        while ($row = $res->fetch_assoc()) {
            $correct_map[(int)$row['id']] = (int)$row['answer'];
        }
        $stmt->close();

        $score = 0;
        for ($i = 0; $i < $total; $i++) {
            $user_ans = $answers[$i] ?? 0;
            $correct  = $correct_map[$questions[$i]] ?? 0;
            if ($user_ans !== 0 && $user_ans === $correct) {
                $score++;
            }
        }

        // Save score to DB
        $stmt = $conn->prepare("INSERT INTO user_scores (user_id, category_id, score, correct_answers, total_questions, created_at) 
                                VALUES (?, ?, ?, ?, ?, NOW())");
        $correct_count = $score;
        $stmt->bind_param("iiiii", $user_id, $category_id, $score, $correct_count, $total);
        $stmt->execute();
        $stmt->close();

        // Save result for review page
        $_SESSION['test_result'] = [
            'score'       => $score,
            'total'       => $total,
            'answers'     => $answers,
            'questions'   => $questions,
            'category_id' => $category_id,
            'started_at'  => $started_at,
            'finished_at' => time()
        ];

        // Clean up test session
        unset($_SESSION['test_in_progress'], $_SESSION['test_questions'], $_SESSION['test_answers'],
              $_SESSION['test_category'], $_SESSION['test_started_at']);

        header("Location: test_result.php");
        exit;
    }
}

// --- DISPLAY CURRENT QUESTION ---
$index = ireq('index', 0);
if ($index < 0) $index = 0;
if ($index >= $total) $index = $total - 1;

$qid = $questions[$index];

// Fetch question details
$stmt = $conn->prepare("SELECT question, option1, option2, option3, option4 FROM questions WHERE id = ?");
$stmt->bind_param("i", $qid);
$stmt->execute();
$res = $stmt->get_result();
$Q = $res->fetch_assoc();
$stmt->close();

if (!$Q) {
    echo "<p class='text-center text-red-600'>Question not found!</p>";
    exit;
}

$current_choice = $answers[$index] ?? 0;
$time_elapsed   = time() - $started_at;
$time_display   = gmdate("H:i:s", $time_elapsed);
$progress       = (($index + 1) / $total) * 100;
$answered_count = count(array_filter($answers, fn($a) => $a !== 0));
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Practice — Question <?= $index + 1 ?>/<?= $total ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { background: linear-gradient(135deg, #f0fff4 0%, #a7e29e 100%); }
        input[type="radio"]:checked + span {
            border-color: #10B981;
            background-color: #ECFDF5;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="min-h-screen p-6" x-data="{
    time: '<?= $time_display ?>',
    start: <?= $started_at ?>,
    init() {
        setInterval(() => {
            const elapsed = Math.floor((Date.now() / 1000) - this.start);
            this.time = new Date(elapsed * 1000).toISOString().substr(11, 8);
        }, 1000);
    }
}" x-init="init">

<div class="max-w-4xl mx-auto">

    <!-- Progress Header -->
    <div class="bg-white/90 backdrop-blur p-4 rounded-xl shadow-lg border mb-6 sticky top-6 z-10">
        <div class="flex justify-between items-center mb-3">
            <div class="text-sm font-semibold flex items-center gap-1">
                <i class="ph ph-timer text-green-600"></i> Time: <span x-text="time" class="font-bold text-lg"></span>
            </div>
            <div class="text-sm">
                <span class="text-green-600 font-bold"><?= $answered_count ?></span> / <?= $total ?> Answered
            </div>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-green-600 h-3 rounded-full transition-all" style="width: <?= $progress ?>%"></div>
        </div>
        <div class="text-right text-xs text-gray-500 mt-1">Question <?= $index + 1 ?> of <?= $total ?></div>
    </div>

    <!-- Question Card -->
    <div class="bg-white p-8 rounded-2xl shadow-xl border">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b">
            <span class="text-green-600">Q<?= $index + 1 ?>:</span> <?= nl2br(htmlspecialchars($Q['question'])) ?>
        </h2>

        <form method="POST" class="mt-6">
            <input type="hidden" name="index" value="<?= $index ?>">

            <div class="space-y-4">
                <?php 
                $labels = ['A', 'B', 'C', 'D'];
                for ($i = 1; $i <= 4; $i++): 
                    $opt = $Q["option$i"];
                    if (empty($opt)) continue;
                    $checked = ($current_choice == $i);
                ?>
                    <label class="block cursor-pointer">
                        <input type="radio" name="chosen" value="<?= $i ?>" <?= $checked ? 'checked' : '' ?>
                               class="hidden peer" onchange="this.form.submit()">
                        <span class="flex items-center p-4 border-2 border-gray-200 rounded-xl transition-all hover:border-green-400">
                            <span class="w-8 h-8 flex-center text-sm font-bold rounded-full mr-4 <?= $checked ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600' ?>">
                                <?= $labels[$i-1] ?>
                            </span>
                            <span class="font-medium text-gray-800"><?= htmlspecialchars($opt) ?></span>
                        </span>
                    </label>
                <?php endfor; ?>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex justify-between items-center mt-8 pt-6 border-t">
                <div class="flex gap-3">
                    <button type="submit" name="action" value="prev" <?= $index == 0 ? 'disabled' : '' ?>
                            class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 disabled:opacity-50">
                        ← Previous
                    </button>
                    <button type="submit" name="action" value="skip"
                            class="px-4 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200">
                        Skip →
                    </button>
                </div>

                <div>
                    <?php if ($index < $total - 1): ?>
                        <button type="submit" name="action" value="next"
                                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 shadow">
                            Save & Next →
                        </button>
                    <?php else: ?>
                        <button type="submit" name="action" value="finish"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-lg">
                            Finish Test (<?= $answered_count ?>/<?= $total ?>)
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <div class="mt-6 text-xs text-gray-500 text-right">Question ID: <?= $qid ?></div>
    </div>
</div>

</body>
</html>