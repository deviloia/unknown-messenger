<?php
session_start();
$host = 'localhost';
$dbname = 'messenger';
$dbuser = 'root';
$dbpass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        echo "<script>alert('請輸入帳號與密碼'); window.location.href='register.php';</script>";
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->rowCount() > 0) {
        echo "<script>alert('使用者已存在'); window.location.href='register.php';</script>";
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    if ($stmt->execute([$username, $password])) {
        echo "<script>alert('註冊成功'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('註冊失敗'); window.location.href='register.php';</script>";
    }

} catch (PDOException $e) {
    die("連線失敗：" . $e->getMessage());
}
?>
