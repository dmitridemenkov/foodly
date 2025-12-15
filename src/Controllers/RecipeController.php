<?php

namespace HealthDiet\Controllers;

use HealthDiet\Models\Recipe;

class RecipeController
{
    /**
     * Получить рецепты пользователя
     * GET /api/recipes.php?action=list
     */
    public static function list(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return ['error' => 'Требуется авторизация'];
        }
        
        $recipes = Recipe::getByUser($userId);
        
        return [
            'success' => true,
            'count' => count($recipes),
            'recipes' => $recipes
        ];
    }
    
    /**
     * Получить рецепт по ID
     * GET /api/recipes.php?action=get&id=123
     */
    public static function get(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$userId) {
            return ['error' => 'Требуется авторизация'];
        }
        
        if ($id <= 0) {
            return ['error' => 'Неверный ID рецепта'];
        }
        
        $recipe = Recipe::getById($id, $userId);
        
        if (!$recipe) {
            return ['error' => 'Рецепт не найден'];
        }
        
        return [
            'success' => true,
            'recipe' => $recipe
        ];
    }

    /**
     * Создать рецепт
     * POST /api/recipes.php?action=create
     */
    public static function create(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return ['error' => 'Требуется авторизация'];
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $title = trim($input['title'] ?? '');
        $servings = (int)($input['servings'] ?? 1);
        $ingredients = $input['ingredients'] ?? [];
        
        if (empty($title)) {
            return ['error' => 'Название рецепта обязательно'];
        }
        
        if ($servings < 1) {
            $servings = 1;
        }
        
        // Создаём рецепт
        $recipeId = Recipe::create($userId, $title, $servings);
        
        // Добавляем ингредиенты
        foreach ($ingredients as $ing) {
            $productId = (int)($ing['product_id'] ?? 0);
            $grams = (float)($ing['grams'] ?? 0);
            
            if ($productId > 0 && $grams > 0) {
                Recipe::addIngredient($recipeId, $productId, $grams);
            }
        }
        
        return [
            'success' => true,
            'recipe_id' => $recipeId,
            'message' => 'Рецепт создан'
        ];
    }
    
    /**
     * Добавить ингредиент в рецепт
     * POST /api/recipes.php?action=add_ingredient
     */
    public static function addIngredient(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return ['error' => 'Требуется авторизация'];
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $recipeId = (int)($input['recipe_id'] ?? 0);
        $productId = (int)($input['product_id'] ?? 0);
        $grams = (float)($input['grams'] ?? 0);
        
        if ($recipeId <= 0 || $productId <= 0 || $grams <= 0) {
            return ['error' => 'Неверные параметры'];
        }
        
        // Проверяем что рецепт принадлежит пользователю
        $recipe = Recipe::getById($recipeId, $userId);
        if (!$recipe) {
            return ['error' => 'Рецепт не найден'];
        }
        
        $ingredientId = Recipe::addIngredient($recipeId, $productId, $grams);
        
        return [
            'success' => true,
            'ingredient_id' => $ingredientId,
            'message' => 'Ингредиент добавлен'
        ];
    }

    /**
     * Удалить ингредиент
     * POST /api/recipes.php?action=remove_ingredient
     */
    public static function removeIngredient(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return ['error' => 'Требуется авторизация'];
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $ingredientId = (int)($input['ingredient_id'] ?? 0);
        
        if ($ingredientId <= 0) {
            return ['error' => 'Неверный ID ингредиента'];
        }
        
        $deleted = Recipe::removeIngredient($ingredientId, $userId);
        
        return [
            'success' => $deleted,
            'message' => $deleted ? 'Ингредиент удалён' : 'Не удалось удалить'
        ];
    }
    
    /**
     * Удалить рецепт
     * POST /api/recipes.php?action=delete
     */
    public static function delete(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return ['error' => 'Требуется авторизация'];
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $recipeId = (int)($input['recipe_id'] ?? 0);
        
        if ($recipeId <= 0) {
            return ['error' => 'Неверный ID рецепта'];
        }
        
        $deleted = Recipe::delete($recipeId, $userId);
        
        return [
            'success' => $deleted,
            'message' => $deleted ? 'Рецепт удалён' : 'Не удалось удалить'
        ];
    }
    
    /**
     * Обновить рецепт
     * POST /api/recipes.php?action=update
     */
    public static function update(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return ['error' => 'Требуется авторизация'];
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $recipeId = (int)($input['recipe_id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $servings = (int)($input['servings'] ?? 1);
        
        if ($recipeId <= 0) {
            return ['error' => 'Неверный ID рецепта'];
        }
        
        if (empty($title) || mb_strlen($title) < 2) {
            return ['error' => 'Название слишком короткое'];
        }
        
        $updated = Recipe::update($recipeId, $userId, $title, $servings);
        
        return [
            'success' => $updated,
            'message' => $updated ? 'Рецепт обновлён' : 'Не удалось обновить'
        ];
    }
    
    /**
     * Поиск рецептов
     * GET /api/recipes.php?action=search&q=творог
     */
    public static function search(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        $query = $_GET['q'] ?? '';
        $limit = (int)($_GET['limit'] ?? 20);
        
        if (!$userId) {
            return ['error' => 'Требуется авторизация'];
        }
        
        if (empty($query)) {
            return ['error' => 'Параметр q обязателен'];
        }
        
        $recipes = Recipe::search($userId, $query, $limit);
        
        return [
            'success' => true,
            'query' => $query,
            'count' => count($recipes),
            'recipes' => $recipes
        ];
    }
    
    /**
     * Обновить граммы ингредиента
     * POST /api/recipes.php?action=update_ingredient
     */
    public static function updateIngredient(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return ['error' => 'Требуется авторизация'];
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $ingredientId = (int)($input['ingredient_id'] ?? 0);
        $grams = (float)($input['grams'] ?? 0);
        
        if ($ingredientId <= 0) {
            return ['error' => 'Неверный ID ингредиента'];
        }
        
        if ($grams <= 0) {
            return ['error' => 'Граммы должны быть больше 0'];
        }
        
        $updated = Recipe::updateIngredient($ingredientId, $userId, $grams);
        
        return [
            'success' => $updated,
            'message' => $updated ? 'Обновлено' : 'Не удалось обновить'
        ];
    }
}
