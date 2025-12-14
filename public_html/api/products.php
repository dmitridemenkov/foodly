<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Config.php';

use HealthDiet\Config;
use HealthDiet\Controllers\ProductController;

header('Content-Type: application/json');

Config::init();

$action = $_GET['action'] ?? 'search';

try {
    switch ($action) {
        case 'search':
            // GET /api/products.php?action=search&q=яйца&limit=20
            $result = ProductController::search();
            break;
            
        case 'get':
            // GET /api/products.php?action=get&id=123
            $result = ProductController::getById();
            break;
            
        case 'calculate':
            // GET /api/products.php?action=calculate&id=123&grams=150
            $result = ProductController::calculate();
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
