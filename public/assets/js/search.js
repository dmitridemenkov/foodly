// ============================================
// ПОИСК ПРОДУКТОВ
// ============================================

let searchTimeout = null

export function initSearch() {
    const searchInput = document.getElementById('product-search')
    const resultsDiv = document.getElementById('search-results')
    
    if (!searchInput) return
    
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
        
        const response = await fetch(`/api/products.php?action=search&q=${encodeURIComponent(query)}`)
        const data = await response.json()
        
        if (!data.success || data.products.length === 0) {
            resultsDiv.innerHTML = `
                <div class="p-4 text-center text-text-secondary">
                    Ничего не найдено 😞
                </div>
            `
            return
        }
        
        displaySearchResults(data.products)
        
    } catch (error) {
        console.error('Ошибка поиска:', error)
        resultsDiv.innerHTML = `
            <div class="p-4 text-center text-red-500">
                Ошибка поиска. Попробуйте ещё раз.
            </div>
        `
    }
}

function displaySearchResults(products) {
    const resultsDiv = document.getElementById('search-results')
    
    const html = products.slice(0, 10).map(product => `
        <div class="flex items-center gap-3 px-5 py-4 hover:bg-background-light dark:hover:bg-[#1c3029] border-b border-[#f0f4f3] dark:border-[#1c3029] last:border-b-0 transition-colors group cursor-pointer"
            onclick="window.selectProduct(${product.id}, '${escapeHtml(product.title)}')">
            
            <!-- Звёздочка слева -->
            <button 
                onclick="event.stopPropagation(); console.log('В избранное:', ${product.id})"
                class="flex-shrink-0 text-text-secondary hover:text-yellow-500 transition-colors"
            >
                <span class="material-symbols-outlined text-xl">star</span>
            </button>
            
            <!-- Контент (название + КБЖУ) -->
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
            
            <!-- Стрелка справа (опционально) -->
            <span class="material-symbols-outlined text-text-secondary opacity-0 group-hover:opacity-100 transition-opacity">
                chevron_right
            </span>
        </div>
    `).join('')
    
    resultsDiv.innerHTML = html
    resultsDiv.classList.remove('hidden')
}

// Выбор продукта из результатов поиска
let selectedProduct = null

window.selectProduct = function(productId, productTitle) {
    console.log('Выбран продукт:', productId, productTitle)
    
    // Закрываем результаты
    document.getElementById('search-results').classList.add('hidden')
    
    // Загружаем полную информацию о продукте
    loadProductDetails(productId)
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
}

window.submitAddProduct = async function() {
    if (!selectedProduct) return
    
    const grams = parseFloat(document.getElementById('panel-grams').value)
    const mealType = document.getElementById('panel-meal-type').value
    
    if (!grams || grams <= 0) {
        alert('Укажите количество грамм')
        return
    }
    
    await addMealItem(selectedProduct.id, grams, mealType)
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

function escapeHtml(text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
}
