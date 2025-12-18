<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Config.php';

use HealthDiet\Config;
use HealthDiet\Controllers\AuthController;

header('Content-Type: application/json');

Config::init();
$pdo = Config::getPdo();
$authController = new AuthController($pdo);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        // POST /api/auth.php?action=login
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            echo json_encode(['success' => false, 'error' => 'Не указаны email или пароль']);
            exit;
        }
        
        $result = $authController->login($data['email'], $data['password']);
        echo json_encode($result);
        break;
    
    case 'register':
        // POST /api/auth.php?action=register
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
            echo json_encode(['success' => false, 'error' => 'Заполните все поля']);
            exit;
        }
        
        $result = $authController->register($data['email'], $data['password'], $data['name'], $data['calorie_goal'] ?? 2000);
        echo json_encode($result);
        break;
        
    case 'logout':
        // POST /api/auth.php?action=logout
        $authController->logout();
        echo json_encode(['success' => true]);
        break;
        
    case 'check':
        // GET /api/auth.php?action=check
        $result = $authController->check();
        echo json_encode($result);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
        break;
}
