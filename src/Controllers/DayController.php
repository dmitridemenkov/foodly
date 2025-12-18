<?php

namespace HealthDiet\Controllers;

use HealthDiet\Models\Day;
use HealthDiet\Config;

class DayController
{
    /**
     * Получить данные дня с приёмами пищи
     * GET /api/?action=get_day&date=2025-12-02
     */
    public static function getDay(): array
    {
        $date = $_GET['date'] ?? date(Config::DATE_FORMAT);

        // Валидация даты
        if (!self::isValidDate($date)) {
            return ['error' => 'Неверный формат даты. Используйте Y-m-d'];
        }

        $day = Day::getOrCreate($date);
        $meals = Day::getMeals($day['id']);
        $summary = Day::getSummary($day['id']);

        return [
            'success' => true,
            'day' => $day,
            'meals' => $meals,
            'summary' => $summary
        ];
    }

    /**
     * Получить саммари дня
     * GET /api/?action=get_summary&date=2025-12-02
     */
    public static function getSummary(): array
    {
        $date = $_GET['date'] ?? date(Config::DATE_FORMAT);

        if (!self::isValidDate($date)) {
            return ['error' => 'Неверный формат даты'];
        }

        $day = Day::getOrCreate($date);
        $summary = Day::getSummary($day['id']);

        return [
            'success' => true,
            'date' => $date,
            'summary' => $summary
        ];
    }

    /**
     * Получить список последних дней
     * GET /api/?action=get_recent_days&limit=30
     */
    public static function getRecentDays(): array
    {
        $limit = (int)($_GET['limit'] ?? 30);

        if ($limit < 1 || $limit > 365) {
            $limit = 30;
        }

        $days = Day::getRecent($limit);

        // Добавляем саммари для каждого дня
        foreach ($days as &$day) {
            $summary = Day::getSummary($day['id']);
            $day['summary'] = $summary;
        }

        return [
            'success' => true,
            'count' => count($days),
            'days' => $days
        ];
    }

    /**
     * Валидация даты
     */
    private static function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat(Config::DATE_FORMAT, $date);
        return $d && $d->format(Config::DATE_FORMAT) === $date;
    }

    /**
     * Получить статистику за период
     * GET /api/?action=get_stats&days=7
     */
    public static function getStats(): array
    {
        $days = (int)($_GET['days'] ?? 7);

        if ($days < 1 || $days > 365) {
            $days = 7;
        }

        $db = \HealthDiet\Database::getConnection();
        
        // Получаем user_id из сессии
        $userId = $_SESSION['user_id'] ?? null;

        // Получаем даты за последние N дней
        $endDate = date(\HealthDiet\Config::DATE_FORMAT);
        $startDate = date(\HealthDiet\Config::DATE_FORMAT, strtotime("-{$days} days"));

        $stmt = $db->prepare("
        SELECT id, date 
        FROM days 
        WHERE date BETWEEN :start AND :end AND user_id = :user_id
        ORDER BY date DESC
    ");

        $stmt->bindValue(':start', $startDate, \PDO::PARAM_STR);
        $stmt->bindValue(':end', $endDate, \PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->execute();

        $daysData = $stmt->fetchAll();

        // Собираем статистику по каждому дню
        $stats = [];
        $totalCalories = 0;
        $totalProteins = 0;
        $totalFats = 0;
        $totalCarbs = 0;
        $daysWithData = 0;

        $minCalories = PHP_INT_MAX;
        $maxCalories = 0;

        foreach ($daysData as $day) {
            $summary = Day::getSummary($day['id']);

            if ($summary['meals_count'] > 0) {
                $daysWithData++;

                $calories = $summary['total_calories'];
                $totalCalories += $calories;
                $totalProteins += $summary['total_proteins'];
                $totalFats += $summary['total_fats'];
                $totalCarbs += $summary['total_carbohydrates'];

                if ($calories > 0) {
                    $minCalories = min($minCalories, $calories);
                    $maxCalories = max($maxCalories, $calories);
                }

                $stats[] = [
                    'date' => $day['date'],
                    'calories' => round($calories, 1),
                    'proteins' => round($summary['total_proteins'], 1),
                    'fats' => round($summary['total_fats'], 1),
                    'carbohydrates' => round($summary['total_carbohydrates'], 1),
                    'meals_count' => $summary['meals_count']
                ];
            }
        }

        // Средние значения
        $avgCalories = $daysWithData > 0 ? $totalCalories / $daysWithData : 0;
        $avgProteins = $daysWithData > 0 ? $totalProteins / $daysWithData : 0;
        $avgFats = $daysWithData > 0 ? $totalFats / $daysWithData : 0;
        $avgCarbs = $daysWithData > 0 ? $totalCarbs / $daysWithData : 0;

        return [
            'success' => true,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $days,
                'days_with_data' => $daysWithData
            ],
            'averages' => [
                'calories' => round($avgCalories, 1),
                'proteins' => round($avgProteins, 1),
                'fats' => round($avgFats, 1),
                'carbohydrates' => round($avgCarbs, 1)
            ],
            'range' => [
                'min_calories' => $minCalories === PHP_INT_MAX ? 0 : round($minCalories, 1),
                'max_calories' => round($maxCalories, 1)
            ],
            'daily_stats' => $stats
        ];
    }
}
