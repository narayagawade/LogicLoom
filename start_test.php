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
    if (isset($_REQUEST[$k]) && is_numeric($_REQUEST[$k])) return (int)$_REQUEST[$k];
    return $default;
}

// Start new test: GET ?cat=ID
if (isset($_GET['cat']) && is_numeric($_GET['cat']) && !isset($_SESSION['test_in_progress'])) {
    $cat = (int)$_GET['cat'];

    // fetch up to 20 random non-pattern questions for that category
    $stmt = $conn->prepare("SELECT id FROM questions WHERE category_id = ? AND (pattern_code IS NULL OR pattern_code = '') ORDER BY RAND() LIMIT 20");
    $stmt->bind_param("i", $cat);
    $stmt->execute();
    $res = $stmt->get_result();
    $ids = [];
    while ($r = $res->fetch_assoc()) $ids[] = (int)$r['id'];
    $stmt->close();

    if (count($ids) === 0) {
        echo "<p>No questions found for this category.</p><p><a href='user_dashboard.php'>Back</a></p>";
        exit;
    }

    // init session for this test
    $_SESSION['test_in_progress'] = true;
    $_SESSION['test_category'] = $cat;
    $_SESSION['test_questions'] = $ids;         // ordered list of question ids
    $_SESSION['test_answers'] = array_fill(0, count($ids), 0); // 0 = unanswered, 1..4 are answers
    $_SESSION['test_started_at'] = time();

    // redirect to first question (index 0)
    header("Location: start_test.php?action=show&index=0");
    exit;
}

// if no in-progress test, error
if (!isset($_SESSION['test_in_progress']) || !isset($_SESSION['test_questions'])) {
    echo "<p>No test in progress. Choose a category and start a new test.</p><p><a href='user_dashboard.php'>Back</a></p>";
    exit;
}

$questions = &$_SESSION['test_questions'];
$answers = &$_SESSION['test_answers'];
$total = count($questions);

// Action handling: user submitted an answer / navigation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $index = ireq('index', 0);
    $action = $_POST['action'] ?? 'next'; // next, prev, skip, finish, submit_answer
    // store given answer if any
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
        // already saved 0 if none; go next
        $next = min($total - 1, $index + 1);
        header("Location: start_test.php?action=show&index={$next}");
        exit;
    } elseif ($action === 'finish') {
        // compute score
        $score = 0;
        // fetch correct answers for all asked questions
        $placeholders = implode(',', array_fill(0, count($questions), '?'));
        $types = str_repeat('i', count($questions));
        $stmt = $conn->prepare("SELECT id, answer FROM questions WHERE id IN ($placeholders)");
        // dynamic bind
        $bind_names[] = $types;
        for ($i=0; $i<count($questions); $i++) $bind_names[] = $questions[$i];
        // call_user_func_array requires references
        $tmp = [];
        $refArr = [];
        foreach ($bind_names as $k => $v) $tmp[$k] = &$bind_names[$k];
        call_user_func_array([$stmt, 'bind_param'], $tmp);
        $stmt->execute();
        $res = $stmt->get_result();
        $correct_map = [];
        while ($r = $res->fetch_assoc()) $correct_map[(int)$r['id']] = (int)$r['answer'];
        $stmt->close();

        // tally
        for ($i=0; $i<count($questions); $i++) {
            $qid = $questions[$i];
            $user_choice = $answers[$i] ?? 0;
            $correct = $correct_map[$qid] ?? 0;
            if ($user_choice !== 0 && $user_choice === $correct) $score++;
        }

        // store score in user_scores
        $stmt2 = $conn->prepare("INSERT INTO user_scores (user_id, score, created_at) VALUES (?, ?, NOW())");
        $stmt2->bind_param("ii", $user_id, $score);
        $stmt2->execute();
        $stmt2->close();

        // save result in session for results page
        $_SESSION['test_result'] = [
            'score' => $score,
            'total' => $total,
            'answers' => $answers,
            'questions' => $questions,
            'started_at' => $_SESSION['test_started_at'],
            'finished_at' => time()
        ];

        // clear current test
        unset($_SESSION['test_in_progress'], $_SESSION['test_questions'], $_SESSION['test_answers'], $_SESSION['test_category'], $_SESSION['test_started_at']);

        // redirect to results
        header("Location: test_result.php");
        exit;
    }
}

// Show question page
$index = ireq('index', 0);
if ($index < 0) $index = 0;
if ($index > $total - 1) $index = $total - 1;
$qid = (int)$questions[$index];

// fetch question details
$stmt = $conn->prepare("SELECT id, question, option1, option2, option3, option4 FROM questions WHERE id = ?");
$stmt->bind_param("i", $qid);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows == 0) {
    echo "<p>Question not found.</p>";
    exit;
}
$Q = $res->fetch_assoc();
$stmt->close();

// current selected
$current_choice = $answers[$index] ?? 0;

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Practice — Question <?php echo $index+1 ?>/<?php echo $total ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#c9e9c1] to-[#b8e0a6] p-6">

  <div class="max-w-3xl mx-auto bg-white p-6 rounded-2xl shadow-lg">
    <div class="flex justify-between items-start">
      <div>
        <h2 class="text-xl font-semibold">Question <?php echo ($index+1) ?> / <?php echo $total ?></h2>
        <p class="text-gray-700 mt-3"><?php echo nl2br(htmlspecialchars($Q['question'])); ?></p>
      </div>
      <div class="text-sm text-gray-600">
        <div>Category ID: <?php echo htmlspecialchars($_SESSION['test_category'] ?? ''); ?></div>
      </div>
    </div>

    <form method="POST" class="mt-6">
      <input type="hidden" name="index" value="<?php echo $index ?>">
      <div class="space-y-3">
        <?php for ($opt=1; $opt<=4; $opt++):
            $text = $Q["option{$opt}"];
            if ($text === null) continue;
            $checked = ($current_choice == $opt) ? 'checked' : '';
        ?>
          <label class="block p-3 border rounded-lg cursor-pointer <?php echo ($current_choice == $opt) ? 'bg-green-50' : '' ?>">
            <input type="radio" name="chosen" value="<?php echo $opt; ?>" <?php echo $checked; ?> class="mr-2">
            <?php echo htmlspecialchars($text); ?>
          </label>
        <?php endfor; ?>
      </div>

      <div class="flex gap-2 mt-6">
        <button type="submit" name="action" value="prev" class="px-4 py-2 bg-gray-200 rounded">Previous</button>
        <button type="submit" name="action" value="skip" class="px-4 py-2 bg-yellow-200 rounded">Skip</button>
        <button type="submit" name="action" value="next" class="px-4 py-2 bg-blue-500 text-white rounded">Save & Next</button>

        <?php if ($index == $total - 1): ?>
          <button type="submit" name="action" value="finish" class="ml-auto px-4 py-2 bg-green-600 text-white rounded">Finish Test</button>
        <?php else: ?>
          <div class="ml-auto text-sm text-gray-600">Progress: <?php echo $index+1 ?>/<?php echo $total ?></div>
        <?php endif; ?>
      </div>
    </form>

    <div class="mt-4 text-xs text-gray-600">
      <strong>Note:</strong> You can move back and change answers before finishing.
    </div>
  </div>

</body>
</html>
