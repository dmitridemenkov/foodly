<?php
// Простой редирект с токеном

$token = $_GET['token'] ?? '';

if (!empty($token)) {
    // Если токен есть - редиректим в public
    header('Location: /public/index.php?token=' . urlencode($token));
    exit;
}

// Если токена нет - показываем welcome страницу
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍽️ Трекер Питания</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .welcome-box {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 400px;
        }
        h1 { margin: 0 0 20px; color: #333; }
        .emoji { font-size: 64px; margin-bottom: 20px; }
        p { color: #666; line-height: 1.6; }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 32px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        code {
            background: #f5f5f5;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="welcome-box">
        <div class="emoji">🍽️</div>
        <h1>Трекер Питания</h1>
        <p>Добро пожаловать! Для доступа к приложению добавьте токен к URL:</p>
        <p><code>?token=start</code></p>
        <a href="?token=start" class="btn">Войти</a>
    </div>
</body>
</html>