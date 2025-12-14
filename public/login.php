<?php
require_once __DIR__ . '/../src/Config.php';
use HealthDiet\Config;

Config::init();
session_start();

// Если уже авторизован → редирект
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Foodly</title>
    <link rel="stylesheet" href="/assets/css/output.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Manrope', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-green-400 via-green-500 to-green-600 min-h-screen flex items-center justify-center p-4">
    
    <div class="bg-white rounded-3xl shadow-2xl p-10 w-full max-w-md">
        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <div class="bg-green-500 rounded-full p-4 text-white">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
        </div>
        
        <h1 class="text-3xl font-bold text-center mb-2 text-gray-800">
            Добро пожаловать в Foodly
        </h1>
        <p class="text-center text-gray-500 mb-8">
            Войдите, чтобы продолжить
        </p>
        
        <form id="login-form" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Email
                </label>
                <input 
                    type="email" 
                    name="email" 
                    required
                    value="dima@foodly.ru"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:outline-none transition-colors"
                    placeholder="your@email.com"
                >
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Пароль
                </label>
                <input 
                    type="password" 
                    name="password" 
                    required
                    value="test123"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-green-500 focus:outline-none transition-colors"
                    placeholder="••••••••"
                >
            </div>
            
            <div id="error-message" class="hidden bg-red-50 border-2 border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"></div>
            
            <button 
                type="submit"
                class="w-full bg-green-500 text-white py-4 rounded-xl font-bold text-lg hover:bg-green-600 transition-all transform active:scale-95 shadow-lg"
            >
                Войти
            </button>
        </form>
        
        <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-center text-sm text-gray-500">
                <strong>Тестовый аккаунт:</strong><br>
                dima@foodly.ru / test123
            </p>
        </div>
    </div>
    
    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault()
            
            const formData = new FormData(e.target)
            const data = {
                email: formData.get('email'),
                password: formData.get('password')
            }
            
            const errorDiv = document.getElementById('error-message')
            errorDiv.classList.add('hidden')
            
            try {
                const response = await fetch('/api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                
                const result = await response.json()
                
                if (result.success) {
                    window.location.href = '/'
                } else {
                    errorDiv.textContent = result.error
                    errorDiv.classList.remove('hidden')
                }
            } catch (error) {
                console.error('Login error:', error)
                errorDiv.textContent = 'Ошибка соединения с сервером'
                errorDiv.classList.remove('hidden')
            }
        })
    </script>
</body>
</html>
