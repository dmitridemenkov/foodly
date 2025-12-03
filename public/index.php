<?php
require_once __DIR__ . '/../src/Config.php';
use HealthDiet\Config;

Config::init();

// Простая "авторизация" по токену
$token = $_GET['token'] ?? '';
$isAuthorized = ($token === Config::ACCESS_TOKEN);

if (!$isAuthorized) {
    http_response_code(403);
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Доступ закрыт</title>
        <link rel="stylesheet" href="assets/css/output.css">
    </head>
    <body class="bg-gradient-to-br from-purple-500 to-purple-700 min-h-screen flex items-center justify-center p-5">
        <div class="bg-white rounded-2xl shadow-2xl p-10 text-center max-w-md">
            <div class="text-6xl mb-5">🔒</div>
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Доступ закрыт</h1>
            <p class="text-gray-600 mb-6">Добавьте <code class="bg-gray-100 px-2 py-1 rounded">?token=start</code> к URL</p>
            <a href="?token=start" class="inline-block bg-purple-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-purple-700 transition">
                Войти
            </a>
        </div>
    </body>
    </html>';
    exit;
}

$currentDate = date(Config::DATE_FORMAT);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍽️ Трекер Питания</title>
    <link rel="stylesheet" href="assets/css/output.css">
</head>
<body class="bg-gradient-to-br from-purple-500 to-purple-700 min-h-screen p-5">
    <div class="max-w-4xl mx-auto">
        <!-- HEADER -->
        <header class="bg-white rounded-2xl shadow-xl p-6 mb-5">
            <h1 class="text-3xl font-bold text-center mb-6 text-purple-600">🍽️ Трекер Питания</h1>
            
            <!-- Навигация по датам -->
            <div class="flex flex-wrap gap-3 items-center justify-center">
                <button id="prev-day" class="bg-purple-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-purple-700 transition">
                    ← Вчера
                </button>
                <input type="date" id="current-date" value="<?= $currentDate ?>" 
                    class="px-4 py-2 border-2 border-gray-300 rounded-lg font-semibold focus:border-purple-600 focus:outline-none">
                <button id="next-day" class="bg-purple-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-purple-700 transition">
                    Завтра →
                </button>
                <button id="today" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400 transition">
                    Сегодня
                </button>
                <button id="stats-btn" class="bg-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700 transition">
                    📊 Статистика
                </button>
            </div>
        </header>

        <main class="space-y-5">
            <!-- ПОИСК -->
            <section class="bg-white rounded-2xl shadow-xl p-6">
                <div class="relative">
                    <input 
                        type="text" 
                        id="product-search" 
                        placeholder="🔍 Начните вводить название продукта..."
                        autocomplete="off"
                        class="w-full px-5 py-3 border-2 border-gray-300 rounded-xl text-base focus:border-purple-600 focus:outline-none transition"
                    >
                    <div id="search-results" class="absolute top-full left-0 right-0 bg-white border-2 border-gray-300 border-t-0 rounded-b-xl max-h-96 overflow-y-auto shadow-lg z-50 hidden"></div>
                </div>
            </section>

            <!-- МОДАЛКА ДОБАВЛЕНИЯ -->
            <div id="add-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-5 z-50 hidden">
                <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-lg w-full relative">
                    <span class="modal-close absolute top-4 right-4 text-4xl text-gray-400 hover:text-gray-800 cursor-pointer">&times;</span>
                    <h3 id="modal-product-name" class="text-2xl font-bold text-gray-800 mb-2">Название продукта</h3>
                    <p id="modal-product-category" class="text-gray-500 text-sm mb-5"></p>
                    
                    <div class="mb-5">
                        <label for="product-grams" class="block mb-2 font-semibold text-gray-800">Количество грамм:</label>
                        <input 
                            type="number" 
                            id="product-grams" 
                            min="1" 
                            max="10000" 
                            value="100"
                            step="1"
                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg text-base focus:border-purple-600 focus:outline-none transition"
                        >
                    </div>

                    <div class="mb-5">
                        <label for="meal-type" class="block mb-2 font-semibold text-gray-800">Тип приёма пищи (опционально):</label>
                        <select id="meal-type" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg text-base focus:border-purple-600 focus:outline-none transition">
                            <option value="">Не указан</option>
                            <option value="завтрак">Завтрак</option>
                            <option value="обед">Обед</option>
                            <option value="ужин">Ужин</option>
                            <option value="перекус">Перекус</option>
                        </select>
                    </div>

                    <div class="bg-purple-50 p-4 rounded-lg mb-5">
                        <p class="font-bold mb-1">На введённое количество:</p>
                        <p class="text-gray-700">Калории: <span id="preview-calories" class="font-semibold">0</span> ккал</p>
                        <p class="text-gray-700">
                            Белки: <span id="preview-proteins" class="font-semibold">0</span>г | 
                            Жиры: <span id="preview-fats" class="font-semibold">0</span>г | 
                            Углеводы: <span id="preview-carbs" class="font-semibold">0</span>г
                        </p>
                    </div>

                    <button id="add-meal-btn" class="w-full bg-purple-600 text-white py-3 rounded-lg text-base font-semibold hover:bg-purple-700 transition">
                        Добавить
                    </button>
                </div>
            </div>

            <!-- СПИСОК ПРИЁМОВ ПИЩИ -->
            <section class="bg-white rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Добавленные продукты</h2>
                <div id="meals-list" class="space-y-3">
                    <p class="text-center py-10 text-gray-400 italic">Пока ничего не добавлено</p>
                </div>
            </section>

            <!-- САММАРИ ДНЯ -->
            <section class="bg-white rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-center text-gray-800 mb-5">📊 Итого за день</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-700 p-5 rounded-xl text-center text-white">
                        <div class="text-sm opacity-90 mb-2">Калории</div>
                        <div id="total-calories" class="text-3xl font-bold mb-1">0</div>
                        <div class="text-sm opacity-80">ккал</div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500 to-purple-700 p-5 rounded-xl text-center text-white">
                        <div class="text-sm opacity-90 mb-2">Белки</div>
                        <div id="total-proteins" class="text-3xl font-bold mb-1">0</div>
                        <div class="text-sm opacity-80">г</div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500 to-purple-700 p-5 rounded-xl text-center text-white">
                        <div class="text-sm opacity-90 mb-2">Жиры</div>
                        <div id="total-fats" class="text-3xl font-bold mb-1">0</div>
                        <div class="text-sm opacity-80">г</div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500 to-purple-700 p-5 rounded-xl text-center text-white">
                        <div class="text-sm opacity-90 mb-2">Углеводы</div>
                        <div id="total-carbs" class="text-3xl font-bold mb-1">0</div>
                        <div class="text-sm opacity-80">г</div>
                    </div>
                </div>
            </section>

            <!-- МОДАЛКА СТАТИСТИКИ -->
            <div id="stats-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-5 z-50 hidden">
                <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-3xl w-full relative max-h-[90vh] overflow-y-auto">
                    <span id="stats-modal-close" class="absolute top-4 right-4 text-4xl text-gray-400 hover:text-gray-800 cursor-pointer">&times;</span>
                    <h2 class="text-2xl font-bold text-center text-gray-800 mb-5">📊 Статистика за неделю</h2>
                    
                    <div class="flex gap-3 justify-center mb-6">
                        <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg font-semibold hover:bg-purple-600 hover:text-white transition" data-days="7">7 дней</button>
                        <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg font-semibold hover:bg-purple-600 hover:text-white transition" data-days="14">14 дней</button>
                        <button class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg font-semibold hover:bg-purple-600 hover:text-white transition" data-days="30">30 дней</button>
                    </div>
                    
                    <div id="stats-content" class="min-h-48">
                        <div class="text-center py-10 text-gray-400 italic">Загрузка...</div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        window.APP_TOKEN = '<?= $token ?>';
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>