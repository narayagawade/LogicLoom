<?php
$conn = new mysqli("localhost", "root", "", "logicloom");

$id = $_POST['question_id'];
$language = $_POST['language'];
$user_code = $_POST['user_code'];

// Get expected pattern
$q = $conn->query("SELECT * FROM pattern_questions WHERE id=$id")->fetch_assoc();
$expected = $q['expected_output'];

// AI API Call
$api_key = "YOUR_OPENAI_API_KEY";

$data = [
    "model" => "gpt-4o-mini",
    "messages" => [
        [
            "role" => "system",
            "content" => "You are a strict code validator. Compare the ACTUAL OUTPUT of the user's code with the expected pattern. If EXACT MATCH → return ONLY 'correct'. Else return only 'incorrect'."
        ],
        [
            "role" => "user",
            "content" => "Language: $language\n\nUser code:\n$user_code\n\nExpected Output:\n$expected"
        ]
    ]
];

$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $api_key"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
curl_close($ch);

$res = json_decode($response, true);
$ai_reply = strtolower(trim($res['choices'][0]['message']['content']));

$correct = ($ai_reply == "correct");
?>
<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-6 bg-gray-100">

<div class="max-w-xl mx-auto bg-white p-6 rounded-xl shadow-lg text-center">

    <?php if ($correct) { ?>
        <h1 class="text-3xl font-bold text-green-600">Correct! 🎉</h1>
    <?php } else { ?>
        <h1 class="text-3xl font-bold text-red-600">Incorrect ❌</h1>
    <?php } ?>

    <a href="solve_pattern.php?id=<?= $id ?>" class="mt-4 inline-block text-blue-600">Try Again</a>

</div>

</body>
</html>
