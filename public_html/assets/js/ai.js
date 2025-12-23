// ============================================
// AI ASSISTANT (Фиа)
// ============================================

let isThinking = false
let historyLoaded = false

export function initAI() {
    window.toggleAIChat = toggleAIChat
    window.sendAIMessage = sendAIMessage
    window.closeAIChat = closeAIChat
    window.clearAIHistory = clearAIHistory
    
    // Enter для отправки
    const input = document.getElementById('ai-input')
    if (input) {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault()
                sendAIMessage()
            }
        })
    }
}

async function toggleAIChat() {
    const panel = document.getElementById('ai-chat-panel')
    panel.classList.toggle('translate-x-full')
    
    // При открытии загружаем историю
    if (!panel.classList.contains('translate-x-full')) {
        if (!historyLoaded) {
            await loadChatHistory()
            historyLoaded = true
        }
        setTimeout(() => {
            document.getElementById('ai-input')?.focus()
        }, 300)
    }
}

async function loadChatHistory() {
    try {
        const response = await fetch('/api/ai.php?action=history')
        const data = await response.json()
        
        if (data.success && data.messages.length > 0) {
            const container = document.getElementById('ai-messages')
            // Убираем приветствие
            const welcome = container.querySelector('.ai-welcome')
            if (welcome) welcome.remove()
            
            // Добавляем сообщения
            data.messages.forEach(msg => {
                addMessageToChat(msg.role, msg.content, false)
            })
            
            container.scrollTop = container.scrollHeight
        }
    } catch (error) {
        console.error('Error loading chat history:', error)
    }
}

function closeAIChat() {
    document.getElementById('ai-chat-panel').classList.add('translate-x-full')
}

async function sendAIMessage() {
    const input = document.getElementById('ai-input')
    const message = input.value.trim()
    
    if (!message || isThinking) return
    
    input.value = ''
    
    // Добавляем сообщение пользователя
    addMessageToChat('user', message)
    
    // Показываем состояние "думает"
    setThinking(true)
    
    try {
        const response = await fetch('/api/ai.php?action=chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message })
        })
        
        const data = await response.json()
        
        setThinking(false)
        
        if (data.success) {
            addMessageToChat('assistant', data.message)
        } else {
            addMessageToChat('error', data.error || 'Ошибка получения ответа')
        }
    } catch (error) {
        console.error('AI error:', error)
        setThinking(false)
        addMessageToChat('error', 'Ошибка соединения')
    }
}

async function clearAIHistory() {
    if (!confirm('Очистить историю чата с Фиа?')) return
    
    try {
        await fetch('/api/ai.php?action=clear_history', { method: 'POST' })
        
        const container = document.getElementById('ai-messages')
        container.innerHTML = `
            <div class="ai-welcome flex flex-col items-center justify-center h-full text-center">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center mb-4">
                    <span class="text-white text-2xl font-bold">F</span>
                </div>
                <h4 class="text-lg font-bold text-text-primary dark:text-white mb-2">Привет! Я Фиа 👋</h4>
                <p class="text-text-secondary text-sm max-w-xs">
                    Твой AI-помощник в Foodly. Спроси меня о питании, калориях или попроси совет!
                </p>
            </div>
        `
        historyLoaded = true
    } catch (error) {
        console.error('Error clearing history:', error)
    }
}

function addMessageToChat(role, content, scroll = true) {
    const container = document.getElementById('ai-messages')
    
    // Убираем приветствие если оно есть
    const welcome = container.querySelector('.ai-welcome')
    if (welcome) welcome.remove()
    
    const messageDiv = document.createElement('div')
    messageDiv.className = `flex ${role === 'user' ? 'justify-end' : 'justify-start'} mb-4`
    
    if (role === 'user') {
        messageDiv.innerHTML = `
            <div class="max-w-[80%] bg-primary text-white px-4 py-3 rounded-2xl rounded-br-md">
                ${escapeHtml(content)}
            </div>
        `
    } else if (role === 'assistant') {
        messageDiv.innerHTML = `
            <div class="flex gap-3 max-w-[85%]">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center flex-shrink-0">
                    <span class="text-white text-sm font-bold">F</span>
                </div>
                <div class="bg-white dark:bg-[#1c3029] px-4 py-3 rounded-2xl rounded-bl-md border border-[#dbe6e2] dark:border-[#2a3f38]">
                    ${formatAIMessage(content)}
                </div>
            </div>
        `
    } else if (role === 'error') {
        messageDiv.innerHTML = `
            <div class="flex gap-3 max-w-[85%]">
                <div class="w-8 h-8 rounded-full bg-red-500 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-white text-sm">error</span>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 px-4 py-3 rounded-2xl text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800">
                    ${escapeHtml(content)}
                </div>
            </div>
        `
    }
    
    container.appendChild(messageDiv)
    if (scroll) {
        container.scrollTop = container.scrollHeight
    }
}

function setThinking(thinking) {
    isThinking = thinking
    const circle = document.getElementById('ai-circle')
    const thinkingIndicator = document.getElementById('ai-thinking')
    const sendBtn = document.getElementById('ai-send-btn')
    
    if (thinking) {
        circle?.classList.add('thinking')
        circle?.classList.remove('idle')
        thinkingIndicator?.classList.remove('hidden')
        sendBtn?.setAttribute('disabled', 'true')
    } else {
        circle?.classList.remove('thinking')
        circle?.classList.add('idle')
        thinkingIndicator?.classList.add('hidden')
        sendBtn?.removeAttribute('disabled')
    }
}

function formatAIMessage(content) {
    // Простое форматирование: переносы строк и эмодзи
    return escapeHtml(content)
        .replace(/\n/g, '<br>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
}

function escapeHtml(text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
}
