<?php
require_once __DIR__ . '/../src/Config.php';
use HealthDiet\Config;

Config::init();
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$calorieGoal = $_SESSION['calorie_goal'] ?? 2000;
?>
<!DOCTYPE html>
<html class="light" lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodly - Dashboard</title>
    
    <!-- Preload шрифтов -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" as="style">
    
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/output.css">
    
    <style>
        /* Скрываем контент до загрузки иконок */
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            -moz-osx-font-smoothing: grayscale;
            font-feature-settings: 'liga';
        }
        
        /* Показываем контент только после загрузки */
        body {
            visibility: hidden;
        }
        
        body.fonts-loaded {
            visibility: visible;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-text-primary antialiased h-screen overflow-hidden flex">

<!-- Sidebar -->
<aside class="w-[350px] h-full bg-white dark:bg-[#152822] flex flex-col border-r border-[#dbe6e2] dark:border-[#2a3f38] flex-shrink-0 z-20 overflow-y-auto hide-scrollbar">
    <!-- Logo -->
    <div class="px-6 py-8">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-primary !text-[28px]">eco</span>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-text-primary dark:text-white">Foodly</h1>
                <p class="text-xs text-text-secondary font-medium">Дневник питания</p>
            </div>
        </div>
    </div>
    
    <!-- Mini Calendar -->
    <div class="px-4 mb-6">
        <div class="bg-background-light dark:bg-[#1c3029] rounded-2xl p-4" id="mini-calendar">
            <!-- Календарь будет генериться JS -->
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 px-4 flex flex-col gap-2">
        <button data-screen="dashboard" class="nav-btn flex items-center gap-3 px-4 py-3 bg-primary/10 rounded-xl group transition-all">
            <span class="material-symbols-outlined text-primary group-hover:text-primary-hover">dashboard</span>
            <span class="text-sm font-semibold text-text-primary dark:text-white">Главная</span>
        </button>
        <button data-screen="stats" class="nav-btn flex items-center gap-3 px-4 py-3 hover:bg-background-light dark:hover:bg-[#1c3029] rounded-xl group transition-all">
            <span class="material-symbols-outlined text-text-secondary group-hover:text-text-primary dark:group-hover:text-white">bar_chart</span>
            <span class="text-sm font-medium text-text-secondary group-hover:text-text-primary dark:group-hover:text-white">Статистика</span>
        </button>
        <button data-screen="favorites" class="nav-btn flex items-center gap-3 px-4 py-3 hover:bg-background-light dark:hover:bg-[#1c3029] rounded-xl group transition-all">
            <span class="material-symbols-outlined text-text-secondary group-hover:text-text-primary dark:group-hover:text-white">favorite</span>
            <span class="text-sm font-medium text-text-secondary group-hover:text-text-primary dark:group-hover:text-white">Избранное</span>
        </button>
        <a href="/logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-background-light dark:hover:bg-[#1c3029] rounded-xl group transition-all">
            <span class="material-symbols-outlined text-text-secondary group-hover:text-red-500">logout</span>
            <span class="text-sm font-medium text-text-secondary group-hover:text-red-500">Выход</span>
        </a>
    </nav>
    
    <!-- User Profile -->
    <div class="p-4 border-t border-[#dbe6e2] dark:border-[#2a3f38]">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold text-lg">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-bold text-text-primary dark:text-white"><?= htmlspecialchars($userName) ?></span>
                <span class="text-xs text-text-secondary">ID: <?= $userId ?></span>
            </div>
        </div>
    </div>
</aside>

<!-- Main Content -->
<main class="flex-1 h-full overflow-y-auto bg-background-light dark:bg-background-dark">
    
    <!-- Dashboard Screen -->
    <section id="dashboard" class="screen active">
        <div class="w-full px-8 py-10">
            <!-- Header Date -->
            <div class="mb-10">
                <h1 class="text-3xl font-bold text-text-primary dark:text-white mb-1 tracking-tight" id="current-date">
                    Загрузка...
                </h1>
                <p class="text-text-secondary text-sm">Продолжай в том же духе! 💪</p>
            </div>
            
            <!-- SEARCH BAR -->
            <div class="bg-white dark:bg-[#152822] rounded-3xl p-6 mb-8 border border-[#dbe6e2] dark:border-[#2a3f38] shadow-sm">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-text-secondary">
                        search
                    </span>
                    <input 
                        type="text" 
                        id="product-search"
                        placeholder="Поиск продуктов... (яйца, курица, хлеб)"
                        autocomplete="off"
                        class="w-full pl-12 pr-4 py-4 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white placeholder-text-secondary focus:border-primary focus:outline-none transition-colors"
                    >
                    <!-- Dropdown результатов -->
                    <div id="search-results" class="hidden absolute top-full left-0 right-0 mt-2 bg-white dark:bg-[#152822] rounded-xl border border-[#dbe6e2] dark:border-[#2a3f38] shadow-lg max-h-96 overflow-y-auto z-50">
                        <!-- JS заполнит -->
                    </div>
                </div>
            </div>
            
            <!-- Calories Big Stat -->
            <div class="bg-white dark:bg-[#152822] rounded-3xl p-8 mb-8 border border-[#dbe6e2] dark:border-[#2a3f38] shadow-sm">
                <div class="flex flex-col md:flex-row gap-10 items-center justify-between">
                    <!-- Left: Number -->
                    <div class="flex flex-col gap-1 text-center md:text-left">
                        <span class="text-text-secondary font-semibold uppercase tracking-wider text-xs">Осталось калорий</span>
                        <div class="flex items-baseline gap-2 justify-center md:justify-start">
                            <span class="text-6xl md:text-7xl font-extrabold text-text-primary dark:text-white tracking-tighter" id="calories-left">
                                <?= $calorieGoal ?>
                            </span>
                            <span class="text-xl font-medium text-text-secondary">ккал</span>
                        </div>
                    </div>
                    <!-- Middle: Progress -->
                    <div class="flex-1 w-full max-w-md flex flex-col gap-3">
                        <div class="flex justify-between items-end text-sm">
                            <span class="font-bold text-text-primary dark:text-white">
                                <span id="calories-eaten">0</span> съедено
                            </span>
                            <span class="text-text-secondary">Цель: <?= $calorieGoal ?></span>
                        </div>
                        <div class="h-4 w-full bg-background-light dark:bg-[#1c3029] rounded-full overflow-hidden">
                            <div id="calories-progress" class="h-full bg-primary rounded-full transition-all duration-1000 ease-out" style="width: 0%;"></div>
                        </div>
                    </div>
                    <!-- Right: Macros -->
                    <div class="flex gap-6" id="macros-circles">
                        <!-- JS заполнит -->
                    </div>
                </div>
            </div>
            
            <!-- Meals Section -->
            <div class="flex flex-col gap-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-text-primary dark:text-white">Приёмы пищи</h2>
                </div>
                
                <div id="meals-list">
                    <!-- JS заполнит -->
                </div>
            </div>
        </div>
    </section>
    
    <!-- Statistics Screen -->
    <section id="stats" class="screen hidden">
        <div class="w-full px-8 py-10">
            <h2 class="text-3xl font-bold mb-6">Статистика</h2>
            <p class="text-text-secondary">Скоро здесь будут графики и аналитика 📊</p>
        </div>
    </section>
    
    <!-- Favorites Screen -->
    <section id="favorites" class="screen hidden">
        <div class="w-full px-8 py-10">
            <h2 class="text-3xl font-bold mb-6">Избранное</h2>
            <p class="text-text-secondary">Скоро здесь будут избранные продукты ⭐</p>
        </div>
    </section>
    
</main>

<!-- Раскрывающаяся панель добавления продукта -->
<div id="add-product-panel" class="fixed bottom-0 left-0 right-0 bg-white dark:bg-[#152822] border-t-2 border-primary shadow-2xl transform translate-y-full transition-transform duration-300 z-50">
    <div class="max-w-2xl mx-auto p-6">
        <!-- Заголовок -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 id="panel-product-name" class="text-lg font-bold text-text-primary dark:text-white">Выберите продукт</h3>
                <p id="panel-product-nutrition" class="text-sm text-text-secondary mt-1">КБЖУ на 100г</p>
            </div>
            <button onclick="window.closeAddPanel()" class="text-text-secondary hover:text-red-500 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <!-- Форма -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Количество -->
            <div>
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">
                    Количество (грамм)
                </label>
                <input 
                    type="number" 
                    id="panel-grams"
                    value="100"
                    min="1"
                    max="10000"
                    class="w-full px-4 py-3 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white focus:border-primary focus:outline-none transition-colors"
                >
            </div>
            
            <!-- Приём пищи -->
            <div>
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">
                    Приём пищи
                </label>
                <select 
                    id="panel-meal-type"
                    class="w-full px-4 py-3 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white focus:border-primary focus:outline-none transition-colors"
                >
                    <option value="завтрак">Завтрак</option>
                    <option value="обед">Обед</option>
                    <option value="ужин">Ужин</option>
                    <option value="перекус">Перекус</option>
                </select>
            </div>
            
            <!-- Кнопка -->
            <div class="flex items-end">
                <button 
                    onclick="window.submitAddProduct()"
                    class="w-full bg-primary hover:bg-primary-hover text-white py-3 rounded-xl font-bold transition-all transform active:scale-95 flex items-center justify-center gap-2"
                >
                    <span class="material-symbols-outlined">add</span>
                    Добавить
                </button>
            </div>
        </div>
        
        <!-- Рассчитанные КБЖУ -->
        <div id="panel-calculated-nutrition" class="grid grid-cols-4 gap-4 p-4 bg-background-light dark:bg-[#1c3029] rounded-xl">
            <div class="text-center">
                <div class="text-2xl font-bold text-primary" id="calc-calories">0</div>
                <div class="text-xs text-text-secondary mt-1">ккал</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-500" id="calc-proteins">0</div>
                <div class="text-xs text-text-secondary mt-1">белки</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-yellow-500" id="calc-fats">0</div>
                <div class="text-xs text-text-secondary mt-1">жиры</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-500" id="calc-carbs">0</div>
                <div class="text-xs text-text-secondary mt-1">углеводы</div>
            </div>
        </div>
    </div>
</div>

<!-- Модалка подтверждения удаления -->
<div id="delete-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-[#152822] rounded-2xl p-6 max-w-md mx-4 shadow-2xl">
        <div class="flex items-start gap-4 mb-6">
            <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-red-600 dark:text-red-400 text-2xl">delete</span>
            </div>
            <div>
                <h3 class="text-lg font-bold text-text-primary dark:text-white mb-2">Удалить продукт?</h3>
                <p id="delete-modal-text" class="text-sm text-text-secondary">Это действие нельзя отменить</p>
            </div>
        </div>
        
        <div class="flex gap-3">
            <button 
                onclick="window.closeDeleteModal()"
                class="flex-1 px-4 py-3 bg-background-light dark:bg-[#1c3029] text-text-primary dark:text-white rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-[#2a3f38] transition-colors"
            >
                Отмена
            </button>
            <button 
                onclick="window.confirmDelete()"
                class="flex-1 px-4 py-3 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 transition-colors"
            >
                Удалить
            </button>
        </div>
    </div>
</div>

<!-- Передаём данные в JS -->
<script>
    window.APP_USER_ID = <?= $userId ?>;
    window.APP_USER_NAME = '<?= htmlspecialchars($userName, ENT_QUOTES) ?>';
    window.APP_CALORIE_GOAL = <?= $calorieGoal ?>;
    
    // Показываем контент после загрузки шрифтов
    if (document.fonts) {
        document.fonts.ready.then(() => {
            document.body.classList.add('fonts-loaded');
        });
    } else {
        // Fallback для старых браузеров
        window.addEventListener('load', () => {
            document.body.classList.add('fonts-loaded');
        });
    }
</script>

<script type="module" src="/assets/js/app.js"></script>

</body>
</html>
