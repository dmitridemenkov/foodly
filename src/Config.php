<?php

namespace HealthDiet;

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
    
    public static function init(): void
    {
        date_default_timezone_set(self::TIMEZONE);
    }
}