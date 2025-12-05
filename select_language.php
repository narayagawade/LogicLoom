<?php
session_start();
$_SESSION['pattern_q_id'] = $_GET['qid'];  // Selected question from list
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Language</title>
</head>
<body>

<h2>Select Programming Language</h2>

<form action="pattern_dashboard.php" method="GET">
    <input type="hidden" name="qid" value="<?php echo $_SESSION['pattern_q_id']; ?>">

    <select name="lang" required>
        <option value="">Choose Language</option>
        <option value="c">C</option>
        <option value="cpp">C++</option>
        <option value="python">Python</option>
        <option value="java">Java</option>
        <option value="javascript">JavaScript</option>
    </select>
    <br><br>

    <button type="submit">Start Coding</button>
</form>

</body>
</html>
