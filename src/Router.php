<?php

namespace HealthDiet;

class Router
{
    private array $routes = [];
    
    /**
     * Добавить GET маршрут
     */
    public function get(string $action, callable $handler): void
    {
        $this->routes['GET'][$action] = $handler;
    }
    
    /**
     * Добавить POST маршрут
     */
    public function post(string $action, callable $handler): void
    {
        $this->routes['POST'][$action] = $handler;
    }
    
    /**
     * Обработать запрос
     */
    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? 'index';
        
        if (!isset($this->routes[$method][$action])) {
            $this->sendJson(['error' => 'Action not found'], 404);
            return;
        }
        
        try {
            $handler = $this->routes[$method][$action];
            $result = $handler();
            $this->sendJson($result);
        } catch (\Exception $e) {
            $this->sendJson([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
    
    /**
     * Отправить JSON ответ
     */
    private function sendJson($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Получить данные POST запроса
     */
    public static function getPostData(): array
    {
        $input = file_get_contents('php://input');
        
        // Пробуем JSON
        $data = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
        
        // Иначе используем $_POST
        return $_POST;
    }
}