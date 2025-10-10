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
            <div class="table-responsive">
                <table class="table" id="devices-table">
                    <thead>
                        <tr>
                            <th>Serial Number</th>
                            <th>MAC Address</th>
                            <th>IP TR069</th>
                            <th>WiFi SSID</th>
                            <th>Rx Power</th>
                            <th>Temperature</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="devices-tbody">
                        <tr>
                            <td colspan="8" class="text-center">
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
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">
                    <i class="bi bi-lightning-charge"></i> Konfirmasi Summon Device
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Summon Device?</h5>
                <p class="text-muted mb-0">Apakah Anda yakin ingin melakukan connection request ke device ini?</p>
                <p class="text-muted mb-0"><small>Device ID: <strong id="summon-device-id"></strong></small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Batal
                </button>
                <button type="button" class="btn btn-warning" onclick="confirmSummon()">
                    <i class="bi bi-lightning-charge"></i> Ya, Summon
                </button>
            </div>
        </div>
    </div>
</div>

<script>

async function loadDevices() {
    const tbody = document.getElementById('devices-tbody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="spinner"></div></td></tr>';

    const result = await fetchAPI('/api/get-devices.php');

    if (result && result.success) {
        const devices = result.devices;

        if (devices.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No devices found</td></tr>';
            return;
        }

        tbody.innerHTML = '';

        devices.forEach(device => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><a href="/device-detail.php?id=${encodeURIComponent(device.device_id)}">${device.serial_number}</a></td>
                <td>${device.mac_address}</td>
                <td>${extractIP(device.ip_tr069)}</td>
                <td>${device.wifi_ssid}</td>
                <td>${device.rx_power} dBm</td>
                <td>${device.temperature}°C</td>
                <td><span class="badge ${device.status === 'Online' ? 'online' : 'offline'}">${device.status}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="summonDeviceQuick('${device.device_id}')">
                        <i class="bi bi-lightning-charge"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    } else {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Failed to load devices</td></tr>';
    }
}

function extractIP(ipString) {
    if (!ipString || ipString === 'N/A') return 'N/A';
    const match = ipString.match(/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/);
    return match ? match[1] : 'N/A';
}

let currentSummonDeviceId = null;

function summonDeviceQuick(deviceId) {
    currentSummonDeviceId = deviceId;
    document.getElementById('summon-device-id').textContent = deviceId;
    const modal = new bootstrap.Modal(document.getElementById('summonModal'));
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
