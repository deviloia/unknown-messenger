<?php
// db.php - 資料庫連線設定

$host = 'localhost';       // 主機位置 (本機通常是localhost)
$dbname = 'messenger'; // 你的資料庫名稱，改成你自己的
$user = 'root';   // 資料庫使用者帳號
$pass = '';   // 資料庫密碼
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // 出錯時丟例外
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // 預設用關聯陣列取資料
    PDO::ATTR_EMULATE_PREPARES => false, // 使用真正的 prepared statements
];

try {
    $db = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // 若連線失敗，印錯誤並結束程式
    echo "資料庫連線失敗：" . $e->getMessage();
    exit();
}
