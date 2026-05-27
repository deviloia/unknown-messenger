<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require("db_connect.php");


@$username = $_SESSION['username'];
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("無法連線資料庫：" . $e->getMessage());
}

@$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
@$stmt->execute([$user_id]);
@$user = $stmt->fetch();
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Kiwi+Maru&display=swap');
    @font-face {
      font-family: "Monomaniac One";
      src: url('fonts/MonomaniacOne-Regular.ttf');
      unicode-range: U+0000-024F;
    }
    @font-face {
      font-family: "huninn-fws";
      src: url('fonts/jf-openhuninn-2.1.ttf');
      unicode-range: U+3000-303F, U+FF00-FFEF;
    }
    @font-face {
      font-family: "huninn";
      src: url('fonts/jf-openhuninn-2.1.ttf');
    }
    * { box-sizing: border-box; font-family: "Monomaniac One", "huninn-fws", "Kiwi Maru", "huninn", sans-serif!important; }

    .navbar {
      display: flex;
      justify-content: space-between;
      padding: 15px 40px;
      background: #fff;
      border-bottom: 2px solid #d7c2dd;
      align-items: center;
      z-index: 10000;
    }
    .navbar .site-name {
      font-weight: 900;
      font-size: 28px;
      letter-spacing: 7px;
    }
    .navbar a {
      text-decoration: none;
      color: #333;
      margin-left: 20px;
      font-weight: bold;
    }
    .navbar .user-info {
      font-weight: normal;
      color: #4a3a2b;
      margin-left: 20px;
    }
    .navbar .title-banner {
      text-align: center;
      background: #e0c792;
      color: #3b2f20;
      padding: 15px;
      font-size: 28px;
      margin-top: 10px;
    }
    .navbar .container {
      display: flex;
    }

    /* ===== 下拉選單風格 ===== */
    .user-dropdown {
      position: relative;
      display: inline-block;
      margin-left: 20px;
      font-weight: normal;
      color: #4a3a2b;
    }

    .user-dropdown-button {
      background: #fff8ee;
      border: 1.5px solid #d7c2dd;
      border-radius: 12px;
      padding: 6px 12px;
      font-weight: bold;
      cursor: pointer;
      color: #4a3a2b;
      transition: background-color 0.2s ease;
      font-size: 14px;
    }

    .user-dropdown-button:hover {
      background-color: #e0c792;
      color: #3b2f20;
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      top: 110%;
      right: 0;
      background: #fff;
      border: 1.5px solid #d7c2dd;
      border-radius: 12px;
      min-width: 140px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      z-index: 1000;
      font-weight: normal;
    }

    .dropdown-menu a {
      display: block;
      padding: 10px 15px;
      color: #4a3a2b;
      text-decoration: none;
      font-size: 14px;
      border-bottom: 1px solid #d7c2dd;
      transition: background-color 0.2s ease;
    }

    .dropdown-menu a:last-child {
      border-bottom: none;
    }

    .dropdown-menu a:hover {
      background-color: #e0c792;
      color: #3b2f20;
    }

    .user-dropdown.open .dropdown-menu {
      display: block;
    }
    .navbar-left {
    display: flex;
    align-items: center;
    gap: 15px;  /* logo 和標題之間的間距 */
}

/* Logo 樣式 */
.navbar .logo {
    max-height: 40px;
    transform: scale(1.1);
    width: auto;
    flex-shrink: 0;  /* 防止 logo 被壓縮 */
}

/* 右側容器樣式 */
.navbar-right {
    display: flex;
    align-items: center;
    gap: 40px;  /* 各個連結之間的間距 */
}

.navbar-right a {
    padding: 5px 20px;
}

.navbar-right a:hover {
    background-color: #00000022;
    border-radius: 25px;
}

/* 修改網站名稱的連結樣式，移除原有的 margin */
@font-face {
  font-family: "nikumaru";
  src: url('fonts/nikumaru.otf');
}
.navbar .site-name a, .navbar .site-name a span {
    font-family: "nikumaru"!important;
    font-weight: 900!important;
    text-decoration: none;
    margin: 0;  /* 移除 margin-left */
}

/* 修改一般連結樣式，移除原有的 margin */
.navbar a {
    text-decoration: none;
    color: #333;
    font-weight: 900;
    margin: 0;  /* 移除原有的 margin-left: 20px */
}

/* 確保下拉選單的 margin 也被移除 */
.user-dropdown {
    position: relative;
    display: inline-block;
    margin: 0;  /* 移除原有的 margin-left: 20px */
    font-weight: normal;
    color: #4a3a2b;
}
</style>

<div class="navbar">
    <div class="navbar-left">
        <img src="images/logo.png" alt="logo" class="logo">
        <div class="site-name"><a href="home.php">　<span style="color: #6f6146;">無‧名</span>‧<span style="color: #ff5757;">信‧使</span></a></div>
    </div>
    <div class="navbar-right">
        <a href="story.php">故事集</a>
        <a href="resources.php">心理協助資源</a>
        <a href="about.php">關於我們</a>
        <?php if (isset($_SESSION['username'])): ?>
        <div class="user-dropdown">
            <?php
              $stmt_nickname = $pdo->prepare("SELECT nickname FROM users WHERE username = ?");
              $stmt_nickname->execute([$_SESSION['username']]);
              $nickname = $stmt_nickname->fetchColumn();
            ?>
            <button class="user-dropdown-button" onclick="toggleDropdown()">👤 <?= htmlspecialchars(!empty($nickname) ? $nickname : '@' . $_SESSION['username']) ?> ▼</button>
            <div class="dropdown-menu" id="userDropdownMenu">
                <a href="settings.php">帳號設定</a>
                <a href="logout.php">登出</a>
            </div>
        </div>
        <?php else: ?>
        <span style="float:right;">
            <a href="login.php">登入</a>
        </span>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleDropdown() {
  const dropdown = document.querySelector('.user-dropdown');
  dropdown.classList.toggle('open');
}

// 點擊頁面其他地方收起選單
window.addEventListener('click', function(e) {
  const dropdown = document.querySelector('.user-dropdown');
  if (!dropdown) return;
  if (!dropdown.contains(e.target) && dropdown.classList.contains('open')) {
    dropdown.classList.remove('open');
  }
});
</script>
