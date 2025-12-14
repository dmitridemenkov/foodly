<?php
namespace HealthDiet\Models;

use PDO;

class User {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Найти пользователя по email
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare("
            SELECT id, email, password_hash, name, daily_calorie_goal, created_at
            FROM users 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user ?: null;
    }
    
    /**
     * Найти пользователя по ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT id, email, name, daily_calorie_goal, created_at
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user ?: null;
    }
    
    /**
     * Проверить пароль
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Создать нового пользователя
     */
    public function create(string $email, string $password, string $name, int $calorieGoal = 2000): ?int {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO users (email, password_hash, name, daily_calorie_goal)
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$email, $hash, $name, $calorieGoal])) {
            return (int)$this->pdo->lastInsertId();
        }
        
        return null;
    }
    
    /**
     * Обновить цель калорий
     */
    public function updateCalorieGoal(int $userId, int $calorieGoal): bool {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET daily_calorie_goal = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        return $stmt->execute([$calorieGoal, $userId]);
    }
}
