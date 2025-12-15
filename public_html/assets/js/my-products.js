// ============================================
// МОИ ПРОДУКТЫ И БЛЮДА
// ============================================

let currentTab = 'products'

export function initMyProducts() {
    // Загружаем данные при переключении на экран
    window.switchMyProductsTab = switchTab
    window.loadMyProducts = loadMyProducts
    window.loadMyRecipes = loadMyRecipes
    window.deleteMyProduct = deleteMyProduct
    window.deleteMyRecipe = deleteMyRecipe
    window.editMyProduct = editMyProduct
    window.openCreateRecipeModal = openCreateRecipeModal
    window.closeCreateRecipeModal = closeCreateRecipeModal
    window.addIngredientToRecipe = addIngredientToRecipe
    window.removeIngredientFromRecipe = removeIngredientFromRecipe
    window.submitCreateRecipe = submitCreateRecipe
}

function switchTab(tab) {
    currentTab = tab
    
    const tabProducts = document.getElementById('tab-products')
    const tabRecipes = document.getElementById('tab-recipes')
    const listProducts = document.getElementById('my-products-list')
    const listRecipes = document.getElementById('my-recipes-list')
    
    if (tab === 'products') {
        tabProducts.className = 'px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors'
        tabRecipes.className = 'px-4 py-2 bg-background-light dark:bg-[#1c3029] text-text-secondary rounded-lg font-medium transition-colors hover:text-text-primary'
        listProducts.classList.remove('hidden')
        listRecipes.classList.add('hidden')
        loadMyProducts()
    } else {
        tabRecipes.className = 'px-4 py-2 bg-purple-500 text-white rounded-lg font-medium transition-colors'
        tabProducts.className = 'px-4 py-2 bg-background-light dark:bg-[#1c3029] text-text-secondary rounded-lg font-medium transition-colors hover:text-text-primary'
        listRecipes.classList.remove('hidden')
        listProducts.classList.add('hidden')
        loadMyRecipes()
    }
}

// ============================================
// ПРОДУКТЫ
// ============================================

async function loadMyProducts() {
    const container = document.getElementById('my-products-list')
    
    container.innerHTML = `
        <div class="text-center py-12 text-text-secondary">
            <span class="material-symbols-outlined text-5xl mb-3 animate-spin">progress_activity</span>
            <p>Загрузка...</p>
        </div>
    `
    
    try {
        const response = await fetch('/api/products.php?action=my_products')
        const data = await response.json()
        
        if (data.success && data.products.length > 0) {
            renderMyProducts(data.products)
        } else {
            container.innerHTML = `
                <div class="text-center py-12 text-text-secondary">
                    <span class="material-symbols-outlined text-5xl mb-3">inventory_2</span>
                    <p class="mb-4">У вас пока нет своих продуктов</p>
                    <button 
                        onclick="window.openCreateProductModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg font-medium transition-colors"
                    >
                        <span class="material-symbols-outlined text-lg">add</span>
                        Создать первый продукт
                    </button>
                </div>
            `
        }
    } catch (error) {
        console.error('Ошибка загрузки продуктов:', error)
        container.innerHTML = `
            <div class="text-center py-12 text-red-500">
                <span class="material-symbols-outlined text-5xl mb-3">error</span>
                <p>Ошибка загрузки</p>
            </div>
        `
    }
}

function renderMyProducts(products) {
    const container = document.getElementById('my-products-list')
    
    let html = products.map(p => `
        <div class="bg-white dark:bg-[#152822] rounded-xl border border-[#dbe6e2] dark:border-[#2a3f38] p-4 flex items-center justify-between gap-4 group">
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-text-primary dark:text-white truncate">${escapeHtml(p.title)}</h3>
                <div class="flex items-center gap-2 text-xs text-text-secondary mt-1">
                    <span class="text-primary font-bold">${p.calories} ккал</span>
                    <span>•</span>
                    <span>Б: ${p.proteins}г</span>
                    <span>•</span>
                    <span>Ж: ${p.fats}г</span>
                    <span>•</span>
                    <span>У: ${p.carbohydrates}г</span>
                </div>
            </div>
            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <button 
                    onclick="window.editMyProduct(${p.id})"
                    class="p-2 text-text-secondary hover:text-primary transition-colors"
                    title="Редактировать"
                >
                    <span class="material-symbols-outlined text-xl">edit</span>
                </button>
                <button 
                    onclick="window.deleteMyProduct(${p.id}, '${escapeHtml(p.title)}')"
                    class="p-2 text-text-secondary hover:text-red-500 transition-colors"
                    title="Удалить"
                >
                    <span class="material-symbols-outlined text-xl">delete</span>
                </button>
            </div>
        </div>
    `).join('')
    
    // Кнопка добавления внизу
    html += `
        <button 
            onclick="window.openCreateProductModal()"
            class="flex items-center justify-center gap-2 p-4 border-2 border-dashed border-[#dbe6e2] dark:border-[#2a3f38] rounded-xl text-text-secondary hover:text-primary hover:border-primary transition-colors"
        >
            <span class="material-symbols-outlined">add</span>
            Добавить продукт
        </button>
    `
    
    container.innerHTML = html
}


async function deleteMyProduct(productId, title) {
    window.showDeleteModal('product', productId, title)
}

async function editMyProduct(productId) {
    try {
        const response = await fetch(`/api/products.php?action=get&id=${productId}`)
        const data = await response.json()
        
        if (data.success) {
            const p = data.product
            document.getElementById('edit-product-id').value = p.id
            document.getElementById('edit-product-title').value = p.title
            document.getElementById('edit-product-calories').value = p.calories || ''
            document.getElementById('edit-product-proteins').value = p.proteins || ''
            document.getElementById('edit-product-fats').value = p.fats || ''
            document.getElementById('edit-product-carbs').value = p.carbohydrates || ''
            
            document.getElementById('edit-product-modal').classList.remove('hidden')
        } else {
            alert('Ошибка загрузки продукта')
        }
    } catch (error) {
        console.error('Ошибка:', error)
        alert('Ошибка загрузки продукта')
    }
}

window.closeEditProductModal = function() {
    document.getElementById('edit-product-modal').classList.add('hidden')
}

window.submitEditProduct = async function() {
    const id = document.getElementById('edit-product-id').value
    const title = document.getElementById('edit-product-title').value.trim()
    const calories = parseFloat(document.getElementById('edit-product-calories').value) || 0
    const proteins = parseFloat(document.getElementById('edit-product-proteins').value) || 0
    const fats = parseFloat(document.getElementById('edit-product-fats').value) || 0
    const carbs = parseFloat(document.getElementById('edit-product-carbs').value) || 0
    
    if (!title || title.length < 2) {
        alert('Введите название продукта')
        return
    }
    
    try {
        const response = await fetch('/api/products.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: id,
                title,
                calories,
                proteins,
                fats,
                carbohydrates: carbs
            })
        })
        
        const data = await response.json()
        
        if (data.success) {
            window.closeEditProductModal()
            loadMyProducts()
        } else {
            alert('Ошибка: ' + (data.error || 'Не удалось сохранить'))
        }
    } catch (error) {
        console.error('Ошибка:', error)
        alert('Ошибка сохранения')
    }
}

// ============================================
// БЛЮДА (РЕЦЕПТЫ)
// ============================================

async function loadMyRecipes() {
    const container = document.getElementById('my-recipes-list')
    
    container.innerHTML = `
        <div class="text-center py-12 text-text-secondary">
            <span class="material-symbols-outlined text-5xl mb-3 animate-spin">progress_activity</span>
            <p>Загрузка...</p>
        </div>
    `
    
    try {
        const response = await fetch('/api/recipes.php?action=list')
        const data = await response.json()
        
        if (data.success && data.recipes.length > 0) {
            renderMyRecipes(data.recipes)
        } else {
            container.innerHTML = `
                <div class="text-center py-12 text-text-secondary">
                    <span class="material-symbols-outlined text-5xl mb-3">menu_book</span>
                    <p class="mb-4">У вас пока нет своих блюд</p>
                    <button 
                        onclick="window.openCreateRecipeModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg font-medium transition-colors"
                    >
                        <span class="material-symbols-outlined text-lg">add</span>
                        Создать первое блюдо
                    </button>
                </div>
            `
        }
    } catch (error) {
        console.error('Ошибка загрузки блюд:', error)
        container.innerHTML = `
            <div class="text-center py-12 text-red-500">
                <span class="material-symbols-outlined text-5xl mb-3">error</span>
                <p>Ошибка загрузки</p>
            </div>
        `
    }
}

function renderMyRecipes(recipes) {
    const container = document.getElementById('my-recipes-list')
    
    let html = recipes.map(r => `
        <div class="bg-white dark:bg-[#152822] rounded-xl border border-[#dbe6e2] dark:border-[#2a3f38] p-4 flex items-center justify-between gap-4 group">
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-text-primary dark:text-white truncate">${escapeHtml(r.title)}</h3>
                <div class="flex items-center gap-2 text-xs text-text-secondary mt-1">
                    <span class="text-purple-500 font-bold">${Math.round(r.total_calories || 0)} ккал</span>
                    <span>•</span>
                    <span>${r.ingredients_count || 0} ингредиентов</span>
                    <span>•</span>
                    <span>${r.servings || 1} порц.</span>
                </div>
            </div>
            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <button 
                    onclick="window.editMyRecipe(${r.id})"
                    class="p-2 text-text-secondary hover:text-purple-500 transition-colors"
                    title="Редактировать"
                >
                    <span class="material-symbols-outlined text-xl">edit</span>
                </button>
                <button 
                    onclick="window.deleteMyRecipe(${r.id}, '${escapeHtml(r.title)}')"
                    class="p-2 text-text-secondary hover:text-red-500 transition-colors"
                    title="Удалить"
                >
                    <span class="material-symbols-outlined text-xl">delete</span>
                </button>
            </div>
        </div>
    `).join('')
    
    // Кнопка добавления внизу
    html += `
        <button 
            onclick="window.openCreateRecipeModal()"
            class="flex items-center justify-center gap-2 p-4 border-2 border-dashed border-[#dbe6e2] dark:border-[#2a3f38] rounded-xl text-text-secondary hover:text-purple-500 hover:border-purple-500 transition-colors"
        >
            <span class="material-symbols-outlined">add</span>
            Добавить блюдо
        </button>
    `
    
    container.innerHTML = html
}

async function deleteMyRecipe(recipeId, title) {
    window.showDeleteModal('recipe', recipeId, title)
}

// ============================================
// РЕДАКТИРОВАНИЕ БЛЮДА
// ============================================

let editRecipeData = null

window.editMyRecipe = async function(recipeId) {
    try {
        const response = await fetch(`/api/recipes.php?action=get&id=${recipeId}`)
        const data = await response.json()
        
        if (data.success) {
            editRecipeData = data.recipe
            
            document.getElementById('edit-recipe-id').value = data.recipe.id
            document.getElementById('edit-recipe-title').value = data.recipe.title
            document.getElementById('edit-recipe-servings').value = data.recipe.servings || 1
            
            renderEditRecipeIngredients()
            
            document.getElementById('edit-recipe-modal').classList.remove('hidden')
        } else {
            alert('Ошибка загрузки блюда')
        }
    } catch (error) {
        console.error('Ошибка:', error)
        alert('Ошибка загрузки блюда')
    }
}

function renderEditRecipeIngredients() {
    const container = document.getElementById('edit-recipe-ingredients')
    const totalsContainer = document.getElementById('edit-recipe-totals')
    const ingredients = editRecipeData?.ingredients || []
    
    if (ingredients.length === 0) {
        container.innerHTML = '<p class="text-center text-text-secondary py-2">Нет ингредиентов</p>'
        totalsContainer.innerHTML = ''
        return
    }
    
    let totalCal = 0, totalProt = 0, totalFat = 0, totalCarb = 0
    
    container.innerHTML = ingredients.map((ing, idx) => {
        // Пересчитываем КБЖУ на основе граммов
        // Используем поля из API: calories, proteins, fats, carbohydrates (на 100г)
        // или calories_per_100 если добавлено через UI
        const ratio = ing.grams / 100
        const cal = (parseFloat(ing.calories_per_100 || ing.calories) || 0) * ratio
        const prot = (parseFloat(ing.proteins_per_100 || ing.proteins) || 0) * ratio
        const fat = (parseFloat(ing.fats_per_100 || ing.fats) || 0) * ratio
        const carb = (parseFloat(ing.carbs_per_100 || ing.carbohydrates) || 0) * ratio
        
        totalCal += cal
        totalProt += prot
        totalFat += fat
        totalCarb += carb
        
        return `
            <div class="flex items-center gap-2 py-2 border-b border-[#f0f4f3] dark:border-[#1c3029] last:border-0 group">
                <div class="flex-1 min-w-0">
                    <span class="text-sm font-medium text-text-primary dark:text-white truncate block">${escapeHtml(ing.product_title)}</span>
                    <span class="text-xs text-text-secondary">${Math.round(cal)} ккал</span>
                </div>
                <input 
                    type="number" 
                    value="${ing.grams}"
                    min="1"
                    data-ingredient-id="${ing.id}"
                    data-idx="${idx}"
                    onchange="window.updateEditIngredientGrams(${ing.id}, ${idx}, this.value)"
                    class="w-16 px-2 py-1 text-sm bg-white dark:bg-[#152822] border border-[#dbe6e2] dark:border-[#2a3f38] rounded text-center"
                >
                <span class="text-xs text-text-secondary">г</span>
                <button onclick="window.removeEditIngredient(${ing.id})" class="text-red-500 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
        `
    }).join('')
    
    const servings = parseInt(document.getElementById('edit-recipe-servings').value) || 1
    totalsContainer.innerHTML = `
        <div class="grid grid-cols-4 gap-2 text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <div>
                <div class="text-lg font-bold text-purple-600">${Math.round(totalCal)}</div>
                <div class="text-xs text-text-secondary">ккал</div>
            </div>
            <div>
                <div class="text-lg font-bold text-text-primary dark:text-white">${totalProt.toFixed(1)}</div>
                <div class="text-xs text-text-secondary">белки</div>
            </div>
            <div>
                <div class="text-lg font-bold text-text-primary dark:text-white">${totalFat.toFixed(1)}</div>
                <div class="text-xs text-text-secondary">жиры</div>
            </div>
            <div>
                <div class="text-lg font-bold text-text-primary dark:text-white">${totalCarb.toFixed(1)}</div>
                <div class="text-xs text-text-secondary">углеводы</div>
            </div>
        </div>
        <p class="text-xs text-text-secondary text-center mt-2">На порцию: ${Math.round(totalCal / servings)} ккал</p>
    `
}

window.closeEditRecipeModal = function() {
    document.getElementById('edit-recipe-modal').classList.add('hidden')
    editRecipeData = null
}

window.submitEditRecipe = async function() {
    const id = document.getElementById('edit-recipe-id').value
    const title = document.getElementById('edit-recipe-title').value.trim()
    const servings = parseInt(document.getElementById('edit-recipe-servings').value) || 1
    
    if (!title || title.length < 2) {
        alert('Введите название блюда')
        return
    }
    
    try {
        const response = await fetch('/api/recipes.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                recipe_id: id,
                title,
                servings
            })
        })
        
        const data = await response.json()
        
        if (data.success) {
            window.closeEditRecipeModal()
            loadMyRecipes()
        } else {
            alert('Ошибка: ' + (data.error || 'Не удалось сохранить'))
        }
    } catch (error) {
        console.error('Ошибка:', error)
        alert('Ошибка сохранения')
    }
}

// Удаление ингредиента при редактировании
window.removeEditIngredient = async function(ingredientId) {
    try {
        const response = await fetch('/api/recipes.php?action=remove_ingredient', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ingredient_id: ingredientId })
        })
        
        const data = await response.json()
        
        if (data.success) {
            // Удаляем из локальных данных
            editRecipeData.ingredients = editRecipeData.ingredients.filter(i => i.id !== ingredientId)
            renderEditRecipeIngredients()
        } else {
            alert('Ошибка: ' + (data.error || 'Не удалось удалить'))
        }
    } catch (error) {
        console.error('Ошибка:', error)
    }
}

// Обновление граммов ингредиента
let gramsUpdateTimeout = null

window.updateEditIngredientGrams = async function(ingredientId, idx, newGrams) {
    const grams = parseFloat(newGrams) || 0
    if (grams <= 0) return
    
    // Обновляем локально сразу для отзывчивости
    editRecipeData.ingredients[idx].grams = grams
    
    // Пересчитываем итоги без полного перерендера
    recalcEditTotals()
    
    // Дебаунс для API
    clearTimeout(gramsUpdateTimeout)
    gramsUpdateTimeout = setTimeout(async () => {
        try {
            await fetch('/api/recipes.php?action=update_ingredient', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ingredient_id: ingredientId, grams: grams })
            })
        } catch (error) {
            console.error('Ошибка обновления граммов:', error)
        }
    }, 500)
}

function recalcEditTotals() {
    const totalsContainer = document.getElementById('edit-recipe-totals')
    const ingredients = editRecipeData?.ingredients || []
    
    let totalCal = 0, totalProt = 0, totalFat = 0, totalCarb = 0
    
    ingredients.forEach(ing => {
        const ratio = ing.grams / 100
        totalCal += (parseFloat(ing.calories_per_100 || ing.calories) || 0) * ratio
        totalProt += (parseFloat(ing.proteins_per_100 || ing.proteins) || 0) * ratio
        totalFat += (parseFloat(ing.fats_per_100 || ing.fats) || 0) * ratio
        totalCarb += (parseFloat(ing.carbs_per_100 || ing.carbohydrates) || 0) * ratio
    })
    
    const servings = parseInt(document.getElementById('edit-recipe-servings').value) || 1
    totalsContainer.innerHTML = `
        <div class="grid grid-cols-4 gap-2 text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <div>
                <div class="text-lg font-bold text-purple-600">${Math.round(totalCal)}</div>
                <div class="text-xs text-text-secondary">ккал</div>
            </div>
            <div>
                <div class="text-lg font-bold text-text-primary dark:text-white">${totalProt.toFixed(1)}</div>
                <div class="text-xs text-text-secondary">белки</div>
            </div>
            <div>
                <div class="text-lg font-bold text-text-primary dark:text-white">${totalFat.toFixed(1)}</div>
                <div class="text-xs text-text-secondary">жиры</div>
            </div>
            <div>
                <div class="text-lg font-bold text-text-primary dark:text-white">${totalCarb.toFixed(1)}</div>
                <div class="text-xs text-text-secondary">углеводы</div>
            </div>
        </div>
        <p class="text-xs text-text-secondary text-center mt-2">На порцию: ${Math.round(totalCal / servings)} ккал</p>
    `
}

// Добавление ингредиента при редактировании
window.addEditIngredient = async function(productId, title, calories, proteins, fats, carbs) {
    const recipeId = document.getElementById('edit-recipe-id').value
    const grams = 100 // По умолчанию 100г
    
    try {
        const response = await fetch('/api/recipes.php?action=add_ingredient', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                recipe_id: recipeId,
                product_id: productId,
                grams: grams
            })
        })
        
        const data = await response.json()
        
        if (data.success) {
            // Добавляем в локальные данные с КБЖУ на 100г
            editRecipeData.ingredients.push({
                id: data.ingredient_id,
                product_id: productId,
                product_title: title,
                grams: grams,
                calories_per_100: parseFloat(calories) || 0,
                proteins_per_100: parseFloat(proteins) || 0,
                fats_per_100: parseFloat(fats) || 0,
                carbs_per_100: parseFloat(carbs) || 0
            })
            renderEditRecipeIngredients()
            
            // Очищаем поиск
            document.getElementById('edit-recipe-ingredient-search').value = ''
            document.getElementById('edit-recipe-search-results').classList.add('hidden')
        } else {
            alert('Ошибка: ' + (data.error || 'Не удалось добавить'))
        }
    } catch (error) {
        console.error('Ошибка:', error)
    }
}

// Поиск ингредиентов для редактирования
let editRecipeSearchTimeout = null

export function initEditRecipeSearch() {
    document.addEventListener('input', (e) => {
        if (e.target.id !== 'edit-recipe-ingredient-search') return
        
        const query = e.target.value.trim()
        const resultsDiv = document.getElementById('edit-recipe-search-results')
        
        clearTimeout(editRecipeSearchTimeout)
        
        if (query.length < 2) {
            resultsDiv.classList.add('hidden')
            return
        }
        
        editRecipeSearchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`/api/products.php?action=search&q=${encodeURIComponent(query)}&limit=5`)
                const data = await response.json()
                
                if (data.success && data.products.length > 0) {
                    resultsDiv.innerHTML = data.products.map(p => `
                        <div 
                            onclick="window.addEditIngredient(${p.id}, '${escapeHtml(p.title)}', '${p.calories}', '${p.proteins}', '${p.fats}', '${p.carbohydrates}')"
                            class="px-3 py-2 hover:bg-background-light dark:hover:bg-[#1c3029] cursor-pointer transition-colors"
                        >
                            <div class="font-medium text-sm text-text-primary dark:text-white">${escapeHtml(p.title)}</div>
                            <div class="text-xs text-text-secondary">${p.calories} ккал / 100г</div>
                        </div>
                    `).join('')
                    resultsDiv.classList.remove('hidden')
                } else {
                    resultsDiv.innerHTML = '<div class="px-3 py-2 text-text-secondary text-sm">Ничего не найдено</div>'
                    resultsDiv.classList.remove('hidden')
                }
            } catch (error) {
                console.error('Ошибка поиска:', error)
            }
        }, 300)
    })
}


// ============================================
// СОЗДАНИЕ БЛЮДА
// ============================================

let recipeIngredients = []

function openCreateRecipeModal() {
    const modal = document.getElementById('create-recipe-modal')
    if (!modal) {
        console.error('Модалка создания блюда не найдена!')
        return
    }
    
    // Сбрасываем форму
    document.getElementById('new-recipe-title').value = ''
    document.getElementById('new-recipe-servings').value = '1'
    document.getElementById('recipe-ingredient-search').value = ''
    recipeIngredients = []
    renderRecipeIngredients()
    
    modal.classList.remove('hidden')
    document.getElementById('new-recipe-title').focus()
}

function closeCreateRecipeModal() {
    document.getElementById('create-recipe-modal').classList.add('hidden')
    recipeIngredients = []
}

function addIngredientToRecipe(productId, title, calories, proteins, fats, carbs) {
    // Проверяем что не добавлен уже
    if (recipeIngredients.find(i => i.product_id === productId)) {
        alert('Этот продукт уже добавлен')
        return
    }
    
    recipeIngredients.push({
        product_id: productId,
        title,
        grams: 100,
        calories: parseFloat(calories) || 0,
        proteins: parseFloat(proteins) || 0,
        fats: parseFloat(fats) || 0,
        carbohydrates: parseFloat(carbs) || 0
    })
    
    renderRecipeIngredients()
    
    // Очищаем поиск
    document.getElementById('recipe-ingredient-search').value = ''
    document.getElementById('recipe-search-results').classList.add('hidden')
}

function removeIngredientFromRecipe(index) {
    recipeIngredients.splice(index, 1)
    renderRecipeIngredients()
}

function updateIngredientGrams(index, grams) {
    recipeIngredients[index].grams = parseFloat(grams) || 0
    renderRecipeIngredients()
}

function renderRecipeIngredients() {
    const container = document.getElementById('recipe-ingredients-list')
    const totalsContainer = document.getElementById('recipe-totals')
    
    if (recipeIngredients.length === 0) {
        container.innerHTML = `
            <p class="text-center text-text-secondary py-4">
                Добавьте ингредиенты через поиск выше
            </p>
        `
        totalsContainer.innerHTML = ''
        return
    }
    
    // Считаем итого
    let totalCal = 0, totalProt = 0, totalFat = 0, totalCarb = 0
    
    container.innerHTML = recipeIngredients.map((ing, idx) => {
        const ratio = ing.grams / 100
        const cal = ing.calories * ratio
        const prot = ing.proteins * ratio
        const fat = ing.fats * ratio
        const carb = ing.carbohydrates * ratio
        
        totalCal += cal
        totalProt += prot
        totalFat += fat
        totalCarb += carb
        
        return `
            <div class="flex items-center gap-3 py-2 border-b border-[#f0f4f3] dark:border-[#1c3029] last:border-0">
                <div class="flex-1 min-w-0">
                    <span class="text-sm font-medium text-text-primary dark:text-white truncate block">${escapeHtml(ing.title)}</span>
                    <span class="text-xs text-text-secondary">${Math.round(cal)} ккал</span>
                </div>
                <input 
                    type="number" 
                    value="${ing.grams}"
                    min="1"
                    onchange="window.updateIngredientGrams(${idx}, this.value)"
                    class="w-20 px-2 py-1 text-sm bg-background-light dark:bg-[#1c3029] rounded-lg text-center"
                >
                <span class="text-xs text-text-secondary">г</span>
                <button onclick="window.removeIngredientFromRecipe(${idx})" class="text-red-500 hover:text-red-600">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
        `
    }).join('')
    
    // Итого
    const servings = parseInt(document.getElementById('new-recipe-servings').value) || 1
    totalsContainer.innerHTML = `
        <div class="grid grid-cols-4 gap-2 text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <div>
                <div class="text-lg font-bold text-purple-600">${Math.round(totalCal)}</div>
                <div class="text-xs text-text-secondary">ккал</div>
            </div>
            <div>
                <div class="text-lg font-bold text-text-primary dark:text-white">${totalProt.toFixed(1)}</div>
                <div class="text-xs text-text-secondary">белки</div>
            </div>
            <div>
                <div class="text-lg font-bold text-text-primary dark:text-white">${totalFat.toFixed(1)}</div>
                <div class="text-xs text-text-secondary">жиры</div>
            </div>
            <div>
                <div class="text-lg font-bold text-text-primary dark:text-white">${totalCarb.toFixed(1)}</div>
                <div class="text-xs text-text-secondary">углеводы</div>
            </div>
        </div>
        <p class="text-xs text-text-secondary text-center mt-2">
            На порцию: ${Math.round(totalCal / servings)} ккал
        </p>
    `
}

// Экспортируем для доступа из onchange
window.updateIngredientGrams = updateIngredientGrams


async function submitCreateRecipe() {
    const title = document.getElementById('new-recipe-title').value.trim()
    const servings = parseInt(document.getElementById('new-recipe-servings').value) || 1
    
    if (!title || title.length < 2) {
        alert('Введите название блюда')
        return
    }
    
    if (recipeIngredients.length === 0) {
        alert('Добавьте хотя бы один ингредиент')
        return
    }
    
    try {
        const response = await fetch('/api/recipes.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title,
                servings,
                ingredients: recipeIngredients.map(i => ({
                    product_id: i.product_id,
                    grams: i.grams
                }))
            })
        })
        
        const data = await response.json()
        
        if (data.success) {
            closeCreateRecipeModal()
            // Переключаемся на таб блюд и обновляем
            switchTab('recipes')
        } else {
            alert('Ошибка: ' + (data.error || 'Не удалось создать блюдо'))
        }
    } catch (error) {
        console.error('Ошибка создания блюда:', error)
        alert('Ошибка создания блюда')
    }
}

// ============================================
// ПОИСК ИНГРЕДИЕНТОВ ДЛЯ РЕЦЕПТА
// ============================================

let recipeSearchTimeout = null

export function initRecipeSearch() {
    const searchInput = document.getElementById('recipe-ingredient-search')
    const resultsDiv = document.getElementById('recipe-search-results')
    
    if (!searchInput) return
    
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim()
        
        clearTimeout(recipeSearchTimeout)
        
        if (query.length < 2) {
            resultsDiv.classList.add('hidden')
            return
        }
        
        recipeSearchTimeout = setTimeout(() => {
            searchIngredientsForRecipe(query)
        }, 300)
    })
}

async function searchIngredientsForRecipe(query) {
    const resultsDiv = document.getElementById('recipe-search-results')
    
    try {
        const response = await fetch(`/api/products.php?action=search&q=${encodeURIComponent(query)}&limit=5`)
        const data = await response.json()
        
        if (data.success && data.products.length > 0) {
            resultsDiv.innerHTML = data.products.map(p => `
                <div 
                    onclick="window.addIngredientToRecipe(${p.id}, '${escapeHtml(p.title)}', '${p.calories}', '${p.proteins}', '${p.fats}', '${p.carbohydrates}')"
                    class="px-3 py-2 hover:bg-background-light dark:hover:bg-[#1c3029] cursor-pointer transition-colors"
                >
                    <div class="font-medium text-sm text-text-primary dark:text-white">${escapeHtml(p.title)}</div>
                    <div class="text-xs text-text-secondary">${p.calories} ккал / 100г</div>
                </div>
            `).join('')
            resultsDiv.classList.remove('hidden')
        } else {
            resultsDiv.innerHTML = '<div class="px-3 py-2 text-text-secondary text-sm">Ничего не найдено</div>'
            resultsDiv.classList.remove('hidden')
        }
    } catch (error) {
        console.error('Ошибка поиска:', error)
    }
}

function escapeHtml(text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
}
