<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Config.php';

use HealthDiet\Config;
use HealthDiet\Controllers\MealController;
use HealthDiet\Controllers\DayController;

header('Content-Type: application/json');

Config::init();
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Требуется авторизация']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_day':
            // GET /api/meals.php?action=get_day&date=2025-12-15
            $result = DayController::getDay();
            break;
            
        case 'get_summary':
            // GET /api/meals.php?action=get_summary&date=2025-12-15
            $result = DayController::getSummary();
            break;
            
        case 'add':
            // POST /api/meals.php?action=add
            // Body: { "product_id": 123, "grams": 150, "meal_type": "завтрак", "date": "2025-12-15" }
            $result = MealController::add();
            break;
            
        case 'update':
            // POST /api/meals.php?action=update
            // Body: { "meal_id": 5, "grams": 200, "meal_type": "обед" }
            $result = MealController::update();
            break;
            
        case 'delete':
            // DELETE /api/meals.php?action=delete&id=5
            // или POST с meal_id в body
            $result = MealController::delete();
            break;
            
        case 'get_stats':
            // GET /api/meals.php?action=get_stats&days=7
            $result = DayController::getStats();
            break;
            
        default:
            http_response_code(400);
            $result = ['error' => 'Неизвестное действие'];
            break;
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Ошибка сервера',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
