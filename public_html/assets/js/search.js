// ============================================
// ПОИСК ПРОДУКТОВ
// ============================================

let searchTimeout = null
let favoriteProductIds = new Set()
let favoriteRecipeIds = new Set()

// Экспортируем в window для синхронизации с другими модулями
window.favoriteProductIds = favoriteProductIds
window.favoriteRecipeIds = favoriteRecipeIds

export function initSearch() {
    const searchInput = document.getElementById('product-search')
    const resultsDiv = document.getElementById('search-results')
    
    if (!searchInput) return
    
    // Загружаем ID избранных при старте
    loadFavoriteIds()
    
    // Экспортируем функции
    window.toggleFavorite = toggleFavorite
    
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim()
        
        // Очищаем предыдущий таймер
        clearTimeout(searchTimeout)
        
        if (query.length < 2) {
            resultsDiv.classList.add('hidden')
            resultsDiv.innerHTML = ''
            return
        }
        
        // Debounce - ждём 300ms после последнего ввода
        searchTimeout = setTimeout(() => {
            searchProducts(query)
        }, 300)
    })
    
    // Закрытие результатов при клике вне
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.classList.add('hidden')
        }
    })
}

// Фокус на поиск (вызывается из кнопок "Добавить продукт")
let preselectedMealType = null

window.focusSearch = function(mealType) {
    // Сохраняем выбранный тип приёма пищи
    if (mealType) {
        preselectedMealType = mealType
    }
    
    const searchInput = document.getElementById('product-search')
    if (searchInput) {
        searchInput.focus()
        searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' })
    }
}

async function searchProducts(query) {
    const resultsDiv = document.getElementById('search-results')
    
    try {
        resultsDiv.innerHTML = `
            <div class="p-4 text-center text-text-secondary">
                <span class="material-symbols-outlined animate-spin">progress_activity</span>
                Поиск...
            </div>
        `
        resultsDiv.classList.remove('hidden')
        
        // Параллельный поиск продуктов и рецептов
        const [productsRes, recipesRes] = await Promise.all([
            fetch(`/api/products.php?action=search&q=${encodeURIComponent(query)}`),
            fetch(`/api/recipes.php?action=search&q=${encodeURIComponent(query)}`)
        ])
        
        const productsData = await productsRes.json()
        const recipesData = await recipesRes.json()
        
        const products = productsData.success ? productsData.products : []
        const recipes = recipesData.success ? recipesData.recipes : []
        
        if (products.length === 0 && recipes.length === 0) {
            resultsDiv.innerHTML = `
                <div class="p-4 text-center text-text-secondary">
                    Ничего не найдено 😞
                </div>
            `
            return
        }
        
        displaySearchResults(products, recipes)
        
    } catch (error) {
        console.error('Ошибка поиска:', error)
        resultsDiv.innerHTML = `
            <div class="p-4 text-center text-red-500">
                Ошибка поиска. Попробуйте ещё раз.
            </div>
        `
    }
}

function displaySearchResults(products, recipes = []) {
    const resultsDiv = document.getElementById('search-results')
    
    let html = ''
    
    // Сначала рецепты (если есть), избранные первыми
    if (recipes.length > 0) {
        html += `<div class="px-4 py-2 text-xs font-semibold text-purple-500 bg-purple-50 dark:bg-purple-900/20">Мои блюда</div>`
        
        // Сортируем: избранные сверху
        const sortedRecipes = [...recipes].sort((a, b) => {
            const aFav = favoriteRecipeIds.has(a.id) ? 0 : 1
            const bFav = favoriteRecipeIds.has(b.id) ? 0 : 1
            return aFav - bFav
        })
        
        html += sortedRecipes.slice(0, 5).map(recipe => {
            const isFavorite = favoriteRecipeIds.has(recipe.id)
            return `
            <div class="flex items-center gap-3 px-5 py-4 hover:bg-purple-50 dark:hover:bg-purple-900/20 border-b border-[#f0f4f3] dark:border-[#1c3029] transition-colors group cursor-pointer"
                onclick="window.selectRecipe(${recipe.id}, '${escapeHtml(recipe.title)}')">
                
                <button 
                    onclick="window.toggleFavorite('recipe', ${recipe.id}, event)"
                    class="flex-shrink-0 ${isFavorite ? 'text-yellow-500' : 'text-text-secondary'} hover:text-yellow-500 transition-colors"
                >
                    <span class="material-symbols-outlined text-xl">star</span>
                </button>
                
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-text-primary dark:text-white group-hover:text-purple-500 transition-colors truncate">
                        ${escapeHtml(recipe.title)}
                    </div>
                    <div class="flex items-center gap-2 text-xs text-text-secondary mt-1">
                        <span class="text-purple-500 font-bold">${Math.round(recipe.total_calories || 0)} ккал</span>
                        <span>•</span>
                        <span>${recipe.servings || 1} порц.</span>
                    </div>
                </div>
                
                <span class="material-symbols-outlined text-purple-400 opacity-0 group-hover:opacity-100 transition-opacity">
                    chevron_right
                </span>
            </div>
        `}).join('')
    }
    
    // Затем продукты (избранные первыми)
    if (products.length > 0) {
        if (recipes.length > 0) {
            html += `<div class="px-4 py-2 text-xs font-semibold text-text-secondary bg-background-light dark:bg-[#1c3029]">Продукты</div>`
        }
        
        // Сортируем: избранные сверху
        const sortedProducts = [...products].sort((a, b) => {
            const aFav = favoriteProductIds.has(a.id) ? 0 : 1
            const bFav = favoriteProductIds.has(b.id) ? 0 : 1
            return aFav - bFav
        })
        
        html += sortedProducts.slice(0, 10).map(product => {
            const isFavorite = favoriteProductIds.has(product.id)
            return `
            <div class="flex items-center gap-3 px-5 py-4 hover:bg-background-light dark:hover:bg-[#1c3029] border-b border-[#f0f4f3] dark:border-[#1c3029] last:border-b-0 transition-colors group cursor-pointer"
                onclick="window.selectProduct(${product.id}, '${escapeHtml(product.title)}')">
                
                <button 
                    onclick="window.toggleFavorite('product', ${product.id}, event)"
                    class="flex-shrink-0 ${isFavorite ? 'text-yellow-500' : 'text-text-secondary'} hover:text-yellow-500 transition-colors"
                >
                    <span class="material-symbols-outlined text-xl">star</span>
                </button>
                
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-text-primary dark:text-white group-hover:text-primary transition-colors truncate">
                        ${escapeHtml(product.title)}
                    </div>
                    <div class="flex items-center gap-2 text-xs text-text-secondary mt-1">
                        <span class="text-primary font-bold">${product.calories} ккал</span>
                        <span>•</span>
                        <span>Б: ${product.proteins}г</span>
                        <span>•</span>
                        <span>Ж: ${product.fats}г</span>
                        <span>•</span>
                        <span>У: ${product.carbohydrates}г</span>
                    </div>
                </div>
                
                <span class="material-symbols-outlined text-text-secondary opacity-0 group-hover:opacity-100 transition-opacity">
                    chevron_right
                </span>
            </div>
        `}).join('')
    }
    
    resultsDiv.innerHTML = html
    resultsDiv.classList.remove('hidden')
}

// Выбор продукта из результатов поиска
let selectedProduct = null
let selectedRecipe = null

window.selectProduct = function(productId, productTitle) {
    console.log('Выбран продукт:', productId, productTitle)
    
    // Закрываем результаты
    document.getElementById('search-results').classList.add('hidden')
    
    // Загружаем полную информацию о продукте
    loadProductDetails(productId)
}

// Выбор рецепта из результатов поиска
window.selectRecipe = function(recipeId, recipeTitle) {
    console.log('Выбран рецепт:', recipeId, recipeTitle)
    
    // Закрываем результаты
    document.getElementById('search-results').classList.add('hidden')
    
    // Загружаем полную информацию о рецепте
    loadRecipeDetails(recipeId)
}

async function loadRecipeDetails(recipeId) {
    try {
        const response = await fetch(`/api/recipes.php?action=get&id=${recipeId}`)
        const data = await response.json()
        
        if (data.success) {
            selectedRecipe = data.recipe
            showAddRecipePanel(data.recipe)
        } else {
            alert('Ошибка загрузки рецепта')
        }
    } catch (error) {
        console.error('Ошибка:', error)
        alert('Ошибка загрузки рецепта')
    }
}

function showAddRecipePanel(recipe) {
    const panel = document.getElementById('add-product-panel')
    
    // Используем per_serving для отображения
    const perServing = recipe.per_serving || {}
    
    // Заполняем данные
    document.getElementById('panel-product-name').textContent = '🍽️ ' + recipe.title
    document.getElementById('panel-product-nutrition').textContent = 
        `${Math.round(perServing.calories || 0)} ккал • Б: ${(perServing.proteins || 0).toFixed(1)}г • Ж: ${(perServing.fats || 0).toFixed(1)}г • У: ${(perServing.carbohydrates || 0).toFixed(1)}г (на порцию)`
    
    // Сбрасываем форму - для рецепта граммы = 1 порция
    document.getElementById('panel-grams').value = '1'
    
    // Определяем приём пищи по времени
    let mealType
    if (preselectedMealType) {
        mealType = preselectedMealType
        preselectedMealType = null
    } else {
        const hour = new Date().getHours()
        if (hour < 11) mealType = 'завтрак'
        else if (hour < 16) mealType = 'обед'
        else if (hour < 21) mealType = 'ужин'
        else mealType = 'перекус'
    }
    
    document.getElementById('panel-meal-type').value = mealType
    
    // Обновляем расчёт КБЖУ
    updateCalculatedNutritionForRecipe()
    
    // Показываем панель
    panel.classList.remove('translate-y-full')
    
    // Меняем обработчик граммов на порции
    document.getElementById('panel-grams').oninput = updateCalculatedNutritionForRecipe
}

function updateCalculatedNutritionForRecipe() {
    if (!selectedRecipe) return
    
    const servings = parseFloat(document.getElementById('panel-grams').value) || 0
    const perServing = selectedRecipe.per_serving || {}
    
    document.getElementById('calc-calories').textContent = Math.round((perServing.calories || 0) * servings)
    document.getElementById('calc-proteins').textContent = ((perServing.proteins || 0) * servings).toFixed(1)
    document.getElementById('calc-fats').textContent = ((perServing.fats || 0) * servings).toFixed(1)
    document.getElementById('calc-carbs').textContent = ((perServing.carbohydrates || 0) * servings).toFixed(1)
}

async function loadProductDetails(productId) {
    try {
        const response = await fetch(`/api/products.php?action=get&id=${productId}`)
        const data = await response.json()
        
        if (data.success) {
            selectedProduct = data.product
            showAddPanel(data.product)
        } else {
            alert('Ошибка загрузки продукта')
        }
    } catch (error) {
        console.error('Ошибка:', error)
        alert('Ошибка загрузки продукта')
    }
}

function showAddPanel(product) {
    const panel = document.getElementById('add-product-panel')
    
    // Заполняем данные
    document.getElementById('panel-product-name').textContent = product.title
    document.getElementById('panel-product-nutrition').textContent = 
        `${product.calories} ккал • Б: ${product.proteins}г • Ж: ${product.fats}г • У: ${product.carbohydrates}г (на 100г)`
    
    // Сбрасываем форму
    document.getElementById('panel-grams').value = '100'
    
    // Определяем приём пищи
    let mealType
    
    if (preselectedMealType) {
        // Если кликнули на конкретный приём - используем его
        mealType = preselectedMealType
        preselectedMealType = null // Сбрасываем после использования
    } else {
        // Иначе определяем по времени
        const hour = new Date().getHours()
        if (hour < 11) mealType = 'завтрак'
        else if (hour < 16) mealType = 'обед'
        else if (hour < 21) mealType = 'ужин'
        else mealType = 'перекус'
    }
    
    document.getElementById('panel-meal-type').value = mealType
    
    // Пересчитываем КБЖУ
    updateCalculatedNutrition()
    
    // Показываем панель
    panel.classList.remove('translate-y-full')
    
    // Слушатель изменения граммов
    document.getElementById('panel-grams').oninput = updateCalculatedNutrition
}

function updateCalculatedNutrition() {
    if (!selectedProduct) return
    
    const grams = parseFloat(document.getElementById('panel-grams').value) || 0
    const ratio = grams / 100
    
    // Парсим значения из базы (могут быть строками!)
    const calories = parseFloat(selectedProduct.calories) || 0
    const proteins = parseFloat(selectedProduct.proteins) || 0
    const fats = parseFloat(selectedProduct.fats) || 0
    const carbs = parseFloat(selectedProduct.carbohydrates) || 0
    
    document.getElementById('calc-calories').textContent = Math.round(calories * ratio)
    document.getElementById('calc-proteins').textContent = (proteins * ratio).toFixed(1)
    document.getElementById('calc-fats').textContent = (fats * ratio).toFixed(1)
    document.getElementById('calc-carbs').textContent = (carbs * ratio).toFixed(1)
}

window.closeAddPanel = function() {
    const panel = document.getElementById('add-product-panel')
    panel.classList.add('translate-y-full')
    selectedProduct = null
    selectedRecipe = null
}

window.submitAddProduct = async function() {
    const mealType = document.getElementById('panel-meal-type').value
    const quantity = parseFloat(document.getElementById('panel-grams').value)
    
    if (!quantity || quantity <= 0) {
        alert(selectedRecipe ? 'Укажите количество порций' : 'Укажите количество грамм')
        return
    }
    
    if (selectedRecipe) {
        // Добавляем рецепт (все ингредиенты)
        await addRecipeToMeal(selectedRecipe, quantity, mealType)
    } else if (selectedProduct) {
        // Добавляем продукт
        await addMealItem(selectedProduct.id, quantity, mealType)
    }
    
    window.closeAddPanel()
    window.closeAddPanel()
    
    // Очищаем поиск
    document.getElementById('product-search').value = ''
}

async function addMealItem(productId, grams, mealType) {
    try {
        const response = await fetch('/api/meals.php?action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: productId,
                grams: grams,
                meal_type: mealType,
                date: window.dashboardModule?.getCurrentDate() || new Date().toISOString().split('T')[0]
            })
        })
        
        const data = await response.json()
        
        if (data.success) {
            console.log('✅ Продукт добавлен!')
            // Перезагружаем приёмы пищи
            if (window.dashboardModule) {
                window.dashboardModule.loadMeals()
            }
        } else {
            alert('Ошибка: ' + data.error)
        }
        
    } catch (error) {
        console.error('Ошибка добавления:', error)
        alert('Ошибка добавления продукта')
    }
}

// Добавление рецепта — добавляем все ингредиенты с пересчётом на порции
async function addRecipeToMeal(recipe, servings, mealType) {
    const date = window.dashboardModule?.getCurrentDate() || new Date().toISOString().split('T')[0]
    const totalServings = recipe.servings || 1
    const ratio = servings / totalServings
    
    try {
        // Добавляем каждый ингредиент
        for (const ing of recipe.ingredients || []) {
            const grams = Math.round(ing.grams * ratio * 10) / 10
            
            await fetch('/api/meals.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    product_id: ing.product_id,
                    grams: grams,
                    meal_type: mealType,
                    date: date
                })
            })
        }
        
        console.log(`✅ Рецепт "${recipe.title}" добавлен (${servings} порц.)`)
        
        // Перезагружаем приёмы пищи
        if (window.dashboardModule) {
            window.dashboardModule.loadMeals()
        }
        
    } catch (error) {
        console.error('Ошибка добавления рецепта:', error)
        alert('Ошибка добавления рецепта')
    }
}

function escapeHtml(text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
}

// ============================================
// СОЗДАНИЕ СВОЕГО ПРОДУКТА
// ============================================

window.openCreateProductModal = function(prefillTitle = '') {
    const modal = document.getElementById('create-product-modal')
    if (!modal) {
        console.error('Модалка создания продукта не найдена!')
        return
    }
    
    // Сбрасываем форму
    document.getElementById('new-product-title').value = prefillTitle
    document.getElementById('new-product-calories').value = ''
    document.getElementById('new-product-proteins').value = ''
    document.getElementById('new-product-fats').value = ''
    document.getElementById('new-product-carbs').value = ''
    
    modal.classList.remove('hidden')
    document.getElementById('new-product-title').focus()
}

window.closeCreateProductModal = function() {
    document.getElementById('create-product-modal').classList.add('hidden')
}

window.submitCreateProduct = async function() {
    const title = document.getElementById('new-product-title').value.trim()
    const calories = parseFloat(document.getElementById('new-product-calories').value) || 0
    const proteins = parseFloat(document.getElementById('new-product-proteins').value) || 0
    const fats = parseFloat(document.getElementById('new-product-fats').value) || 0
    const carbs = parseFloat(document.getElementById('new-product-carbs').value) || 0
    
    if (!title || title.length < 2) {
        alert('Введите название продукта (минимум 2 символа)')
        return
    }
    
    try {
        const response = await fetch('/api/products.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title,
                calories,
                proteins,
                fats,
                carbohydrates: carbs
            })
        })
        
        const data = await response.json()
        
        if (data.success) {
            console.log('✅ Продукт создан! ID:', data.product_id)
            window.closeCreateProductModal()
            
            // Закрываем результаты поиска
            document.getElementById('search-results').classList.add('hidden')
            
            // Обновляем список "Мои продукты" если он открыт
            if (window.loadMyProducts) {
                window.loadMyProducts()
            }
            
            // Сразу выбираем созданный продукт
            window.selectProduct(data.product_id, title)
        } else {
            alert('Ошибка: ' + (data.error || 'Не удалось создать продукт'))
        }
        
    } catch (error) {
        console.error('Ошибка создания продукта:', error)
        alert('Ошибка создания продукта')
    }
}

// ============================================
// ИЗБРАННОЕ
// ============================================

async function loadFavoriteIds() {
    try {
        const response = await fetch('/api/favorites.php?action=ids')
        const data = await response.json()
        
        if (data.success) {
            favoriteProductIds = new Set(data.product_ids)
            favoriteRecipeIds = new Set(data.recipe_ids)
        }
    } catch (error) {
        console.error('Ошибка загрузки избранного:', error)
    }
}

async function toggleFavorite(type, id, event) {
    event.stopPropagation()
    
    const isProduct = type === 'product'
    const set = isProduct ? favoriteProductIds : favoriteRecipeIds
    const isFavorite = set.has(id)
    
    const btn = event.currentTarget
    const icon = btn.querySelector('.material-symbols-outlined')
    
    try {
        if (isFavorite) {
            // Удаляем из избранного
            const response = await fetch('/api/favorites.php?action=remove', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(isProduct ? { product_id: id } : { recipe_id: id })
            })
            const data = await response.json()
            
            if (data.success) {
                set.delete(id)
                icon.textContent = 'star'
                icon.classList.remove('text-yellow-500')
                icon.classList.add('text-text-secondary')
            }
        } else {
            // Добавляем в избранное
            const response = await fetch('/api/favorites.php?action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(isProduct ? { product_id: id } : { recipe_id: id })
            })
            const data = await response.json()
            
            if (data.success) {
                set.add(id)
                icon.textContent = 'star'
                icon.classList.add('text-yellow-500')
                icon.classList.remove('text-text-secondary')
            }
        }
    } catch (error) {
        console.error('Ошибка избранного:', error)
    }
}

// Экспортируем для использования в других модулях
export { favoriteProductIds, favoriteRecipeIds, loadFavoriteIds }
