<?php
session_start();
require("db_connect.php");

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("無法連線資料庫：" . $e->getMessage());
}

// 登出處理
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: story.php');
    exit;
}

// 處理 POST 請求（AJAX 評價功能）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    // 沒登入就拒絕
    if (!isset($_SESSION['username'])) {
        echo json_encode(['error' => '請先登入']);
        exit;
    }

    // 抓 user_id
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $user_id = $stmt->fetchColumn();
    if (!$user_id) {
        echo json_encode(['error' => '使用者不存在']);
        exit;
    }

    $story_id = isset($_POST['story_id']) ? intval($_POST['story_id']) : 0;
    $emoji_id = isset($_POST['emoji']) ? intval($_POST['emoji']) : 0;

    if ($story_id < 0 || !in_array($emoji_id, [0, 1, 2, 3])) {
        echo json_encode(['error' => '參數錯誤']);
        exit;
    }

    // 寫入評價，若已存在則更新
    if ($emoji_id === 0) {
    // 如果點擊的是已選表情，則設為0（取消）
    $sql = "UPDATE reactions SET emoji_id = 0 WHERE user_id = ? AND story_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $story_id]);
} else {
    // 否則照原本邏輯寫入或更新
    $sql = "INSERT INTO reactions (user_id, story_id, emoji_id) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE emoji_id = VALUES(emoji_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $story_id, $emoji_id]);
}


    // 重新統計該故事的評價數量
    $count_sql = "SELECT emoji_id, COUNT(*) AS cnt 
                  FROM reactions 
                  WHERE story_id = ? AND emoji_id IN (1,2,3) 
                  GROUP BY emoji_id";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute([$story_id]);
    $counts = [1 => 0, 2 => 0, 3 => 0];
    while ($row = $count_stmt->fetch(PDO::FETCH_ASSOC)) {
        $counts[(int)$row['emoji_id']] = (int)$row['cnt'];
    }

    echo json_encode($counts);
    exit;
}


// GET 請求部分

// 篩選參數
$filter_tag = isset($_GET['tag']) ? $_GET['tag'] : null;

// 分頁參數
$allowed_per_page = [10, 20, 50];
$per_page = isset($_GET['per_page']) && in_array(intval($_GET['per_page']), $allowed_per_page) ? intval($_GET['per_page']) : 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $per_page;

// 計算總筆數
$count_sql = "SELECT COUNT(*) FROM stories WHERE status = 'answered'" . ($filter_tag ? " AND tag = :tag" : "");
$count_stmt = $pdo->prepare($count_sql);
if ($filter_tag) {
    $count_stmt->bindValue(':tag', $filter_tag);
}
$count_stmt->execute();
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// 撈故事
$story_sql = "SELECT * FROM stories WHERE status = 'answered'" . ($filter_tag ? " AND tag = :tag" : "") . " ORDER BY created_at DESC LIMIT :start, :per_page";
$stmt = $pdo->prepare($story_sql);
if ($filter_tag) {
    $stmt->bindValue(':tag', $filter_tag);
}
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$stmt->execute();
$stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 撈標籤
$tags = $pdo->query("SELECT DISTINCT tag FROM stories WHERE status = 'answered'")->fetchAll(PDO::FETCH_COLUMN);

// 撈該頁故事的 reactions 統計
$story_ids = array_column($stories, 'id');
$reactions = [];
if ($story_ids) {
    $in  = str_repeat('?,', count($story_ids) - 1) . '?';
    $reaction_sql = "SELECT story_id, emoji_id, COUNT(*) as cnt FROM reactions WHERE story_id IN ($in) AND emoji_id IN (1,2,3) GROUP BY story_id, emoji_id";
    $reaction_stmt = $pdo->prepare($reaction_sql);
    $reaction_stmt->execute($story_ids);
    while ($row = $reaction_stmt->fetch(PDO::FETCH_ASSOC)) {
        $reactions[$row['story_id']][(int)$row['emoji_id']] = (int)$row['cnt'];
    }
}

if (isset($_GET['ajax']) && isset($_GET['story_id'])) {
    $story_id = intval($_GET['story_id']);
    $stmt = $pdo->prepare("SELECT s.*, u.username FROM stories s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $stmt->execute([$story_id]);
    $story = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($story) {
        echo "<h2>" . htmlspecialchars($story['title']) . "</h2>";
        echo "<p>" . nl2br(htmlspecialchars($story['content'])) . "</p>";

        // 顯示回覆
        $stmt_replies = $pdo->prepare("SELECT r.*, u.username FROM replies r JOIN users u ON r.user_id = u.id WHERE r.story_id = ? ORDER BY r.created_at ASC");
        $stmt_replies->execute([$story_id]);
        $replies = $stmt_replies->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>回覆：</h3>";
        if ($replies) {
            foreach ($replies as $reply) {
                echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>";
                echo nl2br(htmlspecialchars($reply['content']));
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
<html lang="zh-TW">
<head>
  <meta charset="UTF-8" />
  <title>溫暖故事集</title>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC&display=swap" rel="stylesheet" />
  <style>
    /* 省略重複 CSS，與你提供的一致 */
    * { box-sizing: border-box; }
    body {
      background: #f6f1e7;
      margin: 0;
      padding: 0;
      color: #4a3a2b;
    }
    .title-banner {
      text-align: center;
      background: #e0c792;
      color: #3b2f20;
      padding: 15px;
      font-size: 28px;
      margin-top: 10px;
    }
    .container {
      display: flex;
    }
    .tag-sidebar {
      width: 200px;
      background: #fff8ee;
      padding: 20px;
      border-right: 2px solid #d7c2dd;
    }
    .tag-sidebar h3 {
      margin-bottom: 10px;
      font-size: 18px;
    }
    .tag-sidebar ul {
      list-style: none;
      padding: 0;
    }
    .tag-sidebar li {
      margin-bottom: 10px;
    }
    .tag-sidebar a {
      text-decoration: none;
      color: #4a3a2b;
      font-weight: bold;
    }
    .main-content {
      flex: 1;
      padding: 20px;
    }
    .filter-form {
      text-align: center;
      margin-bottom: 15px;
    }
    .story-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 25px;
    }
    .envelope {
      background: #fff;
      border: 2px solid #ccc;
      width: 400px;
      padding: 25px;
      border-radius: 16px;
      position: relative;
      transition: all 0.3s ease-in-out;
      cursor: pointer;
      overflow: hidden;
    }
    .envelope:hover {
      transform: scale(1.03);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .letter-content {
      display: none;
      margin-top: 15px;
      font-size: 16px;
      line-height: 1.6;
    }
    .envelope.open .letter-content {
      display: block;
    }
    .story-title {
      font-size: 20px;
      font-weight: bold;
    }
    .tags {
      margin-top: 10px;
    }
    .tag {
      background: #d0c3ae;
      border-radius: 10px;
      padding: 5px 10px;
      margin-right: 5px;
      font-size: 12px;
      display: inline-block;
    }
    .pagination {
      text-align: center;
      margin: 20px;
    }
    .pagination a {
      margin: 0 5px;
      text-decoration: none;
      font-weight: bold;
      color: #6c4f3d;
    }
    .pagination a.active {
      color: red;
    }
    .reaction-bar {
      margin-top: 12px;
    }
    .reaction-bar button {
      background: none;
      border: none;
      font-size: 18px;
      cursor: pointer;
      margin-right: 10px;
      color: #4a3a2b;
    }
    .reaction-bar button:hover {
      transform: scale(1.2);
    }
    button.disabled {
  opacity: 0.5;
  cursor: not-allowed;
  pointer-events: auto; /* 確保點擊事件仍然觸發 */
}
.reaction-bar button.unselected {
  filter: grayscale(100%);
  opacity: 0.5;
}
.reaction-bar button.selected {
  filter: none;
  opacity: 1;
  
}
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

<div class="title-banner">故事集</div>

<div class="container">
  <div class="tag-sidebar">
    <h3>分類標籤</h3>
    <ul>
      <li><a href="story.php">全部</a></li>
      <?php foreach ($tags as $tag): ?>
        <li><a href="?tag=<?= urlencode($tag) ?>"><?= htmlspecialchars($tag) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="main-content">
    <div class="filter-form">
      <form method="get" id="filterForm">
        每頁顯示筆數：
        <select name="per_page" onchange="document.getElementById('filterForm').submit()">
          <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
          <option value="20" <?= $per_page == 20 ? 'selected' : '' ?>>20</option>
          <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
        </select>
        <?php if ($filter_tag): ?>
          <input type="hidden" name="tag" value="<?= htmlspecialchars($filter_tag) ?>">
        <?php endif; ?>
        <input type="hidden" name="page" value="1">
      </form>
    </div>

    <div class="story-grid">
    <?php foreach ($stories as $story): ?>
      <div class="envelope" onclick="openModal(<?= $story['id'] ?>)">
        <div class="story-title"><?= htmlspecialchars($story['title']) ?></div>
        <div class="tags">
          <span class="tag"><?= htmlspecialchars($story['tag'] ?? '無分類') ?></span>
          <span class="tag"><?= date("Y-m-d", strtotime($story['created_at'])) ?></span>
        </div>
        <div class="letter-content"><?= nl2br(htmlspecialchars($story['content'])) ?></div>
        <div class="reaction-bar" data-story-id="<?= $story['id'] ?>">
          <?php
            $count_heart = $reactions[$story['id']][1] ?? 0;
            $count_sad = $reactions[$story['id']][2] ?? 0;
            $count_laugh = $reactions[$story['id']][3] ?? 0;
          ?>
          <?php
          $user_reaction = 0;
          if (isset($_SESSION['username'])) {
              $stmt_user = $pdo->prepare("SELECT id FROM users WHERE username = ?");
              $stmt_user->execute([$_SESSION['username']]);
              $user_id = $stmt_user->fetchColumn();

              if ($user_id) {
                  $stmt_react = $pdo->prepare("SELECT emoji_id FROM reactions WHERE story_id = ? AND user_id = ?");
                  $stmt_react->execute([$story['id'], $user_id]);
                  $user_reaction = $stmt_react->fetchColumn() ?: 0;
              }
          }

          $selected_class_1 = ($user_reaction == 1) ? 'selected' : 'unselected';
          $selected_class_2 = ($user_reaction == 2) ? 'selected' : 'unselected';
          $selected_class_3 = ($user_reaction == 3) ? 'selected' : 'unselected';
          ?>
          <button onclick="event.stopPropagation(); react(this, 1)" class="<?= $selected_class_1 ?>">❤️ <span class="count count-heart"><?= $count_heart ?? 0 ?></span></button>
          <button onclick="event.stopPropagation(); react(this, 2)" class="<?= $selected_class_2 ?>">😢 <span class="count count-sad"><?= $count_sad ?? 0 ?></span></button>
          <button onclick="event.stopPropagation(); react(this, 3)" class="<?= $selected_class_3 ?>">😂 <span class="count count-laugh"><?= $count_laugh ?? 0 ?></span></button>

        </div>
      </div>
    <?php endforeach; ?>
  </div>

    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>&per_page=<?= $per_page ?><?= $filter_tag ? '&tag=' . urlencode($filter_tag) : '' ?>">上一頁</a>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?= $i ?>&per_page=<?= $per_page ?><?= $filter_tag ? '&tag=' . urlencode($filter_tag) : '' ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
      <?php endfor; ?>

      <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?>&per_page=<?= $per_page ?><?= $filter_tag ? '&tag=' . urlencode($filter_tag) : '' ?>">下一頁</a>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- Modal 結構 -->
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
  }
  .modal-content {
    background: #fff;
    color: black;
    margin: 5% auto;
    padding: 20px;
    width: 60%;
    max-height: 80%;
    overflow-y: auto;
    border-radius: 10px;
  }
  .close {
    float: right;
    cursor: pointer;
    color: red;
    font-size: 20px;
  }
</style>

<script>
  function openModal(storyId) {
    fetch("story.php?ajax=1&story_id=" + storyId)
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

</script>

<script>
function react(button, emoji) {
    const reactionBar = button.closest('.reaction-bar');
    const storyId = reactionBar.dataset.storyId;
    const isSelected = button.classList.contains('selected');
    const emojiId = isSelected ? 0 : emoji;

    fetch('story.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `story_id=${storyId}&emoji=${emojiId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            // 更新reaction-bar的數量與樣式
            const counts = {
                1: data[1] ?? 0,
                2: data[2] ?? 0,
                3: data[3] ?? 0
            };
            reactionBar.querySelector('.count-heart').innerText = counts[1];
            reactionBar.querySelector('.count-sad').innerText = counts[2];
            reactionBar.querySelector('.count-laugh').innerText = counts[3];

            // 清除所有selected
            reactionBar.querySelectorAll('button').forEach(btn => {
                btn.classList.remove('selected');
                btn.classList.add('unselected');
            });
            // 如果emojiId不為0則加上selected
            if (emojiId !== 0) {
                button.classList.remove('unselected');
                button.classList.add('selected');
            }
        }
    })
    .catch(error => console.error(error));
}

</script>
</body>
</html>
