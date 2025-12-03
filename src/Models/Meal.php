<?php

namespace HealthDiet\Models;

use HealthDiet\Database;
use PDO;

class Meal
{
    /**
     * Добавить приём пищи
     * 
     * @param int $dayId ID дня
     * @param int $productId ID продукта
     * @param float $grams Количество грамм
     * @param string|null $mealType Тип приёма пищи (завтрак, обед, ужин, перекус)
     * @return int ID созданного приёма пищи
     */
    public static function add(int $dayId, int $productId, float $grams, ?string $mealType = null): int
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO meals (day_id, product_id, grams, meal_type)
            VALUES (:day_id, :product_id, :grams, :meal_type)
        ");
        
        $stmt->bindValue(':day_id', $dayId, PDO::PARAM_INT);
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':grams', $grams);
        $stmt->bindValue(':meal_type', $mealType, PDO::PARAM_STR);
        
        $stmt->execute();
        
        return (int)$db->lastInsertId();
    }
    
    /**
     * Удалить приём пищи
     * 
     * @param int $mealId ID приёма пищи
     * @return bool Успешность удаления
     */
    public static function delete(int $mealId): bool
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("DELETE FROM meals WHERE id = :id");
        $stmt->bindValue(':id', $mealId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Обновить приём пищи
     * 
     * @param int $mealId ID приёма пищи
     * @param float $grams Новое количество грамм
     * @param string|null $mealType Новый тип приёма пищи
     * @return bool Успешность обновления
     */
    public static function update(int $mealId, float $grams, ?string $mealType = null): bool
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            UPDATE meals 
            SET grams = :grams, meal_type = :meal_type
            WHERE id = :id
        ");
        
        $stmt->bindValue(':id', $mealId, PDO::PARAM_INT);
        $stmt->bindValue(':grams', $grams);
        $stmt->bindValue(':meal_type', $mealType, PDO::PARAM_STR);
        
        return $stmt->execute();
    }
    
    /**
     * Получить приём пищи по ID
     * 
     * @param int $mealId ID приёма пищи
     * @return array|null Данные приёма пищи или null
     */
    public static function getById(int $mealId): ?array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT 
                m.id,
                m.day_id,
                m.grams,
                m.meal_type,
                m.created_at,
                p.id as product_id,
                p.title as product_title,
                p.category,
                p.calories,
                p.proteins,
                p.fats,
                p.carbohydrates
            FROM meals m
            JOIN products p ON m.product_id = p.id
            WHERE m.id = :id
        ");
        
        $stmt->bindValue(':id', $mealId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
    
    /**
     * Получить приёмы пищи по типу (завтрак, обед и т.д.)
     * 
     * @param int $dayId ID дня
     * @param string $mealType Тип приёма пищи
     * @return array Массив приёмов пищи
     */
    public static function getByType(int $dayId, string $mealType): array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT 
                m.id,
                m.grams,
                m.created_at,
                p.id as product_id,
                p.title as product_title,
                p.category,
                p.calories,
                p.proteins,
                p.fats,
                p.carbohydrates
            FROM meals m
            JOIN products p ON m.product_id = p.id
            WHERE m.day_id = :day_id AND m.meal_type = :meal_type
            ORDER BY m.created_at ASC
        ");
        
        $stmt->bindValue(':day_id', $dayId, PDO::PARAM_INT);
        $stmt->bindValue(':meal_type', $mealType, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}