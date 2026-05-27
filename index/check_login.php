<?php
session_start();

// 資料庫連線設定
$host = 'localhost';
$dbname = 'messenger';
$dbuser = 'root';
$dbpass = '';

try {
    // 建立 PDO 連線
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 取得並處理表單資料
    $inputUser = trim($_POST['username']);
    $inputPass = trim($_POST['password']);

    if (empty($inputUser) || empty($inputPass)) {
        echo "<script>alert('請輸入帳號與密碼'); window.location.href='login.php';</script>";
        exit;
    }

    // 查詢使用者資料
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$inputUser]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 驗證帳號與密碼（建議日後改為 password_verify()）
    if ($user && $user['password'] === $inputPass) {
        $_SESSION['username'] = $user['username'];
        header("Location: home.php");
        exit;
    } else {
        echo "<script>alert('帳號或密碼錯誤'); window.location.href='login.php';</script>";
        exit;
    }

} catch (PDOException $e) {
    die("連線失敗：" . $e->getMessage());
}
