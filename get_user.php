<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/../db.php';

if(!isset($_SESSION['user_id'])) {
  echo json_encode(['ok'=>false,'error'=>'not_logged_in']);
  exit;
}

$user_id = intval($_SESSION['user_id']);
$q = $conn->prepare("SELECT id, full_name, email, role FROM users WHERE id = ?");
$q->bind_param("i",$user_id);
$q->execute();
$res = $q->get_result();
$user = $res->fetch_assoc();

if($user) echo json_encode(['ok'=>true,'user'=>$user]);
else echo json_encode(['ok'=>false,'error'=>'user_not_found']);
