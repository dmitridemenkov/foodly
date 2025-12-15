<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Config.php';

use HealthDiet\Config;
use HealthDiet\Controllers\ProductController;

header('Content-Type: application/json');

Config::init();
session_start();

$action = $_GET['action'] ?? 'search';
$method = $_SERVER['REQUEST_METHOD'];

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
            
        case 'create':
            // POST /api/products.php?action=create
            if ($method !== 'POST') {
                http_response_code(405);
                $result = ['error' => 'Метод не разрешён'];
                break;
            }
            $result = ProductController::create();
            break;
            
        case 'my_products':
            // GET /api/products.php?action=my_products
            $result = ProductController::getMyProducts();
            break;
            
        case 'delete':
            // POST /api/products.php?action=delete
            if ($method !== 'POST') {
                http_response_code(405);
                $result = ['error' => 'Метод не разрешён'];
                break;
            }
            $result = ProductController::delete();
            break;
            
        case 'update':
            // POST /api/products.php?action=update
            if ($method !== 'POST') {
                http_response_code(405);
                $result = ['error' => 'Метод не разрешён'];
                break;
            }
            $result = ProductController::update();
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
