// Главный JS файл приложения
// ===== КОНФИГУРАЦИЯ =====
const API_URL = '/api/index.php';
const TOKEN = window.APP_TOKEN || '';

// ===== СОСТОЯНИЕ ПРИЛОЖЕНИЯ =====
let currentDate = document.getElementById('current-date').value;
let selectedProduct = null;
let searchTimeout = null;

// ===== ЭЛЕМЕНТЫ DOM =====
const elements = {
    dateInput: document.getElementById('current-date'),
    prevDayBtn: document.getElementById('prev-day'),
    nextDayBtn: document.getElementById('next-day'),
    todayBtn: document.getElementById('today'),
    
    searchInput: document.getElementById('product-search'),
    searchResults: document.getElementById('search-results'),
    
    modal: document.getElementById('add-modal'),
    modalClose: document.querySelector('.modal-close'),
    modalProductName: document.getElementById('modal-product-name'),
    modalProductCategory: document.getElementById('modal-product-category'),
    productGramsInput: document.getElementById('product-grams'),
    mealTypeSelect: document.getElementById('meal-type'),
    addMealBtn: document.getElementById('add-meal-btn'),
    
    previewCalories: document.getElementById('preview-calories'),
    previewProteins: document.getElementById('preview-proteins'),
    previewFats: document.getElementById('preview-fats'),
    previewCarbs: document.getElementById('preview-carbs'),
    
    mealsList: document.getElementById('meals-list'),
    
    totalCalories: document.getElementById('total-calories'),
    totalProteins: document.getElementById('total-proteins'),
    totalFats: document.getElementById('total-fats'),
    totalCarbs: document.getElementById('total-carbs'),

    statsBtn: document.getElementById('stats-btn'),
    statsModal: document.getElementById('stats-modal'),
    statsModalClose: document.getElementById('stats-modal-close'),
    statsContent: document.getElementById('stats-content')
};

// ===== ИНИЦИАЛИЗАЦИЯ =====
document.addEventListener('DOMContentLoaded', () => {
    initEventListeners();
    loadDay(currentDate);
});

// ===== СОБЫТИЯ =====
function initEventListeners() {
    // Навигация по датам
    elements.dateInput.addEventListener('change', (e) => {
        currentDate = e.target.value;
        loadDay(currentDate);
    });
    
    elements.prevDayBtn.addEventListener('click', () => changeDate(-1));
    elements.nextDayBtn.addEventListener('click', () => changeDate(1));
    elements.todayBtn.addEventListener('click', () => {
        const today = new Date().toISOString().split('T')[0];
        elements.dateInput.value = today;
        currentDate = today;
        loadDay(currentDate);
    });
    
    // Поиск продуктов
    elements.searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            hideSearchResults();
            return;
        }
        
        searchTimeout = setTimeout(() => searchProducts(query), 300);
    });
    
    // Закрытие результатов при клике вне
    document.addEventListener('click', (e) => {
        if (!elements.searchInput.contains(e.target) && 
            !elements.searchResults.contains(e.target)) {
            hideSearchResults();
        }
    });
    
    // Модальное окно
    elements.modalClose.addEventListener('click', closeModal);
    elements.modal.addEventListener('click', (e) => {
        if (e.target === elements.modal) closeModal();
    });
    
    // Расчёт КБЖУ при изменении грамм
    elements.productGramsInput.addEventListener('input', updateNutritionPreview);
    
    // Добавление приёма пищи
    elements.addMealBtn.addEventListener('click', addMeal);
    
    // Enter в поле грамм = добавить
    elements.productGramsInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') addMeal();
    });

    // Статистика
    elements.statsBtn.addEventListener('click', () => openStatsModal(7));
    elements.statsModalClose.addEventListener('click', closeStatsModal);
    elements.statsModal.addEventListener('click', (e) => {
        if (e.target === elements.statsModal) closeStatsModal();
    });

    // Переключение периода статистики
    document.querySelectorAll('.stats-period button').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.stats-period button').forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            const days = parseInt(e.target.dataset.days);
            loadStats(days);
        });
    });
}

// ===== РАБОТА С ДАТАМИ =====
function changeDate(days) {
    const date = new Date(currentDate);
    date.setDate(date.getDate() + days);
    const newDate = date.toISOString().split('T')[0];
    elements.dateInput.value = newDate;
    currentDate = newDate;
    loadDay(currentDate);
}

// ===== API ЗАПРОСЫ =====
async function apiRequest(params) {
    try {
        const url = new URL(API_URL, window.location.origin);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        alert('Ошибка: ' + error.message);
        throw error;
    }
}

async function apiPost(action, body) {
    try {
        const url = new URL(API_URL, window.location.origin);
        url.searchParams.append('action', action);
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(body)
        });
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        alert('Ошибка: ' + error.message);
        throw error;
    }
}

// ===== ЗАГРУЗКА ДНЯ =====
async function loadDay(date) {
    try {
        const data = await apiRequest({ action: 'get_day', date });
        
        renderMeals(data.meals);
        renderSummary(data.summary);
    } catch (error) {
        console.error('Failed to load day:', error);
    }
}

// ===== ПОИСК ПРОДУКТОВ =====
async function searchProducts(query) {
    try {
        const data = await apiRequest({ 
            action: 'search', 
            q: query,
            limit: 100
        });
        
        renderSearchResults(data.products);
    } catch (error) {
        console.error('Search failed:', error);
        hideSearchResults();
    }
}

function renderSearchResults(products) {
    if (!products || products.length === 0) {
        elements.searchResults.innerHTML = '<div class="search-no-results">Ничего не найдено</div>';
        elements.searchResults.style.display = 'block';
        return;
    }
    
    const html = products.map(product => `
        <div class="search-result-item" data-product='${JSON.stringify(product)}'>
            <div class="result-title">${escapeHtml(product.title)}</div>
            <div class="result-category">${escapeHtml(product.category)}</div>
            <div class="result-nutrition">
                К: ${product.calories} | Б: ${product.proteins} | Ж: ${product.fats} | У: ${product.carbohydrates}
            </div>
        </div>
    `).join('');
    
    elements.searchResults.innerHTML = html;
    elements.searchResults.style.display = 'block';
    
    // Клик по результату
    elements.searchResults.querySelectorAll('.search-result-item').forEach(item => {
        item.addEventListener('click', () => {
            const product = JSON.parse(item.dataset.product);
            openModal(product);
        });
    });
}

function hideSearchResults() {
    elements.searchResults.style.display = 'none';
    elements.searchResults.innerHTML = '';
}

// ===== МОДАЛЬНОЕ ОКНО =====
function openModal(product) {
    selectedProduct = product;
    
    elements.modalProductName.textContent = product.title;
    elements.modalProductCategory.textContent = product.category;
    elements.productGramsInput.value = 100;
    elements.mealTypeSelect.value = '';
    
    updateNutritionPreview();
    
    elements.modal.style.display = 'flex';
    elements.productGramsInput.focus();
    elements.productGramsInput.select();
    
    hideSearchResults();
    elements.searchInput.value = '';
}

function closeModal() {
    elements.modal.style.display = 'none';
    selectedProduct = null;
}

function updateNutritionPreview() {
    if (!selectedProduct) return;
    
    const grams = parseFloat(elements.productGramsInput.value) || 0;
    const multiplier = grams / 100;
    
    const calories = parseFloat(selectedProduct.calories) * multiplier;
    const proteins = parseFloat(selectedProduct.proteins) * multiplier;
    const fats = parseFloat(selectedProduct.fats) * multiplier;
    const carbs = parseFloat(selectedProduct.carbohydrates) * multiplier;
    
    elements.previewCalories.textContent = calories.toFixed(1);
    elements.previewProteins.textContent = proteins.toFixed(1);
    elements.previewFats.textContent = fats.toFixed(1);
    elements.previewCarbs.textContent = carbs.toFixed(1);
}

// ===== ДОБАВЛЕНИЕ ПРИЁМА ПИЩИ =====
async function addMeal() {
    if (!selectedProduct) return;
    
    const grams = parseFloat(elements.productGramsInput.value);
    
    if (!grams || grams <= 0) {
        alert('Укажите количество грамм');
        return;
    }
    
    const mealType = elements.mealTypeSelect.value || null;
    
    try {
        elements.addMealBtn.disabled = true;
        elements.addMealBtn.textContent = 'Добавление...';
        
        const data = await apiPost('add_meal', {
            date: currentDate,
            product_id: selectedProduct.id,
            grams: grams,
            meal_type: mealType
        });
        
        closeModal();
        loadDay(currentDate);
        
    } catch (error) {
        console.error('Failed to add meal:', error);
    } finally {
        elements.addMealBtn.disabled = false;
        elements.addMealBtn.textContent = 'Добавить';
    }
}

// ===== ОТОБРАЖЕНИЕ ПРИЁМОВ ПИЩИ =====
function renderMeals(meals) {
    if (!meals || meals.length === 0) {
        elements.mealsList.innerHTML = '<p class="empty-state">Пока ничего не добавлено</p>';
        return;
    }
    
    const html = meals.map(meal => `
        <div class="meal-item">
            <div class="meal-info">
                <div class="meal-title">${escapeHtml(meal.product_title)}</div>
                <div class="meal-grams">${meal.grams}г${meal.meal_type ? ` • ${meal.meal_type}` : ''}</div>
                <div class="meal-nutrition">
                    К: ${meal.calories} ккал | Б: ${meal.proteins}г | Ж: ${meal.fats}г | У: ${meal.carbohydrates}г
                </div>
            </div>
            <div class="meal-actions">
                <button class="btn btn-danger" onclick="deleteMeal(${meal.id})">✕</button>
            </div>
        </div>
    `).join('');
    
    elements.mealsList.innerHTML = html;
}

// ===== УДАЛЕНИЕ ПРИЁМА ПИЩИ =====
async function deleteMeal(mealId) {
    if (!confirm('Удалить этот приём пищи?')) return;
    
    try {
        await apiPost('delete_meal', { meal_id: mealId });
        loadDay(currentDate);
    } catch (error) {
        console.error('Failed to delete meal:', error);
    }
}

// ===== ОТОБРАЖЕНИЕ САММАРИ =====
function renderSummary(summary) {
    elements.totalCalories.textContent = summary.total_calories.toFixed(0);
    elements.totalProteins.textContent = summary.total_proteins.toFixed(1);
    elements.totalFats.textContent = summary.total_fats.toFixed(1);
    elements.totalCarbs.textContent = summary.total_carbohydrates.toFixed(1);
}

// ===== УТИЛИТЫ =====
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Экспортируем для использования в HTML (onclick)
window.deleteMeal = deleteMeal;

// ===== СТАТИСТИКА =====
async function openStatsModal(days = 7) {
    elements.statsModal.style.display = 'flex';
    
    // Активируем нужную кнопку
    document.querySelectorAll('.stats-period button').forEach(btn => {
        btn.classList.toggle('active', parseInt(btn.dataset.days) === days);
    });
    
    loadStats(days);
}

function closeStatsModal() {
    elements.statsModal.style.display = 'none';
}

async function loadStats(days) {
    elements.statsContent.innerHTML = '<div class="stats-loading">Загрузка...</div>';
    
    try {
        const data = await apiRequest({ action: 'get_stats', days });
        renderStats(data);
    } catch (error) {
        console.error('Failed to load stats:', error);
        elements.statsContent.innerHTML = '<div class="stats-loading">Ошибка загрузки статистики</div>';
    }
}

function renderStats(data) {
    const { period, averages, range, daily_stats } = data;
    
    const html = `
        <div class="stats-info">
            За период ${formatDate(period.start_date)} — ${formatDate(period.end_date)}<br>
            Дней с записями: ${period.days_with_data} из ${period.total_days}
        </div>
        
        <div class="stats-grid">
            <div class="stats-card highlight">
                <div class="stats-label">Средняя калорийность</div>
                <div class="stats-value">${averages.calories.toFixed(0)}</div>
                <div class="stats-unit">ккал/день</div>
            </div>
            
            <div class="stats-card">
                <div class="stats-label">Белки</div>
                <div class="stats-value">${averages.proteins.toFixed(1)}</div>
                <div class="stats-unit">г/день</div>
            </div>
            
            <div class="stats-card">
                <div class="stats-label">Жиры</div>
                <div class="stats-value">${averages.fats.toFixed(1)}</div>
                <div class="stats-unit">г/день</div>
            </div>
            
            <div class="stats-card">
                <div class="stats-label">Углеводы</div>
                <div class="stats-value">${averages.carbohydrates.toFixed(1)}</div>
                <div class="stats-unit">г/день</div>
            </div>
        </div>
        
        ${range.min_calories > 0 ? `
            <div class="stats-info">
                Диапазон калорий: ${range.min_calories.toFixed(0)} — ${range.max_calories.toFixed(0)} ккал
            </div>
        ` : ''}
        
        ${daily_stats.length > 0 ? `
            <h3 style="margin: 20px 0 12px; color: #333;">По дням:</h3>
            <div class="daily-list">
                ${daily_stats.map(day => `
                    <div class="daily-item">
                        <div>
                            <div class="daily-date">${formatDate(day.date)}</div>
                            <div class="daily-values">${day.meals_count} приём${getDeclension(day.meals_count)}</div>
                        </div>
                        <div class="daily-values">
                            ${day.calories.toFixed(0)} ккал<br>
                            Б: ${day.proteins.toFixed(1)} | Ж: ${day.fats.toFixed(1)} | У: ${day.carbohydrates.toFixed(1)}
                        </div>
                    </div>
                `).join('')}
            </div>
        ` : ''}
    `;
    
    elements.statsContent.innerHTML = html;
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    const day = date.getDate();
    const month = date.toLocaleDateString('ru-RU', { month: 'short' });
    return `${day} ${month}`;
}

function getDeclension(count) {
    const lastDigit = count % 10;
    const lastTwoDigits = count % 100;
    
    if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
        return 'ов пищи';
    }
    
    if (lastDigit === 1) return ' пищи';
    if (lastDigit >= 2 && lastDigit <= 4) return 'а пищи';
    return 'ов пищи';
}

// Экспортируем
window.openStatsModal = openStatsModal;