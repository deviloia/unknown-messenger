<?php
session_start();
require("db_connect.php");

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("無法連線資料庫：" . $e->getMessage());
    
}
$stmt_user = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt_user->execute([$username]);
$user_id = $stmt_user->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $tag = $_POST['tag'];
    $content = $_POST['content'];

    $stmt = $pdo->prepare("INSERT INTO stories (user_id, title, tag, content, created_at, status)
                           VALUES (?, ?, ?, ?, NOW(), 'unassigned')");
    $stmt->execute([$user_id, $title, $tag, $content]);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(['success' => true, 'message' => '信件寄出成功']);
    exit;
}
?>
