// ============================================
// ДАШБОРД - ПРИЁМЫ ПИЩИ
// ============================================

import { getCurrentDate, formatDate } from './calendar.js'

export function initDashboard() {
    // Экспортируем в window для доступа из других модулей
    window.dashboardModule = {
        loadMeals: (date) => {
            // Если передан Date объект - конвертируем в строку
            if (date instanceof Date) {
                loadMeals(formatDate(date))
            } else {
                loadMeals(date)
            }
        },
        getCurrentDate: () => formatDate(getCurrentDate())
    }
    
    // Функции редактирования цели
    window.startEditGoal = startEditGoal
    window.saveGoal = saveGoal
    window.cancelEditGoal = cancelEditGoal
    
    // Загружаем приёмы за текущий день
    loadMeals()
}

// ============================================
// РЕДАКТИРОВАНИЕ ЦЕЛИ КАЛОРИЙ
// ============================================

function startEditGoal() {
    const display = document.getElementById('calorie-goal-display')
    const input = document.getElementById('calorie-goal-input')
    const editBtn = display.nextElementSibling
    
    display.classList.add('hidden')
    editBtn.classList.add('hidden')
    input.classList.remove('hidden')
    input.value = window.APP_CALORIE_GOAL
    input.focus()
    input.select()
}

function cancelEditGoal() {
    const display = document.getElementById('calorie-goal-display')
    const input = document.getElementById('calorie-goal-input')
    const editBtn = display.nextElementSibling
    
    input.classList.add('hidden')
    display.classList.remove('hidden')
    editBtn.classList.remove('hidden')
}

async function saveGoal() {
    const input = document.getElementById('calorie-goal-input')
    const newGoal = parseInt(input.value) || 2000
    
    // Валидация
    if (newGoal < 500 || newGoal > 10000) {
        alert('Цель должна быть от 500 до 10000 ккал')
        input.focus()
        return
    }
    
    // Если не изменилось — просто закрываем
    if (newGoal === window.APP_CALORIE_GOAL) {
        cancelEditGoal()
        return
    }
    
    try {
        const response = await fetch('/api/user.php?action=update_goal', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ calorie_goal: newGoal })
        })
        
        const data = await response.json()
        
        if (data.success) {
            // Обновляем глобально и в UI
            window.APP_CALORIE_GOAL = newGoal
            document.getElementById('calorie-goal-value').textContent = newGoal
            
            cancelEditGoal()
            
            // Перезагружаем данные дня чтобы пересчитать прогресс
            loadMeals()
        } else {
            alert('Ошибка: ' + (data.error || 'Не удалось сохранить'))
        }
    } catch (error) {
        console.error('Ошибка сохранения цели:', error)
        alert('Ошибка сохранения')
    }
}

async function loadMeals(date) {
    const dateStr = date || formatDate(getCurrentDate())
    
    console.log('Загрузка приёмов за:', dateStr)
    
    try {
        const response = await fetch(`/api/meals.php?action=get_day&date=${dateStr}`)
        const data = await response.json()
        
        if (data.success) {
            renderMeals(data.meals || [])
            updateSummary(data.summary || {
                total_calories: 0,
                total_proteins: 0,
                total_fats: 0,
                total_carbohydrates: 0
            })
        } else {
            console.error('Ошибка загрузки приёмов:', data.error)
            renderEmptyMeals()
        }
        
    } catch (error) {
        console.error('Ошибка запроса приёмов:', error)
        renderEmptyMeals()
    }
}

function renderMeals(meals) {
    const container = document.getElementById('meals-list')
    if (!container) return
    
    if (meals.length === 0) {
        renderEmptyMeals()
        return
    }
    
    // Группируем по типу приёма
    const grouped = {}
    meals.forEach(meal => {
        const type = meal.meal_type || 'Другое'
        if (!grouped[type]) {
            grouped[type] = []
        }
        grouped[type].push(meal)
    })
    
    const mealTypes = [
        { key: 'завтрак', label: 'Завтрак', icon: 'bakery_dining', colorClasses: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400' },
        { key: 'обед', label: 'Обед', icon: 'restaurant', colorClasses: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' },
        { key: 'ужин', label: 'Ужин', icon: 'dinner_dining', colorClasses: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400' },
        { key: 'перекус', label: 'Перекус', icon: 'icecream', colorClasses: 'bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400' }
    ]
    
    let html = ''
    
    mealTypes.forEach(mealType => {
        const items = grouped[mealType.key] || []
        const totalCalories = Math.round(items.reduce((sum, item) => sum + (parseFloat(item.calories) || 0), 0))
        
        if (items.length > 0) {
            // Развёрнутая карточка с продуктами
            html += renderMealCard(mealType, items, totalCalories, true)
        } else {
            // Пустая карточка
            html += renderMealCard(mealType, [], 0, false)
        }
    })
    
    container.innerHTML = html
}

function renderMealCard(mealType, items, totalCalories, hasItems) {
    const { label, icon, colorClasses } = mealType
    
    if (!hasItems) {
        // Пустая карточка
        return `
            <div class="bg-white dark:bg-[#152822] rounded-2xl border border-[#dbe6e2] dark:border-[#2a3f38] shadow-sm transition-all hover:border-primary/50 group cursor-pointer mb-4" onclick="window.focusSearch('${mealType.key}')">
                <div class="p-5 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full ${colorClasses} flex items-center justify-center">
                            <span class="material-symbols-outlined">${icon}</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-text-primary dark:text-white text-base">${label}</h3>
                            <p class="text-xs text-text-secondary group-hover:text-primary transition-colors">Пока пусто</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-lg font-bold text-text-secondary dark:text-gray-600">0 <span class="text-sm font-normal">ккал</span></span>
                        <button class="w-8 h-8 rounded-full bg-background-light dark:bg-[#1c3029] flex items-center justify-center text-text-secondary hover:bg-primary hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-lg">add</span>
                        </button>
                    </div>
                </div>
            </div>
        `
    }
    
    // Карточка с продуктами
    return `
        <div class="bg-white dark:bg-[#152822] rounded-2xl border border-[#dbe6e2] dark:border-[#2a3f38] shadow-sm overflow-hidden mb-4">
            <div class="p-5 flex items-center justify-between border-b border-[#f0f4f3] dark:border-[#1c3029] bg-white dark:bg-[#152822]">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full ${colorClasses} flex items-center justify-center">
                        <span class="material-symbols-outlined">${icon}</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-text-primary dark:text-white text-base">${label}</h3>
                        <p class="text-xs text-text-secondary">${items.length} ${items.length === 1 ? 'продукт' : 'продукта'}</p>
                    </div>
                </div>
                <span class="text-lg font-bold text-text-primary dark:text-white">${totalCalories} <span class="text-sm font-normal text-text-secondary">ккал</span></span>
            </div>
            <div class="p-5 bg-[#fafbfb] dark:bg-[#12241e] flex flex-col gap-3">
                ${items.map(item => `
                    <div class="flex items-center justify-between gap-3 flex-wrap w-full group">
                        <!-- Левая часть: название и вес -->
                        <div class="flex items-center gap-3">
                            <div class="w-1 h-1 rounded-full bg-text-secondary flex-shrink-0"></div>
                            <span class="text-sm text-text-primary dark:text-gray-200 font-medium">
                                ${item.product_title} - ${item.grams}г
                            </span>
                        </div>
                        
                        <!-- Правая часть: КБЖУ + кнопка -->
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2 text-xs text-text-secondary">
                                <span class="font-bold">${Math.round(item.calories)} ккал</span>
                                <span>•</span>
                                <span>Б: ${parseFloat(item.proteins || 0).toFixed(1)}г</span>
                                <span>•</span>
                                <span>Ж: ${parseFloat(item.fats || 0).toFixed(1)}г</span>
                                <span>•</span>
                                <span>У: ${parseFloat(item.carbohydrates || 0).toFixed(1)}г</span>
                            </div>
                            <button 
                                onclick="window.deleteMealItem(${item.id}, '${escapeHtml(item.product_title)}')"
                                class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-600 transition-all flex-shrink-0"
                            >
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
                        </div>
                    </div>
                `).join('')}
                <button class="flex items-center gap-2 mt-1 text-xs font-semibold text-primary hover:text-primary-hover w-fit" onclick="window.focusSearch('${mealType.key}')">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Добавить продукт
                </button>
            </div>
        </div>
    `
}

function renderEmptyMeals() {
    const container = document.getElementById('meals-list')
    if (!container) return
    
    container.innerHTML = `
        <div class="text-center py-12">
            <span class="material-symbols-outlined text-6xl text-text-secondary mb-4">restaurant</span>
            <p class="text-text-secondary">Пока нет записей за этот день</p>
            <p class="text-sm text-text-secondary mt-2">Начните с поиска продукта выше ☝️</p>
        </div>
    `
}

function updateSummary(summary) {
    const calorieGoal = window.APP_CALORIE_GOAL || 2000
    const eaten = parseFloat(summary.total_calories) || 0
    const left = Math.round((calorieGoal - eaten) * 10) / 10  // Округляем до десятых
    const progress = Math.min((eaten / calorieGoal) * 100, 100)
    
    // Обновляем калории
    document.getElementById('calories-left').textContent = left
    document.getElementById('calories-eaten').textContent = eaten
    document.getElementById('calories-progress').style.width = `${progress}%`
    
    // Обновляем БЖУ круги
    updateMacrosCircles(summary, calorieGoal)
}

function updateMacrosCircles(summary, calorieGoal) {
    const container = document.getElementById('macros-circles')
    if (!container) return
    
    // Получаем съеденные граммы
    const carbs = parseFloat(summary.total_carbohydrates) || 0
    const fats = parseFloat(summary.total_fats) || 0
    const proteins = parseFloat(summary.total_proteins) || 0
    
    // Рассчитываем калории от каждого макроса
    const carbsCal = carbs * 4      // 4 ккал/г
    const fatsCal = fats * 9        // 9 ккал/г
    const proteinsCal = proteins * 4 // 4 ккал/г
    
    const totalMacroCal = carbsCal + fatsCal + proteinsCal
    
    // Процент от СЪЕДЕННОГО (не от цели!)
    const carbsPercent = totalMacroCal > 0 ? Math.round((carbsCal / totalMacroCal) * 100) : 0
    const fatsPercent = totalMacroCal > 0 ? Math.round((fatsCal / totalMacroCal) * 100) : 0
    const proteinsPercent = totalMacroCal > 0 ? Math.round((proteinsCal / totalMacroCal) * 100) : 0
    
    const circumference = 2 * Math.PI * 24 // r=24
    
    container.innerHTML = `
        ${createMacroCircle('Углеводы', carbsPercent, 'blue-500', circumference)}
        ${createMacroCircle('Жиры', fatsPercent, 'yellow-500', circumference)}
        ${createMacroCircle('Белки', proteinsPercent, 'purple-500', circumference)}
    `
}

function createMacroCircle(label, percent, color, circumference) {
    const offset = circumference - (circumference * percent / 100)
    
    return `
        <div class="flex flex-col items-center gap-2">
            <div class="relative w-14 h-14">
                <svg class="w-full h-full transform -rotate-90">
                    <circle class="text-background-light dark:text-[#1c3029]" cx="28" cy="28" fill="transparent" r="24" stroke="currentColor" stroke-width="4"></circle>
                    <circle class="text-${color}" cx="28" cy="28" fill="transparent" r="24" stroke="currentColor" stroke-dasharray="${circumference}" stroke-dashoffset="${offset}" stroke-width="4"></circle>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-xs font-bold text-text-primary dark:text-white">${Math.round(percent)}%</span>
                </div>
            </div>
            <span class="text-xs font-medium text-text-secondary">${label}</span>
        </div>
    `
}

// ============================================
// УНИВЕРСАЛЬНАЯ МОДАЛКА УДАЛЕНИЯ
// ============================================

let deleteContext = null // { type: 'meal'|'product'|'recipe', id: number, title: string }

window.showDeleteModal = function(type, id, title) {
    deleteContext = { type, id, title }
    
    const modal = document.getElementById('delete-modal')
    const text = document.getElementById('delete-modal-text')
    
    const typeNames = {
        'meal': 'продукт из приёма',
        'product': 'продукт',
        'recipe': 'блюдо'
    }
    
    text.textContent = `Удалить ${typeNames[type] || 'элемент'} "${title}"?`
    modal.classList.remove('hidden')
}

// Для обратной совместимости с dashboard
window.deleteMealItem = function(mealItemId, productTitle) {
    window.showDeleteModal('meal', mealItemId, productTitle)
}

window.closeDeleteModal = function() {
    document.getElementById('delete-modal').classList.add('hidden')
    deleteContext = null
}

window.confirmDelete = async function() {
    if (!deleteContext) return
    
    const { type, id } = deleteContext
    
    try {
        let url, body
        
        switch (type) {
            case 'meal':
                url = '/api/meals.php?action=delete'
                body = { meal_id: id }
                break
            case 'product':
                url = '/api/products.php?action=delete'
                body = { product_id: id }
                break
            case 'recipe':
                url = '/api/recipes.php?action=delete'
                body = { recipe_id: id }
                break
            default:
                return
        }
        
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        })
        
        const data = await response.json()
        
        if (data.success) {
            console.log(`✅ ${type} удалён!`)
            window.closeDeleteModal()
            
            // Обновляем нужный список
            if (type === 'meal') {
                loadMeals()
            } else if (type === 'product' && window.loadMyProducts) {
                window.loadMyProducts()
            } else if (type === 'recipe' && window.loadMyRecipes) {
                window.loadMyRecipes()
            }
        } else {
            alert('Ошибка: ' + (data.error || 'Не удалось удалить'))
        }
        
    } catch (error) {
        console.error('Ошибка удаления:', error)
        alert('Ошибка удаления')
    }
}

function escapeHtml(text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
}
