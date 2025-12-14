-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    name TEXT NOT NULL,
    daily_calorie_goal INTEGER DEFAULT 2000,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Добавляем user_id к таблице days
ALTER TABLE days ADD COLUMN user_id INTEGER REFERENCES users(id);

-- Создаём индексы для быстрого поиска
CREATE INDEX IF NOT EXISTS idx_days_user_date ON days(user_id, date);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- Тестовый пользователь
-- Email: dima@foodly.ru
-- Пароль: test123
INSERT OR IGNORE INTO users (email, password_hash, name, daily_calorie_goal) 
VALUES (
    'dima@foodly.ru',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Дмитрий',
    2000
);
