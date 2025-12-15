-- Добавляем search_title для поиска по кириллице
ALTER TABLE recipes ADD COLUMN search_title TEXT;

-- Заполняем существующие записи
UPDATE recipes SET search_title = LOWER(title);

-- Индекс для быстрого поиска
CREATE INDEX IF NOT EXISTS idx_recipes_search_title ON recipes(search_title);
