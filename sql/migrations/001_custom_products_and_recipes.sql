-- ============================================
-- Миграция: Свои продукты + Рецепты
-- ============================================

-- 1. Добавляем user_id к продуктам (NULL = общий продукт из базы)
ALTER TABLE products ADD COLUMN user_id INTEGER REFERENCES users(id);

-- Индекс для быстрого поиска своих продуктов
CREATE INDEX IF NOT EXISTS idx_products_user ON products(user_id);

-- 2. Таблица рецептов (блюд)
CREATE TABLE IF NOT EXISTS recipes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    servings INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_recipes_user ON recipes(user_id);

-- 3. Ингредиенты рецептов
CREATE TABLE IF NOT EXISTS recipe_ingredients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recipe_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    grams REAL NOT NULL,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE INDEX IF NOT EXISTS idx_recipe_ingredients_recipe ON recipe_ingredients(recipe_id);

-- 4. Расширяем meals чтобы можно было добавлять рецепт целиком
-- recipe_id = NULL если добавлен отдельный продукт
-- recipe_id = число если добавлен рецепт (тогда product_id может быть NULL)
ALTER TABLE meals ADD COLUMN recipe_id INTEGER REFERENCES recipes(id);
