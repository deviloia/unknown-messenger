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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'], $_POST['story_id'])) {
    $story_id = intval($_POST['story_id']);
    $reply_content = $_POST['reply'];

    $pdo->prepare("UPDATE stories SET status='answered' WHERE id=?")->execute([$story_id]);
    $pdo->prepare("INSERT INTO replies (story_id, user_id, content, created_at)
                   VALUES (?, ?, ?, NOW())")->execute([$story_id, $user_id, $reply_content]);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(['success' => true, 'message' => '信件回覆成功']);
    exit;
}
?>
