// ============================================
// NAVIGATION MODULE
// ============================================

export function initNavigation() {
    const navButtons = document.querySelectorAll('[data-screen]')
    
    navButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const screenId = e.currentTarget.dataset.screen
            switchScreen(screenId)
            
            // Закрываем мобильное меню при переключении
            window.closeMobileMenu()
        })
    })
    
    // Инициализация мобильного меню
    initMobileMenu()
}

// ============================================
// МОБИЛЬНОЕ МЕНЮ
// ============================================

function initMobileMenu() {
    window.toggleMobileMenu = toggleMobileMenu
    window.closeMobileMenu = closeMobileMenu
    window.openMobileMenu = openMobileMenu
}

function toggleMobileMenu() {
    const sidebar = document.getElementById('sidebar')
    const overlay = document.getElementById('sidebar-overlay')
    const icon = document.getElementById('burger-icon')
    
    if (sidebar.classList.contains('open')) {
        closeMobileMenu()
    } else {
        openMobileMenu()
    }
}

function openMobileMenu() {
    const sidebar = document.getElementById('sidebar')
    const overlay = document.getElementById('sidebar-overlay')
    const icon = document.getElementById('burger-icon')
    
    sidebar.classList.add('open')
    overlay.classList.add('open')
    icon.textContent = 'close'
    document.body.style.overflow = 'hidden'
}

function closeMobileMenu() {
    const sidebar = document.getElementById('sidebar')
    const overlay = document.getElementById('sidebar-overlay')
    const icon = document.getElementById('burger-icon')
    
    sidebar.classList.remove('open')
    overlay.classList.remove('open')
    icon.textContent = 'menu'
    document.body.style.overflow = ''
}

function switchScreen(screenId) {
    console.log('Switching to screen:', screenId)
    
    // Скрываем все экраны
    document.querySelectorAll('.screen').forEach(screen => {
        screen.classList.add('hidden')
        screen.classList.remove('active')
    })
    
    // Убираем active с кнопок
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.classList.remove('bg-primary/10')
        btn.classList.add('hover:bg-background-light')
        
        const icon = btn.querySelector('.material-symbols-outlined')
        const text = btn.querySelector('span:last-child')
        
        if (icon) {
            icon.classList.remove('text-primary')
            icon.classList.add('text-text-secondary')
        }
        if (text) {
            text.classList.remove('font-semibold', 'text-text-primary')
            text.classList.add('font-medium', 'text-text-secondary')
        }
    })
    
    // Показываем нужный экран
    const screen = document.getElementById(screenId)
    if (screen) {
        screen.classList.remove('hidden')
        screen.classList.add('active')
    }
    
    // Загружаем данные для экрана "Мои продукты"
    if (screenId === 'my-products' && window.loadMyProducts) {
        window.loadMyProducts()
    }
    
    // Загружаем статистику при переходе
    if (screenId === 'stats' && window.loadStats) {
        window.loadStats()
    }
    
    // Активируем кнопку
    const activeBtn = document.querySelector(`[data-screen="${screenId}"]`)
    if (activeBtn) {
        activeBtn.classList.add('bg-primary/10')
        activeBtn.classList.remove('hover:bg-background-light')
        
        const icon = activeBtn.querySelector('.material-symbols-outlined')
        const text = activeBtn.querySelector('span:last-child')
        
        if (icon) {
            icon.classList.add('text-primary')
            icon.classList.remove('text-text-secondary')
        }
        if (text) {
            text.classList.add('font-semibold', 'text-text-primary')
            text.classList.remove('font-medium', 'text-text-secondary')
        }
    }
}
