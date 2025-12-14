// ============================================
// CALENDAR MODULE
// ============================================

let currentDate = new Date()

export function initCalendar() {
    renderMiniCalendar()
    updateCurrentDateDisplay()
}

function renderMiniCalendar() {
    const container = document.getElementById('mini-calendar')
    if (!container) return
    
    const year = currentDate.getFullYear()
    const month = currentDate.getMonth()
    
    // Название месяца
    const monthNames = [
        'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
        'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
    ]
    
    // Первый день месяца
    const firstDay = new Date(year, month, 1)
    const lastDay = new Date(year, month + 1, 0)
    const daysInMonth = lastDay.getDate()
    const startDay = firstDay.getDay() // 0 = Sunday
    
    let html = `
        <div class="flex items-center justify-between mb-4">
            <button id="prev-month" class="p-1 hover:bg-white dark:hover:bg-[#2a3f38] rounded-full transition-colors">
                <span class="material-symbols-outlined text-text-secondary text-sm">chevron_left</span>
            </button>
            <span class="text-sm font-bold text-text-primary dark:text-white">${monthNames[month]} ${year}</span>
            <button id="next-month" class="p-1 hover:bg-white dark:hover:bg-[#2a3f38] rounded-full transition-colors">
                <span class="material-symbols-outlined text-text-secondary text-sm">chevron_right</span>
            </button>
        </div>
        
        <div class="grid grid-cols-7 gap-1 text-center mb-2">
            <span class="text-[10px] font-bold text-text-secondary">Вс</span>
            <span class="text-[10px] font-bold text-text-secondary">Пн</span>
            <span class="text-[10px] font-bold text-text-secondary">Вт</span>
            <span class="text-[10px] font-bold text-text-secondary">Ср</span>
            <span class="text-[10px] font-bold text-text-secondary">Чт</span>
            <span class="text-[10px] font-bold text-text-secondary">Пт</span>
            <span class="text-[10px] font-bold text-text-secondary">Сб</span>
        </div>
        
        <div class="grid grid-cols-7 gap-1">
    `
    
    // Пустые клетки до начала месяца
    for (let i = 0; i < startDay; i++) {
        html += '<div class="h-7"></div>'
    }
    
    // Дни месяца
    const today = new Date()
    for (let day = 1; day <= daysInMonth; day++) {
        const isToday = (
            day === today.getDate() && 
            month === today.getMonth() && 
            year === today.getFullYear()
        )
        
        const isSelected = (
            day === currentDate.getDate() &&
            month === currentDate.getMonth() &&
            year === currentDate.getFullYear()
        )
        
        if (isToday) {
            // Сегодня - зелёный фон
            html += `
                <button class="h-7 w-7 rounded-full bg-primary text-white font-bold shadow-sm flex items-center justify-center text-xs mx-auto cursor-pointer hover:bg-primary-hover transition-colors" data-day="${day}">
                    ${day}
                </button>
            `
        } else if (isSelected) {
            // Выбранный день - обводка
            html += `
                <button class="h-7 w-7 rounded-full border-2 border-primary text-primary font-bold flex items-center justify-center text-xs mx-auto cursor-pointer hover:bg-primary/10 transition-colors" data-day="${day}">
                    ${day}
                </button>
            `
        } else {
            // Обычный день
            html += `
                <button class="h-7 flex items-center justify-center text-xs text-text-primary dark:text-gray-300 hover:bg-white dark:hover:bg-[#2a3f38] rounded-full cursor-pointer transition-colors" data-day="${day}">
                    ${day}
                </button>
            `
        }
    }
    
    html += '</div>'
    
    container.innerHTML = html
    
    // События навигации
    document.getElementById('prev-month')?.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1)
        renderMiniCalendar()
        updateCurrentDateDisplay()
    })
    
    document.getElementById('next-month')?.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1)
        renderMiniCalendar()
        updateCurrentDateDisplay()
    })
    
    // Клик по дню
    document.querySelectorAll('[data-day]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const day = parseInt(e.target.dataset.day)
            currentDate.setDate(day)
            renderMiniCalendar()
            updateCurrentDateDisplay()
            
            // Загрузить данные за выбранный день
            if (window.dashboardModule) {
                window.dashboardModule.loadMeals(currentDate)
            }
        })
    })
}

function updateCurrentDateDisplay() {
    const dateEl = document.getElementById('current-date')
    if (!dateEl) return
    
    const days = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота']
    const months = ['янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек']
    
    const dayName = days[currentDate.getDay()]
    const monthName = months[currentDate.getMonth()]
    const day = currentDate.getDate()
    
    dateEl.textContent = `${dayName}, ${day} ${monthName}`
}

export function getCurrentDate() {
    return currentDate
}

export function formatDate(date) {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}
