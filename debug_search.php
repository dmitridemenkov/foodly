<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Config.php';

use HealthDiet\Config;
use HealthDiet\Database;

Config::init();

$db = Database::getConnection();

echo "=== Все рецепты ===\n";
$stmt = $db->query("SELECT id, title, search_title FROM recipes");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, title: '{$row['title']}', search_title: '{$row['search_title']}'\n";
}

echo "\n=== Тест поиска 'тест' ===\n";
$query = 'тест';
$stmt = $db->prepare("SELECT id, title FROM recipes WHERE search_title LIKE :query");
$stmt->bindValue(':query', '%' . mb_strtolower($query) . '%', PDO::PARAM_STR);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Найдено: {$row['title']}\n";
}

echo "\n=== mb_strtolower тест ===\n";
echo "mb_strtolower('Тестовое блюдо') = '" . mb_strtolower('Тестовое блюдо') . "'\n";
