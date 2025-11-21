<?php
// admin_ai_generate.php
session_start();
include 'db.php';           // existing DB connection (mysqli $conn)
@include 'ai_config.php';  // optional: OPENAI_API_KEY, OPENAI_MODEL

// Protect route: only admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.html");
    exit;
}

// fetch categories
$categories = [];
$catRes = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
while ($r = $catRes->fetch_assoc()) $categories[] = $r;

// helper: simple fallback generator (non-AI)
function fallback_generate_questions($category_name, $count, $pattern=false) {
    $out = [];
    for ($i=1;$i<=$count;$i++) {
        $q = [];
        $q['question'] = ($pattern ? "Pattern: Draw the following star pattern #$i" : "Sample question $i for category $category_name");
        if ($pattern) {
            $q['pattern_code'] = "for (i=1;i<=n;i++) { /* pattern $i */ }";
            // Options still present but may be unused for coding tasks
        } else {
            $q['pattern_code'] = null;
        }
        $opts = [
            "Option A example for $i",
            "Option B example for $i",
            "Option C example for $i",
            "Option D example for $i"
        ];
        shuffle($opts);
        $q['option1'] = $opts[0];
        $q['option2'] = $opts[1];
        $q['option3'] = $opts[2];
        $q['option4'] = $opts[3];
        $q['answer'] = rand(1,4);
        $out[] = $q;
    }
    return $out;
}

// helper: call OpenAI
function call_openai_generate($prompt, $model='gpt-4o-mini') {
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    if (empty($api_key)) return null;

    $url = "https://api.openai.com/v1/chat/completions";
    $payload = [
        "model" => $model,
        "messages" => [
            ["role"=>"system","content"=>"You are an assistant that outputs JSON only."],
            ["role"=>"user","content"=>$prompt]
        ],
        "max_tokens" => 1200,
        "temperature" => 0.2
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) return null;
    $json = json_decode($resp, true);
    if (!isset($json['choices'][0]['message']['content'])) return null;
    return $json['choices'][0]['message']['content'];
}

// process form
$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category'] ?? 0);
    $num = intval($_POST['num_questions'] ?? 0);
    $num = max(1, min(50, $num)); // clamp: 1..50
    $pattern_flag = isset($_POST['is_pattern']) ? 1 : 0;

    // get category name
    $catName = '';
    $catQ = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $catQ->bind_param('i', $category_id);
    $catQ->execute();
    $catQ->bind_result($catName);
    $catQ->fetch();
    $catQ->close();

    if (empty($catName)) {
        $messages[] = ['type'=>'error','text'=>'Invalid category selected.'];
    } else {
        // Build AI prompt
        $prompt = "Generate {$num} distinct multiple-choice questions for the category \"{$catName}\"."
                . " Each item must be output as a JSON array element with keys: question, option1, option2, option3, option4, answer (1-4).";
        if ($pattern_flag) {
            $prompt .= " These are pattern-solving problems. For each question include an extra field pattern_code with a short example or ascii diagram. You can leave options as hints.";
        }
        $prompt .= " Output must be valid JSON array only. Example: [{\"question\":\"...\",\"option1\":\"...\",\"option2\":\"...\",\"option3\":\"...\",\"option4\":\"...\",\"answer\":2}]";

        $generated = null;
        // if OPENAI key present, call real API
        if (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
            $raw = call_openai_generate($prompt, defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini');
            if ($raw) {
                // try to extract JSON (strip markdown)
                // find first '[' and last ']'
                $first = strpos($raw, '[');
                $last = strrpos($raw, ']');
                if ($first !== false && $last !== false && $last > $first) {
                    $jsonText = substr($raw, $first, $last - $first + 1);
                    $decoded = json_decode($jsonText, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $generated = $decoded;
                    } else {
                        $messages[] = ['type'=>'error','text'=>'AI returned invalid JSON. Falling back to simple generator.'];
                    }
                } else {
                    $messages[] = ['type'=>'error','text'=>'AI response did not contain JSON array. Falling back.'];
                }
            } else {
                $messages[] = ['type'=>'error','text'=>'AI call failed or key invalid. Using fallback generator.'];
            }
        }

        // if not generated by AI, fallback
        if ($generated === null) {
            $generated = fallback_generate_questions($catName, $num, $pattern_flag);
        }

        // Insert into DB
        $insertSQL = "INSERT INTO questions 
            (category_id, question, pattern_code, option1, option2, option3, option4, answer, ai_generated, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insertSQL);
        if (!$stmt) {
            $messages[] = ['type'=>'error','text'=>'DB prepare error: '.$conn->error];
        } else {
            $ai_flag = 1;
            $countInserted = 0;
            foreach ($generated as $g) {
                // normalize keys
                $ques = $g['question'] ?? ($g[0] ?? null);
                $opt1 = $g['option1'] ?? $g['option_1'] ?? ($g['options'][0] ?? '');
                $opt2 = $g['option2'] ?? $g['option_2'] ?? ($g['options'][1] ?? '');
                $opt3 = $g['option3'] ?? $g['option_3'] ?? ($g['options'][2] ?? '');
                $opt4 = $g['option4'] ?? $g['option_4'] ?? ($g['options'][3] ?? '');
                $ans  = intval($g['answer'] ?? ($g['correct'] ?? 1));
                $pcode = $g['pattern_code'] ?? null;

                // sanitize minimal
                if (empty($ques)) continue;

                $stmt->bind_param("isssssiis", $category_id, $ques, $pcode, $opt1, $opt2, $opt3, $opt4, $ans, $ai_flag);
                if ($stmt->execute()) $countInserted++;
            }
            $stmt->close();
            $messages[] = ['type'=>'success','text'=>"Inserted {$countInserted} questions into category '{$catName}'."];
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>AI Generate Questions - LogicLoom Admin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#c9e9c1] to-[#b8e0a6] p-6">
  <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-xl p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800">AI Question Generator</h1>
      <div class="text-sm text-gray-600">Logged in as <strong><?php echo htmlspecialchars($_SESSION['email'] ?? 'admin'); ?></strong></div>
    </div>

    <?php foreach ($messages as $m): ?>
      <div class="mb-4 p-3 rounded <?php echo ($m['type']=='error') ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
        <?php echo htmlspecialchars($m['text']); ?>
      </div>
    <?php endforeach; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="font-semibold">Category</label>
        <select id="categorySelect" name="category" required class="w-full p-3 border rounded-lg">
          <option value="">Choose…</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="font-semibold">Number of Questions</label>
          <input name="num_questions" type="number" min="1" max="50" value="5" class="w-full p-3 border rounded-lg" required>
        </div>
        <div>
          <label class="font-semibold">Pattern Questions?</label>
          <div class="mt-2">
            <label class="inline-flex items-center">
              <input type="checkbox" name="is_pattern" class="form-checkbox h-5 w-5 text-green-600">
              <span class="ml-2">Yes (pattern solving)</span>
            </label>
          </div>
        </div>
      </div>

      <div>
        <label class="font-semibold">Use Real AI?</label>
        <p class="text-sm text-gray-500 mb-2">If you configured OPENAI_API_KEY in ai_config.php, this page will call the model. Otherwise a fallback generator will be used.</p>
        <button type="submit" class="w-full bg-[#9cd48b] hover:bg-[#8cc07e] text-white p-3 rounded-lg font-semibold">Generate & Insert</button>
      </div>
    </form>

    <div class="mt-6 text-sm text-gray-700">
      <strong>Notes:</strong>
      <ul class="list-disc ml-5">
        <li>AI will try to return a JSON array of questions. If it fails, the system falls back to a built-in generator.</li>
        <li>Pattern questions include a <code>pattern_code</code> field for admin to edit later.</li>
      </ul>
    </div>
  </div>

</body>
</html>
