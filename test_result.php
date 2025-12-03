<?php
session_start();
include 'db.php';
if (!isset($_SESSION['test_result']) || !isset($_SESSION['user_id'])) {
    echo "<p>No result available.</p><p><a href='user_dashboard.php'>Back to dashboard</a></p>";
    exit;
}

$resdata = $_SESSION['test_result'];
$questions = $resdata['questions'];
$answers = $resdata['answers'];
$score = (int)$resdata['score'];
$total = (int)$resdata['total'];
$user_id = (int)$_SESSION['user_id'];

// fetch question details for the asked questions
$placeholders = implode(',', array_fill(0, count($questions), '?'));
$types = str_repeat('i', count($questions));
$stmt = $conn->prepare("SELECT id, question, option1, option2, option3, option4, answer FROM questions WHERE id IN ($placeholders)");
$bind_names = [$types];
foreach ($questions as $id) $bind_names[] = $id;
$tmp = [];
foreach ($bind_names as $k => $v) $tmp[$k] = &$bind_names[$k];
call_user_func_array([$stmt, 'bind_param'], $tmp);
$stmt->execute();
$result = $stmt->get_result();

$qmap = [];
while ($r = $result->fetch_assoc()) $qmap[(int)$r['id']] = $r;
$stmt->close();

// display
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Test Result</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#c9e9c1] to-[#b8e0a6] p-6">
  <div class="max-w-4xl mx-auto bg-white p-6 rounded-2xl shadow-lg">
    <h1 class="text-2xl font-bold">Test Result</h1>
    <p class="mt-2">Score: <strong><?php echo $score; ?></strong> / <?php echo $total; ?></p>
    <p class="text-sm text-gray-600 mt-1">Submitted at: <?php echo date("Y-m-d H:i:s", $resdata['finished_at']); ?></p>

    <div class="mt-6 space-y-4">
      <?php foreach ($questions as $idx => $qid): 
          $q = $qmap[$qid];
          $user_choice = $answers[$idx] ?? 0;
          $correct = (int)$q['answer'];
          $is_correct = ($user_choice !== 0 && $user_choice == $correct);
      ?>
        <div class="p-4 border rounded-lg <?php echo $is_correct ? 'bg-green-50' : 'bg-red-50'; ?>">
          <div class="flex justify-between items-start">
            <div>
              <div class="text-sm text-gray-600">Q<?php echo $idx+1; ?></div>
              <div class="font-semibold mt-1"><?php echo nl2br(htmlspecialchars($q['question'])); ?></div>
            </div>
            <div class="text-sm">
              <?php echo $is_correct ? '<span class="text-green-700 font-semibold">Correct</span>' : '<span class="text-red-700 font-semibold">Wrong</span>'; ?>
            </div>
          </div>

          <div class="mt-3 space-y-2">
            <?php for ($opt=1;$opt<=4;$opt++):
                $text = $q["option{$opt}"];
                if ($text === null || $text === '') continue;
                $cls = '';
                if ($opt == $correct) $cls = 'border-green-600 bg-green-100';
                if ($opt == $user_choice && $opt != $correct) $cls = 'border-red-600 bg-red-100';
            ?>
              <div class="p-2 border rounded <?php echo $cls; ?>">
                <strong>Option <?php echo $opt; ?>:</strong> <?php echo htmlspecialchars($text); ?>
                <?php if ($opt == $correct) echo " <span class='text-green-700'>(Correct)</span>"; ?>
                <?php if ($opt == $user_choice && $opt != $correct) echo " <span class='text-red-700'>(Your choice)</span>"; ?>
              </div>
            <?php endfor; ?>
          </div>

          <div class="mt-3">
            <!-- Explanation button placeholder - call AI here if implemented -->
            <button class="px-3 py-1 bg-indigo-600 text-white rounded" onclick="alert('Explanation feature coming soon (AI).')">Explanation</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="mt-6">
      <a href="user_dashboard.php" class="px-4 py-2 bg-[#9cd48b] rounded text-white">Back to Dashboard</a>
    </div>
  </div>
</body>
</html>

<?php
// Optional: clear session test_result if you want one-time view
// unset($_SESSION['test_result']);
?>
