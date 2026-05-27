<?php
session_start();
require("db_connect.php");
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <title>關於我們</title>
  <style>
    body {
      margin: 0;
      background: #f8f5ec;
      text-align: center;
      position: relative;
      overflow-x: hidden;
    }

    /* 新增：浮動裝飾元素 */
    .floating-elements {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: -1;
    }

    .floating-circle {
      position: absolute;
      border-radius: 50%;
      background: linear-gradient(45deg, rgba(220, 195, 186, 0.3), rgba(215, 194, 221, 0.3));
      animation: float 6s ease-in-out infinite;
    }

    .floating-circle:nth-child(1) {
      width: 60px;
      height: 60px;
      top: 20%;
      left: 10%;
      animation-delay: 0s;
    }

    .floating-circle:nth-child(2) {
      width: 40px;
      height: 40px;
      top: 60%;
      right: 15%;
      animation-delay: 2s;
    }

    .floating-circle:nth-child(3) {
      width: 80px;
      height: 80px;
      top: 40%;
      left: 80%;
      animation-delay: 4s;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(180deg); }
    }

    h1 {
      font-size: 40px;
      margin: 40px 0 20px;
      background: linear-gradient(45deg, #444, #d7c2dd, #dcc3ba);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: textShimmer 3s ease-in-out infinite;
      position: relative;
    }

    @keyframes textShimmer {
      0%, 100% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
    }

    /* 新增：標題下方裝飾線 */
    h1::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 100px;
      height: 3px;
      background: linear-gradient(90deg, transparent, #d7c2dd, transparent);
      animation: lineGlow 2s ease-in-out infinite;
    }

    @keyframes lineGlow {
      0%, 100% { opacity: 0.5; width: 100px; }
      50% { opacity: 1; width: 150px; }
    }

    .top-photos {
      display: flex;
      justify-content: center;
      gap: 60px;
      flex-wrap: wrap;
      margin-bottom: 60px;
      animation: fadeIn 1s ease-in-out;
    }

    .frame {
      position: relative;
      padding: 15px;
      background: #f5f5dc;
      border: 4px solid #eee;
      width: 200px;
      height: 200px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.2);
      transition: all 0.4s ease;
      transform-origin: center;
    }

    /* 新增：懸停效果增強 */
    .frame:hover {
      transform: translateY(-10px) rotate(2deg);
      box-shadow: 0 15px 30px rgba(0,0,0,0.3);
      border-color: #d7c2dd;
    }

    .frame:nth-child(even):hover {
      transform: translateY(-10px) rotate(-2deg);
    }

    .frame img.main-photo {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: all 0.3s ease;
    }

    .frame:hover img.main-photo {
      filter: brightness(1.1) contrast(1.1);
    }

    .tape {
      position: absolute;
      width: 80px;
      height: auto;
      top: -25px;
      left: -25px;
      pointer-events: none;
      transition: all 0.3s ease;
    }

    .frame:hover .tape {
      transform: rotate(5deg);
    }

    /* 新增：照片框發光效果 */
    .frame::before {
      content: '';
      position: absolute;
      top: -5px;
      left: -5px;
      right: -5px;
      bottom: -5px;
      background: linear-gradient(45deg, #d7c2dd, #dcc3ba, #d7c2dd);
      border-radius: 10px;
      opacity: 0;
      z-index: -1;
      transition: opacity 0.3s ease;
    }

    .frame:hover::before {
      opacity: 0.7;
    }

    .bottom-circles {
      display: flex;
      justify-content: center;
      gap: 40px;
      flex-wrap: wrap;
      animation: fadeIn 1s ease-in-out;
      margin-bottom: 50px;
    }

    .circle {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      overflow: hidden;
      border: 3px solid #fff;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      transition: all 0.4s ease;
      cursor: pointer;
      position: relative;
    }

    .circle:hover {
      transform: scale(1.2) rotate(360deg);
      border-color: #d7c2dd;
      box-shadow: 0 8px 20px rgba(215, 194, 221, 0.5);
    }

    .circle img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: all 0.3s ease;
    }

    .circle:hover img {
      filter: brightness(1.2) saturate(1.2);
    }

    /* 新增：圓框光暈效果 */
    .circle::after {
      content: '';
      position: absolute;
      top: -5px;
      left: -5px;
      right: -5px;
      bottom: -5px;
      border-radius: 50%;
      background: conic-gradient(from 0deg, #d7c2dd, #dcc3ba, #d7c2dd, #dcc3ba);
      opacity: 0;
      z-index: -1;
      transition: opacity 0.3s ease;
    }

    .circle:hover::after {
      opacity: 0.8;
      animation: spin 2s linear infinite;
    }

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 999;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
      justify-content: center;
      align-items: center;
      backdrop-filter: blur(5px);
    }

    .modal-content {
      background: #fff;
      padding: 30px;
      border-radius: 20px;
      text-align: center;
      max-width: 400px;
      animation: modalSlideIn 0.4s ease-out;
      position: relative;
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
      border: 2px solid #d7c2dd;
    }

    @keyframes modalSlideIn {
      from {
        opacity: 0;
        transform: translateY(-50px) scale(0.8);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .modal-content img {
      width: 80%;
      border-radius: 15px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.2);
      transition: transform 0.3s ease;
    }

    .modal-content img:hover {
      transform: scale(1.05);
    }

    .modal-content h2 {
      color: #444;
      margin-bottom: 20px;
      font-size: 24px;
      background: linear-gradient(45deg, #d7c2dd, #dcc3ba);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* 新增：關閉按鈕 */
    .modal-close {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 28px;
      color: #999;
      cursor: pointer;
      transition: color 0.3s ease;
    }

    .modal-close:hover {
      color: #d7c2dd;
      transform: rotate(90deg);
    }

    /* 新增：鼠標跟隨效果 */
    .cursor-glow {
      position: fixed;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(215, 194, 221, 0.6), transparent);
      pointer-events: none;
      z-index: 1000;
      transition: all 0.1s ease;
      transform: translate(-50%, -50%);
    }

    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(20px);}
      to {opacity: 1; transform: translateY(0);}
    }

    /* 新增：響應式設計增強 */
    @media (max-width: 768px) {
      .top-photos {
        gap: 30px;
      }
      
      .frame {
        width: 150px;
        height: 150px;
      }
      
      .bottom-circles {
        gap: 20px;
      }
      
      h1 {
        font-size: 32px;
      }
    }
  </style>
</head>
<body>
  <!-- 新增：浮動裝飾元素 -->
  <div class="floating-elements">
    <div class="floating-circle"></div>
    <div class="floating-circle"></div>
    <div class="floating-circle"></div>
  </div>

  <!-- 新增：鼠標跟隨光暈 -->
  <div class="cursor-glow" id="cursorGlow"></div>

  <?php include 'navbar.php'; ?>

  <h1>關於我們</h1>

  <!-- 上半照片 -->
  <div class="top-photos">
    <div class="frame">
      <img class="main-photo" src="images/solo.jpg">
      <img class="tape" src="images/tape.png">
    </div>
    <div class="frame">
      <img class="main-photo" src="images/fat.jpg">
      <img class="tape" src="images/tape.png">
    </div>
    <div class="frame">
      <img class="main-photo" src="images/cat.jpg">
      <img class="tape" src="images/tape.png">
    </div>
    <div class="frame">
      <img class="main-photo" src="images\abc.jpg">
      <img class="tape" src="images/tape.png">
    </div>
  </div>

  <!-- 下方圓框 -->
  <div class="bottom-circles">
    <div class="circle" onclick="showModal(1)"><img src="images/solo.jpg"></div>
    <div class="circle" onclick="showModal(2)"><img src="images/fat.jpg"></div>
    <div class="circle" onclick="showModal(3)"><img src="images/cat.jpg"></div>
    <div class="circle" onclick="showModal(4)"><img src="images\abc.jpg"></div>
  </div>

  <!-- 彈窗 -->
  <div class="modal" id="modal" onclick="this.style.display='none'">
    <div class="modal-content" onclick="event.stopPropagation()">
      <span class="modal-close" onclick="document.getElementById('modal').style.display='none'">&times;</span>
      <h2 id="modalName"></h2>
      <img id="modalImg" src="">
    </div>
  </div>

  <script>
    const names = ["林佳駿", "張凱翔", "黃冠恆", "張宇寬"];
    function showModal(index) {
      document.getElementById("modal").style.display = "flex";
      document.getElementById("modalImg").src = `member${index}.jpg`;
      document.getElementById("modalName").innerText = names[index - 1];
    }

    // 新增：鼠標跟隨效果
    document.addEventListener('mousemove', (e) => {
      const glow = document.getElementById('cursorGlow');
      glow.style.left = e.clientX + 'px';
      glow.style.top = e.clientY + 'px';
    });

    // 新增：頁面載入動畫
    window.addEventListener('load', () => {
      document.body.style.opacity = '0';
      document.body.style.transition = 'opacity 0.5s ease';
      setTimeout(() => {
        document.body.style.opacity = '1';
      }, 100);
    });

    // 新增：滾動視差效果
    window.addEventListener('scroll', () => {
      const scrolled = window.pageYOffset;
      const rate = scrolled * -0.5;
      
      document.querySelector('.floating-elements').style.transform = `translateY(${rate}px)`;
    });

    // 新增：點擊漣漪效果
    document.addEventListener('click', (e) => {
      const ripple = document.createElement('div');
      ripple.style.position = 'fixed';
      ripple.style.left = e.clientX + 'px';
      ripple.style.top = e.clientY + 'px';
      ripple.style.width = '0';
      ripple.style.height = '0';
      ripple.style.borderRadius = '50%';
      ripple.style.background = 'rgba(215, 194, 221, 0.3)';
      ripple.style.transform = 'translate(-50%, -50%)';
      ripple.style.pointerEvents = 'none';
      ripple.style.zIndex = '999';
      ripple.style.transition = 'all 0.6s ease-out';
      
      document.body.appendChild(ripple);
      
      setTimeout(() => {
        ripple.style.width = '100px';
        ripple.style.height = '100px';
        ripple.style.opacity = '0';
      }, 10);
      
      setTimeout(() => {
        document.body.removeChild(ripple);
      }, 600);
    });
  </script>

</body>
</html>