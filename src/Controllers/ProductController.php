<?php

namespace HealthDiet\Controllers;

use HealthDiet\Models\Product;

class ProductController
{
    /**
     * Поиск продуктов
     * GET /api/products.php?action=search&q=молоко&limit=20
     */
    public static function search(): array
    {
        $query = $_GET['q'] ?? '';
        $limit = (int)($_GET['limit'] ?? 20);
        $userId = $_SESSION['user_id'] ?? null;
        
        if (empty($query)) {
            return ['error' => 'Параметр q обязателен'];
        }
        
        if (mb_strlen($query) < 2) {
            return ['error' => 'Минимум 2 символа для поиска'];
        }
        
        $products = Product::search($query, $limit, $userId);
        
        return [
            'success' => true,
            'query' => $query,
            'count' => count($products),
            'products' => $products
        ];
    }
    
    /**
     * Создать свой продукт
     * POST /api/products.php?action=create
     */
    public static function create(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return ['error' => 'Требуется авторизация'];
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $title = trim($input['title'] ?? '');
        $calories = (float)($input['calories'] ?? 0);
        $proteins = (float)($input['proteins'] ?? 0);
        $fats = (float)($input['fats'] ?? 0);
        $carbohydrates = (float)($input['carbohydrates'] ?? 0);
        $category = trim($input['category'] ?? 'Мои продукты');
        $barcode = trim($input['barcode'] ?? '');
        
        if (empty($title)) {
            return ['error' => 'Название продукта обязательно'];
        }
        
        if (mb_strlen($title) < 2) {
            return ['error' => 'Название слишком короткое'];
        }
        
        $productId = Product::create($userId, $title, $calories, $proteins, $fats, $carbohydrates, $category, $barcode);
        
        return [
            'success' => true,
            'product_id' => $productId,
            'message' => 'Продукт создан'
        ];
    }
    
    /**
     * Получить свои продукты
     * GET /api/products.php?action=my_products
     */
    public static function getMyProducts(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return ['error' => 'Требуется авторизация'];
        }
        
        $products = Product::getByUser($userId);
        
        return [
            'success' => true,
            'count' => count($products),
            'products' => $products
        ];
    }
    
    /**
     * Удалить свой продукт
     * POST /api/products.php?action=delete
     */
    public static function delete(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return ['error' => 'Требуется авторизация'];
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = (int)($input['product_id'] ?? 0);
        
        if ($productId <= 0) {
            return ['error' => 'Неверный ID продукта'];
        }
        
        $deleted = Product::delete($productId, $userId);
        
        return [
            'success' => $deleted,
            'message' => $deleted ? 'Продукт удалён' : 'Не удалось удалить'
        ];
    }
    
    /**
     * Обновить свой продукт
     * POST /api/products.php?action=update
     */
    public static function update(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return ['error' => 'Требуется авторизация'];
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $productId = (int)($input['product_id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $calories = (float)($input['calories'] ?? 0);
        $proteins = (float)($input['proteins'] ?? 0);
        $fats = (float)($input['fats'] ?? 0);
        $carbohydrates = (float)($input['carbohydrates'] ?? 0);
        
        if ($productId <= 0) {
            return ['error' => 'Неверный ID продукта'];
        }
        
        if (empty($title) || mb_strlen($title) < 2) {
            return ['error' => 'Название слишком короткое'];
        }
        
        $updated = Product::update($productId, $userId, $title, $calories, $proteins, $fats, $carbohydrates);
        
        return [
            'success' => $updated,
            'message' => $updated ? 'Продукт обновлён' : 'Не удалось обновить'
        ];
    }
    
    /**
     * Получить продукт по ID
     * GET /api/?action=get_product&id=123
     */
    public static function getById(): array
    {
        $id = (int)($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            return ['error' => 'Неверный ID продукта'];
        }
        
        $product = Product::getById($id);
        
        if (!$product) {
            return ['error' => 'Продукт не найден'];
        }
        
        return [
            'success' => true,
            'product' => $product
        ];
    }
    
    /**
     * Рассчитать КБЖУ для продукта
     * GET /api/?action=calculate&id=123&grams=150
     */
    public static function calculate(): array
    {
        $id = (int)($_GET['id'] ?? 0);
        $grams = (float)($_GET['grams'] ?? 0);
        
        if ($id <= 0 || $grams <= 0) {
            return ['error' => 'Неверные параметры'];
        }
        
        $product = Product::getById($id);
        
        if (!$product) {
            return ['error' => 'Продукт не найден'];
        }
        
        $nutrition = Product::calculateNutrition($product, $grams);
        
        return [
            'success' => true,
            'product' => $product,
            'nutrition' => $nutrition
        ];
    }
}