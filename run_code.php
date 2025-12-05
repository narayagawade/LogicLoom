<?php
if(!isset($_POST['code'])) die("No code found!");

$code = $_POST['code'];
$lang = $_POST['lang'];

$filename = "temp_" . time();

if($lang == "python") {
    file_put_contents("$filename.py", $code);
    echo shell_exec("python $filename.py 2>&1");
}

if($lang == "javascript") {
    file_put_contents("$filename.js", $code);
    echo shell_exec("node $filename.js 2>&1");
}

if($lang == "c") {
    file_put_contents("$filename.c", $code);
    shell_exec("gcc $filename.c -o $filename.exe 2>&1");
    echo shell_exec("$filename.exe 2>&1");
}

if($lang == "cpp") {
    file_put_contents("$filename.cpp", $code);
    shell_exec("g++ $filename.cpp -o $filename.exe 2>&1");
    echo shell_exec("$filename.exe 2>&1");
}

if($lang == "java") {
    file_put_contents("$filename.java", $code);
    shell_exec("javac $filename.java 2>&1");
    echo shell_exec("java $filename 2>&1");
}
?>
