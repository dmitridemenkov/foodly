// ============================================
// MAIN APP LOGIC
// ============================================

import { initSearch } from './search.js'
import { initDashboard } from './dashboard.js'
import { initNavigation } from './navigation.js'
import { initCalendar } from './calendar.js'
import { initMyProducts, initRecipeSearch, initEditRecipeSearch } from './my-products.js'
import { initStats } from './stats.js'
import { initFavorites } from './favorites.js'
import { initBarcode } from './barcode.js'
import { initAI } from './ai.js'

// Проверка авторизации при загрузке
async function checkAuth() {
    try {
        const response = await fetch('/api/auth.php?action=check')
        const data = await response.json()
        
        if (!data.authenticated) {
            window.location.href = '/login.php'
        }
    } catch (error) {
        console.error('Auth check failed:', error)
    }
}

// Проверяем авторизацию каждые 5 минут
setInterval(checkAuth, 5 * 60 * 1000)

// Инициализация приложения
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Foodly App started')
    console.log('User ID:', window.APP_USER_ID)
    console.log('User Name:', window.APP_USER_NAME)
    console.log('Calorie Goal:', window.APP_CALORIE_GOAL)
    
    // Инициализация модулей
    initNavigation()
    initCalendar()
    initSearch()
    initDashboard()
    initMyProducts()
    initRecipeSearch()
    initEditRecipeSearch()
    initStats()
    initFavorites()
    initBarcode()
    initAI()
    
    console.log('✅ All modules initialized')
})
