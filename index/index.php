<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8" />
  <title>無名信使 - 主頁</title>
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
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Monomaniac One", "huninn-fws", "Kiwi Maru", "huninn", sans-serif;
    }

    html, body {
      width: 100%;
      height: 100%;
      background: #fbeee2;
      overflow: hidden;
      position: relative;
    }

    canvas {
      position: absolute;
      top: 0;
      left: 0;
      z-index: 10;
    }

    #envelope-img {
      position: absolute;
      width: 200px;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) scale(1);
      transform-origin: center;
      animation: zoomAndFade 2.5s ease forwards;
      z-index: 50;
    }

    @keyframes zoomAndFade {
      0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
      }
      100% {
        transform: translate(-50%, -50%) scale(2);
        opacity: 0;
      }
    }

    #main-content {
      opacity: 0;
      pointer-events: none;
      transition: opacity 1s ease;
      z-index: 20;
      position: absolute;
      width: 100%;
      height: 100%;
    }

    .floating-text {
      position: absolute;
      background: #f8d9c5;
      padding: 20px;
      border-radius: 24px;
      font-size: 24px;
      font-weight: bold;
      color: #6a4e42;
      animation: floatText 4s ease-in-out infinite alternate;
    }

    @keyframes floatText {
      0% { transform: translateY(0px); }
      100% { transform: translateY(-15px); }
    }

    .paper-plane-img {
      position: absolute;
      width: 80px;
      z-index: 30;
      pointer-events: none;
      display: none;
    }
  </style>
</head>
<body>

<!-- 信封動畫 -->
<img id="envelope-img" src="images/信封.gif" alt="信封">

<!-- 畫布背景雲 -->
<canvas id="scene"></canvas>

<!-- 飛機動畫 -->
<img id="plane" src="images/飛機.png" alt="飛機" class="paper-plane-img">

<!-- 主內容 -->
<div id="main-content">
  <div class="floating-text" style="top: 20%; left: 15%;">I'm feeling overwhelmed</div>
  <div class="floating-text" style="top: 70%; left: 25%;">Today was a good day</div>
  <div class="floating-text" style="top: 60%; left: 75%;">You are not alone</div>

  <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
    <h1 style="font-size: 36px; color: #5a4430;">
      <span id="title-deco" style="color: #f4a259; font-size: 48px; margin-right: 10px; display: none;">✶</span>
      今天的心情想和誰分享呢？
    </h1>
    <button 
      style="margin-top: 20px; background: #5a5a3b; color: #f8f0d9; border: none; padding: 12px 24px; border-radius: 24px; font-size: 20px; cursor: pointer;"
      onclick="location.href='login.php'">
      傳送給陌生人
    </button>
  </div>
</div>

<script>
const canvas = document.getElementById('scene');
const ctx = canvas.getContext('2d');
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;

let frame = 0;
let showDecorations = false;
const plane = document.getElementById('plane');

// 隱藏信封動畫，顯示主畫面
setTimeout(() => {
  document.getElementById('envelope-img').style.display = 'none';
  document.getElementById('main-content').style.opacity = '1';
  document.getElementById('main-content').style.pointerEvents = 'auto';
  document.getElementById('title-deco').style.display = 'inline';
  flyPlaneLoop();
  setInterval(flyPlaneLoop, 15000);
}, 2500); // 2.5秒後切畫面

function drawSoftHeart(x, y, size) {
  ctx.fillStyle = '#f79b5f';
  ctx.beginPath();
  ctx.moveTo(x, y);
  ctx.bezierCurveTo(x - size * 0.6, y - size * 0.8, x - size, y + size * 0.3, x, y + size);
  ctx.bezierCurveTo(x + size, y + size * 0.3, x + size * 0.6, y - size * 0.8, x, y);
  ctx.fill();
}

function drawStar(x, y, radius, color) {
  ctx.fillStyle = color;
  ctx.beginPath();
  for (let i = 0; i < 5; i++) {
    const angle = (Math.PI * 2 / 5) * i - Math.PI / 2;
    const x1 = x + radius * Math.cos(angle);
    const y1 = y + radius * Math.sin(angle);
    ctx.lineTo(x1, y1);
  }
  ctx.closePath();
  ctx.fill();
}

function drawCloud(x, y, r) {
  ctx.fillStyle = '#f9e8d2';
  ctx.beginPath();
  ctx.arc(x, y, r, 0, Math.PI * 2);
  ctx.arc(x + r * 0.9, y, r * 1.1, 0, Math.PI * 2);
  ctx.arc(x + r * 1.8, y, r, 0, Math.PI * 2);
  ctx.fill();
}

function animate() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  drawCloud((frame % 800) - 100, 120, 30);
  drawCloud((frame * 1.2 % 1000) - 150, 200, 40);
  drawCloud((frame * 0.8 % 1000), 500, 50);
  drawCloud((frame * 0.6 % 1100), 620, 40);

  if (showDecorations) {
    drawSoftHeart(150, 300 + Math.sin(frame / 20) * 10, 18);
    drawSoftHeart(canvas.width - 100, canvas.height - 150 + Math.cos(frame / 30) * 10, 18);
    drawStar(80, 80, 10, '#f4a259');
    drawStar(canvas.width - 100, canvas.height - 100, 10, '#f4a259');
  }

  frame++;
  requestAnimationFrame(animate);
}

function flyPlaneLoop() {
  showDecorations = true;
  const totalFrames = 600;
  let pFrame = 0;
  plane.style.display = 'block';

  function move() {
    const progress = pFrame / totalFrames;
    const x = -100 + progress * (canvas.width + 200);
    const y = canvas.height * (0.8 - 0.7 * progress);
    plane.style.left = `${x}px`;
    plane.style.top = `${y}px`;

    pFrame++;
    if (pFrame <= totalFrames) {
      requestAnimationFrame(move);
    } else {
      plane.style.display = 'none';
    }
  }

  move();
}

animate();
</script>

</body>
</html>
