<?php

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/Models/Product.php';
require_once __DIR__ . '/../src/Models/Day.php';
require_once __DIR__ . '/../src/Models/Meal.php';
require_once __DIR__ . '/../src/Controllers/ProductController.php';
require_once __DIR__ . '/../src/Controllers/DayController.php';
require_once __DIR__ . '/../src/Controllers/MealController.php';

use HealthDiet\Config;
use HealthDiet\Router;
use HealthDiet\Controllers\ProductController;
use HealthDiet\Controllers\DayController;
use HealthDiet\Controllers\MealController;

// Инициализация
Config::init();

// CORS заголовки (если нужно для AJAX)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight запрос
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Создаём роутер
$router = new Router();

// === ПРОДУКТЫ ===
$router->get('search', [ProductController::class, 'search']);
$router->get('get_product', [ProductController::class, 'getById']);
$router->get('calculate', [ProductController::class, 'calculate']);

// === ДНИ ===
$router->get('get_day', [DayController::class, 'getDay']);
$router->get('get_summary', [DayController::class, 'getSummary']);
$router->get('get_recent_days', [DayController::class, 'getRecentDays']);
$router->get('get_stats', [DayController::class, 'getStats']);

// === ПРИЁМЫ ПИЩИ ===
$router->post('add_meal', [MealController::class, 'add']);
$router->post('delete_meal', [MealController::class, 'delete']);
$router->post('update_meal', [MealController::class, 'update']);

// Обрабатываем запрос
$router->handle();