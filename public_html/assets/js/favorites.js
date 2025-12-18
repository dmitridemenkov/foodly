// ============================================
// ИЗБРАННОЕ
// ============================================

export function initFavorites() {
    window.loadFavorites = loadFavorites
    window.removeFavorite = removeFavoriteFromList
}

async function loadFavorites() {
    const container = document.getElementById('favorites-content')
    
    if (!container) return
    
    container.innerHTML = `
        <div class="flex items-center justify-center py-20">
            <span class="material-symbols-outlined text-4xl animate-spin text-primary">progress_activity</span>
        </div>
    `
    
    try {
        const response = await fetch('/api/favorites.php?action=list')
        const data = await response.json()
        
        if (data.success) {
            renderFavorites(data.favorites)
        } else {
            container.innerHTML = `<p class="text-red-500 text-center py-10">${data.error}</p>`
        }
    } catch (error) {
        console.error('Ошибка загрузки избранного:', error)
        container.innerHTML = `<p class="text-red-500 text-center py-10">Ошибка загрузки</p>`
    }
}

function renderFavorites(favorites) {
    const container = document.getElementById('favorites-content')
    
    if (favorites.length === 0) {
        container.innerHTML = `
            <div class="text-center py-16">
                <span class="material-symbols-outlined text-6xl text-text-secondary mb-4">star</span>
                <h3 class="text-xl font-bold text-text-primary dark:text-white mb-2">Пока пусто</h3>
                <p class="text-text-secondary">Добавляй продукты и блюда в избранное<br>через поиск, нажимая на звёздочку ⭐</p>
            </div>
        `
        return
    }
    
    // Разделяем на продукты и рецепты
    const products = favorites.filter(f => f.type === 'product')
    const recipes = favorites.filter(f => f.type === 'recipe')
    
    let html = ''
    
    // Рецепты
    if (recipes.length > 0) {
        html += `
            <div class="mb-8">
                <h3 class="text-lg font-bold text-text-primary dark:text-white mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-purple-500">menu_book</span>
                    Блюда (${recipes.length})
                </h3>
                <div class="grid gap-3">
                    ${recipes.map(item => renderFavoriteCard(item)).join('')}
                </div>
            </div>
        `
    }
    
    // Продукты
    if (products.length > 0) {
        html += `
            <div>
                <h3 class="text-lg font-bold text-text-primary dark:text-white mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">grocery</span>
                    Продукты (${products.length})
                </h3>
                <div class="grid gap-3">
                    ${products.map(item => renderFavoriteCard(item)).join('')}
                </div>
            </div>
        `
    }
    
    container.innerHTML = html
}

function renderFavoriteCard(item) {
    const isRecipe = item.type === 'recipe'
    const colorClass = isRecipe ? 'purple' : 'primary'
    
    return `
        <div class="bg-white dark:bg-[#152822] rounded-xl border border-[#dbe6e2] dark:border-[#2a3f38] p-4 flex items-center justify-between gap-4 group">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="w-10 h-10 rounded-full ${isRecipe ? 'bg-purple-100 dark:bg-purple-900/30' : 'bg-primary/10'} flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined ${isRecipe ? 'text-purple-500' : 'text-primary'}">
                        ${isRecipe ? 'menu_book' : 'grocery'}
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-semibold text-text-primary dark:text-white truncate">${escapeHtml(item.title)}</h4>
                    <div class="flex items-center gap-2 text-xs text-text-secondary mt-1">
                        <span class="${isRecipe ? 'text-purple-500' : 'text-primary'} font-bold">${Math.round(item.calories || 0)} ккал</span>
                        <span>•</span>
                        <span>Б: ${parseFloat(item.proteins || 0).toFixed(1)}г</span>
                        <span>•</span>
                        <span>Ж: ${parseFloat(item.fats || 0).toFixed(1)}г</span>
                        <span>•</span>
                        <span>У: ${parseFloat(item.carbohydrates || 0).toFixed(1)}г</span>
                    </div>
                </div>
            </div>
            <button 
                onclick="window.removeFavorite(${item.favorite_id}, '${item.type}', ${item.id})"
                class="p-2 text-yellow-500 hover:text-text-secondary transition-colors"
                title="Удалить из избранного"
            >
                <span class="material-symbols-outlined">star</span>
            </button>
        </div>
    `
}

async function removeFavoriteFromList(favoriteId, type, itemId) {
    try {
        const response = await fetch('/api/favorites.php?action=remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ favorite_id: favoriteId })
        })
        
        const data = await response.json()
        
        if (data.success) {
            // Обновляем глобальные Set в search.js
            if (type === 'product' && window.favoriteProductIds) {
                window.favoriteProductIds.delete(itemId)
            } else if (type === 'recipe' && window.favoriteRecipeIds) {
                window.favoriteRecipeIds.delete(itemId)
            }
            
            // Перезагружаем список
            loadFavorites()
        }
    } catch (error) {
        console.error('Ошибка удаления из избранного:', error)
    }
}

function escapeHtml(text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
}
