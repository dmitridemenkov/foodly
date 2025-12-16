<?php
/**
 * API для статистики
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Config.php';

use HealthDiet\Config;
use HealthDiet\Database;

Config::init();
session_start();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

try {
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Требуется авторизация']);
        exit;
    }
    
    $db = Database::getConnection();
    
    switch ($action) {
        case 'summary':
            $days = (int)($_GET['days'] ?? 7);
            $days = min(max($days, 7), 90); // 7-90 дней
            
            $result = getStatsSummary($db, $userId, $days);
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

function getStatsSummary(PDO $db, int $userId, int $days): array
{
    $startDate = date('Y-m-d', strtotime("-{$days} days"));
    $today = date('Y-m-d');
    $calorieGoal = $_SESSION['calorie_goal'] ?? 2000;
    
    // 1. Данные по дням для графика
    $stmt = $db->prepare("
        SELECT 
            d.date,
            ROUND(SUM(CAST(p.calories AS REAL) * m.grams / 100), 0) as calories,
            ROUND(SUM(CAST(p.proteins AS REAL) * m.grams / 100), 1) as proteins,
            ROUND(SUM(CAST(p.fats AS REAL) * m.grams / 100), 1) as fats,
            ROUND(SUM(CAST(p.carbohydrates AS REAL) * m.grams / 100), 1) as carbohydrates
        FROM days d
        JOIN meals m ON m.day_id = d.id
        JOIN products p ON p.id = m.product_id
        WHERE d.user_id = :user_id AND d.date >= :start_date
        GROUP BY d.date
        ORDER BY d.date ASC
    ");
    $stmt->execute([':user_id' => $userId, ':start_date' => $startDate]);
    $dailyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Заполняем пропущенные дни нулями
    $dailyMap = [];
    foreach ($dailyData as $row) {
        $dailyMap[$row['date']] = $row;
    }
    
    $chartData = [];
    $currentDate = new DateTime($startDate);
    $endDate = new DateTime($today);
    
    while ($currentDate <= $endDate) {
        $dateStr = $currentDate->format('Y-m-d');
        $chartData[] = [
            'date' => $dateStr,
            'calories' => (int)($dailyMap[$dateStr]['calories'] ?? 0),
            'proteins' => (float)($dailyMap[$dateStr]['proteins'] ?? 0),
            'fats' => (float)($dailyMap[$dateStr]['fats'] ?? 0),
            'carbohydrates' => (float)($dailyMap[$dateStr]['carbohydrates'] ?? 0)
        ];
        $currentDate->modify('+1 day');
    }
    
    // 2. Средние значения за период
    $daysWithData = count($dailyData);
    $totalCalories = array_sum(array_column($dailyData, 'calories'));
    $totalProteins = array_sum(array_column($dailyData, 'proteins'));
    $totalFats = array_sum(array_column($dailyData, 'fats'));
    $totalCarbs = array_sum(array_column($dailyData, 'carbohydrates'));
    
    $averages = [
        'calories' => $daysWithData > 0 ? round($totalCalories / $daysWithData) : 0,
        'proteins' => $daysWithData > 0 ? round($totalProteins / $daysWithData, 1) : 0,
        'fats' => $daysWithData > 0 ? round($totalFats / $daysWithData, 1) : 0,
        'carbohydrates' => $daysWithData > 0 ? round($totalCarbs / $daysWithData, 1) : 0
    ];
    
    // 3. Сравнение с целью
    $daysOnTarget = 0;
    $daysOver = 0;
    $daysUnder = 0;
    $tolerance = 0.1; // 10% погрешность
    
    foreach ($dailyData as $row) {
        $cal = (int)$row['calories'];
        if ($cal == 0) continue;
        
        $lowerBound = $calorieGoal * (1 - $tolerance);
        $upperBound = $calorieGoal * (1 + $tolerance);
        
        if ($cal >= $lowerBound && $cal <= $upperBound) {
            $daysOnTarget++;
        } elseif ($cal > $upperBound) {
            $daysOver++;
        } else {
            $daysUnder++;
        }
    }
    
    // 4. Самые частые продукты
    $stmt = $db->prepare("
        SELECT 
            p.title,
            COUNT(*) as count,
            ROUND(SUM(CAST(p.calories AS REAL) * m.grams / 100), 0) as total_calories
        FROM meals m
        JOIN products p ON p.id = m.product_id
        JOIN days d ON d.id = m.day_id
        WHERE d.user_id = :user_id AND d.date >= :start_date
        GROUP BY m.product_id
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute([':user_id' => $userId, ':start_date' => $startDate]);
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Streak — дней подряд с логами
    $stmt = $db->prepare("
        SELECT DISTINCT d.date 
        FROM days d
        JOIN meals m ON m.day_id = d.id
        WHERE d.user_id = :user_id 
        ORDER BY d.date DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $allDates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $streak = 0;
    $checkDate = new DateTime($today);
    
    foreach ($allDates as $logDate) {
        if ($logDate === $checkDate->format('Y-m-d')) {
            $streak++;
            $checkDate->modify('-1 day');
        } else {
            break;
        }
    }
    
    return [
        'success' => true,
        'period' => $days,
        'calorie_goal' => $calorieGoal,
        'chart_data' => $chartData,
        'averages' => $averages,
        'goal_comparison' => [
            'on_target' => $daysOnTarget,
            'over' => $daysOver,
            'under' => $daysUnder,
            'days_with_data' => $daysWithData
        ],
        'top_products' => $topProducts,
        'streak' => $streak
    ];
}
