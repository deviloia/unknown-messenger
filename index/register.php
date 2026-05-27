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
    <style>
        /* 全局設定 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #f3f0e7;
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }
        
        /* 主內容區域 - 加上 padding-top 避免被導覽列遮住 */
        .main-content {
            padding-top: 100px;
        }

        /* 登入表單 */
        .login-box {
            width: 320px;
            margin: 50px auto;
            padding: 30px 20px;
            background: #9db2a7;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            animation: fadeInUp 1.2s ease 0.6s both;
            position: relative;
            overflow: hidden;
        }
        
        .login-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        .login-box h2 {
            font-size: 32px;
            color: #fdf6e3;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
            animation: titleGlow 2s ease-in-out infinite alternate;
        }
        
        .form-group {
            position: relative;
            z-index: 2;
            margin: 15px 0;
        }
        
        input[type="text"], input[type="password"] {
            width: 85%;
            padding: 12px 15px;
            margin: 8px 0;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(122, 102, 80, 0.3);
        }
        
        input[type="text"]:hover, input[type="password"]:hover {
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
        }
        
        button {
            padding: 12px 35px;
            background: #7a6650;
            color: #fff7d4;
            font-weight: bold;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
            font-size: 16px;
            overflow: hidden;
        }
        
        button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transition: all 0.5s ease;
            transform: translate(-50%, -50%);
        }
        
        button:hover::before {
            width: 300px;
            height: 300px;
        }
        
        button:hover {
            background: #5d4d3d;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
        
        button:active {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .register {
            margin-top: 15px;
            font-size: 14px;
            position: relative;
            z-index: 2;
        }
        
        .register a {
            color: #3366cc;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .register a:hover {
            color: #2554aa;
            text-decoration: underline;
        }

        /* 動畫效果 */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-100%);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        @keyframes shimmer {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
            }
        }
        
        @keyframes titleGlow {
            0% {
                text-shadow: 0 0 5px rgba(253, 246, 227, 0.5);
            }
            100% {
                text-shadow: 0 0 20px rgba(253, 246, 227, 0.8), 0 0 30px rgba(253, 246, 227, 0.6);
            }
        }

        /* 響應式設計 */
        @media (max-width: 768px) {
            .navbar {
                padding: 10px 20px;
                flex-direction: column;
                gap: 10px;
            }
            
            .navbar-right a {
                margin: 0 10px;
            }
            
            .main-content {
                padding-top: 140px;
            }
            
            .login-box {
                width: 90%;
                margin: 30px auto;
            }
        }
        
        /* 背景裝飾動畫 */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(220, 195, 186, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(157, 178, 167, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(122, 102, 80, 0.05) 0%, transparent 50%);
            animation: backgroundMove 20s ease-in-out infinite alternate;
            pointer-events: none;
            z-index: -1;
        }
        
        @keyframes backgroundMove {
            0% {
                transform: scale(1) rotate(0deg);
            }
            100% {
                transform: scale(1.1) rotate(2deg);
            }
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

<!-- 主內容區域 -->
<div class="main-content">
    <!-- 登入區塊 -->
    <div class="login-box">
        <h2>註冊</h2>
        <form method="POST" action="session_register.php">
            <div class="form-group">
                <input type="text" name="username" placeholder="註冊帳號" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="註冊密碼" required>
            </div>
            <div class="form-group">
                <button type="submit">確認</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>