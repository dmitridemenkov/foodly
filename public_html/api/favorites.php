<?php
/**
 * API для избранного
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
    
    $db = Database::getConnection();
    
    switch ($action) {
        case 'list':
            // GET /api/favorites.php?action=list
            $result = getFavorites($db, $userId);
            break;
            
        case 'add':
            // POST /api/favorites.php?action=add
            if ($method !== 'POST') {
                http_response_code(405);
                $result = ['error' => 'Метод не разрешён'];
                break;
            }
            $result = addFavorite($db, $userId);
            break;
            
        case 'remove':
            // POST /api/favorites.php?action=remove
            if ($method !== 'POST') {
                http_response_code(405);
                $result = ['error' => 'Метод не разрешён'];
                break;
            }
            $result = removeFavorite($db, $userId);
            break;
            
        case 'check':
            // GET /api/favorites.php?action=check&product_id=123
            $result = checkFavorite($db, $userId);
            break;
            
        case 'ids':
            // GET /api/favorites.php?action=ids — получить все ID избранных
            $result = getFavoriteIds($db, $userId);
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

function getFavorites(PDO $db, int $userId): array
{
    // Получаем избранные продукты
    $stmt = $db->prepare("
        SELECT 
            f.id as favorite_id,
            'product' as type,
            p.id,
            p.title,
            p.calories as calories_raw,
            p.proteins,
            p.fats,
            p.carbohydrates,
            f.created_at
        FROM favorites f
        JOIN products p ON p.id = f.product_id
        WHERE f.user_id = :user_id AND f.product_id IS NOT NULL
    ");
    $stmt->execute([':user_id' => $userId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Парсим калории из строки "270 кКал" в число
    foreach ($products as &$p) {
        $p['calories'] = parseCalories($p['calories_raw']);
        unset($p['calories_raw']);
    }
    
    // Получаем избранные рецепты
    $stmt = $db->prepare("
        SELECT 
            f.id as favorite_id,
            'recipe' as type,
            r.id,
            r.title,
            f.created_at
        FROM favorites f
        JOIN recipes r ON r.id = f.recipe_id
        WHERE f.user_id = :user_id AND f.recipe_id IS NOT NULL
    ");
    $stmt->execute([':user_id' => $userId]);
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Подсчитываем КБЖУ для рецептов
    foreach ($recipes as &$r) {
        $stmt = $db->prepare("
            SELECT 
                ROUND(SUM(CAST(p.calories AS REAL) * ri.grams / 100), 0) as calories,
                ROUND(SUM(CAST(p.proteins AS REAL) * ri.grams / 100), 1) as proteins,
                ROUND(SUM(CAST(p.fats AS REAL) * ri.grams / 100), 1) as fats,
                ROUND(SUM(CAST(p.carbohydrates AS REAL) * ri.grams / 100), 1) as carbohydrates
            FROM recipe_ingredients ri
            JOIN products p ON p.id = ri.product_id
            WHERE ri.recipe_id = :recipe_id
        ");
        $stmt->execute([':recipe_id' => $r['id']]);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Парсим калории ингредиентов тоже
        $r['calories'] = $totals['calories'] ?? 0;
        $r['proteins'] = $totals['proteins'] ?? 0;
        $r['fats'] = $totals['fats'] ?? 0;
        $r['carbohydrates'] = $totals['carbohydrates'] ?? 0;
    }
    
    // Объединяем и сортируем по дате
    $favorites = array_merge($recipes, $products);
    usort($favorites, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
    
    return [
        'success' => true,
        'favorites' => $favorites,
        'count' => count($favorites)
    ];
}

function parseCalories($value): float
{
    if (is_numeric($value)) {
        return (float)$value;
    }
    // Извлекаем число из строки типа "270 кКал"
    if (preg_match('/[\d.]+/', $value, $matches)) {
        return (float)$matches[0];
    }
    return 0;
}

function addFavorite(PDO $db, int $userId): array
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    $productId = isset($input['product_id']) ? (int)$input['product_id'] : null;
    $recipeId = isset($input['recipe_id']) ? (int)$input['recipe_id'] : null;
    
    if (!$productId && !$recipeId) {
        return ['error' => 'Укажите product_id или recipe_id'];
    }
    
    // Проверяем, не добавлено ли уже
    $stmt = $db->prepare("
        SELECT id FROM favorites 
        WHERE user_id = :user_id 
        AND (product_id = :product_id OR recipe_id = :recipe_id)
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':product_id' => $productId,
        ':recipe_id' => $recipeId
    ]);
    
    if ($stmt->fetch()) {
        return ['success' => true, 'message' => 'Уже в избранном'];
    }
    
    $stmt = $db->prepare("
        INSERT INTO favorites (user_id, product_id, recipe_id)
        VALUES (:user_id, :product_id, :recipe_id)
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':product_id' => $productId,
        ':recipe_id' => $recipeId
    ]);
    
    return [
        'success' => true,
        'favorite_id' => (int)$db->lastInsertId()
    ];
}

function removeFavorite(PDO $db, int $userId): array
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    $productId = isset($input['product_id']) ? (int)$input['product_id'] : null;
    $recipeId = isset($input['recipe_id']) ? (int)$input['recipe_id'] : null;
    $favoriteId = isset($input['favorite_id']) ? (int)$input['favorite_id'] : null;
    
    if ($favoriteId) {
        $stmt = $db->prepare("DELETE FROM favorites WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $favoriteId, ':user_id' => $userId]);
    } elseif ($productId) {
        $stmt = $db->prepare("DELETE FROM favorites WHERE product_id = :product_id AND user_id = :user_id");
        $stmt->execute([':product_id' => $productId, ':user_id' => $userId]);
    } elseif ($recipeId) {
        $stmt = $db->prepare("DELETE FROM favorites WHERE recipe_id = :recipe_id AND user_id = :user_id");
        $stmt->execute([':recipe_id' => $recipeId, ':user_id' => $userId]);
    } else {
        return ['error' => 'Укажите favorite_id, product_id или recipe_id'];
    }
    
    return ['success' => true];
}

function checkFavorite(PDO $db, int $userId): array
{
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
    $recipeId = isset($_GET['recipe_id']) ? (int)$_GET['recipe_id'] : null;
    
    $stmt = $db->prepare("
        SELECT id FROM favorites 
        WHERE user_id = :user_id 
        AND (product_id = :product_id OR recipe_id = :recipe_id)
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':product_id' => $productId,
        ':recipe_id' => $recipeId
    ]);
    
    return [
        'success' => true,
        'is_favorite' => (bool)$stmt->fetch()
    ];
}

function getFavoriteIds(PDO $db, int $userId): array
{
    $stmt = $db->prepare("
        SELECT product_id, recipe_id FROM favorites WHERE user_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $productIds = [];
    $recipeIds = [];
    
    foreach ($rows as $row) {
        if ($row['product_id']) $productIds[] = (int)$row['product_id'];
        if ($row['recipe_id']) $recipeIds[] = (int)$row['recipe_id'];
    }
    
    return [
        'success' => true,
        'product_ids' => $productIds,
        'recipe_ids' => $recipeIds
    ];
}
