<?php

namespace HealthDiet\Models;

use HealthDiet\Database;
use PDO;

class Product
{
    /**
     * Поиск продуктов по запросу
     * 
     * @param string $query Поисковый запрос
     * @param int $limit Максимум результатов
     * @return array Массив продуктов
     */
    public static function search(string $query, int $limit = 100): array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT id, title, category, calories, proteins, fats, carbohydrates
            FROM products
            WHERE search_title LIKE :query
            ORDER BY 
                CASE 
                    -- 1. Точное совпадение
                    WHEN search_title = :exact THEN 1
                    -- 2. Начинается с запроса
                    WHEN search_title LIKE :start THEN 2
                    -- 3. Слово целиком (через пробел)
                    WHEN search_title LIKE :word_space THEN 3
                    -- 4. Содержит где-то
                    ELSE 4
                END,
                -- Внутри приоритета сортируем по длине (короткие названия выше)
                LENGTH(title),
                title
            LIMIT :limit
        ");
        
        $searchLower = mb_strtolower($query);
        
        $stmt->bindValue(':query', '%' . $searchLower . '%', PDO::PARAM_STR);
        $stmt->bindValue(':exact', $searchLower, PDO::PARAM_STR);
        $stmt->bindValue(':start', $searchLower . '%', PDO::PARAM_STR);
        $stmt->bindValue(':word_space', '% ' . $searchLower . '%', PDO::PARAM_STR); // " молоко"
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Получить продукт по ID
     * 
     * @param int $id ID продукта
     * @return array|null Данные продукта или null
     */
    public static function getById(int $id): ?array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT id, title, category, calories, proteins, fats, carbohydrates
            FROM products
            WHERE id = :id
        ");
        
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
    
    /**
     * Рассчитать КБЖУ для указанного количества грамм
     * 
     * @param array $product Данные продукта
     * @param float $grams Количество грамм
     * @return array Рассчитанные значения
     */
    public static function calculateNutrition(array $product, float $grams): array
    {
        $multiplier = $grams / 100; // Значения в БД на 100г
        
        return [
            'grams' => $grams,
            'calories' => round((float)$product['calories'] * $multiplier, 1),
            'proteins' => round((float)$product['proteins'] * $multiplier, 1),
            'fats' => round((float)$product['fats'] * $multiplier, 1),
            'carbohydrates' => round((float)$product['carbohydrates'] * $multiplier, 1),
        ];
    }
}