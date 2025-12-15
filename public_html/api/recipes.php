<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Config.php';

use HealthDiet\Config;
use HealthDiet\Controllers\RecipeController;

header('Content-Type: application/json');

Config::init();
session_start();

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'list':
            // GET /api/recipes.php?action=list
            $result = RecipeController::list();
            break;
            
        case 'get':
            // GET /api/recipes.php?action=get&id=123
            $result = RecipeController::get();
            break;
            
        case 'search':
            // GET /api/recipes.php?action=search&q=творог
            $result = RecipeController::search();
            break;
            
        case 'create':
            // POST /api/recipes.php?action=create
            if ($method !== 'POST') {
                http_response_code(405);
                $result = ['error' => 'Метод не разрешён'];
                break;
            }
            $result = RecipeController::create();
            break;
            
        case 'add_ingredient':
            // POST /api/recipes.php?action=add_ingredient
            if ($method !== 'POST') {
                http_response_code(405);
                $result = ['error' => 'Метод не разрешён'];
                break;
            }
            $result = RecipeController::addIngredient();
            break;
            
        case 'remove_ingredient':
            // POST /api/recipes.php?action=remove_ingredient
            if ($method !== 'POST') {
                http_response_code(405);
                $result = ['error' => 'Метод не разрешён'];
                break;
            }
            $result = RecipeController::removeIngredient();
            break;
            
        case 'delete':
            // POST /api/recipes.php?action=delete
            if ($method !== 'POST') {
                http_response_code(405);
                $result = ['error' => 'Метод не разрешён'];
                break;
            }
            $result = RecipeController::delete();
            break;
            
        case 'update':
            // POST /api/recipes.php?action=update
            if ($method !== 'POST') {
                http_response_code(405);
                $result = ['error' => 'Метод не разрешён'];
                break;
            }
            $result = RecipeController::update();
            break;
            
        case 'update_ingredient':
            // POST /api/recipes.php?action=update_ingredient
            if ($method !== 'POST') {
                http_response_code(405);
                $result = ['error' => 'Метод не разрешён'];
                break;
            }
            $result = RecipeController::updateIngredient();
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
