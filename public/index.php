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
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            h1 { color: #e74c3c; }
        </style>
    </head>
    <body>
        <h1>🔒 Доступ закрыт</h1>
        <p>Добавьте <code>?token=start</code> к URL</p>
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🍽️ Трекер Питания</h1>
            
            <!-- Навигация по датам -->
            <div class="date-nav">
                <button id="prev-day" class="btn btn-nav">← Вчера</button>
                <input type="date" id="current-date" value="<?= $currentDate ?>">
                <button id="next-day" class="btn btn-nav">Завтра →</button>
                <button id="today" class="btn btn-secondary">Сегодня</button>
                <button id="stats-btn" class="btn btn-stats">Статистика</button>
            </div>
        </header>

        <main class="main">
            <!-- Поиск продуктов -->
            <section class="search-section">
                <div class="search-box">
                    <input 
                        type="text" 
                        id="product-search" 
                        placeholder="🔍 Начните вводить название продукта..."
                        autocomplete="off"
                    >
                    <div id="search-results" class="search-results" style="display: none;"></div>
                </div>
            </section>

            <!-- Модальное окно добавления продукта -->
            <div id="add-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="modal-close">&times;</span>
                    <h3 id="modal-product-name">Название продукта</h3>
                    <p class="product-category" id="modal-product-category"></p>
                    
                    <div class="form-group">
                        <label for="product-grams">Количество грамм:</label>
                        <input 
                            type="number" 
                            id="product-grams" 
                            min="1" 
                            max="10000" 
                            value="100"
                            step="1"
                        >
                    </div>

                    <div class="form-group">
                        <label for="meal-type">Тип приёма пищи (опционально):</label>
                        <select id="meal-type">
                            <option value="">Не указан</option>
                            <option value="завтрак">Завтрак</option>
                            <option value="обед">Обед</option>
                            <option value="ужин">Ужин</option>
                            <option value="перекус">Перекус</option>
                        </select>
                    </div>

                    <div class="nutrition-preview" id="nutrition-preview">
                        <p><strong>На 100г:</strong></p>
                        <p>Калории: <span id="preview-calories">0</span> ккал</p>
                        <p>Белки: <span id="preview-proteins">0</span>г | 
                           Жиры: <span id="preview-fats">0</span>г | 
                           Углеводы: <span id="preview-carbs">0</span>г</p>
                    </div>

                    <button id="add-meal-btn" class="btn btn-primary">Добавить</button>
                </div>
            </div>

            <!-- Список приёмов пищи -->
            <section class="meals-section">
                <h2>Добавленные продукты</h2>
                <div id="meals-list" class="meals-list">
                    <p class="empty-state">Пока ничего не добавлено</p>
                </div>
            </section>

            <!-- Саммари дня -->
            <section class="summary-section">
                <h2>📊 Итого за день</h2>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Калории</div>
                        <div class="summary-value" id="total-calories">0</div>
                        <div class="summary-unit">ккал</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Белки</div>
                        <div class="summary-value" id="total-proteins">0</div>
                        <div class="summary-unit">г</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Жиры</div>
                        <div class="summary-value" id="total-fats">0</div>
                        <div class="summary-unit">г</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Углеводы</div>
                        <div class="summary-value" id="total-carbs">0</div>
                        <div class="summary-unit">г</div>
                    </div>
                </div>
            </section>

            <!-- Модальное окно статистики -->
            <div id="stats-modal" class="modal" style="display: none;">
                <div class="modal-content modal-stats">
                    <span class="modal-close" id="stats-modal-close">&times;</span>
                    <h2>📊 Статистика за неделю</h2>
                    
                    <div class="stats-period">
                        <button class="btn btn-secondary btn-sm" data-days="7">7 дней</button>
                        <button class="btn btn-secondary btn-sm" data-days="14">14 дней</button>
                        <button class="btn btn-secondary btn-sm" data-days="30">30 дней</button>
                    </div>
                    
                    <div id="stats-content" class="stats-content">
                        <div class="stats-loading">Загрузка...</div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Передаём токен в JS
        window.APP_TOKEN = '<?= $token ?>';
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>