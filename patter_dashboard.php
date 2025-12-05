<?php
session_start();
include "config.php";

$qid = $_GET['qid'];
$lang = $_GET['lang'];

$q = $con->query("SELECT * FROM pattern_questions WHERE id='$qid'");
$data = $q->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pattern Practice</title>
    <style>
        #editor {
            width: 100%;
            height: 300px;
            border: 1px solid black;
        }
        #output {
            width: 100%;
            height: 150px;
            background: #f1f1f1;
            padding: 10px;
            white-space: pre-wrap;
        }
        .btn { padding: 10px 20px; margin: 10px; }
    </style>
</head>

<body>

<h2><?php echo $data['title']; ?></h2>
<p><?php echo nl2br($data['description']); ?></p>

<?php if($data['pattern_image'] != "") { ?>
    <img src="uploads/<?php echo $data['pattern_image']; ?>" width="200px">
<?php } ?>

<h3>Write Your Code:</h3>

<div id="editor"></div>

<br>
<button class="btn" onclick="runCode()">Run Code</button>
<button class="btn">Configuration</button>

<h3>Output:</h3>
<div id="output"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.js"></script>
<script>
    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/monokai");

    <?php
        if($lang=="python") echo 'editor.session.setMode("ace/mode/python");';
        if($lang=="c") echo 'editor.session.setMode("ace/mode/c_cpp");';
        if($lang=="cpp") echo 'editor.session.setMode("ace/mode/c_cpp");';
        if($lang=="java") echo 'editor.session.setMode("ace/mode/java");';
        if($lang=="javascript") echo 'editor.session.setMode("ace/mode/javascript");';
    ?>

function runCode() {
    let code = editor.getValue();
    let lang = "<?php echo $lang; ?>";

    fetch("run_code.php", {
        method: "POST",
        headers: { "Content-type": "application/x-www-form-urlencoded" },
        body: "code=" + encodeURIComponent(code) + "&lang=" + lang
    })
    .then(res => res.text())
    .then(data => {
        document.getElementById("output").innerText = data;
    });
}
</script>

</body>
</html>
