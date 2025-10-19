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

    <!-- Device Detail Card with Tabs -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-router"></i> Device Details
            <span id="device-id-badge" class="badge bg-secondary ms-2">Loading...</span>
        </div>
        <div class="card-body">
            <!-- Loading Spinner (shown initially) -->
            <div id="loading-spinner" class="text-center">
                <div class="spinner"></div>
            </div>

            <!-- Tab Navigation (hidden initially) -->
            <ul class="nav nav-tabs" id="deviceTabs" role="tablist" style="display:none;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                        <i class="bi bi-info-circle"></i> Overview
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="topology-tab" data-bs-toggle="tab" data-bs-target="#topology" type="button" role="tab">
                        <i class="bi bi-diagram-3"></i> Topology Location
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="wan-tab" data-bs-toggle="tab" data-bs-target="#wan" type="button" role="tab">
                        <i class="bi bi-globe"></i> WAN Connections <span id="wan-count-badge" class="badge bg-primary ms-1">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="dhcp-tab" data-bs-toggle="tab" data-bs-target="#dhcp" type="button" role="tab">
                        <i class="bi bi-router"></i> DHCP Server
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="devices-tab" data-bs-toggle="tab" data-bs-target="#devices" type="button" role="tab">
                        <i class="bi bi-hdd-network"></i> Connected Devices <span id="devices-count-badge" class="badge bg-primary ms-1">0</span>
                    </button>
                </li>
            </ul>

            <!-- Tab Content (hidden initially) -->
            <div class="tab-content mt-3" id="deviceTabContent" style="display:none;">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div id="overview-content"></div>
                </div>

                <!-- Topology Location Tab -->
                <div class="tab-pane fade" id="topology" role="tabpanel">
                    <div id="topology-content"></div>
                </div>

                <!-- WAN Connections Tab -->
                <div class="tab-pane fade" id="wan" role="tabpanel">
                    <div id="wan-content"></div>
                </div>

                <!-- DHCP Server Tab -->
                <div class="tab-pane fade" id="dhcp" role="tabpanel">
                    <div id="dhcp-content"></div>
                </div>

                <!-- Connected Devices Tab -->
                <div class="tab-pane fade" id="devices" role="tabpanel">
                    <div id="devices-content"></div>
                </div>
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

<!-- Edit WiFi Configuration Modal -->
<div class="modal fade" id="editWiFiModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-wifi"></i> Edit WiFi Configuration
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Info:</strong> Perubahan akan dikirim ke ONU melalui GenieACS. Device mungkin perlu reboot untuk menerapkan konfigurasi baru.
                </div>
                <form id="editWiFiForm">
                    <input type="hidden" id="edit-device-id" name="device_id">

                    <div class="mb-3">
                        <label for="edit-wifi-ssid" class="form-label">
                            <i class="bi bi-wifi"></i> WiFi SSID
                        </label>
                        <input type="text" class="form-control" id="edit-wifi-ssid" name="wifi_ssid"
                               required minlength="1" maxlength="32"
                               placeholder="Enter WiFi SSID (1-32 characters)">
                        <div class="form-text">SSID harus antara 1-32 karakter</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-security-mode" class="form-label">
                            <i class="bi bi-shield-lock"></i> Security Mode
                        </label>
                        <select class="form-select" id="edit-security-mode" name="security_mode" onchange="togglePasswordField()">
                            <option value="WPA2PSK">WPA2-PSK (Recommended)</option>
                            <option value="WPAPSK">WPA-PSK</option>
                            <option value="WPA2PSKWPAPSK">WPA/WPA2-PSK Mixed</option>
                            <option value="None">Open (No Security)</option>
                        </select>
                        <div class="form-text">Pilih mode keamanan WiFi</div>
                    </div>

                    <div class="mb-3" id="password-field-group">
                        <label for="edit-wifi-password" class="form-label">
                            <i class="bi bi-key"></i> WiFi Password
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="edit-wifi-password" name="wifi_password"
                                   minlength="8" maxlength="63"
                                   placeholder="Enter WiFi Password (8-63 characters)">
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleEditPassword()">
                                <i id="edit-toggle-icon" class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Password harus antara 8-63 karakter untuk WPA/WPA2</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-wlan-index" class="form-label">
                            <i class="bi bi-router"></i> WLAN Interface
                        </label>
                        <select class="form-select" id="edit-wlan-index" name="wlan_index">
                            <option value="1" selected>WLAN 1 (2.4GHz - Default)</option>
                            <option value="2">WLAN 2 (5GHz)</option>
                            <option value="3">WLAN 3</option>
                            <option value="4">WLAN 4</option>
                        </select>
                        <div class="form-text">Pilih interface WLAN yang ingin diubah</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Batal
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmUpdateWiFi()">
                    <i class="bi bi-check-lg"></i> Update WiFi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit WAN Configuration Modal -->
<div class="modal fade" id="editWANModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-globe"></i> Edit WAN Connection
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning" id="tr069-warning" style="display:none;">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>WARNING:</strong> This is a TR069 management connection. Modifying this connection may break device communication with GenieACS. Only proceed if you know what you're doing.
                </div>
                <form id="editWANForm">
                    <input type="hidden" id="edit-wan-device-id">
                    <input type="hidden" id="edit-wan-connection-index">
                    <input type="hidden" id="edit-wan-connection-type">

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-info-circle"></i> Connection Name
                        </label>
                        <input type="text" class="form-control" id="edit-wan-name" readonly>
                        <div class="form-text">Connection name is read-only</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-toggle-on"></i> Enable Connection
                        </label>
                        <select class="form-select" id="edit-wan-enable">
                            <option value="true">Enabled</option>
                            <option value="false">Disabled</option>
                        </select>
                    </div>

                    <div class="mb-3" id="edit-wan-username-group">
                        <label class="form-label">
                            <i class="bi bi-person"></i> PPPoE Username
                        </label>
                        <input type="text" class="form-control" id="edit-wan-username" placeholder="Enter PPPoE username">
                    </div>

                    <div class="mb-3" id="edit-wan-password-group">
                        <label class="form-label">
                            <i class="bi bi-key"></i> PPPoE Password
                        </label>
                        <input type="password" class="form-control" id="edit-wan-password" placeholder="Enter PPPoE password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-shield"></i> NAT Enabled
                        </label>
                        <select class="form-select" id="edit-wan-nat">
                            <option value="true">Enabled</option>
                            <option value="false">Disabled</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-tag"></i> VLAN ID
                        </label>
                        <input type="number" class="form-control" id="edit-wan-vlan" min="1" max="4094" placeholder="1-4094">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmUpdateWAN()">
                    <i class="bi bi-check-lg"></i> Update WAN
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add WAN Configuration Modal -->
<div class="modal fade" id="addWANModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-lg"></i> Add New WAN Connection
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Info:</strong> A new WAN connection will be created on the device. Make sure you choose an unused connection index.
                </div>
                <form id="addWANForm">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-hash"></i> Connection Index
                        </label>
                        <select class="form-select" id="add-wan-index" required>
                            <option value="">Select connection index...</option>
                            <option value="1">Connection 1</option>
                            <option value="2">Connection 2</option>
                            <option value="3">Connection 3</option>
                            <option value="4">Connection 4</option>
                            <option value="5">Connection 5</option>
                            <option value="6">Connection 6</option>
                            <option value="7">Connection 7</option>
                            <option value="8">Connection 8</option>
                        </select>
                        <div class="form-text">Choose an unused connection slot</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-diagram-3"></i> Connection Type
                        </label>
                        <select class="form-select" id="add-wan-type" required onchange="toggleAddWANFields()">
                            <option value="">Select connection type...</option>
                            <option value="ppp">PPPoE Connection</option>
                            <option value="ip">IP Connection</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-pencil"></i> Connection Name
                        </label>
                        <input type="text" class="form-control" id="add-wan-name" placeholder="e.g., 4_INTERNET_R_VID_100">
                        <div class="form-text">Optional: Auto-generated if left empty</div>
                    </div>

                    <div class="mb-3" id="add-wan-username-group" style="display:none;">
                        <label class="form-label">
                            <i class="bi bi-person"></i> PPPoE Username
                        </label>
                        <input type="text" class="form-control" id="add-wan-username" placeholder="Enter PPPoE username">
                    </div>

                    <div class="mb-3" id="add-wan-password-group" style="display:none;">
                        <label class="form-label">
                            <i class="bi bi-key"></i> PPPoE Password
                        </label>
                        <input type="password" class="form-control" id="add-wan-password" placeholder="Enter PPPoE password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-cloud"></i> Service List
                        </label>
                        <select class="form-select" id="add-wan-service">
                            <option value="INTERNET">INTERNET</option>
                            <option value="VOIP">VOIP</option>
                            <option value="CUSTOM">Custom (enter below)</option>
                        </select>
                        <input type="text" class="form-control mt-2" id="add-wan-service-custom" placeholder="Enter custom service name" style="display:none;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-tag"></i> VLAN ID
                        </label>
                        <input type="number" class="form-control" id="add-wan-vlan" min="1" max="4094" placeholder="1-4094" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-shield"></i> NAT Enabled
                        </label>
                        <select class="form-select" id="add-wan-nat">
                            <option value="true" selected>Enabled</option>
                            <option value="false">Disabled</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" onclick="confirmAddWAN()">
                    <i class="bi bi-plus-lg"></i> Add Connection
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete WAN Confirmation Modal -->
<div class="modal fade" id="deleteWANModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-trash"></i> Confirm Delete WAN Connection
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div id="delete-wan-normal-warning">
                    <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: var(--warning-color);"></i>
                    <h5 class="mt-3">Delete WAN Connection?</h5>
                    <p class="text-muted mb-2">Are you sure you want to delete this WAN connection?</p>
                    <p class="mb-0"><strong>Connection:</strong> <span id="delete-wan-name"></span></p>
                    <p class="mb-0"><strong>Service:</strong> <span id="delete-wan-service"></span></p>
                    <p class="text-muted mt-2"><small>This action cannot be undone.</small></p>
                </div>
                <div id="delete-wan-tr069-warning" style="display:none;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 3rem; color: var(--danger-color);"></i>
                    <h5 class="mt-3 text-danger">DANGER: TR069 Management Connection</h5>
                    <p class="text-muted mb-2">You are about to delete the TR069 management connection!</p>
                    <p class="mb-0"><strong>Connection:</strong> <span id="delete-wan-tr069-name"></span></p>
                    <p class="mb-0"><strong>Service:</strong> <span id="delete-wan-tr069-service"></span></p>
                    <div class="alert alert-danger mt-3 text-start">
                        <strong>Deleting this connection will:</strong>
                        <ul class="mb-0">
                            <li>✗ Break communication with GenieACS</li>
                            <li>✗ Prevent remote management</li>
                            <li>✗ Require manual factory reset to restore</li>
                        </ul>
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="delete-wan-tr069-confirm">
                        <label class="form-check-label" for="delete-wan-tr069-confirm">
                            <strong>I understand the risks and want to proceed</strong>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirm-delete-wan-btn" onclick="confirmDeleteWAN()">
                    <i class="bi bi-trash"></i> Yes, Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit DHCP Configuration Modal -->
<div class="modal fade" id="editDHCPModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-router"></i> Edit DHCP Server Configuration
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Info:</strong> Changes will be sent to the ONU via GenieACS. Device may need reboot to apply changes.
                </div>
                <form id="editDHCPForm">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-toggle-on"></i> DHCP Server
                        </label>
                        <select class="form-select" id="edit-dhcp-enable" onchange="toggleDHCPFields()">
                            <option value="true">Enabled</option>
                            <option value="false">Disabled</option>
                        </select>
                    </div>

                    <div id="dhcp-config-fields">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-arrow-down-circle"></i> IP Address Pool Start
                            </label>
                            <input type="text" class="form-control" id="edit-dhcp-min-address" placeholder="e.g., 192.168.1.10" pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$">
                            <div class="form-text">First IP address in DHCP pool</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-arrow-up-circle"></i> IP Address Pool End
                            </label>
                            <input type="text" class="form-control" id="edit-dhcp-max-address" placeholder="e.g., 192.168.1.200" pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$">
                            <div class="form-text">Last IP address in DHCP pool</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-diagram-2"></i> Subnet Mask
                            </label>
                            <input type="text" class="form-control" id="edit-dhcp-subnet-mask" placeholder="e.g., 255.255.255.0" pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-router-fill"></i> Default Gateway
                            </label>
                            <input type="text" class="form-control" id="edit-dhcp-gateway" placeholder="e.g., 192.168.1.1" pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-server"></i> DNS Servers
                            </label>
                            <input type="text" class="form-control" id="edit-dhcp-dns" placeholder="e.g., 8.8.8.8,8.8.4.4">
                            <div class="form-text">Comma-separated IP addresses</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-clock"></i> Lease Time (seconds)
                            </label>
                            <input type="number" class="form-control" id="edit-dhcp-lease-time" placeholder="e.g., 86400" min="60">
                            <div class="form-text">Minimum 60 seconds (86400 = 24 hours)</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmUpdateDHCP()">
                    <i class="bi bi-check-lg"></i> Update DHCP
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const deviceId = '<?php echo htmlspecialchars($deviceId, ENT_QUOTES); ?>';

async function loadDeviceDetail() {
    // Show loading spinner, hide tabs
    document.getElementById('loading-spinner').style.display = 'block';
    document.getElementById('deviceTabs').style.display = 'none';
    document.getElementById('deviceTabContent').style.display = 'none';

    const result = await fetchAPI('/api/get-device-detail.php?device_id=' + encodeURIComponent(deviceId));

    if (result && result.success) {
        const device = result.device;

        // Fetch ONU location from map
        const locationResult = await fetchAPI('/api/get-onu-location.php?serial_number=' + encodeURIComponent(device.serial_number));

        // Update badge
        document.getElementById('device-id-badge').textContent = device.serial_number;

        // Update badge counts
        document.getElementById('wan-count-badge').textContent = device.wan_details ? device.wan_details.length : 0;
        document.getElementById('devices-count-badge').textContent = device.connected_devices ? device.connected_devices.length : 0;

        // Populate Overview Tab
        document.getElementById('overview-content').innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="bi bi-info-circle"></i> Basic Information</h6>
                    <table class="table table-sm table-bordered">
                        <tr><th width="40%">Device ID</th><td>${device.device_id}</td></tr>
                        <tr><th>Serial Number</th><td>${device.serial_number}</td></tr>
                        <tr><th>MAC Address</th><td>${device.mac_address}</td></tr>
                        <tr><th>Last Inform</th><td>${device.last_inform}</td></tr>
                        <tr><th>Status</th><td><span class="badge ${device.status === 'online' ? 'online' : 'offline'}">${device.status}</span></td></tr>
                        <tr><th>Manufacturer</th><td>${device.manufacturer}</td></tr>
                        <tr><th>Product Class</th><td>${device.product_class}</td></tr>
                        <tr><th>OUI</th><td>${device.oui}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6><i class="bi bi-cpu"></i> Hardware/Software</h6>
                    <table class="table table-sm table-bordered">
                        <tr><th width="40%">Hardware Version</th><td>${device.hardware_version}</td></tr>
                        <tr><th>Software Version</th><td>${device.software_version}</td></tr>
                        <tr><th>Uptime</th><td>${formatUptime(device.uptime)}</td></tr>
                    </table>

                    <h6 class="mt-4"><i class="bi bi-broadcast"></i> Optical Information</h6>
                    <table class="table table-sm table-bordered">
                        <tr><th width="40%">Rx Power</th><td>${device.rx_power} dBm</td></tr>
                        <tr><th>Temperature</th><td>${device.temperature}°C</td></tr>
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <h6><i class="bi bi-ethernet"></i> Network Information</h6>
                    <table class="table table-sm table-bordered">
                        <tr>
                            <th width="20%">IP TR069</th>
                            <td>${makeIPClickable(extractIP(device.ip_tr069))}</td>
                        </tr>
                        <tr>
                            <th>WiFi SSID</th>
                            <td>
                                ${device.wifi_ssid}
                                <button class="btn btn-sm btn-warning ms-2" onclick="openEditWiFiModal('${device.device_id}', '${device.wifi_ssid.replace(/'/g, "\\'")}', '${device.wifi_password.replace(/'/g, "\\'")}')">
                                    <i class="bi bi-pencil"></i> Edit WiFi
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <th>WiFi Password</th>
                            <td>
                                <span id="wifi-pass-hidden">********</span>
                                <span id="wifi-pass-shown" style="display:none;">${device.wifi_password}</span>
                                <button class="btn btn-sm btn-link" onclick="togglePassword()">
                                    <i id="toggle-icon" class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <th>Full TR069 URL</th>
                            <td><small>${device.ip_tr069}</small></td>
                        </tr>
                    </table>
                </div>
            </div>
        `;

        // Populate Topology Location Tab
        document.getElementById('topology-content').innerHTML = renderTopologyLocationTab(locationResult);

        // Populate WAN Connections Tab
        document.getElementById('wan-content').innerHTML = renderWANDetailsTab(device.wan_details);

        // Populate DHCP Server Tab
        document.getElementById('dhcp-content').innerHTML = renderDHCPServerTab(device.dhcp_server);

        // Populate Connected Devices Tab
        document.getElementById('devices-content').innerHTML = renderConnectedDevicesTab(device.connected_devices);

        // Hide loading, show tabs
        document.getElementById('loading-spinner').style.display = 'none';
        document.getElementById('deviceTabs').style.display = 'flex';
        document.getElementById('deviceTabContent').style.display = 'block';
    } else {
        // Show error in loading area
        document.getElementById('loading-spinner').innerHTML = '<div class="alert alert-danger">Failed to load device details</div>';
    }
}

function extractIP(ipString) {
    if (!ipString || ipString === 'N/A') return 'N/A';
    const match = ipString.match(/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/);
    return match ? match[1] : 'N/A';
}

function makeIPClickable(ip) {
    if (!ip || ip === 'N/A' || ip === '0.0.0.0') {
        return ip;
    }

    // Check if it's a valid IP address
    const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
    if (!ipRegex.test(ip)) {
        return ip;
    }

    return `<a href="http://${ip}" target="_blank" rel="noopener noreferrer" title="Open http://${ip}">${ip}</a>`;
}

function renderTopologyLocationTab(locationResult) {
    if (!locationResult || !locationResult.success) {
        return '<div class="alert alert-info"><i class="bi bi-info-circle"></i> Unable to load topology location</div>';
    }

    // ONU not found in map
    if (!locationResult.location || !locationResult.location.found) {
        return `
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Topology Location:</strong> This device has not been added to the network map yet.
                <a href="/map.php" class="alert-link">Add to map</a>
            </div>
        `;
    }

    const loc = locationResult.location;
    let html = '';

    // Build hierarchy path
    let pathParts = [];
    if (loc.server && loc.server.name) {
        pathParts.push(`<span class="badge bg-secondary">${loc.server.name}</span>`);
    }
    if (loc.olt && loc.olt.name) {
        pathParts.push(`<span class="badge bg-info">${loc.olt.name}</span>`);
    }
    if (loc.odc && loc.odc.name) {
        pathParts.push(`<span class="badge bg-warning text-dark">${loc.odc.name}</span>`);
    }
    if (loc.odp && loc.odp.name) {
        pathParts.push(`<span class="badge bg-success">${loc.odp.name}</span>`);
    }
    if (loc.onu && loc.onu.name) {
        pathParts.push(`<span class="badge bg-primary">${loc.onu.name}</span>`);
    }

    if (pathParts.length > 0) {
        html += '<div class="mb-3">';
        html += '<h6><i class="bi bi-diagram-3"></i> Hierarchy Path</h6>';
        html += '<div class="p-3 bg-light rounded">' + pathParts.join(' <i class="bi bi-arrow-right"></i> ') + '</div>';
        html += '</div>';
    }

    html += '<table class="table table-bordered">';

    // ODP Information
    if (loc.odp && loc.odp.name) {
        html += `
            <tr>
                <th width="30%"><i class="bi bi-box"></i> ODP</th>
                <td>
                    <strong>${loc.odp.name}</strong>
                    <a href="/map.php?focus_type=odp&focus_id=${loc.odp.id}" class="btn btn-sm btn-outline-primary ms-2" target="_blank">
                        <i class="bi bi-map"></i> View on Map
                    </a>
                </td>
            </tr>
        `;
    }

    // Port Number
    if (loc.onu && loc.onu.port && loc.onu.port !== 'N/A') {
        html += `
            <tr>
                <th><i class="bi bi-plug"></i> Port Number</th>
                <td><span class="badge bg-info">Port ${loc.onu.port}</span></td>
            </tr>
        `;
    }

    // ODC Information
    if (loc.odc && loc.odc.name) {
        html += `
            <tr>
                <th><i class="bi bi-building"></i> ODC</th>
                <td>
                    <strong>${loc.odc.name}</strong>
                    <a href="/map.php?focus_type=odc&focus_id=${loc.odc.id}" class="btn btn-sm btn-outline-warning ms-2" target="_blank">
                        <i class="bi bi-map"></i> View on Map
                    </a>
                </td>
            </tr>
        `;
    }

    // OLT Information
    if (loc.olt && loc.olt.name) {
        html += `
            <tr>
                <th><i class="bi bi-hdd-network"></i> OLT</th>
                <td><strong>${loc.olt.name}</strong></td>
            </tr>
        `;
    }

    // Coordinates and Google Maps
    if (loc.onu && loc.onu.lat && loc.onu.lng) {
        const lat = parseFloat(loc.onu.lat);
        const lng = parseFloat(loc.onu.lng);

        // Only show if coordinates are not 0,0
        if (lat !== 0 && lng !== 0) {
            const googleMapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;

            html += `
                <tr>
                    <th><i class="bi bi-geo-alt"></i> Coordinates</th>
                    <td>
                        <code>${lat.toFixed(6)}, ${lng.toFixed(6)}</code>
                        <a href="${googleMapsUrl}" class="btn btn-sm btn-success ms-2" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-globe"></i> View on Google Maps
                        </a>
                        <a href="/map.php?focus_type=onu&focus_id=${loc.onu.id}" class="btn btn-sm btn-outline-primary ms-1" target="_blank">
                            <i class="bi bi-map"></i> View on Network Map
                        </a>
                    </td>
                </tr>
            `;
        }
    }

    html += '</table>';
    return html;
}

function renderTopologyLocation(locationResult) {
    if (!locationResult || !locationResult.success) {
        return '';
    }

    // ONU not found in map
    if (!locationResult.location || !locationResult.location.found) {
        return `
            <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle"></i>
                <strong>Topology Location:</strong> This device has not been added to the network map yet.
                <a href="/map.php" class="alert-link">Add to map</a>
            </div>
        `;
    }

    const loc = locationResult.location;
    let html = '<div class="card mb-3 border-primary">';
    html += '<div class="card-header bg-primary text-white">';
    html += '<i class="bi bi-diagram-3"></i> <strong>Network Topology Location</strong>';
    html += '</div>';
    html += '<div class="card-body">';

    // Build hierarchy path
    let pathParts = [];
    if (loc.server && loc.server.name) {
        pathParts.push(`<span class="badge bg-secondary">${loc.server.name}</span>`);
    }
    if (loc.olt && loc.olt.name) {
        pathParts.push(`<span class="badge bg-info">${loc.olt.name}</span>`);
    }
    if (loc.odc && loc.odc.name) {
        pathParts.push(`<span class="badge bg-warning text-dark">${loc.odc.name}</span>`);
    }
    if (loc.odp && loc.odp.name) {
        pathParts.push(`<span class="badge bg-success">${loc.odp.name}</span>`);
    }
    if (loc.onu && loc.onu.name) {
        pathParts.push(`<span class="badge bg-primary">${loc.onu.name}</span>`);
    }

    if (pathParts.length > 0) {
        html += '<div class="mb-3">';
        html += '<strong>Hierarchy Path:</strong><br>';
        html += pathParts.join(' <i class="bi bi-arrow-right"></i> ');
        html += '</div>';
    }

    html += '<table class="table table-sm table-bordered mb-0">';

    // ODP Information
    if (loc.odp && loc.odp.name) {
        html += `
            <tr>
                <th width="30%"><i class="bi bi-box"></i> ODP</th>
                <td>
                    <strong>${loc.odp.name}</strong>
                    <a href="/map.php?focus_type=odp&focus_id=${loc.odp.id}" class="btn btn-sm btn-outline-primary ms-2" target="_blank">
                        <i class="bi bi-map"></i> View on Map
                    </a>
                </td>
            </tr>
        `;
    }

    // Port Number
    if (loc.onu && loc.onu.port && loc.onu.port !== 'N/A') {
        html += `
            <tr>
                <th><i class="bi bi-plug"></i> Port Number</th>
                <td><span class="badge bg-info">Port ${loc.onu.port}</span></td>
            </tr>
        `;
    }

    // ODC Information
    if (loc.odc && loc.odc.name) {
        html += `
            <tr>
                <th><i class="bi bi-building"></i> ODC</th>
                <td>
                    <strong>${loc.odc.name}</strong>
                    <a href="/map.php?focus_type=odc&focus_id=${loc.odc.id}" class="btn btn-sm btn-outline-warning ms-2" target="_blank">
                        <i class="bi bi-map"></i> View on Map
                    </a>
                </td>
            </tr>
        `;
    }

    // OLT Information
    if (loc.olt && loc.olt.name) {
        html += `
            <tr>
                <th><i class="bi bi-hdd-network"></i> OLT</th>
                <td><strong>${loc.olt.name}</strong></td>
            </tr>
        `;
    }

    // Coordinates and Google Maps
    if (loc.onu && loc.onu.lat && loc.onu.lng) {
        const lat = parseFloat(loc.onu.lat);
        const lng = parseFloat(loc.onu.lng);

        // Only show if coordinates are not 0,0
        if (lat !== 0 && lng !== 0) {
            const googleMapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;

            html += `
                <tr>
                    <th><i class="bi bi-geo-alt"></i> Coordinates</th>
                    <td>
                        <code>${lat.toFixed(6)}, ${lng.toFixed(6)}</code>
                        <a href="${googleMapsUrl}" class="btn btn-sm btn-success ms-2" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-globe"></i> View on Google Maps
                        </a>
                        <a href="/map.php?focus_type=onu&focus_id=${loc.onu.id}" class="btn btn-sm btn-outline-primary ms-1" target="_blank">
                            <i class="bi bi-map"></i> View on Network Map
                        </a>
                    </td>
                </tr>
            `;
        }
    }

    html += '</table>';
    html += '</div>';
    html += '</div>';

    return html;
}

function renderWANDetailsTab(wanDetails) {
    if (!wanDetails || wanDetails.length === 0) {
        return `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6><i class="bi bi-globe"></i> WAN Connection Details</h6>
                <button class="btn btn-sm btn-success" onclick="openAddWANModal()"><i class="bi bi-plus-lg"></i> Add WAN Connection</button>
            </div>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No WAN connections configured on this device.
                <button class="btn btn-sm btn-success ms-2" onclick="openAddWANModal()">
                    <i class="bi bi-plus-lg"></i> Add First Connection
                </button>
            </div>
        `;
    }

    let html = '<div class="d-flex justify-content-between align-items-center mb-3">';
    html += '<h6><i class="bi bi-globe"></i> WAN Connection Details</h6>';
    html += '<button class="btn btn-sm btn-success" onclick="openAddWANModal()"><i class="bi bi-plus-lg"></i> Add WAN Connection</button>';
    html += '</div>';

    wanDetails.forEach((wan, index) => {
        const statusBadge = wan.status === 'Connected' ?
            '<span class="badge online">Connected</span>' :
            '<span class="badge offline">Disconnected</span>';

        // Check if this is a bridge connection
        const isBridge = wan.connection_type && (
            wan.connection_type.includes('Bridge') ||
            wan.connection_type.includes('Bridged')
        );

        // Extract VLAN ID from connection name
        const vlanMatch = wan.name.match(/VID[_-]?(\d+)/i);
        const vlanId = vlanMatch ? vlanMatch[1] : null;

        // Check if this is TR069 connection
        const isTR069 = (wan.service_list && (wan.service_list.toUpperCase().includes('TR069') || wan.service_list.toUpperCase().includes('CWMP'))) ||
                        (wan.name && (wan.name.toUpperCase().includes('TR069') || wan.name.toUpperCase().includes('CWMP')));

        html += `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${wan.name}</strong>
                            <span class="badge bg-info ms-2">${wan.type}</span>
                            ${statusBadge}
                            ${isBridge ? '<span class="badge bg-secondary ms-2">Bridge Mode</span>' : ''}
                            ${isTR069 ? '<span class="badge bg-danger ms-2"><i class="bi bi-exclamation-triangle"></i> TR069</span>' : ''}
                        </div>
                        <div>
                            <button class="btn btn-sm btn-warning" onclick='openEditWANModal(${JSON.stringify(wan)})'>
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick='openDeleteWANModal(${JSON.stringify(wan)})'>
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">`;

        // Build table content (same as before)
        if (isBridge) {
            html += `
                    <table class="table table-sm table-bordered mb-0">
                        <tr>
                            <th width="30%">Connection Type</th>
                            <td>${wan.connection_type}</td>
                        </tr>`;

            if (vlanId) {
                html += `
                        <tr>
                            <th>VLAN ID</th>
                            <td>${vlanId}</td>
                        </tr>`;
            }

            if (wan.binding && wan.binding !== 'N/A') {
                html += `
                        <tr>
                            <th>Bound to</th>
                            <td><span class="badge bg-primary">${wan.binding}</span></td>
                        </tr>`;
            }

            html += `
                    </table>`;
        } else {
            html += `
                    <table class="table table-sm table-bordered mb-0">
                        <tr>
                            <th width="30%">Connection Type</th>
                            <td>${wan.connection_type}</td>
                        </tr>`;

            if (vlanId) {
                html += `
                        <tr>
                            <th>VLAN ID</th>
                            <td>${vlanId}</td>
                        </tr>`;
            }

            if (wan.binding && wan.binding !== 'N/A') {
                html += `
                        <tr>
                            <th>Bound to</th>
                            <td><span class="badge bg-primary">${wan.binding}</span></td>
                        </tr>`;
            }

            if (wan.external_ip && wan.external_ip !== 'N/A' && wan.external_ip !== '0.0.0.0') {
                html += `
                        <tr>
                            <th>External IP</th>
                            <td>${makeIPClickable(wan.external_ip)}</td>
                        </tr>`;
            }

            if (wan.gateway && wan.gateway !== 'N/A' && wan.gateway !== '0.0.0.0') {
                html += `
                        <tr>
                            <th>Gateway</th>
                            <td>${makeIPClickable(wan.gateway)}</td>
                        </tr>`;
            }

            if (wan.subnet_mask && wan.subnet_mask !== 'N/A') {
                html += `
                        <tr>
                            <th>Subnet Mask</th>
                            <td>${wan.subnet_mask}</td>
                        </tr>`;
            }

            if (wan.dns_servers && wan.dns_servers !== 'N/A' && wan.dns_servers !== '') {
                html += `
                        <tr>
                            <th>DNS Servers</th>
                            <td>${wan.dns_servers}</td>
                        </tr>`;
            }

            if (wan.mac_address && wan.mac_address !== 'N/A' && wan.mac_address !== '00:00:00:00:00:00') {
                html += `
                        <tr>
                            <th>MAC Address</th>
                            <td>${wan.mac_address}</td>
                        </tr>`;
            }

            if (wan.type === 'PPPoE') {
                if (wan.username && wan.username !== 'N/A' && wan.username !== '') {
                    html += `
                        <tr>
                            <th>Username</th>
                            <td>${wan.username}</td>
                        </tr>`;
                }

                if (wan.last_error && wan.last_error !== 'N/A') {
                    html += `
                        <tr>
                            <th>Last Error</th>
                            <td>${wan.last_error}</td>
                        </tr>`;
                }

                if (wan.mru_size && wan.mru_size !== 'N/A' && wan.mru_size !== '0' && wan.mru_size !== 0) {
                    html += `
                        <tr>
                            <th>MRU Size</th>
                            <td>${wan.mru_size}</td>
                        </tr>`;
                }
            }

            if (wan.type === 'IP') {
                if (wan.addressing_type && wan.addressing_type !== 'N/A') {
                    html += `
                        <tr>
                            <th>Addressing Type</th>
                            <td>${wan.addressing_type}</td>
                        </tr>`;
                }
            }

            if (wan.uptime && wan.uptime !== 'N/A' && wan.uptime !== '0' && wan.uptime !== 0) {
                html += `
                        <tr>
                            <th>Uptime</th>
                            <td>${formatUptime(wan.uptime)}</td>
                        </tr>`;
            }

            html += `
                    </table>`;
        }

        html += `
                </div>
            </div>
        `;
    });

    return html;
}

function renderWANDetails(wanDetails) {
    if (!wanDetails || wanDetails.length === 0) {
        return '';
    }

    let html = '<div class="row mt-3"><div class="col-md-12">';
    html += '<div class="d-flex justify-content-between align-items-center mb-2">';
    html += '<h6><i class="bi bi-globe"></i> WAN Connection Details</h6>';
    html += '<button class="btn btn-sm btn-success" onclick="openAddWANModal()"><i class="bi bi-plus-lg"></i> Add WAN Connection</button>';
    html += '</div>';

    wanDetails.forEach((wan, index) => {
        const statusBadge = wan.status === 'Connected' ?
            '<span class="badge online">Connected</span>' :
            '<span class="badge offline">Disconnected</span>';

        // Check if this is a bridge connection
        const isBridge = wan.connection_type && (
            wan.connection_type.includes('Bridge') ||
            wan.connection_type.includes('Bridged')
        );

        // Extract VLAN ID from connection name (e.g., "2_INTERNET_B_VID_20" -> "20")
        const vlanMatch = wan.name.match(/VID[_-]?(\d+)/i);
        const vlanId = vlanMatch ? vlanMatch[1] : null;

        // Check if this is TR069 connection
        const isTR069 = (wan.service_list && (wan.service_list.toUpperCase().includes('TR069') || wan.service_list.toUpperCase().includes('CWMP'))) ||
                        (wan.name && (wan.name.toUpperCase().includes('TR069') || wan.name.toUpperCase().includes('CWMP')));

        html += `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${wan.name}</strong>
                            <span class="badge bg-info ms-2">${wan.type}</span>
                            ${statusBadge}
                            ${isBridge ? '<span class="badge bg-secondary ms-2">Bridge Mode</span>' : ''}
                            ${isTR069 ? '<span class="badge bg-danger ms-2"><i class="bi bi-exclamation-triangle"></i> TR069</span>' : ''}
                        </div>
                        <div>
                            <button class="btn btn-sm btn-warning" onclick='openEditWANModal(${JSON.stringify(wan)})'>
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick='openDeleteWANModal(${JSON.stringify(wan)})'>
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">`;

        // For bridge connections, show minimal info
        if (isBridge) {
            html += `
                    <table class="table table-sm table-bordered mb-0">
                        <tr>
                            <th width="30%">Connection Type</th>
                            <td>${wan.connection_type}</td>
                        </tr>`;

            if (vlanId) {
                html += `
                        <tr>
                            <th>VLAN ID</th>
                            <td>${vlanId}</td>
                        </tr>`;
            }

            if (wan.binding && wan.binding !== 'N/A') {
                html += `
                        <tr>
                            <th>Bound to</th>
                            <td><span class="badge bg-primary">${wan.binding}</span></td>
                        </tr>`;
            }

            html += `
                    </table>`;
        } else {
            // For routed connections, show all available info
            html += `
                    <table class="table table-sm table-bordered mb-0">
                        <tr>
                            <th width="30%">Connection Type</th>
                            <td>${wan.connection_type}</td>
                        </tr>`;

            if (vlanId) {
                html += `
                        <tr>
                            <th>VLAN ID</th>
                            <td>${vlanId}</td>
                        </tr>`;
            }

            if (wan.binding && wan.binding !== 'N/A') {
                html += `
                        <tr>
                            <th>Bound to</th>
                            <td><span class="badge bg-primary">${wan.binding}</span></td>
                        </tr>`;
            }

            // Show External IP if available
            if (wan.external_ip && wan.external_ip !== 'N/A' && wan.external_ip !== '0.0.0.0') {
                html += `
                        <tr>
                            <th>External IP</th>
                            <td>${makeIPClickable(wan.external_ip)}</td>
                        </tr>`;
            }

            // Show Gateway if available
            if (wan.gateway && wan.gateway !== 'N/A' && wan.gateway !== '0.0.0.0') {
                html += `
                        <tr>
                            <th>Gateway</th>
                            <td>${makeIPClickable(wan.gateway)}</td>
                        </tr>`;
            }

            // Show Subnet Mask if available
            if (wan.subnet_mask && wan.subnet_mask !== 'N/A') {
                html += `
                        <tr>
                            <th>Subnet Mask</th>
                            <td>${wan.subnet_mask}</td>
                        </tr>`;
            }

            // Show DNS Servers if available
            if (wan.dns_servers && wan.dns_servers !== 'N/A' && wan.dns_servers !== '') {
                html += `
                        <tr>
                            <th>DNS Servers</th>
                            <td>${wan.dns_servers}</td>
                        </tr>`;
            }

            // Show MAC Address if available
            if (wan.mac_address && wan.mac_address !== 'N/A' && wan.mac_address !== '00:00:00:00:00:00') {
                html += `
                        <tr>
                            <th>MAC Address</th>
                            <td>${wan.mac_address}</td>
                        </tr>`;
            }

            // PPPoE specific fields
            if (wan.type === 'PPPoE') {
                if (wan.username && wan.username !== 'N/A' && wan.username !== '') {
                    html += `
                        <tr>
                            <th>Username</th>
                            <td>${wan.username}</td>
                        </tr>`;
                }

                if (wan.last_error && wan.last_error !== 'N/A') {
                    html += `
                        <tr>
                            <th>Last Error</th>
                            <td>${wan.last_error}</td>
                        </tr>`;
                }

                if (wan.mru_size && wan.mru_size !== 'N/A' && wan.mru_size !== '0' && wan.mru_size !== 0) {
                    html += `
                        <tr>
                            <th>MRU Size</th>
                            <td>${wan.mru_size}</td>
                        </tr>`;
                }
            }

            // IP Connection specific fields
            if (wan.type === 'IP') {
                if (wan.addressing_type && wan.addressing_type !== 'N/A') {
                    html += `
                        <tr>
                            <th>Addressing Type</th>
                            <td>${wan.addressing_type}</td>
                        </tr>`;
                }
            }

            // Show uptime if available and not 0
            if (wan.uptime && wan.uptime !== 'N/A' && wan.uptime !== '0' && wan.uptime !== 0) {
                html += `
                        <tr>
                            <th>Uptime</th>
                            <td>${formatUptime(wan.uptime)}</td>
                        </tr>`;
            }

            html += `
                    </table>`;
        }

        html += `
                </div>
            </div>
        `;
    });

    html += '</div></div>';
    return html;
}

function renderDHCPServerTab(dhcpServer) {
    if (!dhcpServer) {
        return '<div class="alert alert-info"><i class="bi bi-info-circle"></i> DHCP server not supported on this device</div>';
    }

    let html = '<div class="d-flex justify-content-between align-items-center mb-3">';
    html += '<h6><i class="bi bi-router"></i> DHCP Server Configuration</h6>';
    html += `<button class="btn btn-sm btn-primary" onclick='openEditDHCPModal(${JSON.stringify(dhcpServer)})'><i class="bi bi-pencil"></i> Edit DHCP</button>`;
    html += '</div>';

    const dhcpEnabled = dhcpServer.enabled === true || dhcpServer.enabled === 'true';
    const statusBadge = dhcpEnabled ?
        '<span class="badge online">Enabled</span>' :
        '<span class="badge offline">Disabled</span>';

    html += `
        <div class="card">
            <div class="card-header bg-light">
                <strong>DHCP Server Status</strong>
                ${statusBadge}
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered mb-0">
                    <tr>
                        <th width="30%">DHCP Server</th>
                        <td>${dhcpEnabled ? 'Enabled' : 'Disabled'}</td>
                    </tr>`;

    if (dhcpEnabled || dhcpServer.min_address !== 'N/A') {
        html += `
                    <tr>
                        <th>IP Address Pool Start</th>
                        <td>${dhcpServer.min_address}</td>
                    </tr>
                    <tr>
                        <th>IP Address Pool End</th>
                        <td>${dhcpServer.max_address || 'N/A'}</td>
                    </tr>
                    <tr>
                        <th>Subnet Mask</th>
                        <td>${dhcpServer.subnet_mask || 'N/A'}</td>
                    </tr>
                    <tr>
                        <th>Default Gateway</th>
                        <td>${makeIPClickable(dhcpServer.gateway || 'N/A')}</td>
                    </tr>
                    <tr>
                        <th>DNS Servers</th>
                        <td>${dhcpServer.dns_servers || 'N/A'}</td>
                    </tr>
                    <tr>
                        <th>Lease Time</th>
                        <td>${dhcpServer.lease_time ? formatUptime(dhcpServer.lease_time) : 'N/A'}</td>
                    </tr>`;
    }

    html += `
                </table>
            </div>
        </div>
    `;

    return html;
}

function renderDHCPServer(dhcpServer) {
    if (!dhcpServer) {
        return '';
    }

    let html = '<div class="row mt-3"><div class="col-md-12">';
    html += '<div class="d-flex justify-content-between align-items-center mb-2">';
    html += '<h6><i class="bi bi-router"></i> DHCP Server Configuration</h6>';
    html += `<button class="btn btn-sm btn-primary" onclick='openEditDHCPModal(${JSON.stringify(dhcpServer)})'><i class="bi bi-pencil"></i> Edit DHCP</button>`;
    html += '</div>';

    const dhcpEnabled = dhcpServer.enabled === true || dhcpServer.enabled === 'true';
    const statusBadge = dhcpEnabled ?
        '<span class="badge online">Enabled</span>' :
        '<span class="badge offline">Disabled</span>';

    html += `
        <div class="card">
            <div class="card-header bg-light">
                <strong>DHCP Server Status</strong>
                ${statusBadge}
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered mb-0">
                    <tr>
                        <th width="30%">DHCP Server</th>
                        <td>${dhcpEnabled ? 'Enabled' : 'Disabled'}</td>
                    </tr>`;

    if (dhcpEnabled && dhcpServer.min_address) {
        html += `
                    <tr>
                        <th>IP Address Pool Start</th>
                        <td>${dhcpServer.min_address}</td>
                    </tr>
                    <tr>
                        <th>IP Address Pool End</th>
                        <td>${dhcpServer.max_address || 'N/A'}</td>
                    </tr>
                    <tr>
                        <th>Subnet Mask</th>
                        <td>${dhcpServer.subnet_mask || 'N/A'}</td>
                    </tr>
                    <tr>
                        <th>Default Gateway</th>
                        <td>${makeIPClickable(dhcpServer.gateway || 'N/A')}</td>
                    </tr>
                    <tr>
                        <th>DNS Servers</th>
                        <td>${dhcpServer.dns_servers || 'N/A'}</td>
                    </tr>
                    <tr>
                        <th>Lease Time</th>
                        <td>${dhcpServer.lease_time ? formatUptime(dhcpServer.lease_time) : 'N/A'}</td>
                    </tr>`;
    }

    html += `
                </table>
            </div>
        </div>
    `;

    html += '</div></div>';
    return html;
}

function renderConnectedDevicesTab(connectedDevices) {
    if (!connectedDevices || connectedDevices.length === 0) {
        return `
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No devices currently connected to this ONU
            </div>
        `;
    }

    let html = '<h6><i class="bi bi-hdd-network"></i> Connected Devices <span class="badge bg-primary">' + connectedDevices.length + '</span></h6>';

    html += `
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">#</th>
                        <th width="25%"><i class="bi bi-pc-display"></i> Device Name</th>
                        <th width="20%"><i class="bi bi-hdd-network"></i> IP Address</th>
                        <th width="20%"><i class="bi bi-ethernet"></i> MAC Address</th>
                        <th width="15%" class="text-center"><i class="bi bi-wifi"></i> Connection Type</th>
                        <th width="15%" class="text-center"><i class="bi bi-check-circle"></i> Status</th>
                    </tr>
                </thead>
                <tbody>
    `;

    connectedDevices.forEach((device, index) => {
        // Determine connection type icon
        let connectionIcon = 'bi-ethernet';
        let connectionBadge = 'bg-secondary';
        if (device.interface_type === 'WiFi') {
            connectionIcon = 'bi-wifi';
            connectionBadge = 'bg-info';
        } else if (device.interface_type === 'Ethernet') {
            connectionIcon = 'bi-ethernet';
            connectionBadge = 'bg-success';
        }

        // Determine status badge
        const statusBadge = device.active ?
            '<span class="badge online">Active</span>' :
            '<span class="badge offline">Inactive</span>';

        // Display vendor name if available, otherwise use hostname
        const displayName = device.vendor || device.hostname || 'Unknown Device';

        html += `
            <tr>
                <td class="text-center">${index + 1}</td>
                <td>
                    <i class="bi bi-pc-display text-primary"></i> <strong>${displayName}</strong>
                    ${device.hostname && device.vendor && device.hostname !== device.vendor ? `<br><small class="text-muted">Hostname: ${device.hostname}</small>` : ''}
                </td>
                <td>${makeIPClickable(device.ip_address)}</td>
                <td><code>${device.mac_address}</code></td>
                <td class="text-center">
                    <span class="badge ${connectionBadge}">
                        <i class="${connectionIcon}"></i> ${device.interface_type}
                    </span>
                </td>
                <td class="text-center">${statusBadge}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    return html;
}

function renderConnectedDevices(connectedDevices) {
    if (!connectedDevices || connectedDevices.length === 0) {
        return `
            <div class="row mt-3">
                <div class="col-md-12">
                    <h6><i class="bi bi-hdd-network"></i> Connected Devices <span class="badge bg-secondary">0</span></h6>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No devices currently connected to this ONU
                    </div>
                </div>
            </div>
        `;
    }

    let html = '<div class="row mt-3"><div class="col-md-12">';
    html += `<h6><i class="bi bi-hdd-network"></i> Connected Devices <span class="badge bg-primary">${connectedDevices.length}</span></h6>`;

    html += `
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="text-center">#</th>
                        <th width="25%"><i class="bi bi-pc-display"></i> Device Name</th>
                        <th width="20%"><i class="bi bi-hdd-network"></i> IP Address</th>
                        <th width="20%"><i class="bi bi-ethernet"></i> MAC Address</th>
                        <th width="15%" class="text-center"><i class="bi bi-wifi"></i> Connection Type</th>
                        <th width="15%" class="text-center"><i class="bi bi-check-circle"></i> Status</th>
                    </tr>
                </thead>
                <tbody>
    `;

    connectedDevices.forEach((device, index) => {
        // Determine connection type icon
        let connectionIcon = 'bi-ethernet';
        let connectionBadge = 'bg-secondary';
        if (device.interface_type === 'WiFi') {
            connectionIcon = 'bi-wifi';
            connectionBadge = 'bg-info';
        } else if (device.interface_type === 'Ethernet') {
            connectionIcon = 'bi-ethernet';
            connectionBadge = 'bg-success';
        }

        // Determine status badge
        const statusBadge = device.active ?
            '<span class="badge online">Active</span>' :
            '<span class="badge offline">Inactive</span>';

        // Display vendor name if available, otherwise use hostname
        const displayName = device.vendor || device.hostname || 'Unknown Device';

        html += `
            <tr>
                <td class="text-center">${index + 1}</td>
                <td>
                    <i class="bi bi-pc-display text-primary"></i> <strong>${displayName}</strong>
                    ${device.hostname && device.vendor && device.hostname !== device.vendor ? `<br><small class="text-muted">Hostname: ${device.hostname}</small>` : ''}
                </td>
                <td>${makeIPClickable(device.ip_address)}</td>
                <td><code>${device.mac_address}</code></td>
                <td class="text-center">
                    <span class="badge ${connectionBadge}">
                        <i class="${connectionIcon}"></i> ${device.interface_type}
                    </span>
                </td>
                <td class="text-center">${statusBadge}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    html += '</div></div>';
    return html;
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
    const modal = new bootstrap.Modal(document.getElementById('summonModal'), {
        backdrop: false
    });
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

function openEditWiFiModal(deviceId, currentSsid, currentPassword) {
    // Set form values
    document.getElementById('edit-device-id').value = deviceId;
    document.getElementById('edit-wifi-ssid').value = currentSsid;
    document.getElementById('edit-wifi-password').value = currentPassword;
    document.getElementById('edit-wlan-index').value = '1'; // Default to WLAN 1

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editWiFiModal'), {
        backdrop: false
    });
    modal.show();
}

function toggleEditPassword() {
    const passwordField = document.getElementById('edit-wifi-password');
    const icon = document.getElementById('edit-toggle-icon');

    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        passwordField.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function togglePasswordField() {
    const securityMode = document.getElementById('edit-security-mode').value;
    const passwordFieldGroup = document.getElementById('password-field-group');
    const passwordField = document.getElementById('edit-wifi-password');

    if (securityMode === 'None') {
        // Hide password field for Open network
        passwordFieldGroup.style.display = 'none';
        passwordField.removeAttribute('required');
        passwordField.value = ''; // Clear password value
    } else {
        // Show password field for secured network
        passwordFieldGroup.style.display = 'block';
        passwordField.setAttribute('required', 'required');
    }
}

async function confirmUpdateWiFi() {
    const form = document.getElementById('editWiFiForm');

    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Get form values
    const deviceId = document.getElementById('edit-device-id').value;
    const wifiSsid = document.getElementById('edit-wifi-ssid').value.trim();
    const securityMode = document.getElementById('edit-security-mode').value;
    const wifiPassword = document.getElementById('edit-wifi-password').value;
    const wlanIndex = document.getElementById('edit-wlan-index').value;

    // Validate SSID length
    if (wifiSsid.length < 1 || wifiSsid.length > 32) {
        showToast('WiFi SSID harus antara 1-32 karakter', 'danger');
        return;
    }

    // Validate password length only if security mode is not Open
    if (securityMode !== 'None') {
        if (wifiPassword.length < 8 || wifiPassword.length > 63) {
            showToast('WiFi Password harus antara 8-63 karakter', 'danger');
            return;
        }
    }

    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('editWiFiModal'));
    modal.hide();

    // Show loading
    showLoading('Updating WiFi configuration...');

    try {
        const requestData = {
            device_id: deviceId,
            wifi_ssid: wifiSsid,
            security_mode: securityMode,
            wlan_index: parseInt(wlanIndex)
        };

        // Only include password if security mode is not Open
        if (securityMode !== 'None') {
            requestData.wifi_password = wifiPassword;
        }

        const result = await fetchAPI('/api/update-wifi-config.php', {
            method: 'POST',
            body: JSON.stringify(requestData)
        });

        hideLoading();

        if (result && result.success) {
            showToast(result.message || 'WiFi configuration updated successfully!', 'success');

            // Reload device detail after a short delay
            setTimeout(() => {
                loadDeviceDetail();
            }, 2000);
        } else {
            const errorMessage = result && result.message ? result.message : 'Failed to update WiFi configuration';
            showToast(errorMessage, 'danger');
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.error('WiFi update failed:', result);
            }
        }
    } catch (error) {
        hideLoading();
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.error('WiFi update error:', error);
        }
        showToast('Connection error: ' + error.message, 'danger');
    }
}

// Global variable to store WAN data for delete confirmation
let currentWANDelete = null;

// WAN Modal Functions
function openEditWANModal(wanData) {
    // Populate form
    document.getElementById('edit-wan-device-id').value = deviceId;
    document.getElementById('edit-wan-connection-index').value = wanData.connection_index || '';
    document.getElementById('edit-wan-connection-type').value = wanData.type === 'PPPoE' ? 'ppp' : 'ip';
    document.getElementById('edit-wan-name').value = wanData.name || '';
    document.getElementById('edit-wan-enable').value = wanData.status === 'Connected' ? 'true' : 'false';

    // Show/hide PPPoE fields
    const isPPPoE = wanData.type === 'PPPoE';
    document.getElementById('edit-wan-username-group').style.display = isPPPoE ? 'block' : 'none';
    document.getElementById('edit-wan-password-group').style.display = isPPPoE ? 'block' : 'none';

    if (isPPPoE) {
        document.getElementById('edit-wan-username').value = wanData.username || '';
        document.getElementById('edit-wan-password').value = '';  // Don't prefill password
    }

    // NATEnabled may not be available in all WAN data
    if (wanData.nat_enabled !== undefined) {
        document.getElementById('edit-wan-nat').value = wanData.nat_enabled ? 'true' : 'false';
    }

    // Extract VLAN from name
    const vlanMatch = wanData.name.match(/VID[_-]?(\d+)/i);
    if (vlanMatch) {
        document.getElementById('edit-wan-vlan').value = vlanMatch[1];
    }

    // Check if TR069
    const isTR069 = (wanData.service_list && (wanData.service_list.toUpperCase().includes('TR069') || wanData.service_list.toUpperCase().includes('CWMP'))) ||
                    (wanData.name && (wanData.name.toUpperCase().includes('TR069') || wanData.name.toUpperCase().includes('CWMP')));

    document.getElementById('tr069-warning').style.display = isTR069 ? 'block' : 'none';

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editWANModal'), { backdrop: false });
    modal.show();
}

async function confirmUpdateWAN() {
    const connectionIndex = document.getElementById('edit-wan-connection-index').value;
    const connectionType = document.getElementById('edit-wan-connection-type').value;
    const enable = document.getElementById('edit-wan-enable').value === 'true';
    const username = document.getElementById('edit-wan-username').value.trim();
    const password = document.getElementById('edit-wan-password').value;
    const natEnabled = document.getElementById('edit-wan-nat').value === 'true';
    const vlanId = document.getElementById('edit-wan-vlan').value;

    const parameters = {
        Enable: enable,
        NATEnabled: natEnabled
    };

    if (connectionType === 'ppp' && username) {
        parameters.Username = username;
        if (password) {
            parameters.Password = password;
        }
    }

    if (vlanId) {
        parameters['X_CT-COM_VLANID'] = parseInt(vlanId);
    }

    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('editWANModal'));
    modal.hide();

    showLoading('Updating WAN configuration...');

    const result = await fetchAPI('/api/update-wan-config.php', {
        method: 'POST',
        body: JSON.stringify({
            device_id: deviceId,
            connection_index: parseInt(connectionIndex),
            connection_type: connectionType,
            parameters: parameters
        })
    });

    hideLoading();

    if (result && result.success) {
        showToast(result.message || 'WAN configuration updated successfully!', 'success');
        setTimeout(loadDeviceDetail, 2000);
    } else {
        showToast(result.message || 'Failed to update WAN configuration', 'danger');
    }
}

function openAddWANModal() {
    // Reset form
    document.getElementById('addWANForm').reset();
    document.getElementById('add-wan-username-group').style.display = 'none';
    document.getElementById('add-wan-password-group').style.display = 'none';
    document.getElementById('add-wan-service-custom').style.display = 'none';

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('addWANModal'), { backdrop: false });
    modal.show();
}

function toggleAddWANFields() {
    const connectionType = document.getElementById('add-wan-type').value;
    const usernameGroup = document.getElementById('add-wan-username-group');
    const passwordGroup = document.getElementById('add-wan-password-group');

    if (connectionType === 'ppp') {
        usernameGroup.style.display = 'block';
        passwordGroup.style.display = 'block';
    } else {
        usernameGroup.style.display = 'none';
        passwordGroup.style.display = 'none';
    }
}

async function confirmAddWAN() {
    const form = document.getElementById('addWANForm');

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const connectionIndex = parseInt(document.getElementById('add-wan-index').value);
    const connectionType = document.getElementById('add-wan-type').value;
    const connectionName = document.getElementById('add-wan-name').value.trim();
    const username = document.getElementById('add-wan-username').value.trim();
    const password = document.getElementById('add-wan-password').value;
    const serviceList = document.getElementById('add-wan-service').value;
    const vlanId = parseInt(document.getElementById('add-wan-vlan').value);
    const natEnabled = document.getElementById('add-wan-nat').value === 'true';

    // Build parameters
    const parameters = {
        Enable: true,
        ConnectionType: connectionType === 'ppp' ? 'IP_Routed' : 'IP_Routed',
        NATEnabled: natEnabled,
        'X_CT-COM_VLANID': vlanId,
        'X_CT-COM_ServiceList': serviceList === 'CUSTOM' ? document.getElementById('add-wan-service-custom').value : serviceList
    };

    if (connectionType === 'ppp') {
        if (!username || !password) {
            showToast('Username and password are required for PPPoE connections', 'danger');
            return;
        }
        parameters.Username = username;
        parameters.Password = password;
    }

    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('addWANModal'));
    modal.hide();

    showLoading('Creating WAN connection...');

    const result = await fetchAPI('/api/add-wan-config.php', {
        method: 'POST',
        body: JSON.stringify({
            device_id: deviceId,
            connection_index: connectionIndex,
            connection_type: connectionType,
            name: connectionName,
            parameters: parameters
        })
    });

    hideLoading();

    if (result && result.success) {
        showToast(result.message || 'WAN connection created successfully!', 'success');
        setTimeout(loadDeviceDetail, 2000);
    } else {
        showToast(result.message || 'Failed to create WAN connection', 'danger');
    }
}

function openDeleteWANModal(wanData) {
    currentWANDelete = wanData;

    // Check if TR069
    const isTR069 = (wanData.service_list && (wanData.service_list.toUpperCase().includes('TR069') || wanData.service_list.toUpperCase().includes('CWMP'))) ||
                    (wanData.name && (wanData.name.toUpperCase().includes('TR069') || wanData.name.toUpperCase().includes('CWMP')));

    if (isTR069) {
        document.getElementById('delete-wan-normal-warning').style.display = 'none';
        document.getElementById('delete-wan-tr069-warning').style.display = 'block';
        document.getElementById('delete-wan-tr069-name').textContent = wanData.name;
        document.getElementById('delete-wan-tr069-service').textContent = wanData.service_list || 'N/A';
        document.getElementById('delete-wan-tr069-confirm').checked = false;
    } else {
        document.getElementById('delete-wan-normal-warning').style.display = 'block';
        document.getElementById('delete-wan-tr069-warning').style.display = 'none';
        document.getElementById('delete-wan-name').textContent = wanData.name;
        document.getElementById('delete-wan-service').textContent = wanData.service_list || 'N/A';
    }

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('deleteWANModal'), { backdrop: false });
    modal.show();
}

async function confirmDeleteWAN() {
    if (!currentWANDelete) return;

    // Check if TR069 and needs confirmation
    const isTR069 = (currentWANDelete.service_list && (currentWANDelete.service_list.toUpperCase().includes('TR069') || currentWANDelete.service_list.toUpperCase().includes('CWMP'))) ||
                    (currentWANDelete.name && (currentWANDelete.name.toUpperCase().includes('TR069') || currentWANDelete.name.toUpperCase().includes('CWMP')));

    if (isTR069 && !document.getElementById('delete-wan-tr069-confirm').checked) {
        showToast('Please confirm that you understand the risks before deleting TR069 connection', 'warning');
        return;
    }

    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteWANModal'));
    modal.hide();

    showLoading('Deleting WAN connection...');

    const result = await fetchAPI('/api/delete-wan-config.php', {
        method: 'POST',
        body: JSON.stringify({
            device_id: deviceId,
            connection_index: currentWANDelete.connection_index,
            connection_type: currentWANDelete.type === 'PPPoE' ? 'ppp' : 'ip',
            connection_name: currentWANDelete.name,
            service_list: currentWANDelete.service_list || '',
            confirm_tr069_delete: isTR069
        })
    });

    hideLoading();

    if (result && result.success) {
        showToast(result.message || 'WAN connection deleted successfully!', 'success');
        setTimeout(loadDeviceDetail, 2000);
    } else if (result && result.requires_confirmation) {
        showToast('TR069 connection deletion blocked - please confirm deletion', 'warning');
    } else {
        showToast(result.message || 'Failed to delete WAN connection', 'danger');
    }

    currentWANDelete = null;
}

// DHCP Modal Functions
function openEditDHCPModal(dhcpData) {
    // Populate form
    const dhcpEnabled = dhcpData.enabled === true || dhcpData.enabled === 'true';
    document.getElementById('edit-dhcp-enable').value = dhcpEnabled ? 'true' : 'false';
    document.getElementById('edit-dhcp-min-address').value = dhcpData.min_address || '';
    document.getElementById('edit-dhcp-max-address').value = dhcpData.max_address || '';
    document.getElementById('edit-dhcp-subnet-mask').value = dhcpData.subnet_mask || '';
    document.getElementById('edit-dhcp-gateway').value = dhcpData.gateway || '';
    document.getElementById('edit-dhcp-dns').value = dhcpData.dns_servers || '';
    document.getElementById('edit-dhcp-lease-time').value = dhcpData.lease_time || '';

    // Show/hide fields
    toggleDHCPFields();

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editDHCPModal'), { backdrop: false });
    modal.show();
}

function toggleDHCPFields() {
    const dhcpEnabled = document.getElementById('edit-dhcp-enable').value === 'true';
    document.getElementById('dhcp-config-fields').style.display = dhcpEnabled ? 'block' : 'none';
}

async function confirmUpdateDHCP() {
    const dhcpEnabled = document.getElementById('edit-dhcp-enable').value === 'true';

    const parameters = {
        DHCPServerEnable: dhcpEnabled
    };

    if (dhcpEnabled) {
        parameters.MinAddress = document.getElementById('edit-dhcp-min-address').value.trim();
        parameters.MaxAddress = document.getElementById('edit-dhcp-max-address').value.trim();
        parameters.SubnetMask = document.getElementById('edit-dhcp-subnet-mask').value.trim();
        parameters.IPRouters = document.getElementById('edit-dhcp-gateway').value.trim();
        parameters.DNSServers = document.getElementById('edit-dhcp-dns').value.trim();

        const leaseTime = document.getElementById('edit-dhcp-lease-time').value.trim();
        if (leaseTime) {
            parameters.DHCPLeaseTime = parseInt(leaseTime);
        }
    }

    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('editDHCPModal'));
    modal.hide();

    showLoading('Updating DHCP configuration...');

    const result = await fetchAPI('/api/update-dhcp-config.php', {
        method: 'POST',
        body: JSON.stringify({
            device_id: deviceId,
            parameters: parameters
        })
    });

    hideLoading();

    if (result && result.success) {
        showToast(result.message || 'DHCP configuration updated successfully!', 'success');
        setTimeout(loadDeviceDetail, 2000);
    } else {
        showToast(result.message || 'Failed to update DHCP configuration', 'danger');
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
