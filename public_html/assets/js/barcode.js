// ============================================
// BARCODE SCANNER
// ============================================

let barcodeStream = null

export function initBarcode() {
    // Экспортируем функции в window
    window.toggleBarcodeMenu = toggleBarcodeMenu
    window.openBarcodeScanner = openBarcodeScanner
    window.closeBarcodeScanner = closeBarcodeScanner
    window.openBarcodeManual = openBarcodeManual
    window.closeBarcodeManual = closeBarcodeManual
    window.searchByBarcode = searchByBarcode
    window.closeBarcodeResult = closeBarcodeResult
    window.addBarcodeProduct = addBarcodeProduct
    
    // Закрытие меню при клике вне
    document.addEventListener('click', (e) => {
        const menu = document.getElementById('barcode-menu')
        const btn = document.getElementById('barcode-btn')
        if (menu && !menu.contains(e.target) && !btn.contains(e.target)) {
            menu.classList.add('hidden')
        }
    })
    
    // Enter в поле ввода штрихкода
    const input = document.getElementById('barcode-input')
    if (input) {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchByBarcode()
            }
        })
    }
}

function toggleBarcodeMenu() {
    const menu = document.getElementById('barcode-menu')
    menu.classList.toggle('hidden')
}

// ============================================
// CAMERA SCANNER
// ============================================

async function openBarcodeScanner() {
    document.getElementById('barcode-menu').classList.add('hidden')
    document.getElementById('barcode-scanner-modal').classList.remove('hidden')
    
    try {
        const video = document.getElementById('barcode-video')
        barcodeStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }
        })
        video.srcObject = barcodeStream
        video.play()
        
        // Используем BarcodeDetector API если доступен
        if ('BarcodeDetector' in window) {
            const detector = new BarcodeDetector({ formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e'] })
            detectBarcode(video, detector)
        } else {
            // Fallback - загружаем библиотеку
            await loadQuaggaJS()
            startQuaggaScanner()
        }
    } catch (error) {
        console.error('Camera error:', error)
        alert('Не удалось получить доступ к камере')
        closeBarcodeScanner()
    }
}

async function detectBarcode(video, detector) {
    if (!barcodeStream) return
    
    try {
        const barcodes = await detector.detect(video)
        if (barcodes.length > 0) {
            const code = barcodes[0].rawValue
            console.log('Barcode detected:', code)
            closeBarcodeScanner()
            await fetchProductByBarcode(code)
            return
        }
    } catch (e) {
        // Игнорируем ошибки детекции
    }
    
    // Продолжаем сканирование
    if (barcodeStream) {
        requestAnimationFrame(() => detectBarcode(video, detector))
    }
}

function closeBarcodeScanner() {
    document.getElementById('barcode-scanner-modal').classList.add('hidden')
    
    if (barcodeStream) {
        barcodeStream.getTracks().forEach(track => track.stop())
        barcodeStream = null
    }
    
    const video = document.getElementById('barcode-video')
    if (video) {
        video.srcObject = null
    }
}

// ============================================
// MANUAL INPUT
// ============================================

function openBarcodeManual() {
    document.getElementById('barcode-menu').classList.add('hidden')
    document.getElementById('barcode-manual-modal').classList.remove('hidden')
    document.getElementById('barcode-input').value = ''
    document.getElementById('barcode-error').classList.add('hidden')
    document.getElementById('barcode-input').focus()
}

function closeBarcodeManual() {
    document.getElementById('barcode-manual-modal').classList.add('hidden')
}

async function searchByBarcode() {
    const input = document.getElementById('barcode-input')
    const errorDiv = document.getElementById('barcode-error')
    const barcode = input.value.trim()
    
    if (!barcode) {
        errorDiv.textContent = 'Введите штрихкод'
        errorDiv.classList.remove('hidden')
        return
    }
    
    if (!/^\d{8,14}$/.test(barcode)) {
        errorDiv.textContent = 'Штрихкод должен содержать 8-14 цифр'
        errorDiv.classList.remove('hidden')
        return
    }
    
    errorDiv.classList.add('hidden')
    closeBarcodeManual()
    await fetchProductByBarcode(barcode)
}

// ============================================
// OPEN FOOD FACTS API
// ============================================

async function fetchProductByBarcode(barcode) {
    const resultModal = document.getElementById('barcode-result-modal')
    const resultContent = document.getElementById('barcode-result-content')
    
    resultContent.innerHTML = `
        <div class="flex items-center justify-center py-10">
            <span class="material-symbols-outlined text-4xl animate-spin text-primary">progress_activity</span>
        </div>
    `
    resultModal.classList.remove('hidden')
    
    try {
        const response = await fetch(`https://world.openfoodfacts.org/api/v0/product/${barcode}.json`)
        const data = await response.json()
        
        if (data.status === 1 && data.product) {
            displayBarcodeProduct(data.product, barcode)
        } else {
            resultContent.innerHTML = `
                <div class="text-center py-6">
                    <span class="material-symbols-outlined text-5xl text-text-secondary mb-4">search_off</span>
                    <h4 class="text-lg font-bold text-text-primary dark:text-white mb-2">Продукт не найден</h4>
                    <p class="text-text-secondary mb-4">Штрихкод: ${barcode}</p>
                    <p class="text-sm text-text-secondary">Попробуйте добавить продукт вручную</p>
                </div>
            `
        }
    } catch (error) {
        console.error('API error:', error)
        resultContent.innerHTML = `
            <div class="text-center py-6">
                <span class="material-symbols-outlined text-5xl text-red-500 mb-4">error</span>
                <h4 class="text-lg font-bold text-text-primary dark:text-white mb-2">Ошибка поиска</h4>
                <p class="text-text-secondary">Проверьте подключение к интернету</p>
            </div>
        `
    }
}

function displayBarcodeProduct(product, barcode) {
    const resultContent = document.getElementById('barcode-result-content')
    
    const name = product.product_name || product.product_name_ru || 'Без названия'
    const brand = product.brands || ''
    const nutrients = product.nutriments || {}
    
    // Калории: сначала ищем ккал, если нет — конвертируем из кДж
    let calories = nutrients['energy-kcal_100g'] || nutrients['energy-kcal'] || 0
    if (!calories && (nutrients['energy-kj_100g'] || nutrients['energy-kj'] || nutrients['energy_100g'])) {
        const kj = nutrients['energy-kj_100g'] || nutrients['energy-kj'] || nutrients['energy_100g'] || 0
        calories = Math.round(kj / 4.184)
    }
    calories = Math.round(calories)
    
    const proteins = parseFloat(nutrients.proteins_100g || 0).toFixed(1)
    const fats = parseFloat(nutrients.fat_100g || 0).toFixed(1)
    const carbs = parseFloat(nutrients.carbohydrates_100g || 0).toFixed(1)
    
    const imageUrl = product.image_small_url || product.image_url || null
    
    resultContent.innerHTML = `
        <div class="space-y-4">
            ${imageUrl ? `
                <div class="flex justify-center">
                    <img src="${imageUrl}" alt="${name}" class="w-24 h-24 object-contain rounded-xl bg-background-light">
                </div>
            ` : ''}
            
            <div class="text-center">
                <h4 class="text-lg font-bold text-text-primary dark:text-white">${escapeHtml(name)}</h4>
                ${brand ? `<p class="text-sm text-text-secondary">${escapeHtml(brand)}</p>` : ''}
                <p class="text-xs text-text-secondary mt-1">Штрихкод: ${barcode}</p>
            </div>
            
            <div class="bg-background-light dark:bg-[#1c3029] rounded-xl p-4">
                <p class="text-xs text-text-secondary text-center mb-3">На 100 г</p>
                <div class="grid grid-cols-4 gap-2 text-center">
                    <div>
                        <div class="text-lg font-bold text-primary">${calories}</div>
                        <div class="text-xs text-text-secondary">ккал</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-text-primary dark:text-white">${proteins}</div>
                        <div class="text-xs text-text-secondary">белки</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-text-primary dark:text-white">${fats}</div>
                        <div class="text-xs text-text-secondary">жиры</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-text-primary dark:text-white">${carbs}</div>
                        <div class="text-xs text-text-secondary">углев.</div>
                    </div>
                </div>
            </div>
            
            <button 
                onclick="window.addBarcodeProduct('${escapeHtml(name)}', ${calories}, ${proteins}, ${fats}, ${carbs}, '${barcode}')"
                class="w-full py-3 bg-primary hover:bg-primary-hover text-white rounded-xl font-semibold transition-colors flex items-center justify-center gap-2"
            >
                <span class="material-symbols-outlined">add</span>
                Добавить в мои продукты
            </button>
        </div>
    `
}

function closeBarcodeResult() {
    document.getElementById('barcode-result-modal').classList.add('hidden')
}

// ============================================
// ADD PRODUCT
// ============================================

async function addBarcodeProduct(title, calories, proteins, fats, carbs, barcode) {
    try {
        const response = await fetch('/api/products.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title: title,
                calories: calories,
                proteins: proteins,
                fats: fats,
                carbohydrates: carbs,
                barcode: barcode
            })
        })
        
        const data = await response.json()
        
        if (data.success) {
            closeBarcodeResult()
            // Сразу выбираем продукт для добавления
            if (window.selectProduct) {
                window.selectProduct(data.product_id, title)
            }
        } else {
            alert('Ошибка: ' + (data.error || 'Не удалось добавить продукт'))
        }
    } catch (error) {
        console.error('Error adding product:', error)
        alert('Ошибка добавления продукта')
    }
}

// ============================================
// HELPERS
// ============================================

function escapeHtml(text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
}

// Загрузка QuaggaJS для браузеров без BarcodeDetector
async function loadQuaggaJS() {
    if (window.Quagga) return
    
    return new Promise((resolve, reject) => {
        const script = document.createElement('script')
        script.src = 'https://cdn.jsdelivr.net/npm/@ericblade/quagga2@1.8.4/dist/quagga.min.js'
        script.onload = resolve
        script.onerror = reject
        document.head.appendChild(script)
    })
}

function startQuaggaScanner() {
    // Fallback scanner implementation
    // Для простоты пока используем только BarcodeDetector API
    console.log('QuaggaJS fallback not implemented yet')
}
