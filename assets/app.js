/**
 * CheckTrack Application JavaScript
 */

/**
 * Start a live timer from a given check-in time
 * @param {string} checkinTime - MySQL datetime string
 */
function startTimer(checkinTime) {
    const timerElement = document.getElementById('timer');
    if (!timerElement) return;
    
    const startTime = new Date(checkinTime);
    
    // Validate date
    if (isNaN(startTime.getTime())) {
        console.error('Invalid checkinTime:', checkinTime);
        timerElement.textContent = '00:00:00';
        return;
    }
    
    const startTimestamp = startTime.getTime();
    
    function updateTimer() {
        const now = Date.now();
        const diff = now - startTimestamp;
        
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        timerElement.textContent = 
            String(hours).padStart(2, '0') + ':' +
            String(minutes).padStart(2, '0') + ':' +
            String(seconds).padStart(2, '0');
    }
    
    updateTimer();
    return setInterval(updateTimer, 1000);
}

/**
 * Show a toast notification
 * @param {string} message - Message to display
 * @param {string} type - 'success' or 'error'
 */
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    
    toast.className = `toast ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg mb-3 max-w-sm text-center`;
    toast.textContent = message;
    
    container.appendChild(toast);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Send a POST request with JSON body and CSRF token
 * @param {string} url - API endpoint
 * @param {object} data - Request body
 * @returns {Promise<object>} - JSON response
 */
async function postJSON(url, data) {
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
    
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(data)
    });
    
    return response.json();
}
