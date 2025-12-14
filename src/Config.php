<?php

namespace HealthDiet;

use PDO;

class Config
{
    // Путь к базе данных (относительно корня проекта)
    public const DB_PATH = __DIR__ . '/../db/health_diet.db';
    
    // Токен для "авторизации" (пока простой)
    public const ACCESS_TOKEN = 'start';
    
    // Timezone
    public const TIMEZONE = 'Europe/Moscow';
    
    // Формат даты
    public const DATE_FORMAT = 'Y-m-d';
    
    private static ?PDO $pdo = null;
    
    public static function init(): void
    {
        date_default_timezone_set(self::TIMEZONE);
    }
    
    /**
     * Получить PDO соединение
     */
    public static function getPdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite:' . self::DB_PATH);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        
        return self::$pdo;
    }
}
