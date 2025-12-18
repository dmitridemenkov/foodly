<?php
namespace HealthDiet\Controllers;

use HealthDiet\Models\User;
use PDO;

class AuthController {
    private User $userModel;
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
    }
    
    /**
     * Регистрация нового пользователя
     */
    public function register(string $email, string $password, string $name, int $calorieGoal = 2000): array {
        // Валидация email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Некорректный email'];
        }
        
        // Проверка длины пароля
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Пароль должен быть не менее 6 символов'];
        }
        
        // Проверка имени
        if (strlen(trim($name)) < 2) {
            return ['success' => false, 'error' => 'Введите имя'];
        }
        
        // Проверяем, не занят ли email
        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser) {
            return ['success' => false, 'error' => 'Пользователь с таким email уже существует'];
        }
        
        // Создаём пользователя
        $userId = $this->userModel->create($email, $password, trim($name), $calorieGoal);
        
        if (!$userId) {
            return ['success' => false, 'error' => 'Ошибка при создании пользователя'];
        }
        
        // Сразу авторизуем
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = trim($name);
        $_SESSION['calorie_goal'] = $calorieGoal;
        
        return [
            'success' => true,
            'user' => [
                'id' => $userId,
                'email' => $email,
                'name' => trim($name),
                'calorie_goal' => $calorieGoal
            ]
        ];
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
