<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = 'Devices';
$currentPage = 'devices';

$genieacsConfigured = isGenieACSConfigured();

include __DIR__ . '/views/layouts/header.php';
?>

<?php if (!$genieacsConfigured): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        GenieACS belum dikonfigurasi. Silakan konfigurasi terlebih dahulu di
        <a href="/configuration.php">halaman Configuration</a>.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <i class="bi bi-router"></i> Device List
            <button class="btn btn-sm btn-primary float-end" onclick="loadDevices()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
        <div class="card-body">
            <!-- Search Box -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control" id="search-input" placeholder="Search by Serial Number or MAC Address..." onkeyup="filterDevices()">
                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                            <i class="bi bi-x-lg"></i> Clear
                        </button>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted" id="device-count">Loading...</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="devices-table">
                    <thead>
                        <tr>
                            <th>Serial Number</th>
                            <th>MAC Address</th>
                            <th class="sortable" onclick="sortTable('product_class')" style="cursor: pointer;">
                                Product Class <i class="bi bi-chevron-expand sort-icon"></i>
                            </th>
                            <th class="sortable" onclick="sortTable('ip')" style="cursor: pointer;">
                                IP TR069 <i class="bi bi-chevron-expand sort-icon"></i>
                            </th>
                            <th class="sortable" onclick="sortTable('ssid')" style="cursor: pointer;">
                                WiFi SSID <i class="bi bi-chevron-expand sort-icon"></i>
                            </th>
                            <th class="sortable" onclick="sortTable('rx_power')" style="cursor: pointer;">
                                Rx Power <i class="bi bi-chevron-expand sort-icon"></i>
                            </th>
                            <th class="sortable" onclick="sortTable('temperature')" style="cursor: pointer;">
                                Temperature <i class="bi bi-chevron-expand sort-icon"></i>
                            </th>
                            <th class="sortable" onclick="sortTable('status')" style="cursor: pointer;">
                                Status <i class="bi bi-chevron-expand sort-icon"></i>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="devices-tbody">
                        <tr>
                            <td colspan="9" class="text-center">
                                <div class="spinner"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Summon Confirmation Modal -->
<div class="modal fade" id="summonModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-lightning-charge"></i> Konfirmasi Summon Device
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: var(--warning-color);"></i>
                <h5 class="mt-3">Summon Device?</h5>
                <p class="text-muted mb-0">Apakah Anda yakin ingin melakukan connection request ke device ini?</p>
                <p class="text-muted mb-0"><small>Device ID: <strong id="summon-device-id"></strong></small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmSummon()">
                    <i class="bi bi-lightning-charge"></i> Ya, Summon
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables for devices data and sorting
let allDevices = [];
let currentSortColumn = null;
let currentSortDirection = 'asc';

async function loadDevices() {
    const tbody = document.getElementById('devices-tbody');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center"><div class="spinner"></div></td></tr>';

    const result = await fetchAPI('/api/get-devices.php');

    if (result && result.success) {
        allDevices = result.devices;

        if (allDevices.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">No devices found</td></tr>';
            updateDeviceCount(0, 0);
            return;
        }

        // Reset sort state
        currentSortColumn = null;
        currentSortDirection = 'asc';
        resetSortIcons();

        renderDevices(allDevices);
        updateDeviceCount(allDevices.length, allDevices.length);
    } else {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Failed to load devices</td></tr>';
        updateDeviceCount(0, 0);
    }
}

function renderDevices(devices) {
    const tbody = document.getElementById('devices-tbody');
    tbody.innerHTML = '';

    if (devices.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No devices found</td></tr>';
        return;
    }

    devices.forEach(device => {
        const row = document.createElement('tr');
        const ipAddress = extractIP(device.ip_tr069);

        // Create clickable IP link if IP is valid
        let ipDisplay;
        if (ipAddress !== 'N/A' && ipAddress !== '') {
            ipDisplay = `<a href="http://${ipAddress}" target="_blank" rel="noopener noreferrer" title="Open ${ipAddress} in new tab">${ipAddress}</a>`;
        } else {
            ipDisplay = ipAddress;
        }

        row.innerHTML = `
            <td><a href="/device-detail.php?id=${encodeURIComponent(device.device_id)}">${device.serial_number}</a></td>
            <td>${device.mac_address}</td>
            <td data-sort-value="${device.product_class || ''}">${device.product_class || 'N/A'}</td>
            <td data-sort-value="${ipAddress}">${ipDisplay}</td>
            <td data-sort-value="${device.wifi_ssid}">${device.wifi_ssid}</td>
            <td data-sort-value="${parseFloat(device.rx_power) || -999}">${device.rx_power} dBm</td>
            <td data-sort-value="${parseFloat(device.temperature) || -999}">${device.temperature}°C</td>
            <td data-sort-value="${device.status}"><span class="badge ${device.status === 'online' ? 'online' : 'offline'}">${device.status.charAt(0).toUpperCase() + device.status.slice(1)}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="summonDeviceQuick('${device.device_id}')">
                    <i class="bi bi-lightning-charge"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function updateDeviceCount(shown, total) {
    const countElement = document.getElementById('device-count');
    if (shown === total) {
        countElement.textContent = `Showing ${total} device${total !== 1 ? 's' : ''}`;
    } else {
        countElement.textContent = `Showing ${shown} of ${total} device${total !== 1 ? 's' : ''}`;
    }
}

function extractIP(ipString) {
    if (!ipString || ipString === 'N/A') return 'N/A';
    const match = ipString.match(/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/);
    return match ? match[1] : 'N/A';
}

// Search functionality
function filterDevices() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase().trim();

    if (searchTerm === '') {
        renderDevices(allDevices);
        updateDeviceCount(allDevices.length, allDevices.length);
        return;
    }

    const filteredDevices = allDevices.filter(device => {
        const serialNumber = (device.serial_number || '').toLowerCase();
        const macAddress = (device.mac_address || '').toLowerCase();

        return serialNumber.includes(searchTerm) || macAddress.includes(searchTerm);
    });

    renderDevices(filteredDevices);
    updateDeviceCount(filteredDevices.length, allDevices.length);
}

function clearSearch() {
    document.getElementById('search-input').value = '';
    filterDevices();
}

// Sorting functionality
function sortTable(column) {
    // Toggle sort direction if clicking same column
    if (currentSortColumn === column) {
        currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortColumn = column;
        currentSortDirection = 'asc';
    }

    // Get current filtered devices (in case search is active)
    const searchTerm = document.getElementById('search-input').value.toLowerCase().trim();
    let devicesToSort = searchTerm === '' ? [...allDevices] : allDevices.filter(device => {
        const serialNumber = (device.serial_number || '').toLowerCase();
        const macAddress = (device.mac_address || '').toLowerCase();
        return serialNumber.includes(searchTerm) || macAddress.includes(searchTerm);
    });

    // Sort devices
    devicesToSort.sort((a, b) => {
        let valueA, valueB;

        switch (column) {
            case 'product_class':
                valueA = (a.product_class || '').toLowerCase();
                valueB = (b.product_class || '').toLowerCase();
                break;
            case 'ip':
                valueA = extractIP(a.ip_tr069);
                valueB = extractIP(b.ip_tr069);
                // Convert IP to comparable format
                valueA = valueA === 'N/A' ? '' : valueA.split('.').map(n => n.padStart(3, '0')).join('.');
                valueB = valueB === 'N/A' ? '' : valueB.split('.').map(n => n.padStart(3, '0')).join('.');
                break;
            case 'ssid':
                valueA = (a.wifi_ssid || '').toLowerCase();
                valueB = (b.wifi_ssid || '').toLowerCase();
                break;
            case 'rx_power':
                valueA = parseFloat(a.rx_power) || -999;
                valueB = parseFloat(b.rx_power) || -999;
                break;
            case 'temperature':
                valueA = parseFloat(a.temperature) || -999;
                valueB = parseFloat(b.temperature) || -999;
                break;
            case 'status':
                valueA = a.status || '';
                valueB = b.status || '';
                break;
            default:
                return 0;
        }

        let comparison = 0;
        if (valueA > valueB) comparison = 1;
        if (valueA < valueB) comparison = -1;

        return currentSortDirection === 'asc' ? comparison : -comparison;
    });

    renderDevices(devicesToSort);
    updateSortIcons(column, currentSortDirection);
}

function updateSortIcons(column, direction) {
    // Reset all sort icons
    document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.className = 'bi bi-chevron-expand sort-icon';
    });

    // Update active sort icon
    const columnMap = {
        'product_class': 3,
        'ip': 4,
        'ssid': 5,
        'rx_power': 6,
        'temperature': 7,
        'status': 8
    };

    const columnIndex = columnMap[column];
    if (columnIndex) {
        const header = document.querySelector(`thead tr th:nth-child(${columnIndex}) .sort-icon`);
        if (header) {
            header.className = direction === 'asc' ? 'bi bi-chevron-up sort-icon' : 'bi bi-chevron-down sort-icon';
        }
    }
}

function resetSortIcons() {
    document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.className = 'bi bi-chevron-expand sort-icon';
    });
}

let currentSummonDeviceId = null;

function summonDeviceQuick(deviceId) {
    currentSummonDeviceId = deviceId;
    document.getElementById('summon-device-id').textContent = deviceId;
    const modal = new bootstrap.Modal(document.getElementById('summonModal'), {
        backdrop: false
    });
    modal.show();
}

async function confirmSummon() {
    if (!currentSummonDeviceId) return;

    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('summonModal'));
    modal.hide();

    showLoading();

    const result = await fetchAPI('/api/summon-device.php', {
        method: 'POST',
        body: JSON.stringify({ device_id: currentSummonDeviceId })
    });

    hideLoading();

    if (result && result.success) {
        showToast('Device summon berhasil!', 'success');
    } else {
        showToast(result.message || 'Gagal summon device', 'danger');
    }

    currentSummonDeviceId = null;
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($genieacsConfigured): ?>
        loadDevices();
        setInterval(loadDevices, 60000); // Refresh every 60 seconds
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
