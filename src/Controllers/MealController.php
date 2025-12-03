<?php

namespace HealthDiet\Controllers;

use HealthDiet\Models\Day;
use HealthDiet\Models\Meal;
use HealthDiet\Router;
use HealthDiet\Config;

class MealController
{
    /**
     * Добавить приём пищи
     * POST /api/?action=add_meal
     * Body: { "date": "2025-12-02", "product_id": 123, "grams": 150, "meal_type": "завтрак" }
     */
    public static function add(): array
    {
        $data = Router::getPostData();
        
        $date = $data['date'] ?? date(Config::DATE_FORMAT);
        $productId = (int)($data['product_id'] ?? 0);
        $grams = (float)($data['grams'] ?? 0);
        $mealType = $data['meal_type'] ?? null;
        
        // Валидация
        if ($productId <= 0) {
            return ['error' => 'Не указан продукт'];
        }
        
        if ($grams <= 0) {
            return ['error' => 'Укажите количество грамм'];
        }
        
        if ($grams > 10000) {
            return ['error' => 'Слишком большое количество грамм'];
        }
        
        // Создаём/получаем день
        $day = Day::getOrCreate($date);
        
        // Добавляем приём пищи
        $mealId = Meal::add($day['id'], $productId, $grams, $mealType);
        
        // Получаем добавленный приём
        $meal = Meal::getById($mealId);
        
        // Пересчитываем саммари
        $summary = Day::getSummary($day['id']);
        
        return [
            'success' => true,
            'message' => 'Приём пищи добавлен',
            'meal' => $meal,
            'summary' => $summary
        ];
    }
    
    /**
     * Удалить приём пищи
     * POST /api/?action=delete_meal
     * Body: { "meal_id": 5 }
     */
    public static function delete(): array
    {
        $data = Router::getPostData();
        $mealId = (int)($data['meal_id'] ?? $_GET['meal_id'] ?? 0);
        
        if ($mealId <= 0) {
            return ['error' => 'Не указан ID приёма пищи'];
        }
        
        // Получаем приём перед удалением (для возврата day_id)
        $meal = Meal::getById($mealId);
        
        if (!$meal) {
            return ['error' => 'Приём пищи не найден'];
        }
        
        // Удаляем
        $success = Meal::delete($mealId);
        
        if (!$success) {
            return ['error' => 'Не удалось удалить приём пищи'];
        }
        
        // Обновлённое саммари
        $summary = Day::getSummary($meal['day_id']);
        
        return [
            'success' => true,
            'message' => 'Приём пищи удалён',
            'summary' => $summary
        ];
    }
    
    /**
     * Обновить приём пищи
     * POST /api/?action=update_meal
     * Body: { "meal_id": 5, "grams": 200, "meal_type": "обед" }
     */
    public static function update(): array
    {
        $data = Router::getPostData();
        
        $mealId = (int)($data['meal_id'] ?? 0);
        $grams = (float)($data['grams'] ?? 0);
        $mealType = $data['meal_type'] ?? null;
        
        if ($mealId <= 0) {
            return ['error' => 'Не указан ID приёма пищи'];
        }
        
        if ($grams <= 0) {
            return ['error' => 'Укажите количество грамм'];
        }
        
        // Проверяем существование
        $meal = Meal::getById($mealId);
        
        if (!$meal) {
            return ['error' => 'Приём пищи не найден'];
        }
        
        // Обновляем
        $success = Meal::update($mealId, $grams, $mealType);
        
        if (!$success) {
            return ['error' => 'Не удалось обновить приём пищи'];
        }
        
        // Получаем обновлённые данные
        $updatedMeal = Meal::getById($mealId);
        $summary = Day::getSummary($meal['day_id']);
        
        return [
            'success' => true,
            'message' => 'Приём пищи обновлён',
            'meal' => $updatedMeal,
            'summary' => $summary
        ];
    }
}