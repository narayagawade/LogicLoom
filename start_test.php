<?php
session_start();
include 'db.php'; // must set $conn (mysqli)

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// Helper: sanitize int GET/POST
function ireq($k, $default = null) {
    // Note: $_REQUEST includes both GET and POST
    if (isset($_REQUEST[$k]) && is_numeric($_REQUEST[$k])) return (int)$_REQUEST[$k];
    return $default;
}

// Function to safely bind parameters dynamically for IN clause (needed for finish action)
function dynamic_bind_param($stmt, $params) {
    $types = str_repeat('i', count($params));
    $bind_names[] = $types;
    for ($i=0; $i<count($params); $i++) $bind_names[] = $params[$i];
    
    // Create references for call_user_func_array
    $refs = [];
    foreach ($bind_names as $key => $value) {
        $refs[$key] = &$bind_names[$key];
    }
    
    call_user_func_array([$stmt, 'bind_param'], $refs);
}


// --- START NEW TEST LOGIC ---
if (isset($_GET['cat']) && is_numeric($_GET['cat']) && !isset($_SESSION['test_in_progress'])) {
    $cat = (int)$_GET['cat'];

    // Fetch up to 20 random non-pattern questions for that category
    $stmt = $conn->prepare("SELECT id FROM questions WHERE category_id = ? AND (pattern_code IS NULL OR pattern_code = '') ORDER BY RAND() LIMIT 20");
    $stmt->bind_param("i", $cat);
    $stmt->execute();
    $res = $stmt->get_result();
    $ids = [];
    while ($r = $res->fetch_assoc()) $ids[] = (int)$r['id'];
    $stmt->close();

    if (count($ids) === 0) {
        echo "<p class='text-center p-10'>No questions found for this category.</p><p class='text-center'><a href='user_dashboard.php' class='text-blue-500'>Back to Dashboard</a></p>";
        exit;
    }

    // Init session for this test
    $_SESSION['test_in_progress'] = true;
    $_SESSION['test_category'] = $cat;
    $_SESSION['test_answers'] = array_fill(0, count($ids), 0); // 0 = unanswered, 1..4 are answers
    $_SESSION['test_started_at'] = time();

    // Redirect to first question (index 0)
    header("Location: start_test.php?action=show&index=0");
    exit;
}

// if no in-progress test, error
if (!isset($_SESSION['test_in_progress']) || !isset($_SESSION['test_questions'])) {
    echo "<p class='text-center p-10'>No test in progress. Choose a category and start a new test.</p><p class='text-center'><a href='user_dashboard.php' class='text-blue-500'>Back to Dashboard</a></p>";
    exit;
}

$questions = &$_SESSION['test_questions'];
$answers = &$_SESSION['test_answers'];
$total = count($questions);

// --- ACTION HANDLING (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $index = ireq('index', 0);
    $action = $_POST['action'] ?? 'next';
    $chosen = isset($_POST['chosen']) && is_numeric($_POST['chosen']) ? (int)$_POST['chosen'] : 0;

    if ($index >= 0 && $index < $total) {
        // Save answer (0 allowed for skip)
        $answers[$index] = $chosen;
    }

    if ($action === 'next') {
        $next = min($total - 1, $index + 1);
        header("Location: start_test.php?action=show&index={$next}");
        exit;
    } elseif ($action === 'prev') {
        $prev = max(0, $index - 1);
        header("Location: start_test.php?action=show&index={$prev}");
        exit;
    } elseif ($action === 'skip') {
        // Go next (answer already saved as 0 if user didn't select one)
        $next = min($total - 1, $index + 1);
        header("Location: start_test.php?action=show&index={$next}");
        exit;
    } elseif ($action === 'finish') {
        // --- SCORE CALCULATION & SAVE ---
        $score = 0;
        
        // 1. Fetch correct answers for all asked questions
        $placeholders = implode(',', array_fill(0, count($questions), '?'));
        $stmt = $conn->prepare("SELECT id, answer FROM questions WHERE id IN ($placeholders)");
        dynamic_bind_param($stmt, $questions); // Use the new function for binding
        $stmt->execute();
        $res = $stmt->get_result();
        $correct_map = [];
        while ($r = $res->fetch_assoc()) $correct_map[(int)$r['id']] = (int)$r['answer'];
        $stmt->close();

        // 2. Tally score
        for ($i=0; $i<count($questions); $i++) {
            $qid = $questions[$i];
            $user_choice = $answers[$i] ?? 0;
            $correct = $correct_map[$qid] ?? 0;
            // Count if an answer was chosen AND it matches the correct answer
            if ($user_choice !== 0 && $user_choice === $correct) $score++;
        }

        // 3. Store score (for overall user tracking, simplified table)
        // Note: For full tracking, you'd save all answers/questions in a separate test_results table.
        $stmt2 = $conn->prepare("INSERT INTO user_scores (user_id, category_id, score, created_at) VALUES (?, ?, ?, NOW())");
        $cat_id_for_score = $_SESSION['test_category'];
        $stmt2->bind_param("iii", $user_id, $cat_id_for_score, $score);
        $stmt2->execute();
        $stmt2->close();

        // 4. Save result in session for results page
        $_SESSION['test_result'] = [
            'score' => $score,
            'total' => $total,
            'answers' => $answers,
            'questions' => $questions,
            'category_id' => $_SESSION['test_category'],
            'started_at' => $_SESSION['test_started_at'],
            'finished_at' => time()
        ];

        // 5. Clear current test session
        unset($_SESSION['test_in_progress'], $_SESSION['test_questions'], $_SESSION['test_answers'], $_SESSION['test_category'], $_SESSION['test_started_at']);

        // 6. Redirect to results
        header("Location: test_result.php");
        exit;
    }
}

// --- SHOW QUESTION PAGE LOGIC ---
$index = ireq('index', 0);
if ($index < 0) $index = 0;
if ($index > $total - 1) $index = $total - 1;
$qid = (int)$questions[$index];

// Fetch question details
$stmt = $conn->prepare("SELECT id, question, option1, option2, option3, option4 FROM questions WHERE id = ?");
$stmt->bind_param("i", $qid);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows == 0) {
    echo "<p class='text-center p-10'>Question not found.</p>";
    exit;
}
$Q = $res->fetch_assoc();
$stmt->close();

// Current selected answer for this question
$current_choice = $answers[$index] ?? 0;

// Calculate time elapsed
$time_elapsed = time() - ($_SESSION['test_started_at'] ?? time());
$time_display = gmdate("H:i:s", $time_elapsed);

// Calculate progress percentage
$progress_percent = (($index + 1) / $total) * 100;
$answered_count = array_filter($answers, fn($a) => $a !== 0);
$answered_count = count($answered_count);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Practice — Question <?php echo $index+1 ?>/<?php echo $total ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        /* Consistent Dashboard Background */
        body {
            background: linear-gradient(135deg, #f0fff4 0%, #a7e29e 100%);
        }
        /* Custom Checkbox/Radio Styling */
        input[type="radio"]:checked + span {
            border-color: #10B981; /* Emerald-500 */
            background-color: #ECFDF5; /* Emerald-50 */
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
    </style>
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

</head>
<body class="min-h-screen p-6" 
      x-data="{ 
          time: '<?php echo $time_display; ?>',
          start_time: <?php echo $_SESSION['test_started_at'] ?? time(); ?>,
          init() {
              setInterval(() => {
                  const elapsed = Math.floor((Date.now() / 1000) - this.start_time);
                  this.time = new Date(elapsed * 1000).toISOString().substr(11, 8);
              }, 1000);
          }
      }" x-init="init">

    <div class="max-w-4xl mx-auto">
    
        <div class="bg-white/90 backdrop-blur-sm p-4 rounded-xl shadow-lg border border-gray-100 mb-6 sticky top-6 z-10">
            <div class="flex justify-between items-center mb-3">
                <div class="text-sm font-semibold text-gray-700 flex items-center gap-1">
                    <i class="ph ph-timer text-green-600"></i> Time Elapsed: 
                    <span x-text="time" class="font-bold text-lg text-gray-900"></span>
                </div>
                <div class="text-xs font-medium text-gray-500">
                    <span class="text-green-600 font-bold"><?php echo $answered_count; ?></span> Answered / <?php echo $total; ?> Total
                </div>
            </div>

            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-green-600 h-2.5 rounded-full transition-all duration-500" 
                     style="width: <?php echo $progress_percent; ?>%">
                </div>
            </div>
            <div class="text-right text-xs text-gray-500 mt-1">
                Question <?php echo $index+1 ?> of <?php echo $total ?>
            </div>
        </div>

        <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
            
            <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b border-gray-100">
                <span class="text-green-600 mr-2">Q<?php echo ($index+1) ?>:</span> 
                <?php echo nl2br(htmlspecialchars($Q['question'])); ?>
            </h2>

            <form method="POST" class="mt-6">
                <input type="hidden" name="index" value="<?php echo $index ?>">
                
                <div class="space-y-4">
                    <?php 
                    $options = ['A', 'B', 'C', 'D'];
                    for ($opt=1; $opt<=4; $opt++):
                        $text = $Q["option{$opt}"];
                        if ($text === null) continue;
                        $checked = ($current_choice == $opt);
                    ?>
                        <label class="block cursor-pointer">
                            <input type="radio" name="chosen" value="<?php echo $opt; ?>" <?php echo $checked ? 'checked' : ''; ?> 
                                   class="hidden peer" onchange="this.form.submit()"
                            >
                            <span class="flex items-center p-4 border-2 border-gray-200 rounded-xl transition-all duration-150 shadow-sm hover:border-green-400">
                                <span class="w-8 h-8 flex items-center justify-center text-sm font-bold rounded-full mr-4 
                                             <?php echo $checked ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600'; ?> transition-colors duration-150">
                                    <?php echo $options[$opt - 1]; ?>
                                </span>
                                <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($text); ?></span>
                            </span>
                        </label>
                    <?php endfor; ?>
                </div>

                <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-100">
                    
                    <div class="flex gap-3">
                        <button type="submit" name="action" value="prev" 
                                <?php echo ($index == 0) ? 'disabled' : ''; ?>
                                class="flex items-center gap-1 px-4 py-2 bg-gray-100 text-gray-600 font-semibold rounded-lg hover:bg-gray-200 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="ph ph-caret-left"></i> Previous
                        </button>
                        
                        <button type="submit" name="action" value="skip" 
                                class="flex items-center gap-1 px-4 py-2 bg-yellow-500/10 text-yellow-700 font-semibold rounded-lg hover:bg-yellow-500/20 transition">
                            <i class="ph ph-fast-forward"></i> Skip & Next
                        </button>
                    </div>

                    <div class="flex gap-3">
                         <?php if ($index < $total - 1): ?>
                            <button type="submit" name="action" value="next" 
                                    class="flex items-center gap-2 px-6 py-2 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition">
                                Save & Next <i class="ph ph-caret-right"></i>
                            </button>
                        <?php endif; ?>

                        <?php if ($index == $total - 1): ?>
                            <button type="submit" name="action" value="finish" 
                                    class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow-lg shadow-blue-200 hover:bg-blue-700 transition">
                                <i class="ph ph-check-circle"></i> Finish Test (<?php echo $answered_count; ?>/<?php echo $total; ?>)
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            <div class="mt-8 pt-4 border-t border-dashed border-gray-200 text-sm text-gray-500 flex justify-between">
                <div>
                    **Note:** Selecting an option automatically saves your choice and advances, or you can use the **Save & Next** button.
                </div>
                <div>
                    Question ID: <?php echo $qid; ?>
                </div>
            </div>
            
        </div>
        </div>

</body>
</html>