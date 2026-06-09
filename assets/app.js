/**
 * CheckTrack Application JavaScript
 */

// ADD AT TOP of app.js
let activeTimerInterval = null;

function startTimer(checkinTime) {
    const timerElement = document.getElementById('timer');
    if (!timerElement) return;

    // Clear any existing timer first
    if (activeTimerInterval) {
        clearInterval(activeTimerInterval);
        activeTimerInterval = null;
    }

    const startTime = new Date(checkinTime);

    if (isNaN(startTime.getTime())) {
        console.error('Invalid checkinTime:', checkinTime);
        timerElement.textContent = '00:00:00';
        return;
    }

    const startTimestamp = startTime.getTime();

    function updateTimer() {
        const now = Date.now();
        const diff = Math.max(0, now - startTimestamp);

        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        timerElement.textContent =
            String(hours).padStart(2, '0') + ':' +
            String(minutes).padStart(2, '0') + ':' +
            String(seconds).padStart(2, '0');
    }

    updateTimer();
    activeTimerInterval = setInterval(updateTimer, 1000);
    return activeTimerInterval;
}

// ADD cleanup function
function stopTimer() {
    if (activeTimerInterval) {
        clearInterval(activeTimerInterval);
        activeTimerInterval = null;
    }
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

/**
 * Version Checking and Update System
 */
class VersionChecker {
    constructor() {
        this.currentVersion = '';
        this.hasUpdate = false;
        this.init();
    }

    async init() {
        await this.checkForUpdates();
        this.bindEvents();
    }

    bindEvents() {
        // Add click handler for update notification
        document.addEventListener('click', (e) => {
            if (e.target.closest('#updateNotification')) {
                this.showUpdateDialog();
            }
        });
    }

    async checkForUpdates() {
        try {
            const response = await fetch('api/check_version.php');
            const data = await response.json();
            
            if (data.success) {
                this.currentVersion = data.data.current_version;
                this.hasUpdate = data.data.update_available;
                
                if (this.hasUpdate) {
                    this.showUpdateNotification();
                }
            }
        } catch (error) {
            console.error('Version check failed:', error);
        }
    }

    showUpdateNotification() {
        // Remove existing notification if any
        const existing = document.getElementById('updateNotification');
        if (existing) existing.remove();

        const notification = document.createElement('div');
        notification.id = 'updateNotification';
        notification.className = 'fixed top-20 right-4 bg-yellow-500 text-white p-4 rounded-lg shadow-lg z-50 cursor-pointer';
        notification.innerHTML = `
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="font-medium">Update Available!</span>
            </div>
            <p class="text-sm mt-1">Click to view details</p>
        `;
        document.body.appendChild(notification);
    }

    showUpdateDialog() {
        const dialog = document.createElement('div');
        dialog.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        dialog.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-bold mb-4">Update Available</h3>
                <p class="mb-2">Current Version: ${this.currentVersion}</p>
                <p class="mb-4">Latest Version: ${this.latestVersion || 'Checking...'}</p>
                <div class="flex gap-2">
                    <button id="downloadUpdate" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Download Update</button>
                    <button id="closeDialog" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(dialog);

        document.getElementById('downloadUpdate').addEventListener('click', () => {
            this.downloadUpdate();
            dialog.remove();
        });

        document.getElementById('closeDialog').addEventListener('click', () => {
            dialog.remove();
        });
    }

    async downloadUpdate() {
        try {
            const response = await fetch('api/update_system.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('input[name="csrf_token"]')?.value || ''
                },
                body: JSON.stringify({action: 'download'})
            });
            
            const data = await response.json();
            if (data.success) {
                showToast('Update download started', 'success');
                // In a real implementation, you would handle the actual download
                window.open(data.download_url, '_blank');
            } else {
                showToast(data.message || 'Download failed', 'error');
            }
        } catch (error) {
            console.error('Download failed:', error);
            showToast('Failed to download update', 'error');
        }
    }
}

// Initialize version checker when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new VersionChecker();
});
