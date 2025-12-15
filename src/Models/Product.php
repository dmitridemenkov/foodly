<?php

namespace HealthDiet\Models;

use HealthDiet\Database;
use PDO;

class Product
{
    /**
     * Создать свой продукт
     */
    public static function create(
        int $userId,
        string $title,
        float $calories,
        float $proteins,
        float $fats,
        float $carbohydrates,
        string $category = 'Мои продукты'
    ): int {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO products (title, calories, proteins, fats, carbohydrates, category, search_title, user_id)
            VALUES (:title, :calories, :proteins, :fats, :carbohydrates, :category, :search_title, :user_id)
        ");
        
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':calories', $calories);
        $stmt->bindValue(':proteins', $proteins);
        $stmt->bindValue(':fats', $fats);
        $stmt->bindValue(':carbohydrates', $carbohydrates);
        $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->bindValue(':search_title', mb_strtolower($title), PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return (int)$db->lastInsertId();
    }
    
    /**
     * Получить продукты пользователя
     */
    public static function getByUser(int $userId, int $limit = 100): array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT id, title, category, calories, proteins, fats, carbohydrates
            FROM products
            WHERE user_id = :user_id
            ORDER BY title
            LIMIT :limit
        ");
        
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Удалить свой продукт
     */
    public static function delete(int $productId, int $userId): bool
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            DELETE FROM products
            WHERE id = :id AND user_id = :user_id
        ");
        
        $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Обновить свой продукт
     */
    public static function update(
        int $productId,
        int $userId,
        string $title,
        float $calories,
        float $proteins,
        float $fats,
        float $carbohydrates
    ): bool {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            UPDATE products 
            SET title = :title, 
                calories = :calories, 
                proteins = :proteins, 
                fats = :fats, 
                carbohydrates = :carbohydrates,
                search_title = :search_title
            WHERE id = :id AND user_id = :user_id
        ");
        
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':calories', $calories);
        $stmt->bindValue(':proteins', $proteins);
        $stmt->bindValue(':fats', $fats);
        $stmt->bindValue(':carbohydrates', $carbohydrates);
        $stmt->bindValue(':search_title', mb_strtolower($title), PDO::PARAM_STR);
        $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Поиск продуктов по запросу
     * Ищет общие продукты (user_id IS NULL) + свои продукты пользователя
     * 
     * @param string $query Поисковый запрос
     * @param int $limit Максимум результатов
     * @param int|null $userId ID пользователя для поиска своих продуктов
     * @return array Массив продуктов
     */
    public static function search(string $query, int $limit = 100, ?int $userId = null): array
    {
        $db = Database::getConnection();
        
        $sql = "
            SELECT id, title, category, calories, proteins, fats, carbohydrates, user_id
            FROM products
            WHERE search_title LIKE :query
            AND (user_id IS NULL" . ($userId ? " OR user_id = :user_id" : "") . ")
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
        ";
        
        $stmt = $db->prepare($sql);
        $searchLower = mb_strtolower($query);
        
        $stmt->bindValue(':query', '%' . $searchLower . '%', PDO::PARAM_STR);
        $stmt->bindValue(':exact', $searchLower, PDO::PARAM_STR);
        $stmt->bindValue(':start', $searchLower . '%', PDO::PARAM_STR);
        $stmt->bindValue(':word_space', '% ' . $searchLower . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        if ($userId) {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        
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