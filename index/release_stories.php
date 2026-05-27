<?php
session_start();
require('db_connect.php');
$pdo = $db; // 如果 $db 是 PDO 物件

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    exit;
}

if (!empty($data['storyIds']) && is_array($data['storyIds'])) {
    $placeholders = implode(',', array_fill(0, count($data['storyIds']), '?'));
    $sql = "UPDATE stories SET status = 'unassigned' WHERE id IN ($placeholders) AND status = 'assigned'";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data['storyIds']);
        echo "Stories released";
    } catch (Exception $e) {
        http_response_code(500);
        echo "Database error";
    }
}
?>
