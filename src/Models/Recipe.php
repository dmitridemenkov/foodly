<?php

namespace HealthDiet\Models;

use HealthDiet\Database;
use PDO;

class Recipe
{
    /**
     * Создать новый рецепт
     */
    public static function create(int $userId, string $title, int $servings = 1): int
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO recipes (user_id, title, servings, search_title)
            VALUES (:user_id, :title, :servings, :search_title)
        ");
        
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':servings', $servings, PDO::PARAM_INT);
        $stmt->bindValue(':search_title', mb_strtolower($title), PDO::PARAM_STR);
        $stmt->execute();
        
        return (int)$db->lastInsertId();
    }
    
    /**
     * Добавить ингредиент в рецепт
     */
    public static function addIngredient(int $recipeId, int $productId, float $grams): int
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO recipe_ingredients (recipe_id, product_id, grams)
            VALUES (:recipe_id, :product_id, :grams)
        ");
        
        $stmt->bindValue(':recipe_id', $recipeId, PDO::PARAM_INT);
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':grams', $grams);
        $stmt->execute();
        
        return (int)$db->lastInsertId();
    }
    
    /**
     * Обновить граммы ингредиента
     */
    public static function updateIngredient(int $ingredientId, int $userId, float $grams): bool
    {
        $db = Database::getConnection();
        
        // Проверяем что ингредиент принадлежит рецепту пользователя
        $stmt = $db->prepare("
            UPDATE recipe_ingredients 
            SET grams = :grams
            WHERE id = :id 
            AND recipe_id IN (SELECT id FROM recipes WHERE user_id = :user_id)
        ");
        
        $stmt->bindValue(':grams', $grams);
        $stmt->bindValue(':id', $ingredientId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Получить рецепты пользователя
     */
    public static function getByUser(int $userId, int $limit = 50): array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT 
                r.id,
                r.title,
                r.servings,
                r.created_at,
                COUNT(ri.id) as ingredients_count,
                ROUND(SUM(CAST(p.calories AS REAL) * ri.grams / 100), 1) as total_calories,
                ROUND(SUM(CAST(p.proteins AS REAL) * ri.grams / 100), 1) as total_proteins,
                ROUND(SUM(CAST(p.fats AS REAL) * ri.grams / 100), 1) as total_fats,
                ROUND(SUM(CAST(p.carbohydrates AS REAL) * ri.grams / 100), 1) as total_carbohydrates
            FROM recipes r
            LEFT JOIN recipe_ingredients ri ON ri.recipe_id = r.id
            LEFT JOIN products p ON p.id = ri.product_id
            WHERE r.user_id = :user_id
            GROUP BY r.id
            ORDER BY r.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Получить рецепт с ингредиентами
     */
    public static function getById(int $recipeId, int $userId): ?array
    {
        $db = Database::getConnection();
        
        // Сам рецепт
        $stmt = $db->prepare("
            SELECT id, user_id, title, servings, created_at
            FROM recipes
            WHERE id = :id AND user_id = :user_id
        ");
        
        $stmt->bindValue(':id', $recipeId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $recipe = $stmt->fetch();
        if (!$recipe) return null;
        
        // Ингредиенты
        $stmt = $db->prepare("
            SELECT 
                ri.id,
                ri.product_id,
                ri.grams,
                p.title as product_title,
                p.calories,
                p.proteins,
                p.fats,
                p.carbohydrates,
                ROUND(CAST(p.calories AS REAL) * ri.grams / 100, 1) as calc_calories,
                ROUND(CAST(p.proteins AS REAL) * ri.grams / 100, 1) as calc_proteins,
                ROUND(CAST(p.fats AS REAL) * ri.grams / 100, 1) as calc_fats,
                ROUND(CAST(p.carbohydrates AS REAL) * ri.grams / 100, 1) as calc_carbohydrates
            FROM recipe_ingredients ri
            JOIN products p ON p.id = ri.product_id
            WHERE ri.recipe_id = :recipe_id
        ");
        
        $stmt->bindValue(':recipe_id', $recipeId, PDO::PARAM_INT);
        $stmt->execute();
        
        $recipe['ingredients'] = $stmt->fetchAll();
        
        // Суммарное КБЖУ
        $totals = [
            'calories' => 0,
            'proteins' => 0,
            'fats' => 0,
            'carbohydrates' => 0,
            'grams' => 0
        ];
        
        foreach ($recipe['ingredients'] as $ing) {
            $totals['calories'] += $ing['calc_calories'];
            $totals['proteins'] += $ing['calc_proteins'];
            $totals['fats'] += $ing['calc_fats'];
            $totals['carbohydrates'] += $ing['calc_carbohydrates'];
            $totals['grams'] += $ing['grams'];
        }
        
        $recipe['totals'] = $totals;
        
        // КБЖУ на порцию
        $servings = max(1, $recipe['servings']);
        $recipe['per_serving'] = [
            'calories' => round($totals['calories'] / $servings, 1),
            'proteins' => round($totals['proteins'] / $servings, 1),
            'fats' => round($totals['fats'] / $servings, 1),
            'carbohydrates' => round($totals['carbohydrates'] / $servings, 1),
            'grams' => round($totals['grams'] / $servings, 1)
        ];
        
        return $recipe;
    }
    
    /**
     * Удалить рецепт
     */
    public static function delete(int $recipeId, int $userId): bool
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            DELETE FROM recipes
            WHERE id = :id AND user_id = :user_id
        ");
        
        $stmt->bindValue(':id', $recipeId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Обновить рецепт
     */
    public static function update(int $recipeId, int $userId, string $title, int $servings): bool
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            UPDATE recipes 
            SET title = :title, servings = :servings, search_title = :search_title, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND user_id = :user_id
        ");
        
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':servings', $servings, PDO::PARAM_INT);
        $stmt->bindValue(':search_title', mb_strtolower($title), PDO::PARAM_STR);
        $stmt->bindValue(':id', $recipeId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Удалить ингредиент из рецепта
     */
    public static function removeIngredient(int $ingredientId, int $userId): bool
    {
        $db = Database::getConnection();
        
        // Проверяем что ингредиент принадлежит рецепту пользователя
        $stmt = $db->prepare("
            DELETE FROM recipe_ingredients
            WHERE id = :id 
            AND recipe_id IN (SELECT id FROM recipes WHERE user_id = :user_id)
        ");
        
        $stmt->bindValue(':id', $ingredientId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Поиск рецептов пользователя
     */
    public static function search(int $userId, string $query, int $limit = 20): array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT 
                r.id,
                r.title,
                r.servings,
                ROUND(SUM(CAST(p.calories AS REAL) * ri.grams / 100), 1) as total_calories
            FROM recipes r
            LEFT JOIN recipe_ingredients ri ON ri.recipe_id = r.id
            LEFT JOIN products p ON p.id = ri.product_id
            WHERE r.user_id = :user_id AND r.search_title LIKE :query
            GROUP BY r.id
            ORDER BY r.title
            LIMIT :limit
        ");
        
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':query', '%' . mb_strtolower($query) . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
