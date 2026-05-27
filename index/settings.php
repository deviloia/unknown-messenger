<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require("db_connect.php");

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

@$username = $_SESSION['username'];
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("無法連線資料庫：" . $e->getMessage());
}

// —— 1. 處理「修改標籤」的 POST —— 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_tag"])) {
    $story_id = intval($_POST["story_id"]);
    $new_tag  = trim($_POST["tag"]);
    // 撈出該故事的 user_id
    $stmt_chk = $pdo->prepare("SELECT user_id FROM stories WHERE id = ?");
    $stmt_chk->execute([$story_id]);
    $owner_id = $stmt_chk->fetchColumn();
    // 取得當前登入 user_id
    $stmt_uid = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt_uid->execute([$_SESSION["username"]]);
    $curr_uid = $stmt_uid->fetchColumn();
    if ($owner_id && $curr_uid && $owner_id == $curr_uid) {
        $stmt_upd = $pdo->prepare("UPDATE stories SET tag = ? WHERE id = ?");
        $stmt_upd->execute([$new_tag, $story_id]);
        echo "<script>alert('標籤已更新'); window.location.href='settings.php';</script>";
        exit();
    } else {
        echo "<script>alert('沒有修改權限'); window.location.href='settings.php';</script>";
        exit();
    }
}

// —— 2. 處理「刪除故事」與「個人資料／刪除帳號」—— 

// 取得 user_id
$stmt_id = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt_id->execute([$_SESSION['username']]);
$user_id = $stmt_id->fetchColumn();

// 讀取使用者資訊
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 信件刪除處理
if (isset($_GET["delete_story"])) {
    $story_id = intval($_GET["delete_story"]);
    $stmt = $pdo->prepare("DELETE FROM stories WHERE id = ? AND user_id = ?");
    $stmt->execute([$story_id, $user_id]);
    echo "<script>alert('信件已刪除'); location.href='settings.php';</script>";
    exit();
}

// 更新個人資料處理
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["nickname"])) {
    $nickname = $_POST["nickname"];
    // 圖片上傳處理
    if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] == UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES["avatar"]["type"];
        if (!in_array($file_type, $allowed_types)) {
            echo "<script>alert('只允許上傳 JPG, PNG, GIF, WEBP 格式的圖片'); history.back();</script>";
            exit();
        }
        if ($_FILES["avatar"]["size"] > 5 * 1024 * 1024) {
            echo "<script>alert('圖片檔案不能超過 5MB'); history.back();</script>";
            exit();
        }
        $ext = pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION);
        $avatar_name = "avatar_" . $user_id . "_" . time() . "." . $ext;
        $upload_path = $upload_dir . $avatar_name;
        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $upload_path)) {
            if (!empty($user['avatar']) && file_exists($upload_dir . $user['avatar'])) {
                unlink($upload_dir . $user['avatar']);
            }
            $stmt = $pdo->prepare("UPDATE users SET nickname = ?, avatar = ? WHERE id = ?");
            $stmt->execute([$nickname, $avatar_name, $user_id]);
        } else {
            echo "<script>alert('圖片上傳失敗'); history.back();</script>";
            exit();
        }
    } else {
        $stmt = $pdo->prepare("UPDATE users SET nickname = ? WHERE id = ?");
        $stmt->execute([$nickname, $user_id]);
    }
    echo "<script>alert('個人資料已更新'); location.href='settings.php';</script>";
    exit();
}

// 刪除帳號處理
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_account"])) {
    if (!empty($user['avatar']) && file_exists("uploads/" . $user['avatar'])) {
        unlink("uploads/" . $user['avatar']);
    }
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    session_destroy();
    echo "<script>alert('帳號已刪除'); location.href='index.php';</script>";
    exit();
}

// 取得使用者所有故事
$stmt = $pdo->prepare("SELECT id, title, tag FROM stories WHERE user_id = ?");
$stmt->execute([$user_id]);
$stories = $stmt->fetchAll();

// 撈 reaction 統計
$story_ids = array_column($stories, 'id');
$reactions = [];
if ($story_ids) {
    $in  = str_repeat('?,', count($story_ids) - 1) . '?';
    $reaction_sql = "SELECT story_id, emoji_id, COUNT(*) as cnt 
                     FROM reactions 
                     WHERE story_id IN ($in) AND emoji_id IN (1,2,3) 
                     GROUP BY story_id, emoji_id";
    $reaction_stmt = $pdo->prepare($reaction_sql);
    $reaction_stmt->execute($story_ids);
    while ($row = $reaction_stmt->fetch(PDO::FETCH_ASSOC)) {
        $reactions[$row['story_id']][(int)$row['emoji_id']] = (int)$row['cnt'];
    }
}

// AJAX 顯示單篇故事
if (isset($_GET['ajax']) && isset($_GET['story_id'])) {
    $story_id = intval($_GET['story_id']);
    $stmt = $pdo->prepare("SELECT s.*, u.username 
                           FROM stories s 
                           JOIN users u ON s.user_id = u.id 
                           WHERE s.id = ?");
    $stmt->execute([$story_id]);
    $story = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($story) {
        echo "<h2>" . htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') . "</h2>";
        echo "<p>" . nl2br(htmlspecialchars($story['content'], ENT_QUOTES, 'UTF-8')) . "</p>";
        $stmt_replies = $pdo->prepare("
            SELECT r.*, u.username 
            FROM replies r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.story_id = ? 
            ORDER BY r.created_at ASC
        ");
        $stmt_replies->execute([$story_id]);
        $replies = $stmt_replies->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>回覆：</h3>";
        if ($replies) {
            foreach ($replies as $reply) {
                echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>";
                echo nl2br(htmlspecialchars($reply['content'], ENT_QUOTES, 'UTF-8'));
                echo "</div>";
            }
        } else {
            echo "<p>尚無回覆。</p>";
        }
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>帳號設定</title>
    <style>
        body { 
            font-family: Arial; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
        }
        .container { 
            display: flex; 
            margin: 20px; 
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .sidebar {
            width: 200px;
            border-right: 1px solid #ccc;
            padding: 10px;
            background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
        }
        .sidebar button {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 5px;
            background-color: #f9f9f9;
            border: none;
            cursor: pointer;
            text-align: left;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .sidebar button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }
        .sidebar button:hover::before {
            left: 100%;
        }
        .sidebar button:hover {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
        }
        .sidebar button.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .content { 
            flex-grow: 1; 
            padding: 20px; 
            background: white;
        }
        .tab-section { 
            display: none; 
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .tab-section.active { display: block; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        th, td { 
            border: 1px solid #e9ecef; 
            padding: 12px; 
            text-align: center;
        }
        th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        td {
            background: rgba(255,255,255,0.8);
            transition: background 0.3s ease;
        }
        tr:hover td {
            background: rgba(102, 126, 234, 0.1);
        }
        img.avatar-preview {
            width: 80px; height: 80px;
            object-fit: cover;
            border: 3px solid #667eea;
            margin-top: 10px;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s ease;
        }
        img.avatar-preview:hover {
            transform: scale(1.1);
        }
        .reaction-button {
            position: relative;
            display: inline-block;
        }
        .reaction-popup {
            position: absolute;
            background: #fff;
            border: 1px solid #ccc;
            padding: 5px 10px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: none;
            z-index: 1000;
            width: 300%;
        }
        .reaction-popup span {
            margin-right: 10px;
        }
        .reaction-popup .selected {
            cursor: default;
        }
        .mail-operation-button {
            border-radius: 20px;
            border: #333 2px solid;
            padding: 8px 20px;
            font-size: 14px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 2px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .mail-operation-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .profile-header {
            background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.7));
            margin: 20px;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            backdrop-filter: blur(10px);
        }
        .profile-header h2 {
            color: #333;
            margin: 0 0 15px 0;
            font-size: 2em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        input[type="text"], input[type="file"] {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 300px;
            margin: 5px 0;
        }
        input[type="text"]:focus {
            border-color: #667eea;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.3);
            outline: none;
        }
        button[type="submit"] {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        .settings-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .settings-card:hover {
            transform: translateY(-5px);
        }
        .settings-card h3 {
            color: #333;
            margin-top: 0;
            font-size: 1.5em;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
            display: inline-block;
        }
        .file-upload-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .file-upload-preview {
            margin-top: 10px;
            display: none;
        }
        .file-upload-preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
            border: 2px solid #667eea;
        }
    </style>
</head>
<body>

<div class="floating-particles">
    <div class="particle" style="left: 10%; animation-delay: 0s; width: 10px; height: 10px;"></div>
    <div class="particle" style="left: 20%; animation-delay: 1s; width: 8px; height: 8px;"></div>
    <div class="particle" style="left: 30%; animation-delay: 2s; width: 12px; height: 12px;"></div>
    <div class="particle" style="left: 40%; animation-delay: 0.5s; width: 6px; height: 6px;"></div>
    <div class="particle" style="left: 50%; animation-delay: 1.5s; width: 14px; height: 14px;"></div>
    <div class="particle" style="left: 60%; animation-delay: 3s; width: 8px; height: 8px;"></div>
    <div class="particle" style="left: 70%; animation-delay: 2.5s; width: 10px; height: 10px;"></div>
    <div class="particle" style="left: 80%; animation-delay: 4s; width: 12px; height: 12px;"></div>
    <div class="particle" style="left: 90%; animation-delay: 3.5s; width: 6px; height: 6px;"></div>
</div>

<?php include("navbar.php"); ?>

<div class="profile-header">
    <h2>⚙️ 帳號設定</h2>
    <div style="display: flex; align-items: center;">
        <?php if (!empty($user['avatar'])): ?>
            <img src="uploads/<?= htmlspecialchars($user['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="頭像" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-right: 15px; border: 3px solid #667eea; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);">
        <?php else: ?>
            <div style="width: 60px; height: 60px; background: linear-gradient(45deg, #667eea, #764ba2); border-radius: 50%; margin-right: 15px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">👤</div>
        <?php endif; ?>
        <div>
            <div style="font-size: 1.2em; font-weight: bold; color: #333;"><?= htmlspecialchars($user['nickname'] ?? '（未設定暱稱）', ENT_QUOTES, 'UTF-8') ?></div>
            <div style="font-size: 0.9em; color: #667eea;">@<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
</div>

<div class="container">
    <div class="sidebar">
        <button class="tab-btn active" onclick="showTab('profile')">👤 個人資料管理</button>
        <button class="tab-btn" onclick="showTab('messages')">📧 信件管理</button>
        <button class="tab-btn" onclick="showTab('account')">🔧 帳號管理</button>
    </div>

    <div class="content">
        <!-- 個人資料管理 -->
        <div id="profile" class="tab-section active">
            <div class="settings-card">
                <h3>個人資料管理</h3>
                <form method="post" enctype="multipart/form-data">
                    <label>暱稱：<br>
                        <input type="text" name="nickname" value="<?= htmlspecialchars($user['nickname'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </label><br><br>
                    <div class="file-upload-container">
                        <label>上傳頭像（選填）：<br>
                            <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewImage(event)">
                        </label>
                        <div class="file-upload-preview" id="imagePreview">
                            <img id="previewImg" alt="圖片預覽">
                        </div>
                    </div>
                    <?php if (!empty($user['avatar'])): ?>
                        <br><strong>目前頭像：</strong><br>
                        <img class="avatar-preview" src="uploads/<?= htmlspecialchars($user['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="目前頭像">
                    <?php endif; ?>
                    <br><br>
                    <button type="submit">💾 儲存變更</button>
                </form>
            </div>
        </div>

        <!-- 信件管理 -->
        <div id="messages" class="tab-section">
            <div class="settings-card">
                <h3>信件管理</h3>
                <?php if (count($stories) > 0): ?>
                    <table>
                        <tr>
                            <th width="20%;">🆔 ID</th>
                            <th width="40%;">📝 標題</th>
                            <th width="10%;">🏷️ 標籤</th>
                            <th width="30%;">⚡ 操作</th>
                        </tr>
                        <?php foreach ($stories as $story): ?>
                        <?php
                            $count_heart = $reactions[$story['id']][1] ?? 0;
                            $count_sad   = $reactions[$story['id']][2] ?? 0;
                            $count_laugh = $reactions[$story['id']][3] ?? 0;
                            
                            $user_reaction = 0;
                            $stmt_u = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                            $stmt_u->execute([$_SESSION['username']]);
                            $uid = $stmt_u->fetchColumn();
                            if ($uid) {
                                $stmt_r = $pdo->prepare("SELECT emoji_id FROM reactions WHERE story_id = ? AND user_id = ?");
                                $stmt_r->execute([$story['id'], $uid]);
                                $user_reaction = $stmt_r->fetchColumn() ?: 0;
                            }
                        ?>
                        <tr>
                            <td><?= $story['id'] ?></td>
                            <td><?= htmlspecialchars($story['title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" style="display: inline-block;">
                                    <input 
                                        type="text" 
                                        name="tag" 
                                        value="<?= htmlspecialchars($story['tag'], ENT_QUOTES, 'UTF-8') ?>" 
                                        style="width: 80px; padding: 2px; font-size: 0.9em;"
                                    >
                                    <input type="hidden" name="story_id" value="<?= $story['id'] ?>">
                                    <button 
                                        type="submit" 
                                        name="update_tag" 
                                        style="padding: 2px 6px; font-size: 0.8em; margin-left: 4px;"
                                    >修改</button>
                                </form>
                            </td>
                            <td>
                                <button onclick="openStoryModal(<?= $story['id'] ?>)" class="mail-operation-button">👁️ 查看</button>
                                <span class="reaction-button">
                                    <button type="button" onclick="toggleReactionPopup(this)" class="mail-operation-button">😊 心情</button>
                                    <span class="reaction-popup">
                                        <span class="selected">❤️ <span class="count"><?= $count_heart ?></span></span>
                                        <span class="selected">😢 <span class="count"><?= $count_sad ?></span></span>
                                        <span class="selected">😂 <span class="count"><?= $count_laugh ?></span></span>
                                    </span>
                                </span>
                                <a 
                                  href="?delete_story=<?= $story['id'] ?>" 
                                  onclick="return confirm('確定刪除這封信？')" 
                                  class="mail-operation-button" 
                                  style="background: linear-gradient(45deg, #ff6b6b, #ee5a52); text-decoration: none; display: inline-block;"
                                >🗑️ 刪除</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 50px; color: #666;">
                        <div style="font-size: 3em; margin-bottom: 20px;">📭</div>
                        <p style="font-size: 1.2em;">你尚未發表任何信件。</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 帳號管理 -->
        <div id="account" class="tab-section">
            <div class="settings-card">
                <h3>帳號管理</h3>
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <strong>⚠️ 危險區域</strong><br>
                    刪除帳號將永久移除所有資料，此操作無法復原！
                </div>
                <form method="post" onsubmit="return confirm('你確定要刪除帳號嗎？此操作無法復原！');">
                    <input type="hidden" name="delete_account" value="1">
                    <button type="submit" style="background: linear-gradient(45deg, #ff6b6b, #ee5a52); color: white; border: none; padding: 12px 30px; border-radius: 25px; cursor: pointer; font-size: 16px;">❌ 刪除我的帳號</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(id) {
    document.querySelectorAll('.tab-section').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    event.target.classList.add('active');
}

// 圖片上傳預覽功能
function previewImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
}

function openStoryModal(storyId) {
    fetch('settings.php?ajax=1&story_id=' + storyId)
        .then(response => response.text())
        .then(html => {
            document.getElementById("modalContent").innerHTML = html;
            document.getElementById("storyModal").style.display = "block";
        });
}
function closeModal() {
    document.getElementById("storyModal").style.display = "none";
    document.getElementById("modalContent").innerHTML = "";
}

function toggleReactionPopup(button) {
    const popup = button.nextElementSibling;
    const isVisible = popup.style.display === "block";
    document.querySelectorAll('.reaction-popup').forEach(p => p.style.display = 'none');
    if (!isVisible) {
        popup.style.display = "block";
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.matches('.reaction-button button')) {
        document.querySelectorAll('.reaction-popup').forEach(p => p.style.display = 'none');
    }
});

// 動態粒子效果
function createParticle() {
    const particle = document.createElement('div');
    particle.className = 'particle';
    particle.style.left = Math.random() * 100 + '%';
    particle.style.top = Math.random() * 100 + '%';
    particle.style.width = Math.random() * 10 + 5 + 'px';
    particle.style.height = particle.style.width;
    particle.style.animationDelay = Math.random() * 6 + 's';
    document.querySelector('.floating-particles').appendChild(particle);
    
    setTimeout(() => {
        particle.remove();
    }, 6000);
}

setInterval(createParticle, 2000);
</script>

<!-- Modal -->
<div id="storyModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div id="modalContent"></div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(5px);
}
.modal-content {
    background: #fff;
    color: black;
    margin: 5% auto;
    padding: 30px;
    width: 60%;
    max-height: 80%;
    overflow-y: auto;
    border-radius: 15px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.3);
    animation: slideIn 0.3s ease;
}
@keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.close {
    float: right;
    cursor: pointer;
    color: #ff6b6b;
    font-size: 28px;
    font-weight: bold;
    transition: color 0.3s ease;
}
.close:hover {
    color: #ee5a52;
}
</style>

</body>
</html>
