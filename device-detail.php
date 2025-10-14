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

<script>
const deviceId = '<?php echo htmlspecialchars($deviceId, ENT_QUOTES); ?>';

async function loadDeviceDetail() {
    const content = document.getElementById('device-detail-content');
    content.innerHTML = '<div class="text-center"><div class="spinner"></div></div>';

    const result = await fetchAPI('/api/get-device-detail.php?device_id=' + encodeURIComponent(deviceId));

    if (result && result.success) {
        const device = result.device;

        // Fetch ONU location from map
        const locationResult = await fetchAPI('/api/get-onu-location.php?serial_number=' + encodeURIComponent(device.serial_number));

        // Update badge
        document.getElementById('device-id-badge').textContent = device.serial_number;

        content.innerHTML = `
            ${renderTopologyLocation(locationResult)}
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
            ${renderWANDetails(device.wan_details)}
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

function renderWANDetails(wanDetails) {
    if (!wanDetails || wanDetails.length === 0) {
        return '';
    }

    let html = '<div class="row mt-3"><div class="col-md-12">';
    html += '<h6><i class="bi bi-globe"></i> WAN Connection Details</h6>';

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

        html += `
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <strong>${wan.name}</strong>
                    <span class="badge bg-info ms-2">${wan.type}</span>
                    ${statusBadge}
                    ${isBridge ? '<span class="badge bg-secondary ms-2">Bridge Mode</span>' : ''}
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

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($genieacsConfigured): ?>
        loadDeviceDetail();
        // Auto refresh every 30 seconds
        setInterval(loadDeviceDetail, 30000);
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
