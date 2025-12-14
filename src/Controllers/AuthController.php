<?php
namespace HealthDiet\Controllers;

use HealthDiet\Models\User;
use PDO;

class AuthController {
    private User $userModel;
    
    public function __construct(PDO $pdo) {
        $this->userModel = new User($pdo);
    }
    
    /**
     * Авторизация пользователя
     */
    public function login(string $email, string $password): array {
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            return [
                'success' => false, 
                'error' => 'Пользователь не найден'
            ];
        }
        
        // ВРЕМЕННЫЙ ДЕБАГ
        $verify = $this->userModel->verifyPassword($password, $user['password_hash']);
        error_log("DEBUG: Пароль из формы: {$password}");
        error_log("DEBUG: Хеш из базы: {$user['password_hash']}");
        error_log("DEBUG: Результат проверки: " . ($verify ? 'TRUE' : 'FALSE'));
        
        if (!$verify) {
            return [
                'success' => false, 
                'error' => "Неверный пароль (DB hash: " . substr($user['password_hash'], 0, 20) . "...)"
            ];
        }
        
        // Стартуем сессию
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['calorie_goal'] = $user['daily_calorie_goal'];
        
        return [
            'success' => true, 
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'calorie_goal' => $user['daily_calorie_goal']
            ]
        ];
    }
    
    /**
     * Выход из системы
     */
    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
    }
    
    /**
     * Проверка авторизации
     */
    public function check(): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            return ['authenticated' => false];
        }
        
        return [
            'authenticated' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'name' => $_SESSION['user_name'],
                'calorie_goal' => $_SESSION['calorie_goal']
            ]
        ];
    }
    
    /**
     * Получить ID текущего пользователя
     */
    public function getCurrentUserId(): ?int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['user_id'] ?? null;
    }
}
