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

// 取得使用者 ID
$stmt_user = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt_user->execute([$username]);
$user_id = $stmt_user->fetchColumn();

// 獲取多封未被回覆的信件
$stmt = $pdo->prepare("SELECT id, title, content, tag, status FROM stories 
                       WHERE status='unassigned' AND user_id != ? 
                       ORDER BY RAND() LIMIT 3");
$stmt->execute([$user_id]);
$stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($stories) {
    foreach ($stories as $story) {
        $stmt_update = $pdo->prepare("UPDATE stories SET status = 'assigned' WHERE id = ?");
        $stmt_update->execute([$story['id']]);
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>無名信使</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            overflow: hidden;
        }
        body {
            background-image: url('images/beach.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            color: black;
            overflow: auto;
        }
        #background-beach {
            background-image: url('images/beach_wave.png');
            position: fixed;
            top: -50%; left: 0;
            width: 150%; height: 150%;
            background-size: cover;
            background-position: center;
            z-index: -1;
            animation: drift 40s cubic-bezier(0.6, 0, 0.4, 1) infinite alternate;
        }
        @keyframes drift {
            0%   { transform: translateY(0%); opacity: 0; }
            20%  { transform: translateY(20%); opacity: 0.5; }
            50%  { transform: translateY(-6%); opacity: 0; }
            80%  { transform: translateY(3%); opacity: 0.25; }
            90%  { transform: translateY(-1%); opacity: 0; }
            100% { transform: translateY(0%); opacity: 0; }
        }

        /* 主要內容容器 */
        .main-container {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 10;
        }

        .message-item {
            position: absolute;
            transition: all 0.4s ease;
            cursor: pointer;
            opacity: 0;
            width: 400px;
            height: 400px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            filter: drop-shadow(0 12px 24px rgba(255, 175, 54, 0.3));
            bottom: 7vh;
            right: 7vh;
            background-image: url('images/seat.png');
            z-index: 10;
            overflow: visible;
        }

        .message-item:hover {
            transform: scale(1.1) rotate(2deg);
            filter: drop-shadow(0 12px 48px rgba(255, 255, 191, 0.5)) brightness(1.25);
        }

        #send {
            padding: 3px 25px 0 25px;
            font-family: "nikumaru"!important;
            font-size: 48px;
            font-weight: 900;
            color: white;
            background-color: #ff5757;
            filter: drop-shadow(0 0 5px rgba(0, 0, 0, 0.3));
        }

        /* 信件項目樣式 */
        .letter-item {
            background: rgba(255, 255, 255, 0.9);
            margin: 15px 0;
            padding: 20px;
            border-radius: 15px;
            border-left: 4px solid #3498db;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease, opacity 1s ease;
            position: absolute;
            color: #2c3e50;
            z-index: 999;
            width: 400px;
            opacity: 1;
        }

        .letter-item:hover {
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.2);
        }

        .letter-item.selected {
            background: rgba(52, 152, 219, 0.1);
            border-left-color: #e74c3c;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .letter-item h4, .selected-letter-content h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .letter-item .letter-tag, .selected-letter-content .letter-tag {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .letter-item .letter-preview {
            color: #7f8c8d;
            font-size: 14px;
            line-height: 1.4;
            max-height: 60px;
            overflow: hidden;
            position: relative;
        }

        .letter-item .letter-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .letter-item .letter-actions button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .letter-item .letter-actions button:hover {
            background: linear-gradient(135deg, #2980b9, #1f618d);
            transform: translateY(-1px);
        }

        .letter-item .letter-actions .drift-btn {
            background: linear-gradient(135deg, #e67e22, #d35400) !important;
        }

        .letter-item .letter-actions .drift-btn:hover {
            background: linear-gradient(135deg, #d35400, #c0392b) !important;
        }

        /* 無信件提示樣式 */
        .no-letters {
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            border: 2px dashed rgba(255, 255, 255, 0.3);
            color: white;
        }

        .no-letters::before {
            content: '📪';
            font-size: 48px;
            display: block;
            margin-bottom: 15px;
        }

        /* 模態框樣式 */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(240, 248, 255, 0.9));
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #2c3e50;
            margin: 50px auto;
            padding: 40px;
            width: 90%;
            max-width: 900px;
            max-height: 85vh;
            overflow-y: auto;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.4s ease-out;
            position: relative;
        }

        .modal-content h3 {
            font-size: 28px;
            color: #34495e;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .modal-content h3::before {
            content: '✉️';
            font-size: 32px;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            cursor: pointer;
            color: #e74c3c;
            font-size: 28px;
            font-weight: bold;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(231, 76, 60, 0.1);
            transition: all 0.3s ease;
        }

        .close:hover {
            background: rgba(231, 76, 60, 0.2);
        }

        /* 表單樣式美化 */
        .modal-content label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .modal-content input[type="text"],
        .modal-content textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid rgba(52, 152, 219, 0.3);
            border-radius: 12px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .modal-content input[type="text"]:focus,
        .modal-content textarea:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.2);
            transform: translateY(-2px);
        }

        .modal-content textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        .modal-content button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
            margin-right: 10px;
            min-width: 120px;
        }

        .modal-content button:hover {
            background: linear-gradient(135deg, #2980b9, #1f618d);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(52, 152, 219, 0.4);
        }

        .selected-letter-content {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #f39c12;
        }

        /* 背景裝飾元素 */
        .background-decoration {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-text {
            position: absolute;
            font-size: 18px;
            color: rgba(0, 0, 0, 0.6);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            animation: float 6s ease-in-out infinite;
            font-weight: 300;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 6px 12px;
            opacity: 0.8;
        }

        .floating-text:nth-child(1) { top: 85%; left: 5%; animation-delay: 0s; }
        .floating-text:nth-child(2) { top: 80%; left: 45%; animation-delay: 3s; }
        .floating-text:nth-child(3) { top: 70%; left: 25%; animation-delay: 6s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(3deg); }
        }

        .boat {
            position: absolute;
            top: 45%;
            left: 45%;
            width: 400px;       /* 自行調整大小 */
            height: 400px;      /* 自行調整大小 */
            background: url('images/boat.png') no-repeat center center;
            background-size: contain;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 104;         /* 放在 center-quote 下面（z-index:5） */
            filter: drop-shadow(0 -8px 16px rgba(0, 0, 0, 0.5));
        }
        
        .center-quote {
            position: absolute;
            top: calc(45% - 16px);
            left: 45%;
            text-align: center;
            z-index: 105;
            pointer-events: none;
            width: 100vw;
            padding: 20px;
            white-space: normal;
            overflow: visible;
        }

        .quote-text {
            font-size: 32px;
            color: white;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 1);
            font-weight: 700;
            margin-bottom: 15px;
            animation: pulse 4s ease-in-out infinite;
            line-height: 1.2;
            z-index: 106;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.6; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 1; transform: translate(-50%, -50%) scale(1.05); }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 響應式設計 */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .letters-panel {
                width: 100%;
                max-height: 50vh;
            }
            
            .interaction-panel {
                width: 100%;
                height: 50vh;
            }
            
            .message-item {
                width: 80px;
                height: 80px;
                font-size: 12px;
            }
            
            #send {
                bottom: 20%;
                right: 20%;
            }
            
            #receive {
                top: 20%;
                right: 20%;
            }
        }

        .bottle {
            opacity: 0.85;
            position: absolute;
            background: url('images/bottle.png') no-repeat center center;
            background-size: contain;
            cursor: pointer;
            animation: drift-bottle 40s cubic-bezier(0.4, 0, 0.6, 1) infinite alternate;
            transition: left 120s ease-in-out, filter 0.3s ease-in-out, opacity 1s ease;
            z-index: 100; /* 加高層級確保在其他元素上層 */
            width: 90px;
            height: 90px;
            filter: drop-shadow(0 -8px 16px rgba(0, 0, 0, 0.5));
        }
        @keyframes drift-bottle {
            0%   { transform: translateY(0vh); }
            20%  { transform: translateY(3vh); }
            50%  { transform: translateY(-2vh); }
            80%  { transform: translateY(1vh); }
            90%  { transform: translateY(-0.5vh); }
            100% { transform: translateY(0vh); }
        }

        .letter-item .close-btn {
            position: absolute;
            top: 0;
            right: 0;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 24px;
            font-weight: 900;
            color: #888;
        }
        
        .message-item .mail-animation {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 200px;
            height: 200px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            transform: translate(0, 0) scale(0) rotate(0deg);
            opacity: 0;
            transition: transform 0.5s ease, opacity 0.5s ease;
            z-index: 9;
            pointer-events: none;
            overflow: visible;
        }
    </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

   <div id="background-beach"></div>

  <!-- 背景裝飾元素 -->
  <div class="background-decoration">
    <div class="floating-text">「文字的海洋漂流」</div>
    <div class="floating-text">「心靈的秘密花園」</div>
    <div class="floating-text">「字裡行間的邂逅」</div>
  </div>

    <div class="letters-panel" style="display: none;">
        <?php if ($stories): ?>
            <?php foreach ($stories as $story): ?>
                <div class="letter-item" data-id="<?= $story['id'] ?>">
                <h4><?= htmlspecialchars($story['title']) ?></h4>
                <?php if ($story['tag']): ?>
                    <span class="letter-tag"><?= htmlspecialchars($story['tag']) ?></span>
                <?php endif; ?>
                <div class="letter-preview">
                    <?= htmlspecialchars(mb_substr($story['content'], 0, 20)) ?><?= mb_strlen($story['content']) > 20 ? '……' : '' ?>
                </div>
                <div class="letter-actions">
                    <button onclick="openReplyModal(<?= $story['id'] ?>)">💝 回覆</button>
                    <button class="drift-btn" onclick="event.stopPropagation(); alert('此信件將繼續在海洋中漂流。'); removeLetter(<?= $story['id'] ?>);">🌊 漂流</button>
                </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="bottle-container">
    </div>

    <!-- 右側互動區域 -->
    <div class="main-container">
      <!-- 中央引言 -->
      <div class="boat"></div>
      <div class="center-quote">
        <div class="quote-text">在文字的海洋中，每個人都是匿名的詩人。</div>
      </div>
      <a href="#" onclick="openModal('sendModal'); document.getElementById('title').value = ''; document.getElementById('tag').value = ''; document.getElementById('content').value = '';"><div class="message-item"><span id="send">寄信</span></div></a>
    </div>
  </div>

  <!-- 寄送信件 Modal -->
  <div id="sendModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('sendModal')">&times;</span>
      <h3>寄出信件</h3>
      <form method="POST" action="send.php" id="send-form">
        <label>標題</label>
        <input type="text" name="title" id="title" required placeholder="為您的信件取個標題……">
        
        <label>標籤</label>
        <input type="text" name="tag" id="tag" required placeholder="添加標籤，讓合適的人找到您……">
        
        <label>內容</label>
        <textarea style="resize: none;" name="content" id="content" rows="8" required placeholder="在這裡寫下您想分享的故事、心情或想法……"></textarea>
        
        <div style="text-align: center;">
          <button id="send-btn" type="button">📮 確認寄出</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 回覆信件 Modal -->
  <div id="replyModal" class="modal reply-modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('replyModal')">&times;</span>
      <h3>回覆信件</h3>
      
      <div class="selected-letter-content" id="selectedLetterContent">
        <!-- 選中的信件內容會顯示在這裡 -->
      </div>
      
      <form method="POST" action="receive.php" id="replyForm">
        <input type="hidden" name="story_id" id="selectedStoryId" value="">
        <label>回覆</label>
        <textarea style="resize: none;" name="reply" id="reply" data-id="" rows="10" required placeholder="寫下您的回應，與這位陌生朋友分享您的想法……"></textarea>
        
        <div style="text-align: center;">
          <button type="button" onclick="replyLetter(document.querySelector(`#reply`).dataset.id)">💝 確認回覆</button>
        </div>
      </form>
    </div>
  </div>

<script>
  const stories = <?= json_encode($stories) ?>;

  $(document).ready(function() {
    $(".message-item").each(function(index) {
      $(this).delay(index * 500).animate({ opacity: 1 }, 250);
    });

    // 讓中央引言每10秒變換一次
    const quotes = [
      { text: "在文字的海洋中，每個人都是匿名的詩人。" },
      { text: "一封信，一個世界，一份未知的溫暖。" },
      { text: "匿名不是冷漠，而是最純粹的真誠。" },
      { text: "每個故事都值得被聆聽，每顆心都渴望被理解。" }
    ];
    
    let currentQuote = 0;
    setInterval(() => {
      $('.quote-text').fadeOut(1000, function() {
        currentQuote = (currentQuote + 1) % quotes.length;
        $('.quote-text').text(quotes[currentQuote].text);
        $('.quote-text').fadeIn(1000);
      });
    }, 10000);
  });

  function openModal(id) { 
    document.getElementById(id).style.display = 'block'; 
  }
  
  function closeModal(id) { 
    document.getElementById(id).style.display = 'none';
  }

  function openReplyModal(storyId) {
    // 找到對應的故事
    const story = stories.find(s => s.id == storyId);
    if (!story) return;
    
    // 更新隱藏的表單字段
    document.getElementById('selectedStoryId').value = storyId;
    
    // 顯示完整內容
    const contentHtml = `
      <h4>${story.title}</h4>
      ${story.tag ? `<span class="letter-tag">${story.tag}</span>` : ''}
      <p>${story.content.replace(/\n/g, '<br>')}</p>
    `;
    document.getElementById('selectedLetterContent').innerHTML = contentHtml;
    document.querySelector(`#reply`).value = '';
    document.querySelector(`#reply`).dataset.id = storyId;
    
    // 顯示回覆模態框
    openModal('replyModal');
  }

  // 點擊模態框外部關閉
  window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
      event.target.style.display = 'none';
    }
  }
</script>

<script>
document.getElementById('send-btn').addEventListener('click', function(e) {
    e.preventDefault();  // 防止預設提交
    const form = document.getElementById('send-form');
    fetch('send.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("信件寄出成功。");
            closeModal('sendModal');
            form.reset();
        } else {
            alert('寄出失敗：' + (data.error || '未知錯誤'));
        }
    })
    .catch(error => {
        console.error('錯誤:', error);
        alert('寄出失敗，請稍後再試！');
    });
});

function replyLetter(storyId) {
    const replyContent = document.querySelector(`#reply`).value;
    const formData = new FormData();
    formData.append('story_id', storyId);
    formData.append('reply', replyContent);

    fetch('receive.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("信件回覆成功。");
            closeModal('replyModal');
            removeLetter(storyId);
        } else {
            alert(data.error || '發生錯誤');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}


function removeLetter(storyId) {
    // 找到 bottle
    const bottleElement = document.querySelector('.bottle[data-id="' + storyId + '"]');
    bottleElement.style.opacity = '0';
    bottleElement.style.pointerEvents = 'none';

    // 找到 popup
    const popup = document.querySelector('.letter-item[data-id="' + storyId + '"]');
    popup.style.opacity = '0';
    popup.style.pointerEvents = 'none';
    setTimeout(() => { popup.remove() }, 1000);
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.message-item').forEach(item => {
        // 建立 mailAnimation 元素
        const mailAnimation = document.createElement('div');
        mailAnimation.classList.add('mail-animation');
        item.appendChild(mailAnimation);

        item.addEventListener('mouseenter', () => {
        const randomIndex = Math.floor(Math.random() * 3) + 1;
        mailAnimation.style.backgroundImage = `url('images/mail${randomIndex}.png')`;
        mailAnimation.style.transform = 'translate(-200px, -200px) scale(1) rotate(330deg)';
        mailAnimation.style.opacity = '1';
        });

        item.addEventListener('mouseleave', () => {
        mailAnimation.style.transform = 'translate(0, 0) scale(0) rotate(0deg)';
        mailAnimation.style.opacity = '0';
        });
    });

  const letters = document.querySelectorAll('.letters-panel .letter-item');
  const container = document.getElementById('bottle-container');
  const usedLefts = [];
  const minDistance = 10; // 最小相隔距離（單位：vw百分比）

  letters.forEach((letter) => {
    const bottle = document.createElement('div');
    bottle.classList.add('bottle');
    bottle.dataset.id = letter.dataset.id; // storyId 是對應信件的 id

    // 生成初始 left（避免太近）
    let newLeft;
    let attempt = 0;
    do {
      newLeft = 5 + Math.random() * 95;
      attempt++;
    } while (usedLefts.some(existingLeft => Math.abs(existingLeft - newLeft) < minDistance) && attempt < 10);
    usedLefts.push(newLeft);

    const top = 10 + Math.random() * 25;
    bottle.style.left = `${newLeft}vw`;
    bottle.style.top = `${top}vh`;

    // 設定海浪漂浮動畫的延遲（0~40秒）
    const randomDelay = Math.random() * 10;
    bottle.style.animationDelay = `${randomDelay}s`;

    // 左右移動初始延遲（0~5秒）
    const initialDelay = Math.random() * 5000;
    setTimeout(() => {
      setInterval(() => {
        let newLeft2;
        let attempt2 = 0;
        do {
          newLeft2 = 5 + Math.random() * 90;
          attempt2++;
        } while (usedLefts.some(existingLeft => Math.abs(existingLeft - newLeft2) < minDistance) && attempt2 < 15);
        bottle.style.left = `${newLeft2}vw`;
      }, 5000);
    }, initialDelay);

    // 生成 letter-item
    const popup = letter.cloneNode(true);
    popup.style.display = 'none';

    // 加入右上角 X 按鈕
    const closeBtn = document.createElement('div');
    closeBtn.classList.add('close-btn');
    closeBtn.textContent = '×';
    closeBtn.onclick = () => {
        popup.style.display = 'none';
        bottle.style.filter = 'drop-shadow(0 -8px 16px rgba(0, 0, 0, 0.5))'; // 恢復正常陰影
    };
    popup.appendChild(closeBtn);

    // 點擊瓶子彈出信件
    bottle.addEventListener('click', function(e) {
        e.stopPropagation();
        
        const isPopupOpen = popup.style.display === 'block';

        if (isPopupOpen) {
            // 關閉 popup
            popup.style.display = 'none';
            bottle.style.filter = 'drop-shadow(0 -8px 16px rgba(0, 0, 0, 0.5))'; // 恢復預設陰影
        } else {
            // 關閉所有 popup
            document.querySelectorAll('.letter-item').forEach(item => item.style.display = 'none');
            // 恢復所有瓶子的 filter
            document.querySelectorAll('.bottle').forEach(b => {
            b.style.filter = 'drop-shadow(0 -8px 16px rgba(0, 0, 0, 0.5))'; // 預設陰影
            });

            // 開啟對應的 popup
            popup.style.display = 'block';
            // 瓶子持續亮著
            bottle.style.filter = 'drop-shadow(0 -8px 16px rgba(255, 255, 127, 1))';
        }
    });

    container.appendChild(bottle);
    container.appendChild(popup);

    // 讓 letter-item 跟隨瓶子移動
    function followBottle() {
        const rect = bottle.getBoundingClientRect();
        const popupWidth = popup.offsetWidth || 400; // 預設寬度
        let left = rect.left + window.scrollX;

        // 如果 popup 超出右邊界，往左移
        if (left + popupWidth + 50 > window.innerWidth) {
            left = window.innerWidth - popupWidth - 50; // 多留 20px 邊距
            if (left < 0) left = 0; // 避免出現負數
        }

        popup.style.top = `${rect.top + window.scrollY + 80}px`;
        popup.style.left = `${left}px`;

        requestAnimationFrame(followBottle);
    }
    followBottle();
  });

  document.querySelector('.letters-panel').remove();
});
</script>

<script>
window.addEventListener('DOMContentLoaded', function() {
    window.addEventListener('beforeunload', function() {
    const data = new Blob([JSON.stringify({
        storyIds: <?= json_encode(array_column($stories, 'id')) ?>
    })], { type: 'application/json' });

    const success = navigator.sendBeacon('release_stories.php', data);
    });
});
</script>
</body>
</html>