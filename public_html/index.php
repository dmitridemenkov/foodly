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
$userEmail = $_SESSION['user_email'] ?? '';
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        
        /* Мобильное меню */
        @media (max-width: 991px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100%;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                box-shadow: none;
            }
            
            .sidebar.open {
                transform: translateX(0);
                box-shadow: 0 0 50px rgba(0,0,0,0.3);
            }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 15;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .sidebar-overlay.open {
                display: block;
                opacity: 1;
            }
            
            .burger-btn {
                display: flex !important;
            }
            
            .main-content {
                margin-left: 0 !important;
            }
        }
        
        @media (min-width: 992px) {
            .sidebar-overlay {
                display: none !important;
            }
            
            .burger-btn {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-text-primary antialiased h-screen overflow-hidden flex">

<!-- Overlay для мобильного меню -->
<div id="sidebar-overlay" class="sidebar-overlay" onclick="window.closeMobileMenu()"></div>

<!-- Кнопка бургер (мобильная) -->
<button 
    id="burger-btn"
    onclick="window.toggleMobileMenu()"
    class="burger-btn hidden fixed top-4 right-4 z-30 w-12 h-12 bg-white dark:bg-[#152822] rounded-xl shadow-lg items-center justify-center text-text-primary dark:text-white border border-[#dbe6e2] dark:border-[#2a3f38]"
>
    <span class="material-symbols-outlined text-2xl" id="burger-icon">menu</span>
</button>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar w-[350px] h-full bg-white dark:bg-[#152822] flex flex-col border-r border-[#dbe6e2] dark:border-[#2a3f38] flex-shrink-0 z-20 overflow-y-auto hide-scrollbar">
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
        <button data-screen="my-products" class="nav-btn flex items-center gap-3 px-4 py-3 hover:bg-background-light dark:hover:bg-[#1c3029] rounded-xl group transition-all">
            <span class="material-symbols-outlined text-text-secondary group-hover:text-text-primary dark:group-hover:text-white">grocery</span>
            <span class="text-sm font-medium text-text-secondary group-hover:text-text-primary dark:group-hover:text-white">Мои продукты</span>
        </button>
        <a href="/logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-background-light dark:hover:bg-[#1c3029] rounded-xl group transition-all">
            <span class="material-symbols-outlined text-text-secondary group-hover:text-red-500">logout</span>
            <span class="text-sm font-medium text-text-secondary group-hover:text-red-500">Выход</span>
        </a>
    </nav>
    
    <!-- User Profile -->
    <div class="p-4 border-t border-[#dbe6e2] dark:border-[#2a3f38]">
        <a href="/settings.php" class="flex items-center gap-3 p-2 -m-2 rounded-xl hover:bg-background-light dark:hover:bg-[#1c3029] transition-colors group">
            <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold text-lg">
                <?= mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'), 'UTF-8') ?>
            </div>
            <div class="flex flex-col flex-1 min-w-0">
                <span class="text-sm font-bold text-text-primary dark:text-white"><?= htmlspecialchars($userName) ?></span>
                <span class="text-xs text-text-secondary truncate"><?= htmlspecialchars($userEmail) ?></span>
            </div>
            <span class="material-symbols-outlined text-text-secondary group-hover:text-primary transition-colors">settings</span>
        </a>
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
                <div class="relative flex gap-3">
                    <div class="relative flex-1">
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
                    
                    <!-- Barcode Button -->
                    <div class="relative">
                        <button 
                            id="barcode-btn"
                            onclick="window.toggleBarcodeMenu()"
                            class="w-14 h-14 flex items-center justify-center bg-background-light dark:bg-[#1c3029] hover:bg-primary hover:text-white rounded-xl transition-colors text-text-secondary"
                            title="Поиск по штрихкоду"
                        >
                            <span class="material-symbols-outlined text-2xl">barcode_scanner</span>
                        </button>
                        
                        <!-- Barcode Dropdown -->
                        <div id="barcode-menu" class="hidden absolute top-full right-0 mt-2 bg-white dark:bg-[#152822] rounded-xl border border-[#dbe6e2] dark:border-[#2a3f38] shadow-lg overflow-hidden z-50 min-w-[200px]">
                            <button 
                                onclick="window.openBarcodeScanner()"
                                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-background-light dark:hover:bg-[#1c3029] transition-colors text-left"
                            >
                                <span class="material-symbols-outlined text-primary">photo_camera</span>
                                <span class="text-text-primary dark:text-white font-medium">Сканировать камерой</span>
                            </button>
                            <button 
                                onclick="window.openBarcodeManual()"
                                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-background-light dark:hover:bg-[#1c3029] transition-colors text-left border-t border-[#dbe6e2] dark:border-[#2a3f38]"
                            >
                                <span class="material-symbols-outlined text-primary">keyboard</span>
                                <span class="text-text-primary dark:text-white font-medium">Ввести вручную</span>
                            </button>
                        </div>
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
                            <!-- Редактируемая цель -->
                            <div class="flex items-center gap-1">
                                <span id="calorie-goal-display" class="text-text-secondary cursor-pointer hover:text-text-primary transition-colors" onclick="window.startEditGoal()">
                                    Цель: <span id="calorie-goal-value"><?= $calorieGoal ?></span>
                                </span>
                                <button onclick="window.startEditGoal()" class="text-text-secondary/50 hover:text-primary transition-colors" title="Изменить цель">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <!-- Инпут (скрыт по умолчанию) -->
                                <input 
                                    type="number" 
                                    id="calorie-goal-input"
                                    class="hidden w-24 px-2 py-1 text-sm bg-white dark:bg-[#1c3029] border border-primary rounded text-right text-text-primary dark:text-white focus:outline-none"
                                    min="500"
                                    max="10000"
                                    onkeydown="if(event.key==='Enter') window.saveGoal(); if(event.key==='Escape') window.cancelEditGoal();"
                                    onblur="window.saveGoal()"
                                >
                            </div>
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
            <!-- Заголовок + переключатель периода -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
                <h2 class="text-3xl font-bold text-text-primary dark:text-white">Статистика</h2>
                <div class="flex gap-2">
                    <button 
                        class="period-btn px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg font-medium transition-colors"
                        data-days="7"
                        onclick="window.changePeriod(7)"
                    >7 дней</button>
                    <button 
                        class="period-btn px-4 py-2 bg-background-light dark:bg-[#1c3029] text-text-secondary rounded-lg font-medium transition-colors hover:bg-gray-200 dark:hover:bg-[#2a3f38]"
                        data-days="14"
                        onclick="window.changePeriod(14)"
                    >14 дней</button>
                    <button 
                        class="period-btn px-4 py-2 bg-background-light dark:bg-[#1c3029] text-text-secondary rounded-lg font-medium transition-colors hover:bg-gray-200 dark:hover:bg-[#2a3f38]"
                        data-days="30"
                        onclick="window.changePeriod(30)"
                    >30 дней</button>
                </div>
            </div>
            
            <!-- Контент статистики -->
            <div id="stats-content">
                <div class="flex items-center justify-center py-20">
                    <span class="material-symbols-outlined text-4xl animate-spin text-primary">progress_activity</span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Favorites Screen -->
    <section id="favorites" class="screen hidden">
        <div class="w-full px-8 py-10">
            <h2 class="text-3xl font-bold text-text-primary dark:text-white mb-8">Избранное</h2>
            <div id="favorites-content">
                <div class="flex items-center justify-center py-20">
                    <span class="material-symbols-outlined text-4xl animate-spin text-primary">progress_activity</span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- My Products Screen -->
    <section id="my-products" class="screen hidden">
        <div class="w-full px-8 py-10">
            <!-- Заголовок + кнопки добавления -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
                <h2 class="text-3xl font-bold text-text-primary dark:text-white">Мои продукты</h2>
                <div class="flex flex-wrap gap-3">
                    <button 
                        onclick="window.openCreateProductModal()"
                        class="flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-xl font-medium transition-colors"
                    >
                        <span class="material-symbols-outlined text-lg">add</span>
                        Добавить продукт
                    </button>
                    <button 
                        onclick="window.openCreateRecipeModal()"
                        class="flex items-center gap-2 px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-xl font-medium transition-colors"
                    >
                        <span class="material-symbols-outlined text-lg">add</span>
                        Добавить блюдо
                    </button>
                </div>
            </div>
            
            <!-- Табы: Продукты / Блюда -->
            <div class="flex gap-2 mb-4">
                <button 
                    id="tab-products" 
                    onclick="window.switchMyProductsTab('products')"
                    class="px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors"
                >
                    Продукты
                </button>
                <button 
                    id="tab-recipes" 
                    onclick="window.switchMyProductsTab('recipes')"
                    class="px-4 py-2 bg-background-light dark:bg-[#1c3029] text-text-secondary rounded-lg font-medium transition-colors hover:text-text-primary"
                >
                    Блюда
                </button>
            </div>
            
            <!-- Поиск -->
            <div class="relative mb-6">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-text-secondary">search</span>
                <input 
                    type="text" 
                    id="my-products-search"
                    placeholder="Быстрый поиск..."
                    class="w-full pl-12 pr-4 py-3 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white placeholder-text-secondary focus:border-primary focus:outline-none transition-colors"
                >
            </div>
            
            <!-- Список продуктов -->
            <div id="my-products-list" class="flex flex-col gap-3">
                <div class="text-center py-12 text-text-secondary">
                    <span class="material-symbols-outlined text-5xl mb-3">inventory_2</span>
                    <p>Загрузка...</p>
                </div>
            </div>
            
            <!-- Список блюд (скрыт по умолчанию) -->
            <div id="my-recipes-list" class="hidden flex flex-col gap-3">
                <div class="text-center py-12 text-text-secondary">
                    <span class="material-symbols-outlined text-5xl mb-3">menu_book</span>
                    <p>Загрузка...</p>
                </div>
            </div>
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

<!-- Модалка создания своего продукта -->
<div id="create-product-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-[#152822] rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-text-primary dark:text-white">Создать продукт</h3>
            <button onclick="window.closeCreateProductModal()" class="text-text-secondary hover:text-text-primary transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <div class="flex flex-col gap-4">
            <!-- Название -->
            <div>
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">
                    Название продукта
                </label>
                <input 
                    type="text" 
                    id="new-product-title"
                    placeholder="Например: Творог домашний"
                    class="w-full px-4 py-3 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white placeholder-text-secondary focus:border-primary focus:outline-none transition-colors"
                >
            </div>
            
            <!-- КБЖУ на 100г -->
            <p class="text-xs text-text-secondary -mb-2">Пищевая ценность на 100г:</p>
            
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Калории</label>
                    <input 
                        type="number" 
                        id="new-product-calories"
                        placeholder="0"
                        min="0"
                        step="0.1"
                        class="w-full px-3 py-2 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-lg text-text-primary dark:text-white focus:border-primary focus:outline-none transition-colors"
                    >
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Белки (г)</label>
                    <input 
                        type="number" 
                        id="new-product-proteins"
                        placeholder="0"
                        min="0"
                        step="0.1"
                        class="w-full px-3 py-2 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-lg text-text-primary dark:text-white focus:border-primary focus:outline-none transition-colors"
                    >
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Жиры (г)</label>
                    <input 
                        type="number" 
                        id="new-product-fats"
                        placeholder="0"
                        min="0"
                        step="0.1"
                        class="w-full px-3 py-2 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-lg text-text-primary dark:text-white focus:border-primary focus:outline-none transition-colors"
                    >
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Углеводы (г)</label>
                    <input 
                        type="number" 
                        id="new-product-carbs"
                        placeholder="0"
                        min="0"
                        step="0.1"
                        class="w-full px-3 py-2 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-lg text-text-primary dark:text-white focus:border-primary focus:outline-none transition-colors"
                    >
                </div>
            </div>
        </div>
        
        <div class="flex gap-3 mt-6">
            <button 
                onclick="window.closeCreateProductModal()"
                class="flex-1 px-4 py-3 bg-background-light dark:bg-[#1c3029] text-text-primary dark:text-white rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-[#2a3f38] transition-colors"
            >
                Отмена
            </button>
            <button 
                onclick="window.submitCreateProduct()"
                class="flex-1 px-4 py-3 bg-primary hover:bg-primary-hover text-white rounded-xl font-semibold transition-colors flex items-center justify-center gap-2"
            >
                <span class="material-symbols-outlined text-lg">add</span>
                Создать
            </button>
        </div>
    </div>
</div>

<!-- Модалка редактирования продукта -->
<div id="edit-product-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-[#152822] rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-text-primary dark:text-white">Редактировать продукт</h3>
            <button onclick="window.closeEditProductModal()" class="text-text-secondary hover:text-text-primary transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <input type="hidden" id="edit-product-id">
        
        <div class="flex flex-col gap-4">
            <div>
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">Название продукта</label>
                <input type="text" id="edit-product-title" class="w-full px-4 py-3 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white focus:border-primary focus:outline-none transition-colors">
            </div>
            
            <p class="text-xs text-text-secondary -mb-2">Пищевая ценность на 100г:</p>
            
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Калории</label>
                    <input type="number" id="edit-product-calories" min="0" step="0.1" class="w-full px-3 py-2 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-lg text-text-primary dark:text-white focus:border-primary focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Белки (г)</label>
                    <input type="number" id="edit-product-proteins" min="0" step="0.1" class="w-full px-3 py-2 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-lg text-text-primary dark:text-white focus:border-primary focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Жиры (г)</label>
                    <input type="number" id="edit-product-fats" min="0" step="0.1" class="w-full px-3 py-2 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-lg text-text-primary dark:text-white focus:border-primary focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-medium text-text-secondary mb-1">Углеводы (г)</label>
                    <input type="number" id="edit-product-carbs" min="0" step="0.1" class="w-full px-3 py-2 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-lg text-text-primary dark:text-white focus:border-primary focus:outline-none transition-colors">
                </div>
            </div>
        </div>
        
        <div class="flex gap-3 mt-6">
            <button onclick="window.closeEditProductModal()" class="flex-1 px-4 py-3 bg-background-light dark:bg-[#1c3029] text-text-primary dark:text-white rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-[#2a3f38] transition-colors">
                Отмена
            </button>
            <button onclick="window.submitEditProduct()" class="flex-1 px-4 py-3 bg-primary hover:bg-primary-hover text-white rounded-xl font-semibold transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">save</span>
                Сохранить
            </button>
        </div>
    </div>
</div>

<!-- Модалка создания блюда -->
<div id="create-recipe-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto py-8">
    <div class="bg-white dark:bg-[#152822] rounded-2xl p-6 max-w-lg w-full mx-4 shadow-2xl my-auto">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-text-primary dark:text-white">Создать блюдо</h3>
            <button onclick="window.closeCreateRecipeModal()" class="text-text-secondary hover:text-text-primary transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <div class="flex flex-col gap-4">
            <!-- Название -->
            <div>
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">Название блюда</label>
                <input 
                    type="text" 
                    id="new-recipe-title"
                    placeholder="Например: Творог со сметаной"
                    class="w-full px-4 py-3 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white placeholder-text-secondary focus:border-purple-500 focus:outline-none transition-colors"
                >
            </div>
            
            <!-- Порции -->
            <div>
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">Количество порций</label>
                <input 
                    type="number" 
                    id="new-recipe-servings"
                    value="1"
                    min="1"
                    onchange="window.renderRecipeIngredients && window.renderRecipeIngredients()"
                    class="w-24 px-4 py-2 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white focus:border-purple-500 focus:outline-none transition-colors"
                >
            </div>
            
            <!-- Поиск ингредиентов -->
            <div>
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">Добавить ингредиенты</label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="recipe-ingredient-search"
                        placeholder="Поиск продуктов..."
                        autocomplete="off"
                        class="w-full px-4 py-3 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white placeholder-text-secondary focus:border-purple-500 focus:outline-none transition-colors"
                    >
                    <div id="recipe-search-results" class="hidden absolute top-full left-0 right-0 mt-1 bg-white dark:bg-[#152822] rounded-xl border border-[#dbe6e2] dark:border-[#2a3f38] shadow-lg max-h-48 overflow-y-auto z-10">
                    </div>
                </div>
            </div>
            
            <!-- Список ингредиентов -->
            <div>
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">Ингредиенты</label>
                <div id="recipe-ingredients-list" class="bg-background-light dark:bg-[#1c3029] rounded-xl p-3 max-h-48 overflow-y-auto">
                    <p class="text-center text-text-secondary py-4">Добавьте ингредиенты через поиск выше</p>
                </div>
            </div>
            
            <!-- Итого КБЖУ -->
            <div id="recipe-totals"></div>
        </div>
        
        <div class="flex gap-3 mt-6">
            <button 
                onclick="window.closeCreateRecipeModal()"
                class="flex-1 px-4 py-3 bg-background-light dark:bg-[#1c3029] text-text-primary dark:text-white rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-[#2a3f38] transition-colors"
            >
                Отмена
            </button>
            <button 
                onclick="window.submitCreateRecipe()"
                class="flex-1 px-4 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-xl font-semibold transition-colors flex items-center justify-center gap-2"
            >
                <span class="material-symbols-outlined text-lg">add</span>
                Создать
            </button>
        </div>
    </div>
</div>

<!-- Модалка редактирования блюда -->
<div id="edit-recipe-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto py-8">
    <div class="bg-white dark:bg-[#152822] rounded-2xl p-6 max-w-lg w-full mx-4 shadow-2xl my-auto">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-text-primary dark:text-white">Редактировать блюдо</h3>
            <button onclick="window.closeEditRecipeModal()" class="text-text-secondary hover:text-text-primary transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <input type="hidden" id="edit-recipe-id">
        
        <div class="flex flex-col gap-4">
            <div>
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">Название блюда</label>
                <input type="text" id="edit-recipe-title" class="w-full px-4 py-3 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white focus:border-purple-500 focus:outline-none transition-colors">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">Количество порций</label>
                <input type="number" id="edit-recipe-servings" value="1" min="1" class="w-24 px-4 py-2 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white focus:border-purple-500 focus:outline-none transition-colors">
            </div>
            
            <!-- Добавление ингредиентов -->
            <div>
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">Добавить ингредиент</label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="edit-recipe-ingredient-search"
                        placeholder="Поиск продуктов..."
                        autocomplete="off"
                        class="w-full px-4 py-3 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white placeholder-text-secondary focus:border-purple-500 focus:outline-none transition-colors"
                    >
                    <div id="edit-recipe-search-results" class="hidden absolute top-full left-0 right-0 mt-1 bg-white dark:bg-[#152822] rounded-xl border border-[#dbe6e2] dark:border-[#2a3f38] shadow-lg max-h-48 overflow-y-auto z-10">
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-text-primary dark:text-white mb-2">Ингредиенты</label>
                <div id="edit-recipe-ingredients" class="bg-background-light dark:bg-[#1c3029] rounded-xl p-3 max-h-48 overflow-y-auto">
                    <p class="text-center text-text-secondary py-2">Загрузка...</p>
                </div>
            </div>
            
            <div id="edit-recipe-totals"></div>
        </div>
        
        <div class="flex gap-3 mt-6">
            <button onclick="window.closeEditRecipeModal()" class="flex-1 px-4 py-3 bg-background-light dark:bg-[#1c3029] text-text-primary dark:text-white rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-[#2a3f38] transition-colors">
                Отмена
            </button>
            <button onclick="window.submitEditRecipe()" class="flex-1 px-4 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-xl font-semibold transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">save</span>
                Сохранить
            </button>
        </div>
    </div>
</div>

<!-- Barcode Scanner Modal -->
<div id="barcode-scanner-modal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#152822] rounded-2xl w-full max-w-md overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-[#dbe6e2] dark:border-[#2a3f38]">
            <h3 class="text-lg font-bold text-text-primary dark:text-white">Сканировать штрихкод</h3>
            <button onclick="window.closeBarcodeScanner()" class="w-8 h-8 flex items-center justify-center hover:bg-background-light dark:hover:bg-[#1c3029] rounded-lg transition-colors">
                <span class="material-symbols-outlined text-text-secondary">close</span>
            </button>
        </div>
        <div class="p-4">
            <div id="barcode-scanner-container" class="w-full aspect-[4/3] bg-black rounded-xl overflow-hidden relative">
                <video id="barcode-video" class="w-full h-full object-cover"></video>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-64 h-32 border-2 border-primary rounded-lg"></div>
                </div>
            </div>
            <p class="text-center text-text-secondary text-sm mt-3">Наведите камеру на штрихкод</p>
        </div>
    </div>
</div>

<!-- Barcode Manual Input Modal -->
<div id="barcode-manual-modal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#152822] rounded-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-text-primary dark:text-white">Ввести штрихкод</h3>
            <button onclick="window.closeBarcodeManual()" class="w-8 h-8 flex items-center justify-center hover:bg-background-light dark:hover:bg-[#1c3029] rounded-lg transition-colors">
                <span class="material-symbols-outlined text-text-secondary">close</span>
            </button>
        </div>
        
        <div class="space-y-4">
            <div>
                <input 
                    type="text" 
                    id="barcode-input"
                    placeholder="Введите штрихкод (например: 4600682000013)"
                    inputmode="numeric"
                    class="w-full px-4 py-3 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white placeholder-text-secondary focus:border-primary focus:outline-none transition-colors text-center text-lg tracking-widest"
                >
            </div>
            
            <div id="barcode-error" class="hidden bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-4 py-3 rounded-xl text-sm"></div>
            
            <button 
                onclick="window.searchByBarcode()"
                class="w-full py-3 bg-primary hover:bg-primary-hover text-white rounded-xl font-semibold transition-colors flex items-center justify-center gap-2"
            >
                <span class="material-symbols-outlined">search</span>
                Найти продукт
            </button>
        </div>
    </div>
</div>

<!-- Barcode Result Modal -->
<div id="barcode-result-modal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#152822] rounded-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-text-primary dark:text-white">Найден продукт</h3>
            <button onclick="window.closeBarcodeResult()" class="w-8 h-8 flex items-center justify-center hover:bg-background-light dark:hover:bg-[#1c3029] rounded-lg transition-colors">
                <span class="material-symbols-outlined text-text-secondary">close</span>
            </button>
        </div>
        
        <div id="barcode-result-content">
            <!-- JS заполнит -->
        </div>
    </div>
</div>

<!-- AI Assistant Button -->
<button 
    onclick="window.toggleAIChat()"
    class="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-full shadow-lg hover:shadow-xl transition-all hover:scale-105 z-40 flex items-center justify-center group"
    title="Спросить Фиа"
>
    <div id="ai-circle" class="ai-circle idle">
        <div class="ai-circle-inner"></div>
    </div>
</button>

<!-- AI Chat Panel -->
<div id="ai-chat-panel" class="fixed top-0 right-0 h-full w-full max-w-md bg-background-light dark:bg-background-dark border-l border-[#dbe6e2] dark:border-[#2a3f38] shadow-2xl z-50 transform translate-x-full transition-transform duration-300 flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b border-[#dbe6e2] dark:border-[#2a3f38] bg-white dark:bg-[#152822]">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
                <span class="text-white font-bold">F</span>
            </div>
            <div>
                <h3 class="font-bold text-text-primary dark:text-white">Фиа</h3>
                <p id="ai-thinking" class="hidden text-xs text-primary">думает...</p>
                <p class="ai-status-online text-xs text-text-secondary">онлайн</p>
            </div>
        </div>
        <div class="flex items-center gap-1">
            <button onclick="window.clearAIHistory()" class="w-10 h-10 flex items-center justify-center hover:bg-background-light dark:hover:bg-[#1c3029] rounded-xl transition-colors" title="Очистить историю">
                <span class="material-symbols-outlined text-text-secondary">delete</span>
            </button>
            <button onclick="window.closeAIChat()" class="w-10 h-10 flex items-center justify-center hover:bg-background-light dark:hover:bg-[#1c3029] rounded-xl transition-colors">
                <span class="material-symbols-outlined text-text-secondary">close</span>
            </button>
        </div>
    </div>
    
    <!-- Messages -->
    <div id="ai-messages" class="flex-1 overflow-y-auto p-4">
        <div class="ai-welcome flex flex-col items-center justify-center h-full text-center">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center mb-4">
                <span class="text-white text-2xl font-bold">F</span>
            </div>
            <h4 class="text-lg font-bold text-text-primary dark:text-white mb-2">Привет! Я Фиа 👋</h4>
            <p class="text-text-secondary text-sm max-w-xs">
                Твой AI-помощник в Foodly. Спроси меня о питании, калориях или попроси совет!
            </p>
        </div>
    </div>
    
    <!-- Input -->
    <div class="p-4 border-t border-[#dbe6e2] dark:border-[#2a3f38] bg-white dark:bg-[#152822]">
        <div class="flex gap-3">
            <input 
                type="text" 
                id="ai-input"
                placeholder="Напиши сообщение..."
                class="flex-1 px-4 py-3 bg-background-light dark:bg-[#1c3029] border-2 border-transparent rounded-xl text-text-primary dark:text-white placeholder-text-secondary focus:border-primary focus:outline-none transition-colors"
            >
            <button 
                id="ai-send-btn"
                onclick="window.sendAIMessage()"
                class="w-12 h-12 bg-primary hover:bg-primary-hover text-white rounded-xl transition-colors flex items-center justify-center disabled:opacity-50"
            >
                <span class="material-symbols-outlined">send</span>
            </button>
        </div>
    </div>
</div>

<!-- AI Circle Styles -->
<style>
.ai-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    position: relative;
}

.ai-circle-inner {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: conic-gradient(from 0deg, #4ade80, #22c55e, #16a34a, #4ade80);
    animation: rotate 8s linear infinite;
}

.ai-circle.idle .ai-circle-inner {
    animation: rotate 8s linear infinite, breathe 4s ease-in-out infinite;
}

.ai-circle.thinking .ai-circle-inner {
    animation: rotate 1s linear infinite, pulse-think 1s ease-in-out infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes breathe {
    0%, 100% { opacity: 0.8; transform: rotate(0deg) scale(1); }
    50% { opacity: 1; transform: rotate(180deg) scale(1.05); }
}

@keyframes pulse-think {
    0%, 100% { transform: rotate(0deg) scale(1); box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7); }
    50% { transform: rotate(180deg) scale(1.1); box-shadow: 0 0 0 10px rgba(74, 222, 128, 0); }
}

#ai-thinking:not(.hidden) + .ai-status-online {
    display: none;
}
</style>

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
