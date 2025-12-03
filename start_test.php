<?php
// start_test.php
session_start();
include 'db.php'; // must define $conn (mysqli)

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Colors: Tailwind will be loaded in the HTML section below

// Helper: safely get int param
function get_int($key) {
    return isset($_REQUEST[$key]) ? (int)$_REQUEST[$key] : 0;
}

// If user clicked "restart" or category changed, remove previous session test
if (isset($_GET['restart']) && $_GET['restart'] == '1') {
    unset($_SESSION['test_questions'], $_SESSION['test_index'], $_SESSION['test_answers'], $_SESSION['test_category']);
}

// Start a new test when ?cat=ID provided AND no existing session test for this category
$cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

if ($cat <= 0) {
    echo "Invalid category.";
    exit;
}

// If there is no current test or category mismatch -> load questions
if (!isset($_SESSION['test_questions']) || !isset($_SESSION['test_category']) || $_SESSION['test_category'] !== $cat) {

    // Fetch up to 20 random questions for this category
    // Exclude pattern problems by checking pattern_code IS NULL OR empty (adjust if your schema differs)
    $sql = "SELECT id FROM questions WHERE category_id = ? AND (pattern_code IS NULL OR pattern_code = '') ORDER BY RAND() LIMIT 20";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "DB error: " . $conn->error;
        exit;
    }
    $stmt->bind_param("i", $cat);
    $stmt->execute();
    $res = $stmt->get_result();

    $qids = [];
    while ($r = $res->fetch_assoc()) {
        $qids[] = (int)$r['id'];
    }
    $stmt->close();

    if (count($qids) === 0) {
        echo "<p>No questions found for this category. Please ask admin to add questions.</p>";
        exit;
    }

    // Initialize session test state
    $_SESSION['test_category'] = $cat;
    $_SESSION['test_questions'] = $qids;      // array of question ids
    $_SESSION['test_index'] = 0;              // current index (0-based)
    $_SESSION['test_answers'] = array_fill(0, count($qids), null); // user's selected options (1..4) or null
}

// Process form submit (Next, Prev, Skip, Finish)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // read posted index and selected option
    $posted_index = get_int('current_index'); // 0-based index
    $selected_option = isset($_POST['selected_option']) && $_POST['selected_option'] !== '' ? (int)$_POST['selected_option'] : null;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // safety checks
    $total_questions = count($_SESSION['test_questions']);
    if ($posted_index < 0) $posted_index = 0;
    if ($posted_index >= $total_questions) $posted_index = $total_questions - 1;

    // save selected answer if any
    if ($selected_option !== null && in_array($selected_option, [1,2,3,4], true)) {
        $_SESSION['test_answers'][$posted_index] = $selected_option;
    }

    // navigation actions
    if ($action === 'next') {
        if ($posted_index < $total_questions - 1) {
            $_SESSION['test_index'] = $posted_index + 1;
        }
    } elseif ($action === 'prev') {
        if ($posted_index > 0) {
            $_SESSION['test_index'] = $posted_index - 1;
        }
    } elseif ($action === 'skip') {
        // do nothing except move forward
        if ($posted_index < $total_questions - 1) {
            $_SESSION['test_index'] = $posted_index + 1;
        }
    } elseif ($action === 'finish') {
        // Calculate score and store to DB, then clear test session and show result
        $qids = $_SESSION['test_questions'];
        $answers = $_SESSION['test_answers'];

        // fetch correct answers from DB for these qids
        $placeholders = implode(',', array_fill(0, count($qids), '?'));
        // build types string for bind_param
        $types = str_repeat('i', count($qids));
        $sql = "SELECT id, answer FROM questions WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            echo "DB prepare error: " . $conn->error;
            exit;
        }
        // bind dynamic params
        $bind_names = [];
        $bind_names[] = $types;
        foreach ($qids as $k => $id) {
            $bind_names[] = $qids[$k];
        }
        // call_user_func_array requires references
        $tmp = [];
        foreach ($bind_names as $key => $val) {
            $tmp[$key] = &$bind_names[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $tmp);
        $stmt->execute();
        $res = $stmt->get_result();

        $correct_map = [];
        while ($r = $res->fetch_assoc()) {
            $correct_map[(int)$r['id']] = (int)$r['answer'];
        }
        $stmt->close();

        $score = 0;
        foreach ($qids as $idx => $qid) {
            $correct = isset($correct_map[$qid]) ? $correct_map[$qid] : null;
            $sel = $answers[$idx] ?? null;
            if ($correct !== null && $sel !== null && (int)$sel === (int)$correct) {
                $score++;
            }
        }

        // store into user_scores
        $insert = $conn->prepare("INSERT INTO user_scores (user_id, score) VALUES (?, ?)");
        if ($insert === false) {
            echo "DB prepare error: " . $conn->error;
            exit;
        }
        $insert->bind_param("ii", $user_id, $score);
        if (!$insert->execute()) {
            echo "Error saving score: " . $insert->error;
            exit;
        }
        $insert->close();

        // cleanup session test data
        unset($_SESSION['test_questions'], $_SESSION['test_index'], $_SESSION['test_answers'], $_SESSION['test_category']);

        // show result page
        echo "<!doctype html><html><head><meta charset='utf-8'><title>Test Result</title>
        <script src='https://cdn.tailwindcss.com'></script></head><body class='min-h-screen bg-gradient-to-br from-[#c9e9c1] to-[#b8e0a6] flex items-center justify-center'>
        <div class='bg-white p-8 rounded-2xl shadow-lg text-center max-w-md'>
          <h2 class='text-2xl font-bold mb-4'>Test Completed</h2>
          <p class='mb-4'>You scored <span class='font-semibold'>$score</span> out of <span class='font-semibold'>" . count($qids) . "</span>.</p>
          <a href='user_dashboard.php' class='inline-block px-4 py-2 bg-[#9cd48b] hover:bg-[#8cc07e] text-white rounded-lg'>Back to Dashboard</a>
        </div></body></html>";
        exit;
    }
}

// Render current question
$index = $_SESSION['test_index'];
$qids = $_SESSION['test_questions'];
$total = count($qids);
$current_qid = $qids[$index];

// fetch the question details
$qstmt = $conn->prepare("SELECT id, question, option1, option2, option3, option4 FROM questions WHERE id = ?");
$qstmt->bind_param("i", $current_qid);
$qstmt->execute();
$qres = $qstmt->get_result();
$question = $qres->fetch_assoc();
$qstmt->close();

if (!$question) {
    echo "Question fetch error.";
    exit;
}
$selected = $_SESSION['test_answers'][$index]; // 1..4 or null
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Start Test - LogicLoom</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#c9e9c1] to-[#b8e0a6] p-6">

  <div class="max-w-3xl mx-auto bg-white rounded-2xl p-6 shadow-lg">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold">Practice Test</h1>
        <p class="text-sm text-gray-600">Question <?php echo $index + 1; ?> of <?php echo $total; ?></p>
      </div>
      <div>
        <a href="user_dashboard.php" class="px-3 py-2 rounded-md bg-[#9cd48b] text-white hover:bg-[#8cc07e]">Cancel</a>
      </div>
    </div>

    <form method="POST" class="space-y-4">
      <input type="hidden" name="current_index" value="<?php echo $index; ?>">
      <div class="text-lg font-semibold"><?php echo nl2br(htmlspecialchars($question['question'])); ?></div>

      <div class="mt-4 grid gap-3">
        <?php for ($i = 1; $i <= 4; $i++): 
            $opt = $question["option{$i}"];
            if ($opt === null) continue;
            $isChecked = ($selected !== null && (int)$selected === $i) ? 'checked' : '';
        ?>
          <label class="block p-3 border rounded-lg cursor-pointer hover:shadow-md <?php echo ($isChecked ? 'bg-[#e9f9ea]' : 'bg-white'); ?>">
            <input type="radio" name="selected_option" value="<?php echo $i; ?>" <?php echo $isChecked; ?> class="mr-2">
            <?php echo htmlspecialchars($opt); ?>
          </label>
        <?php endfor; ?>
      </div>

      <div class="flex items-center justify-between mt-6">
        <div class="flex gap-3">
          <button type="submit" name="action" value="prev" class="px-3 py-2 rounded-md bg-gray-200 hover:bg-gray-300" <?php echo $index === 0 ? 'disabled' : ''; ?>>Previous</button>
          <button type="submit" name="action" value="skip" class="px-3 py-2 rounded-md bg-yellow-200 hover:bg-yellow-300">Skip</button>
        </div>

        <div class="flex gap-3">
          <?php if ($index < $total - 1): ?>
            <button type="submit" name="action" value="next" class="px-4 py-2 rounded-md bg-[#9cd48b] text-white hover:bg-[#8cc07e]">Next</button>
          <?php else: ?>
            <button type="submit" name="action" value="finish" class="px-4 py-2 rounded-md bg-[#4CAF50] text-white hover:bg-[#3E8E41]">Finish</button>
          <?php endif; ?>
        </div>
      </div>
    </form>

    <div class="mt-6 text-sm text-gray-600">
      <strong>Note:</strong> You can navigate back and change answers before finishing.
    </div>
  </div>

</body>
</html>
