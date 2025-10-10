// GACS Dashboard JavaScript Utilities

// Toast Notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.style.minWidth = '300px';
    toast.style.animation = 'slideInRight 0.3s ease';
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// AJAX Helper
async function fetchAPI(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });

        // Check if response is OK
        if (!response.ok) {
            // Log error for debugging in development
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.error('HTTP Error:', response.status, response.statusText);
            }
        }

        // Get content type
        const contentType = response.headers.get('content-type');

        // Check if response is JSON
        if (contentType && contentType.includes('application/json')) {
            const data = await response.json();
            return data;
        } else {
            // Response is not JSON (probably HTML error page)
            const text = await response.text();
            // Only log in development
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.error('Non-JSON response:', text.substring(0, 200));
            }
            showToast('Server mengembalikan response yang tidak valid.', 'danger');
            return null;
        }
    } catch (error) {
        // Only log in development
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.error('API Error:', error);
        }
        showToast('Terjadi kesalahan koneksi: ' + error.message, 'danger');
        return null;
    }
}

// Format timestamp
function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleString('id-ID');
}

// Confirm dialog
function confirmAction(message) {
    return confirm(message);
}

// Loading overlay
function showLoading() {
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.background = 'rgba(0, 0, 0, 0.5)';
    overlay.style.zIndex = '9998';
    overlay.style.display = 'flex';
    overlay.style.justifyContent = 'center';
    overlay.style.alignItems = 'center';

    overlay.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// Toggle sidebar for mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

// Auto refresh data
function autoRefresh(callback, interval = 30000) {
    setInterval(callback, interval);
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Berhasil disalin!', 'success');
    }).catch(() => {
        showToast('Gagal menyalin', 'danger');
    });
}

// Format uptime
function formatUptime(seconds) {
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    let result = '';
    if (days > 0) result += days + ' hari ';
    if (hours > 0) result += hours + ' jam ';
    if (minutes > 0) result += minutes + ' menit';

    return result || '0 menit';
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
