<?php
require_once __DIR__ . '/../src/Config.php';
use HealthDiet\Config;

Config::init();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'] ?? '';
$calorieGoal = $_SESSION['calorie_goal'] ?? 2000;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки - Foodly</title>
    
    <!-- Preload шрифтов -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" as="style">
    
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/output.css">
    
    <style>
        body { font-family: 'Manrope', sans-serif; }
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            opacity: 0;
            transition: opacity 0.1s;
        }
        .material-symbols-outlined.loaded {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark min-h-screen">
    
    <div class="max-w-2xl mx-auto px-4 py-10">
        <!-- Header -->
        <div class="flex items-center gap-4 mb-8">
            <a href="/" class="w-10 h-10 flex items-center justify-center hover:bg-white dark:hover:bg-[#1c3029] rounded-xl transition-colors">
                <span class="material-symbols-outlined text-text-secondary">arrow_back</span>
            </a>
            <h1 class="text-2xl font-bold text-text-primary dark:text-white">Настройки</h1>
        </div>
        
        <!-- Profile Card -->
        <div class="bg-white dark:bg-[#152822] rounded-2xl border border-[#dbe6e2] dark:border-[#2a3f38] p-6 mb-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center font-bold text-2xl">
                    <?= mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'), 'UTF-8') ?>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-text-primary dark:text-white"><?= htmlspecialchars($userName) ?></h2>
                    <p class="text-text-secondary"><?= htmlspecialchars($userEmail) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Settings Form -->
        <form id="settings-form" class="space-y-6">
            <!-- Name -->
            <div class="bg-white dark:bg-[#152822] rounded-2xl border border-[#dbe6e2] dark:border-[#2a3f38] p-6">
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">
                    Имя
                </label>
                <input 
                    type="text" 
                    name="name" 
                    value="<?= htmlspecialchars($userName) ?>"
                    required
                    class="w-full px-4 py-3 border-2 border-[#dbe6e2] dark:border-[#2a3f38] bg-transparent rounded-xl focus:border-primary focus:outline-none transition-colors text-text-primary dark:text-white"
                >
            </div>
            
            <!-- Email -->
            <div class="bg-white dark:bg-[#152822] rounded-2xl border border-[#dbe6e2] dark:border-[#2a3f38] p-6">
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">
                    Email
                </label>
                <input 
                    type="email" 
                    name="email" 
                    value="<?= htmlspecialchars($userEmail) ?>"
                    required
                    class="w-full px-4 py-3 border-2 border-[#dbe6e2] dark:border-[#2a3f38] bg-transparent rounded-xl focus:border-primary focus:outline-none transition-colors text-text-primary dark:text-white"
                >
            </div>
            
            <!-- Password -->
            <div class="bg-white dark:bg-[#152822] rounded-2xl border border-[#dbe6e2] dark:border-[#2a3f38] p-6">
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">
                    Новый пароль
                </label>
                <input 
                    type="password" 
                    name="password" 
                    placeholder="Оставьте пустым, чтобы не менять"
                    minlength="6"
                    class="w-full px-4 py-3 border-2 border-[#dbe6e2] dark:border-[#2a3f38] bg-transparent rounded-xl focus:border-primary focus:outline-none transition-colors text-text-primary dark:text-white placeholder-text-secondary"
                >
            </div>
            
            <!-- Messages -->
            <div id="success-message" class="hidden bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl text-sm"></div>
            <div id="error-message" class="hidden bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl text-sm"></div>
            
            <!-- Submit -->
            <button 
                type="submit"
                class="w-full bg-primary text-white py-4 rounded-xl font-bold text-lg hover:bg-primary-hover transition-all"
            >
                Сохранить изменения
            </button>
        </form>
        
        <!-- Logout -->
        <div class="mt-8 pt-6 border-t border-[#dbe6e2] dark:border-[#2a3f38]">
            <a 
                href="/logout.php" 
                class="flex items-center justify-center gap-2 w-full py-4 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl font-semibold transition-colors"
            >
                <span class="material-symbols-outlined">logout</span>
                Выйти из аккаунта
            </a>
        </div>
    </div>
    
    <script>
        document.getElementById('settings-form').addEventListener('submit', async (e) => {
            e.preventDefault()
            
            const formData = new FormData(e.target)
            const data = {
                name: formData.get('name'),
                email: formData.get('email'),
                password: formData.get('password') || null
            }
            
            const successDiv = document.getElementById('success-message')
            const errorDiv = document.getElementById('error-message')
            successDiv.classList.add('hidden')
            errorDiv.classList.add('hidden')
            
            try {
                const response = await fetch('/api/user.php?action=update_profile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                
                const result = await response.json()
                
                if (result.success) {
                    successDiv.textContent = 'Настройки сохранены!'
                    successDiv.classList.remove('hidden')
                    
                    // Обновляем аватар если имя изменилось
                    setTimeout(() => location.reload(), 1000)
                } else {
                    errorDiv.textContent = result.error
                    errorDiv.classList.remove('hidden')
                }
            } catch (error) {
                console.error('Settings error:', error)
                errorDiv.textContent = 'Ошибка соединения с сервером'
                errorDiv.classList.remove('hidden')
            }
        })
        
        // Показываем иконки после загрузки шрифта
        document.fonts.ready.then(() => {
            document.querySelectorAll('.material-symbols-outlined').forEach(el => {
                el.classList.add('loaded')
            })
        })
    </script>
</body>
</html>
