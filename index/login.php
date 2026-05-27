<?php
session_start();
session_unset();
session_destroy();
session_start();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>無名信使 - 登入</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* 全局設定 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, 
                #F4D7B7 0%,    /* 淺膚色 */
                #E8C4A8 25%,   /* 溫暖膚色 */
                #DDB299 50%,   /* 中等膚色 */
                #D2A08A 75%,   /* 深一點的膚色 */
                #C78E7B 100%   /* 偏紅的膚色 */
            );
            background-size: 400% 400%;
            animation: gradientShift 10s ease infinite;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* 背景漸變動畫 */
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* 背景粒子效果 */
        .background-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .bg-particle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.6;
            animation: floatUp 12s infinite linear;
        }

        .bg-particle-1 {
            width: 8px;
            height: 8px;
            background: rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }

        .bg-particle-2 {
            width: 6px;
            height: 6px;
            background: rgba(255, 182, 193, 0.5);
            box-shadow: 0 0 8px rgba(255, 182, 193, 0.4);
        }

        .bg-particle-3 {
            width: 4px;
            height: 4px;
            background: rgba(255, 223, 186, 0.6);
            box-shadow: 0 0 6px rgba(255, 223, 186, 0.3);
        }

        @keyframes floatUp {
            0% {
                transform: translateY(100vh) translateX(0px) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.6;
            }
            90% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100px) translateX(50px) rotate(360deg);
                opacity: 0;
            }
        }

        /* 裝飾性幾何圖形 */
        .geometric-shape {
            position: absolute;
            pointer-events: none;
        }

        .shape-1 {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: 15%;
            left: 10%;
            animation: pulse 4s ease-in-out infinite;
        }

        .shape-2 {
            width: 80px;
            height: 80px;
            background: rgba(255, 182, 193, 0.1);
            border-radius: 20px;
            top: 70%;
            right: 15%;
            animation: pulse 4s ease-in-out infinite 2s;
        }

        .shape-3 {
            width: 60px;
            height: 60px;
            background: rgba(255, 223, 186, 0.15);
            border-radius: 50%;
            bottom: 20%;
            left: 20%;
            animation: pulse 4s ease-in-out infinite 1s;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1) rotate(0deg);
                opacity: 0.3;
            }
            50% {
                transform: scale(1.2) rotate(10deg);
                opacity: 0.1;
            }
        }

        /* 登入表單 */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 100px);
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        .login-box {
            width: 380px;
            padding: 40px 30px;
            background: linear-gradient(135deg, 
                rgba(157, 178, 167, 0.9), 
                rgba(140, 160, 150, 0.9)
            );
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            text-align: center;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.1),
                inset 0 2px 0 rgba(255, 255, 255, 0.2);
            animation: slideInScale 1.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        /* 登入框背景光效 */
        .login-box::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, 
                rgba(255, 255, 255, 0.3),
                rgba(255, 182, 193, 0.2),
                rgba(255, 223, 186, 0.2),
                rgba(255, 255, 255, 0.3)
            );
            border-radius: 25px;
            z-index: -1;
            animation: borderGlow 3s ease-in-out infinite;
        }

        @keyframes borderGlow {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        @keyframes slideInScale {
            0% {
                opacity: 0;
                transform: translateY(80px) scale(0.8);
            }
            60% {
                opacity: 0.8;
                transform: translateY(-10px) scale(1.05);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-box h2 {
            font-size: 36px;
            color: #fdf6e3;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: titleBounce 2s ease-out;
        }

        @keyframes titleBounce {
            0% {
                transform: translateY(-50px);
                opacity: 0;
            }
            60% {
                transform: translateY(5px);
                opacity: 0.8;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .input-group {
            position: relative;
            margin: 20px 0;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.9);
            color: #444;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: rgba(205, 133, 63, 0.6);
            background: rgba(255, 255, 255, 1);
            box-shadow: 
                inset 0 2px 4px rgba(0, 0, 0, 0.05),
                0 0 20px rgba(205, 133, 63, 0.3);
            transform: scale(1.02);
        }

        /* 輸入框浮動標籤效果 */
        .input-group::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            background: rgba(139, 69, 19, 0.3);
            border-radius: 50%;
            transition: all 0.3s ease;
            z-index: 1;
        }

        .input-group:nth-child(1)::before {
            background: rgba(100, 149, 237, 0.3);
        }

        .input-group:nth-child(2)::before {
            background: rgba(255, 99, 132, 0.3);
        }

        .login-container button {
            padding: 15px 40px;
            background: linear-gradient(45deg, #7a6650, #8B4513);
            color: #fff7d4;
            font-weight: bold;
            font-size: 16px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(122, 102, 80, 0.3);
            position: relative;
            overflow: hidden;
        }

        .login-container button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.2), 
                transparent
            );
            transition: left 0.5s;
        }

        .login-container button:hover::before {
            left: 100%;
        }

        .login-container button:hover {
            background: linear-gradient(45deg, #8B4513, #A0522D);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(122, 102, 80, 0.4);
        }

        .login-container button:active {
            transform: translateY(-1px);
        }

        .register {
            margin-top: 25px;
            font-size: 14px;
            color: #fdf6e3;
            animation: fadeInUp 1.5s ease 0.5s both;
        }

        .register a {
            color:rgb(50, 109, 132);
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            padding: 3px 15px;
            border-radius: 20px;
        }

        .register a:hover {
            color:rgb(79, 128, 168);
            background: rgba(255, 255, 255, 0.2);
            text-shadow: 0 0 10px rgba(135, 206, 235, 0.5);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 輸入框動畫延遲 */
        .input-group:nth-child(1) {
            animation: slideInLeft 0.8s ease 0.3s both;
        }

        .input-group:nth-child(2) {
            animation: slideInRight 0.8s ease 0.5s both;
        }

        .button-container {
            animation: fadeInUp 0.8s ease 0.7s both;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* 響應式設計 */
        @media (max-width: 480px) {
            .login-box {
                width: 90%;
                padding: 30px 20px;
            }
            
            .navbar {
                padding: 15px 20px;
            }
        }

        /* 載入動畫 */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(244, 215, 183, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(139, 69, 19, 0.3);
            border-top: 5px solid #8B4513;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- 載入動畫 -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- 背景粒子 -->
    <div class="background-particles" id="backgroundParticles"></div>

    <!-- 裝飾性幾何圖形 -->
    <div class="geometric-shape shape-1"></div>
    <div class="geometric-shape shape-2"></div>
    <div class="geometric-shape shape-3"></div>

    <?php include 'navbar.php'; ?>

    <!-- 登入區塊 -->
    <div class="login-container">
        <div class="login-box">
            <h2>登入</h2>
            <form method="POST" action="check_login.php">
                <div class="input-group">
                    <input type="text" name="username" placeholder="帳號" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="密碼" required>
                </div>
                <div class="button-container">
                    <button type="submit">確認登入</button>
                </div>
            </form>
            <div class="register">
                還沒有帳號？　<a href="register.php">立即註冊</a>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // 隱藏載入動畫
            setTimeout(function() {
                $('#loadingOverlay').fadeOut(500);
            }, 1000);

            // 創建背景粒子
            function createBackgroundParticle() {
                const particleTypes = ['bg-particle-1', 'bg-particle-2', 'bg-particle-3'];
                const randomType = particleTypes[Math.floor(Math.random() * particleTypes.length)];
                const particle = $(`<div class="bg-particle ${randomType}"></div>`);
                const startX = Math.random() * 100;
                const delay = Math.random() * 3;
                const duration = 8 + Math.random() * 6;
                
                particle.css({
                    'left': startX + '%',
                    'animation-delay': delay + 's',
                    'animation-duration': duration + 's'
                });
                
                $('#backgroundParticles').append(particle);
                
                setTimeout(() => {
                    particle.remove();
                }, (duration + delay) * 1000);
            }

            // 持續創建背景粒子
            setInterval(createBackgroundParticle, 1000);

            // 輸入框聚焦效果
            $('input').on('focus', function() {
                $(this).parent('.input-group').addClass('focused');
            }).on('blur', function() {
                $(this).parent('.input-group').removeClass('focused');
            });

            // 表單提交動畫
            $('form').on('submit', function() {
                $('button').html('<div style="display: inline-block; width: 20px; height: 20px; border: 2px solid #fff; border-top: 2px solid transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>');
                $('button').prop('disabled', true);
            });

            // 鼠標移動視差效果
            $(document).mousemove(function(e) {
                const mouseX = e.clientX / $(window).width();
                const mouseY = e.clientY / $(window).height();
                
                $('.geometric-shape').each(function(index) {
                    const speed = (index + 1) * 0.3;
                    const x = mouseX * speed * 15;
                    const y = mouseY * speed * 15;
                    
                    $(this).css({
                        'transform': `translate(${x}px, ${y}px)`
                    });
                });
            });

            // 鍵盤快捷鍵
            $(document).keydown(function(e) {
                if (e.key === 'Enter' && e.ctrlKey) {
                    $('form').submit();
                }
            });
        });
    </script>
</body>
</html>