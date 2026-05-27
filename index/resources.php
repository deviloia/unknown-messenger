<?php
session_start();
require("db_connect.php");

@$username = $_SESSION['username'];
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
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8" />
  <title>無名信使 - 心理資源</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { background: #f3f0e7; }
    .resources-header {
      text-align: center;
      font-size: 40px;
      padding: 40px 20px 20px;
    }
    .resources-header::after {
      content: '';
      display: block;
      width: 200px; height: 10px;
      background: #d7c2dd;
      margin: 20px auto 0;
      border-radius: 5px;
    }

    .masonry {
      column-count: 3;
      column-gap: 20px;
      padding: 40px;
      max-width: 1200px;
      margin: auto;
    }

    .grid-item {
      break-inside: avoid;
      margin-bottom: 20px;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
      cursor: pointer;
      transition: transform 0.3s;

      /* 淡入動畫設定 */
      opacity: 0;
      animation: fadeInUp 0.8s ease forwards;
    }

    .grid-item:hover { transform: scale(1.02); }

    .grid-item img {
      width: 100%;
      display: block;
      object-fit: cover;
    }

    .overlay {
      padding: 12px;
      text-align: center;
      background: rgba(0, 0, 0, 0.5);
      color: #fff;
      font-size: 16px;
    }

    /* 動畫效果 */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 100;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
      justify-content: center;
      align-items: center;
      animation: fadeIn 0.5s;
    }

    .modal-content {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      max-width: 500px;
      width: 90%;
      animation: zoomIn 0.3s ease;
    }

    @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
    @keyframes zoomIn { from {transform: scale(0.8);} to {transform: scale(1);} }

    @media (max-width: 768px) {
      .masonry {
        column-count: 2;
      }
    }

    @media (max-width: 500px) {
      .masonry {
        column-count: 1;
      }
    }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="resources-header">心理協助資源</div>

  <div class="masonry" id="resourceGrid"></div>

  <div id="modal" class="modal" onclick="this.style.display='none'">
    <div class="modal-content" onclick="event.stopPropagation()">
      <h3 id="modal-title"></h3>
      <p><a id="modal-link" href="#" target="_blank">點我前往</a></p>
    </div>
  </div>

  <script>
    const resources = [
      { title: "衛福部心理健康資源頁", img: "images/心理資源1.jpg", url: "https://www.mohw.gov.tw/cp-16-76196-1.html" },
      { title: "台灣臨床心理師全聯會諮詢資訊", img: "images/心理資源2.jpg", url: "https://www.taaa.org.tw/news/article/9343" },
      { title: "桃園市大溪心理健康資源指引", img: "images/心理資源3.jpg", url: "https://www.daxi-hro.tycg.gov.tw/News_Content.aspx?n=15003&s=1379429" },
      { title: "Circle We Life 文章分享", img: "images/心理資源4.png", url: "https://circlewelife.com/article/detail/26" },
      { title: "台灣心理資源彙整", img: "images/心理資源5.jpg", url: "https://psychtranslation.com/" },
      { title: "衛福部心理健康司", img: "images/心理資源6.png", url: "https://dep.mohw.gov.tw/DOMHAOH/np-326-107.html" },
      { title: "教育部輔導資源總覽", img: "images/心理資源7.png", url: "https://www.guide.edu.tw/latest-posts/other/a0d9b2bf-1e77-440d-8b5b-c0a008b9178c" },
      { title: "行政院心理支持方案", img: "images/心理資源8.png", url: "https://www.ey.gov.tw/Page/" },
      { title: "中央大學心理支持平台", img: "images/心理資源9.png", url: "https://cis.ncu.edu.tw/NsaSys/studAffairs/134" }
    ];

    const container = document.getElementById('resourceGrid');
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modal-title');
    const modalLink = document.getElementById('modal-link');

    resources.forEach((res, index) => {
      const div = document.createElement('div');
      div.className = 'grid-item';
      div.style.animationDelay = `${index * 0.1}s`; // 淡入延遲效果
      div.innerHTML = `
        <img src="${res.img}" alt="${res.title}">
        <div class="overlay">${res.title}</div>
      `;
      div.onclick = () => {
        modalTitle.textContent = res.title;
        modalLink.href = res.url;
        modal.style.display = 'flex';
      };
      container.appendChild(div);
    });
  </script>
</body>
</html>
