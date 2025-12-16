<?php
/**
 * API для пользователя
 * - update_goal: обновление цели калорий
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Config.php';

use HealthDiet\Config;
use HealthDiet\Database;

Config::init();
session_start();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Требуется авторизация']);
        exit;
    }
    
    switch ($action) {
        case 'update_goal':
            if ($method !== 'POST') {
                http_response_code(405);
                $result = ['error' => 'Метод не разрешён'];
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $calorieGoal = (int)($input['calorie_goal'] ?? 2000);
            
            if ($calorieGoal < 500 || $calorieGoal > 10000) {
                $result = ['error' => 'Цель должна быть от 500 до 10000 ккал'];
                break;
            }
            
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE users SET calorie_goal = :goal WHERE id = :id");
            $stmt->bindValue(':goal', $calorieGoal, PDO::PARAM_INT);
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $_SESSION['calorie_goal'] = $calorieGoal;
            
            $result = [
                'success' => true,
                'calorie_goal' => $calorieGoal
            ];
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
