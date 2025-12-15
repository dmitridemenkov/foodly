<?php
/**
 * Обновление search_title для существующих рецептов
 * Запустить: php fix_recipes_search.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Config.php';

use HealthDiet\Config;
use HealthDiet\Database;

Config::init();

$db = Database::getConnection();

// Получаем ВСЕ рецепты (не только с пустым search_title)
$stmt = $db->query("SELECT id, title FROM recipes");
$recipes = $stmt->fetchAll();

echo "Найдено рецептов для обновления: " . count($recipes) . "\n";

foreach ($recipes as $recipe) {
    $searchTitle = mb_strtolower($recipe['title']);
    
    $update = $db->prepare("UPDATE recipes SET search_title = :search_title WHERE id = :id");
    $update->bindValue(':search_title', $searchTitle, PDO::PARAM_STR);
    $update->bindValue(':id', $recipe['id'], PDO::PARAM_INT);
    $update->execute();
    
    echo "✅ {$recipe['title']} → {$searchTitle}\n";
}

echo "\nГотово!\n";
