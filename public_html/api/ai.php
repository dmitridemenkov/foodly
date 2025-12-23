<?php
/**
 * AI Assistant API
 * Прокси к OpenRouter для работы с MiMo-V2-Flash
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Config.php';

use HealthDiet\Config;
use HealthDiet\Database;

Config::init();
session_start();

header('Content-Type: application/json; charset=utf-8');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Требуется авторизация']);
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Пользователь';

// OpenRouter API
define('OPENROUTER_API_KEY', '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');
define('OPENROUTER_URL', 'https://openrouter.ai/api/v1/chat/completions');
define('AI_MODEL', 'xiaomi/mimo-v2-flash:free');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'chat':
            $result = handleChat($userId, $userName);
            break;
            
        case 'context':
            $result = getUserContext($userId);
            break;
        
        case 'history':
            $result = getChatHistory($userId);
            break;
        
        case 'clear_history':
            $result = clearChatHistory($userId);
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

function handleChat(int $userId, string $userName): array
{
    $input = json_decode(file_get_contents('php://input'), true);
    $userMessage = trim($input['message'] ?? '');
    
    if (empty($userMessage)) {
        return ['error' => 'Сообщение не может быть пустым'];
    }
    
    $db = Database::getConnection();
    
    // Сохраняем сообщение пользователя
    $stmt = $db->prepare("INSERT INTO ai_messages (user_id, role, content) VALUES (:user_id, 'user', :content)");
    $stmt->execute([':user_id' => $userId, ':content' => $userMessage]);
    
    // Загружаем историю из БД (последние 10 сообщений)
    $stmt = $db->prepare("
        SELECT role, content FROM ai_messages 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([':user_id' => $userId]);
    $history = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // Получаем контекст пользователя
    $context = getUserContext($userId);
    
    // Формируем системный промпт
    $systemPrompt = buildSystemPrompt($userName, $context);
    
    // Собираем сообщения
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt]
    ];
    
    // Добавляем историю
    foreach ($history as $msg) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }
    
    // Добавляем текущее сообщение
    $messages[] = ['role' => 'user', 'content' => $userMessage];
    
    // Запрос к OpenRouter
    $response = callOpenRouter($messages);
    
    if (isset($response['error'])) {
        return $response;
    }
    
    // Сохраняем ответ AI в БД
    $stmt = $db->prepare("INSERT INTO ai_messages (user_id, role, content) VALUES (:user_id, 'assistant', :content)");
    $stmt->execute([':user_id' => $userId, ':content' => $response['content']]);
    
    return [
        'success' => true,
        'message' => $response['content'],
        'model' => AI_MODEL
    ];
}

function getChatHistory(int $userId): array
{
    $db = Database::getConnection();
    
    $stmt = $db->prepare("
        SELECT role, content, created_at 
        FROM ai_messages 
        WHERE user_id = :user_id 
        ORDER BY created_at ASC 
        LIMIT 50
    ");
    $stmt->execute([':user_id' => $userId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'messages' => $messages
    ];
}

function clearChatHistory(int $userId): array
{
    $db = Database::getConnection();
    
    $stmt = $db->prepare("DELETE FROM ai_messages WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    
    return [
        'success' => true,
        'message' => 'История очищена'
    ];
}

function buildSystemPrompt(string $userName, array $context): string
{
    $today = $context['today'] ?? [];
    $stats = $context['stats'] ?? [];
    $goal = $context['calorie_goal'] ?? 2000;
    $recipes = $context['recipes'] ?? [];
    $myProducts = $context['my_products'] ?? [];
    $favoriteProducts = $context['favorite_products'] ?? [];
    
    $todayCalories = $today['calories'] ?? 0;
    $todayProteins = $today['proteins'] ?? 0;
    $todayFats = $today['fats'] ?? 0;
    $todayCarbs = $today['carbohydrates'] ?? 0;
    $mealsCount = $today['meals_count'] ?? 0;
    
    $avgCalories = $stats['avg_calories'] ?? 0;
    $streak = $stats['streak'] ?? 0;
    
    // Форматируем рецепты
    $recipesText = "Нет сохранённых блюд";
    if (!empty($recipes)) {
        $recipesText = implode("\n", array_map(function($r) {
            return "- {$r['title']} ({$r['total_calories']} ккал, Б:{$r['total_proteins']}г Ж:{$r['total_fats']}г У:{$r['total_carbohydrates']}г)";
        }, $recipes));
    }
    
    // Форматируем свои продукты
    $myProductsText = "Нет своих продуктов";
    if (!empty($myProducts)) {
        $myProductsText = implode("\n", array_map(function($p) {
            return "- {$p['title']} ({$p['calories']} ккал/100г)";
        }, $myProducts));
    }
    
    // Форматируем избранное
    $favoritesText = "Нет избранных продуктов";
    if (!empty($favoriteProducts)) {
        $favoritesText = implode("\n", array_map(function($p) {
            return "- {$p['title']} ({$p['calories']} ккал/100г)";
        }, $favoriteProducts));
    }
    
    $prompt = <<<PROMPT
Ты — Фиа (fIa = Foodly AI), дружелюбный AI-ассистент в приложении Foodly для отслеживания питания.

ТВОЯ ЛИЧНОСТЬ:
- Общаешься на "ты", дружелюбно и с лёгким юмором
- Поддерживаешь и мотивируешь, но не навязчиво
- Можешь пошутить, но знаешь меру
- Отвечаешь кратко и по делу (2-4 предложения обычно)
- Используешь эмодзи умеренно
- Всегда проверяй грамматику: правильные падежи, лица глаголов, согласование
- Обращаешься к пользователю на "ты" (ты весишь, ты ешь, тебе нужно)

ДАННЫЕ ПОЛЬЗОВАТЕЛЯ:
- Имя: {$userName}
- Цель калорий в день: {$goal} ккал
- Streak (дней подряд с записями): {$streak}

СЕГОДНЯ ({$mealsCount} приёмов пищи):
- Калории: {$todayCalories} из {$goal} ккал
- Белки: {$todayProteins} г
- Жиры: {$todayFats} г
- Углеводы: {$todayCarbs} г

СТАТИСТИКА ЗА НЕДЕЛЮ:
- Среднее потребление: {$avgCalories} ккал/день

СОХРАНЁННЫЕ БЛЮДА (РЕЦЕПТЫ) ПОЛЬЗОВАТЕЛЯ:
{$recipesText}

СВОИ ПРОДУКТЫ ПОЛЬЗОВАТЕЛЯ:
{$myProductsText}

ИЗБРАННЫЕ ПРОДУКТЫ:
{$favoritesText}

ЧТО ТЫ УМЕЕШЬ:
- Показывать и рассказывать о сохранённых блюдах и продуктах пользователя
- Давать советы по питанию на основе данных пользователя
- Подсказывать, чего не хватает в рационе
- Отвечать на вопросы о продуктах и КБЖУ
- Мотивировать и поддерживать
- Предлагать идеи из имеющихся блюд и продуктов

ВАЖНО:
- Используй данные выше для ответов о рецептах и продуктах пользователя
- Если спрашивают о блюдах/рецептах — смотри раздел "СОХРАНЁННЫЕ БЛЮДА"
- Не выдумывай данные, используй только то, что дано выше
- Если не знаешь точный ответ — честно скажи
- Не давай медицинских советов, ты не врач
PROMPT;

    return $prompt;
}

function getUserContext(int $userId): array
{
    $db = Database::getConnection();
    
    // Сегодняшние данные
    $today = date('Y-m-d');
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(CAST(p.calories AS REAL) * m.grams / 100), 0) as calories,
            COALESCE(SUM(CAST(p.proteins AS REAL) * m.grams / 100), 0) as proteins,
            COALESCE(SUM(CAST(p.fats AS REAL) * m.grams / 100), 0) as fats,
            COALESCE(SUM(CAST(p.carbohydrates AS REAL) * m.grams / 100), 0) as carbohydrates,
            COUNT(m.id) as meals_count
        FROM days d
        LEFT JOIN meals m ON m.day_id = d.id
        LEFT JOIN products p ON p.id = m.product_id
        WHERE d.date = :date AND d.user_id = :user_id
    ");
    $stmt->execute([':date' => $today, ':user_id' => $userId]);
    $todayData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Статистика за неделю
    $weekAgo = date('Y-m-d', strtotime('-7 days'));
    $stmt = $db->prepare("
        SELECT 
            AVG(daily_cal) as avg_calories
        FROM (
            SELECT 
                d.date,
                SUM(CAST(p.calories AS REAL) * m.grams / 100) as daily_cal
            FROM days d
            JOIN meals m ON m.day_id = d.id
            JOIN products p ON p.id = m.product_id
            WHERE d.date BETWEEN :week_ago AND :today AND d.user_id = :user_id
            GROUP BY d.date
        )
    ");
    $stmt->execute([':week_ago' => $weekAgo, ':today' => $today, ':user_id' => $userId]);
    $weekStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Streak
    $stmt = $db->prepare("
        SELECT date FROM days 
        WHERE user_id = :user_id 
        ORDER BY date DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $allDates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $streak = 0;
    $checkDate = new DateTime();
    foreach ($allDates as $logDate) {
        if ($logDate === $checkDate->format('Y-m-d')) {
            $streak++;
            $checkDate->modify('-1 day');
        } else {
            break;
        }
    }
    
    // Рецепты пользователя с расчётом КБЖУ
    $stmt = $db->prepare("
        SELECT 
            r.id, 
            r.title,
            COALESCE(SUM(CAST(p.calories AS REAL) * ri.grams / 100), 0) as total_calories,
            COALESCE(SUM(CAST(p.proteins AS REAL) * ri.grams / 100), 0) as total_proteins,
            COALESCE(SUM(CAST(p.fats AS REAL) * ri.grams / 100), 0) as total_fats,
            COALESCE(SUM(CAST(p.carbohydrates AS REAL) * ri.grams / 100), 0) as total_carbohydrates
        FROM recipes r
        LEFT JOIN recipe_ingredients ri ON ri.recipe_id = r.id
        LEFT JOIN products p ON p.id = ri.product_id
        WHERE r.user_id = :user_id
        GROUP BY r.id
        ORDER BY r.title
        LIMIT 20
    ");
    $stmt->execute([':user_id' => $userId]);
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Продукты пользователя (свои)
    $stmt = $db->prepare("
        SELECT id, title, calories, proteins, fats, carbohydrates
        FROM products
        WHERE user_id = :user_id
        ORDER BY title
        LIMIT 30
    ");
    $stmt->execute([':user_id' => $userId]);
    $myProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Избранные продукты
    $stmt = $db->prepare("
        SELECT p.id, p.title, p.calories, p.proteins, p.fats, p.carbohydrates
        FROM favorites f
        JOIN products p ON p.id = f.product_id
        WHERE f.user_id = :user_id AND f.product_id IS NOT NULL
        LIMIT 20
    ");
    $stmt->execute([':user_id' => $userId]);
    $favoriteProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'today' => [
            'calories' => round($todayData['calories'] ?? 0, 1),
            'proteins' => round($todayData['proteins'] ?? 0, 1),
            'fats' => round($todayData['fats'] ?? 0, 1),
            'carbohydrates' => round($todayData['carbohydrates'] ?? 0, 1),
            'meals_count' => (int)($todayData['meals_count'] ?? 0)
        ],
        'stats' => [
            'avg_calories' => round($weekStats['avg_calories'] ?? 0, 1),
            'streak' => $streak
        ],
        'calorie_goal' => $_SESSION['calorie_goal'] ?? 2000,
        'recipes' => $recipes,
        'my_products' => $myProducts,
        'favorite_products' => $favoriteProducts
    ];
}

function callOpenRouter(array $messages): array
{
    $payload = [
        'model' => AI_MODEL,
        'messages' => $messages,
        'max_tokens' => 800,
        'temperature' => 0.5,
        'reasoning' => [
            'enabled' => true
        ]
    ];
    
    $ch = curl_init(OPENROUTER_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENROUTER_API_KEY,
            'HTTP-Referer: https://health-diet.ddev.site',
            'X-Title: Foodly'
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => 'Ошибка соединения: ' . $error];
    }
    
    if ($httpCode !== 200) {
        error_log("OpenRouter error: HTTP {$httpCode}, Response: {$response}");
        return ['error' => 'Ошибка API: HTTP ' . $httpCode];
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['choices'][0]['message']['content'])) {
        error_log("OpenRouter unexpected response: " . $response);
        return ['error' => 'Неожиданный ответ от AI'];
    }
    
    return [
        'content' => $data['choices'][0]['message']['content']
    ];
}
