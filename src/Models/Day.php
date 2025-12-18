<?php

namespace HealthDiet\Models;

use HealthDiet\Database;
use PDO;

class Day
{
    /**
     * Получить или создать день по дате
     * 
     * @param string $date Дата в формате Y-m-d
     * @param int|null $userId ID пользователя (берётся из сессии если не указан)
     * @return array Данные дня (id, date)
     */
    public static function getOrCreate(string $date, ?int $userId = null): array
    {
        $db = Database::getConnection();
        
        // Получаем user_id из сессии если не передан
        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? null;
        }
        
        if (!$userId) {
            throw new \Exception('User ID is required');
        }
        
        // Проверяем существует ли день для этого пользователя
        $stmt = $db->prepare("SELECT id, date FROM days WHERE date = :date AND user_id = :user_id");
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $day = $stmt->fetch();
        
        if ($day) {
            return $day;
        }
        
        // Создаём новый день
        $stmt = $db->prepare("INSERT INTO days (date, user_id) VALUES (:date, :user_id)");
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'id' => $db->lastInsertId(),
            'date' => $date
        ];
    }
    
    /**
     * Получить все приёмы пищи за день
     * 
     * @param int $dayId ID дня
     * @return array Массив приёмов пищи
     */
    public static function getMeals(int $dayId): array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT 
                m.id,
                m.grams,
                m.meal_type,
                m.created_at,
                p.id as product_id,
                p.title as product_title,
                p.category,
                p.calories as calories_per_100g,
                p.proteins as proteins_per_100g,
                p.fats as fats_per_100g,
                p.carbohydrates as carbs_per_100g
            FROM meals m
            JOIN products p ON m.product_id = p.id
            WHERE m.day_id = :day_id
            ORDER BY m.created_at ASC
        ");
        
        $stmt->bindValue(':day_id', $dayId, PDO::PARAM_INT);
        $stmt->execute();
        
        $meals = $stmt->fetchAll();
        
        // Рассчитываем КБЖУ для каждого приёма пищи
        foreach ($meals as &$meal) {
            $multiplier = $meal['grams'] / 100;
            
            $meal['calories'] = round((float)$meal['calories_per_100g'] * $multiplier, 1);
            $meal['proteins'] = round((float)$meal['proteins_per_100g'] * $multiplier, 1);
            $meal['fats'] = round((float)$meal['fats_per_100g'] * $multiplier, 1);
            $meal['carbohydrates'] = round((float)$meal['carbs_per_100g'] * $multiplier, 1);
        }
        
        return $meals;
    }
    
    /**
     * Получить саммари дня (итоговые КБЖУ)
     * 
     * @param int $dayId ID дня
     * @return array Итоговые значения
     */
    public static function getSummary(int $dayId): array
    {
        $meals = self::getMeals($dayId);
        
        $summary = [
            'total_calories' => 0,
            'total_proteins' => 0,
            'total_fats' => 0,
            'total_carbohydrates' => 0,
            'meals_count' => count($meals)
        ];
        
        foreach ($meals as $meal) {
            $summary['total_calories'] += $meal['calories'];
            $summary['total_proteins'] += $meal['proteins'];
            $summary['total_fats'] += $meal['fats'];
            $summary['total_carbohydrates'] += $meal['carbohydrates'];
        }
        
        // Округляем
        $summary['total_calories'] = round($summary['total_calories'], 1);
        $summary['total_proteins'] = round($summary['total_proteins'], 1);
        $summary['total_fats'] = round($summary['total_fats'], 1);
        $summary['total_carbohydrates'] = round($summary['total_carbohydrates'], 1);
        
        return $summary;
    }
    
    /**
     * Получить список всех дней с краткой статистикой
     * 
     * @param int $limit Количество дней
     * @param int|null $userId ID пользователя
     * @return array Массив дней
     */
    public static function getRecent(int $limit = 30, ?int $userId = null): array
    {
        $db = Database::getConnection();
        
        // Получаем user_id из сессии если не передан
        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? null;
        }
        
        $stmt = $db->prepare("
            SELECT id, date, created_at
            FROM days
            WHERE user_id = :user_id
            ORDER BY date DESC
            LIMIT :limit
        ");
        
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}