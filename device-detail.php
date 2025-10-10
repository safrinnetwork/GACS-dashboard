<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = 'Device Detail';
$currentPage = 'devices';

$genieacsConfigured = isGenieACSConfigured();

// Get device ID from URL
$deviceId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$deviceId) {
    header('Location: /devices.php');
    exit;
}

include __DIR__ . '/views/layouts/header.php';
?>

<?php if (!$genieacsConfigured): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        GenieACS belum dikonfigurasi. Silakan konfigurasi terlebih dahulu di
        <a href="/configuration.php">halaman Configuration</a>.
    </div>
<?php else: ?>
    <!-- Back Button -->
    <div class="mb-3">
        <a href="/devices.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Devices
        </a>
        <button class="btn btn-primary" onclick="summonDevice()">
            <i class="bi bi-lightning-charge"></i> Summon Device
        </button>
        <button class="btn btn-info" onclick="loadDeviceDetail()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>

    <!-- Device Detail Card -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-router"></i> Device Details
            <span id="device-id-badge" class="badge bg-secondary ms-2">Loading...</span>
        </div>
        <div class="card-body" id="device-detail-content">
            <div class="text-center">
                <div class="spinner"></div>
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
const deviceId = '<?php echo htmlspecialchars($deviceId, ENT_QUOTES); ?>';

async function loadDeviceDetail() {
    const content = document.getElementById('device-detail-content');
    content.innerHTML = '<div class="text-center"><div class="spinner"></div></div>';

    const result = await fetchAPI('/api/get-device-detail.php?device_id=' + encodeURIComponent(deviceId));

    if (result && result.success) {
        const device = result.device;

        // Update badge
        document.getElementById('device-id-badge').textContent = device.serial_number;

        content.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="bi bi-info-circle"></i> Basic Information</h6>
                    <table class="table table-sm table-bordered">
                        <tr><th width="40%">Device ID:</th><td>${device.device_id}</td></tr>
                        <tr><th>Serial Number:</th><td>${device.serial_number}</td></tr>
                        <tr><th>MAC Address:</th><td>${device.mac_address}</td></tr>
                        <tr><th>Last Inform:</th><td>${device.last_inform}</td></tr>
                        <tr><th>Status:</th><td><span class="badge ${device.status === 'Online' ? 'online' : 'offline'}">${device.status}</span></td></tr>
                        <tr><th>Manufacturer:</th><td>${device.manufacturer}</td></tr>
                        <tr><th>Product Class:</th><td>${device.product_class}</td></tr>
                        <tr><th>OUI:</th><td>${device.oui}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6><i class="bi bi-cpu"></i> Hardware/Software</h6>
                    <table class="table table-sm table-bordered">
                        <tr><th width="40%">Hardware Version:</th><td>${device.hardware_version}</td></tr>
                        <tr><th>Software Version:</th><td>${device.software_version}</td></tr>
                        <tr><th>Uptime:</th><td>${formatUptime(device.uptime)}</td></tr>
                    </table>

                    <h6 class="mt-4"><i class="bi bi-broadcast"></i> Optical Information</h6>
                    <table class="table table-sm table-bordered">
                        <tr><th width="40%">Rx Power:</th><td>${device.rx_power} dBm</td></tr>
                        <tr><th>Temperature:</th><td>${device.temperature}°C</td></tr>
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <h6><i class="bi bi-ethernet"></i> Network Information</h6>
                    <table class="table table-sm table-bordered">
                        <tr>
                            <th width="20%">IP TR069:</th>
                            <td>${extractIP(device.ip_tr069)}</td>
                        </tr>
                        <tr>
                            <th>WiFi SSID:</th>
                            <td>${device.wifi_ssid}</td>
                        </tr>
                        <tr>
                            <th>WiFi Password:</th>
                            <td>
                                <span id="wifi-pass-hidden">********</span>
                                <span id="wifi-pass-shown" style="display:none;">${device.wifi_password}</span>
                                <button class="btn btn-sm btn-link" onclick="togglePassword()">
                                    <i id="toggle-icon" class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <th>Full TR069 URL:</th>
                            <td><small>${device.ip_tr069}</small></td>
                        </tr>
                    </table>
                </div>
            </div>
        `;
    } else {
        content.innerHTML = '<div class="alert alert-danger">Failed to load device details</div>';
    }
}

function extractIP(ipString) {
    if (!ipString || ipString === 'N/A') return 'N/A';
    const match = ipString.match(/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/);
    return match ? match[1] : 'N/A';
}

function togglePassword() {
    const hidden = document.getElementById('wifi-pass-hidden');
    const shown = document.getElementById('wifi-pass-shown');
    const icon = document.getElementById('toggle-icon');

    if (hidden.style.display === 'none') {
        hidden.style.display = 'inline';
        shown.style.display = 'none';
        icon.className = 'bi bi-eye';
    } else {
        hidden.style.display = 'none';
        shown.style.display = 'inline';
        icon.className = 'bi bi-eye-slash';
    }
}

function summonDevice() {
    document.getElementById('summon-device-id').textContent = deviceId;
    const modal = new bootstrap.Modal(document.getElementById('summonModal'));
    modal.show();
}

async function confirmSummon() {
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('summonModal'));
    modal.hide();

    showLoading();

    const result = await fetchAPI('/api/summon-device.php', {
        method: 'POST',
        body: JSON.stringify({ device_id: deviceId })
    });

    hideLoading();

    if (result && result.success) {
        showToast('Device summon berhasil!', 'success');
        // Reload device detail after summon
        setTimeout(loadDeviceDetail, 2000);
    } else {
        showToast(result.message || 'Gagal summon device', 'danger');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($genieacsConfigured): ?>
        loadDeviceDetail();
        // Auto refresh every 30 seconds
        setInterval(loadDeviceDetail, 30000);
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
