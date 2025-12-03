<?php

namespace HealthDiet\Controllers;

use HealthDiet\Models\Product;

class ProductController
{
    /**
     * Поиск продуктов
     * GET /api/?action=search&q=молоко&limit=20
     */
    public static function search(): array
    {
        $query = $_GET['q'] ?? '';
        $limit = (int)($_GET['limit'] ?? 20);
        
        if (empty($query)) {
            return ['error' => 'Параметр q обязателен'];
        }
        
        if (mb_strlen($query) < 2) {
            return ['error' => 'Минимум 2 символа для поиска'];
        }
        
        $products = Product::search($query, $limit);
        
        return [
            'success' => true,
            'query' => $query,
            'count' => count($products),
            'products' => $products
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