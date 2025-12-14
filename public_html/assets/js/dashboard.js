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
    
    // Загружаем приёмы за текущий день
    loadMeals()
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
        { key: 'завтрак', label: 'Завтрак', icon: 'bakery_dining', color: 'orange' },
        { key: 'обед', label: 'Обед', icon: 'restaurant', color: 'blue' },
        { key: 'ужин', label: 'Ужин', icon: 'dinner_dining', color: 'indigo' },
        { key: 'перекус', label: 'Перекус', icon: 'icecream', color: 'pink' }
    ]
    
    let html = ''
    
    mealTypes.forEach(mealType => {
        const items = grouped[mealType.key] || []
        const totalCalories = items.reduce((sum, item) => sum + (item.calories || 0), 0)
        
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
    const { label, icon, color } = mealType
    
    if (!hasItems) {
        // Пустая карточка
        return `
            <div class="bg-white dark:bg-[#152822] rounded-2xl border border-[#dbe6e2] dark:border-[#2a3f38] shadow-sm transition-all hover:border-primary/50 group cursor-pointer mb-4" onclick="window.focusSearch('${mealType.key}')">
                <div class="p-5 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-${color}-100 dark:bg-${color}-900/30 text-${color}-600 dark:text-${color}-400 flex items-center justify-center">
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
                    <div class="w-10 h-10 rounded-full bg-${color}-100 dark:bg-${color}-900/30 text-${color}-600 dark:text-${color}-400 flex items-center justify-center">
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
                                <span class="font-bold">${item.calories} ккал</span>
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
    const eaten = summary.total_calories || 0
    const left = calorieGoal - eaten
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

// Удаление продукта из приёма
let mealItemToDelete = null

window.deleteMealItem = function(mealItemId, productTitle) {
    console.log('Запрос на удаление:', mealItemId)
    
    mealItemToDelete = mealItemId
    
    // Показываем модалку
    const modal = document.getElementById('delete-modal')
    const text = document.getElementById('delete-modal-text')
    
    text.textContent = productTitle ? `Удалить "${productTitle}"?` : 'Удалить этот продукт?'
    modal.classList.remove('hidden')
}

window.closeDeleteModal = function() {
    document.getElementById('delete-modal').classList.add('hidden')
    mealItemToDelete = null
}

window.confirmDelete = async function() {
    if (!mealItemToDelete) return
    
    try {
        const response = await fetch(`/api/meals.php?action=delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                meal_id: mealItemToDelete
            })
        })
        
        const data = await response.json()
        
        if (data.success) {
            console.log('✅ Продукт удалён!')
            window.closeDeleteModal()
            loadMeals()
        } else {
            console.error('Ошибка удаления:', data.error)
            alert('Ошибка: ' + data.error)
        }
        
    } catch (error) {
        console.error('Ошибка удаления:', error)
        alert('Ошибка удаления продукта')
    }
}

function escapeHtml(text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
}
