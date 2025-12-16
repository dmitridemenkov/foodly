// ============================================
// СТАТИСТИКА
// ============================================

let currentPeriod = 7
let statsChart = null

export function initStats() {
    window.loadStats = loadStats
    window.changePeriod = changePeriod
}

async function loadStats(days = null) {
    // Используем сохранённый период если не передан
    if (days === null) {
        days = currentPeriod
    } else {
        currentPeriod = days
    }
    
    const container = document.getElementById('stats-content')
    
    if (!container) return
    
    container.innerHTML = `
        <div class="flex items-center justify-center py-20">
            <span class="material-symbols-outlined text-4xl animate-spin text-primary">progress_activity</span>
        </div>
    `
    
    try {
        const response = await fetch(`/api/stats.php?action=summary&days=${days}`)
        const data = await response.json()
        
        if (data.success) {
            renderStats(data)
        } else {
            container.innerHTML = `<p class="text-red-500 text-center py-10">${data.error}</p>`
        }
    } catch (error) {
        console.error('Ошибка загрузки статистики:', error)
        container.innerHTML = `<p class="text-red-500 text-center py-10">Ошибка загрузки</p>`
    }
}

function changePeriod(days) {
    currentPeriod = days
    
    // Обновляем активную кнопку
    document.querySelectorAll('.period-btn').forEach(btn => {
        if (parseInt(btn.dataset.days) === days) {
            btn.classList.add('bg-primary', 'hover:bg-primary-hover', 'text-white')
            btn.classList.remove('bg-background-light', 'dark:bg-[#1c3029]', 'text-text-secondary', 'hover:bg-gray-200', 'dark:hover:bg-[#2a3f38]')
        } else {
            btn.classList.remove('bg-primary', 'hover:bg-primary-hover', 'text-white')
            btn.classList.add('bg-background-light', 'dark:bg-[#1c3029]', 'text-text-secondary', 'hover:bg-gray-200', 'dark:hover:bg-[#2a3f38]')
        }
    })
    
    loadStats(days)
}

function renderStats(data) {
    const container = document.getElementById('stats-content')
    
    container.innerHTML = `
        <!-- Streak и средние калории -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            ${renderStatCard('local_fire_department', 'orange', 'Streak', data.streak + ' дн.', 'Дней подряд')}
            ${renderStatCard('bolt', 'primary', 'Среднее', data.averages.calories + ' ккал', 'в день')}
            ${renderStatCard('check_circle', 'green', 'В норме', data.goal_comparison.on_target + ' дн.', '±10% от цели')}
            ${renderStatCard('calendar_month', 'blue', 'Записей', data.goal_comparison.days_with_data + ' дн.', 'из ' + data.period)}
        </div>
        
        <!-- График калорий -->
        <div class="bg-white dark:bg-[#152822] rounded-2xl border border-[#dbe6e2] dark:border-[#2a3f38] p-6 mb-6">
            <h3 class="font-bold text-text-primary dark:text-white mb-4">Калории по дням</h3>
            <div class="h-64">
                <canvas id="calories-chart"></canvas>
            </div>
        </div>
        
        <!-- Средние КБЖУ и сравнение с целью -->
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <!-- Средние КБЖУ -->
            <div class="bg-white dark:bg-[#152822] rounded-2xl border border-[#dbe6e2] dark:border-[#2a3f38] p-6">
                <h3 class="font-bold text-text-primary dark:text-white mb-4">Среднее КБЖУ за период</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-4 bg-background-light dark:bg-[#1c3029] rounded-xl">
                        <div class="text-2xl font-bold text-primary">${data.averages.calories}</div>
                        <div class="text-xs text-text-secondary">ккал</div>
                    </div>
                    <div class="text-center p-4 bg-background-light dark:bg-[#1c3029] rounded-xl">
                        <div class="text-2xl font-bold text-purple-500">${data.averages.proteins}</div>
                        <div class="text-xs text-text-secondary">белки (г)</div>
                    </div>
                    <div class="text-center p-4 bg-background-light dark:bg-[#1c3029] rounded-xl">
                        <div class="text-2xl font-bold text-yellow-500">${data.averages.fats}</div>
                        <div class="text-xs text-text-secondary">жиры (г)</div>
                    </div>
                    <div class="text-center p-4 bg-background-light dark:bg-[#1c3029] rounded-xl">
                        <div class="text-2xl font-bold text-blue-500">${data.averages.carbohydrates}</div>
                        <div class="text-xs text-text-secondary">углеводы (г)</div>
                    </div>
                </div>
            </div>
            
            <!-- Сравнение с целью -->
            <div class="bg-white dark:bg-[#152822] rounded-2xl border border-[#dbe6e2] dark:border-[#2a3f38] p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-text-primary dark:text-white">Сравнение с целью (${data.calorie_goal} ккал)</h3>
                    <span class="text-xs text-text-secondary">допуск ±10%</span>
                </div>
                <div class="space-y-3">
                    ${renderGoalBar('В норме', data.goal_comparison.on_target, data.goal_comparison.days_with_data, 'bg-green-500')}
                    ${renderGoalBar('Превышение', data.goal_comparison.over, data.goal_comparison.days_with_data, 'bg-red-500')}
                    ${renderGoalBar('Недобор', data.goal_comparison.under, data.goal_comparison.days_with_data, 'bg-yellow-500')}
                </div>
            </div>
        </div>
        
        <!-- Топ продуктов -->
        <div class="bg-white dark:bg-[#152822] rounded-2xl border border-[#dbe6e2] dark:border-[#2a3f38] p-6">
            <h3 class="font-bold text-text-primary dark:text-white mb-4">Самые частые продукты</h3>
            ${data.top_products.length > 0 ? `
                <div class="space-y-2">
                    ${data.top_products.map((p, i) => `
                        <div class="flex items-center justify-between py-2 ${i < data.top_products.length - 1 ? 'border-b border-[#f0f4f3] dark:border-[#1c3029]' : ''}">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center">${i + 1}</span>
                                <span class="text-sm text-text-primary dark:text-white">${escapeHtml(p.title)}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-medium text-text-primary dark:text-white">${p.count}×</span>
                                <span class="text-xs text-text-secondary ml-2">${p.total_calories} ккал</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            ` : '<p class="text-text-secondary text-center py-4">Пока нет данных</p>'}
        </div>
    `
    
    // Рисуем график
    renderChart(data.chart_data, data.calorie_goal)
}

function renderStatCard(icon, color, title, value, subtitle) {
    const colorClasses = {
        orange: 'bg-orange-100 dark:bg-orange-900/30 text-orange-500',
        primary: 'bg-primary/10 text-primary',
        green: 'bg-green-100 dark:bg-green-900/30 text-green-500',
        blue: 'bg-blue-100 dark:bg-blue-900/30 text-blue-500'
    }
    
    return `
        <div class="bg-white dark:bg-[#152822] rounded-2xl border border-[#dbe6e2] dark:border-[#2a3f38] p-4 text-center">
            <div class="w-10 h-10 rounded-full ${colorClasses[color]} flex items-center justify-center mx-auto mb-2">
                <span class="material-symbols-outlined">${icon}</span>
            </div>
            <div class="text-xs text-text-secondary uppercase tracking-wider">${title}</div>
            <div class="text-xl font-bold text-text-primary dark:text-white">${value}</div>
            <div class="text-xs text-text-secondary">${subtitle}</div>
        </div>
    `
}

function renderGoalBar(label, value, total, colorClass) {
    const percent = total > 0 ? Math.round((value / total) * 100) : 0
    return `
        <div>
            <div class="flex justify-between text-sm mb-1">
                <span class="text-text-secondary">${label}</span>
                <span class="font-medium text-text-primary dark:text-white">${value} дн. (${percent}%)</span>
            </div>
            <div class="h-2 bg-background-light dark:bg-[#1c3029] rounded-full overflow-hidden">
                <div class="${colorClass} h-full rounded-full transition-all" style="width: ${percent}%"></div>
            </div>
        </div>
    `
}

function renderChart(chartData, goal) {
    const canvas = document.getElementById('calories-chart')
    if (!canvas) return
    
    const ctx = canvas.getContext('2d')
    
    // Уничтожаем старый график если есть
    if (statsChart) {
        statsChart.destroy()
    }
    
    const labels = chartData.map(d => {
        const date = new Date(d.date)
        return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
    })
    
    const calories = chartData.map(d => d.calories)
    
    // Градиент для заливки под линией
    const gradient = ctx.createLinearGradient(0, 0, 0, 250)
    gradient.addColorStop(0, 'rgba(34, 197, 94, 0.3)')
    gradient.addColorStop(1, 'rgba(34, 197, 94, 0.02)')
    
    statsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Калории',
                data: calories,
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: 'rgb(34, 197, 94)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: 'rgb(34, 197, 94)',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: (ctx) => `${ctx.raw} ккал`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { font: { size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                }
            }
        }
    })
}

function escapeHtml(text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
}
