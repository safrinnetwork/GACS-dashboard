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
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-router"></i> Device List
                    <span id="device-stats-badges" style="margin-left: 10px;">
                        <span class="badge bg-secondary">Total [0]</span>
                        <span class="badge bg-success">Online [0]</span>
                        <span class="badge bg-danger">Offline [0]</span>
                    </span>
                </div>
                <button class="btn btn-sm btn-primary" onclick="loadDevices()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs mb-3" id="deviceTypeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="onu-tab" data-bs-toggle="tab" data-bs-target="#onu" type="button" role="tab" onclick="filterByType('onu')">
                        <i class="bi bi-wifi"></i> ONU <span class="badge bg-primary ms-1" id="count-onu">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="odp-tab" data-bs-toggle="tab" data-bs-target="#odp" type="button" role="tab" onclick="filterByType('odp')">
                        <i class="bi bi-cube"></i> ODP <span class="badge bg-primary ms-1" id="count-odp">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="odc-tab" data-bs-toggle="tab" data-bs-target="#odc" type="button" role="tab" onclick="filterByType('odc')">
                        <i class="bi bi-box"></i> ODC <span class="badge bg-primary ms-1" id="count-odc">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="olt-tab" data-bs-toggle="tab" data-bs-target="#olt" type="button" role="tab" onclick="filterByType('olt')">
                        <i class="bi bi-broadcast-pin"></i> OLT <span class="badge bg-primary ms-1" id="count-olt">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="server-tab" data-bs-toggle="tab" data-bs-target="#server" type="button" role="tab" onclick="filterByType('server')">
                        <i class="bi bi-server"></i> Server <span class="badge bg-primary ms-1" id="count-server">0</span>
                    </button>
                </li>
            </ul>
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
                    <thead id="table-header">
                        <!-- Table header will be dynamically generated based on active tab -->
                    </thead>
                    <tbody id="devices-tbody">
                        <tr>
                            <td colspan="12" class="text-center">
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

<!-- Not In Map Alert Modal -->
<div class="modal fade" id="notInMapModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-circle"></i> ONU Belum Terdaftar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-map" style="font-size: 3rem; color: var(--secondary-color);"></i>
                <h5 class="mt-3">ONU Belum Terdaftar di Map</h5>
                <p class="text-muted mb-2">Device dengan Serial Number <strong id="not-in-map-serial"></strong> belum terdaftar di Network Map.</p>
                <p class="text-muted mb-0"><small>Silakan tambahkan ONU ini ke map terlebih dahulu untuk melihat lokasi topologi.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Tutup
                </button>
                <button type="button" class="btn btn-primary" onclick="window.open('/map.php', '_blank')">
                    <i class="bi bi-map"></i> Buka Network Map
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables for devices data and sorting
let allDevices = [];
let allMapItems = []; // Store map items
let currentSortColumn = null;
let currentSortDirection = 'asc';
let currentFilterType = 'onu'; // Track current filter type (default: onu)

async function loadDevices() {
    const tbody = document.getElementById('devices-tbody');
    tbody.innerHTML = '<tr><td colspan="12" class="text-center"><div class="spinner"></div></td></tr>';

    // Load devices, map counts, and map items in parallel
    const [devicesResult, mapCountsResult, mapItemsResult] = await Promise.all([
        fetchAPI('/api/get-devices.php'),
        fetchAPI('/api/get-map-counts.php'),
        fetchAPI('/api/map-get-items.php')
    ]);

    if (devicesResult && devicesResult.success) {
        allDevices = devicesResult.devices;

        // Store map items for filtering
        if (mapItemsResult && mapItemsResult.success) {
            allMapItems = mapItemsResult.items;
        } else {
            allMapItems = [];
        }

        if (allDevices.length === 0) {
            tbody.innerHTML = '<tr><td colspan="12" class="text-center">No devices found</td></tr>';
            updateDeviceCount(0, 0);
            return;
        }

        // Reset sort state
        currentSortColumn = null;
        currentSortDirection = 'asc';
        resetSortIcons();

        // Generate initial table header for "ONU" tab (default)
        generateTableHeader('onu');

        // Filter and render ONU devices by default
        const onuDevices = allDevices.filter(device => {
            const productClass = (device.product_class || '').toLowerCase();
            return productClass.includes('f609') || productClass.includes('f660') ||
                   productClass.includes('gm219') || productClass.includes('xpon') ||
                   productClass.includes('ont') || productClass.includes('onu');
        });
        renderDevices(onuDevices);
        updateDeviceCount(onuDevices.length, onuDevices.length);
        updateDeviceStats(allDevices);

        // Update search placeholder for ONU tab (default)
        updateSearchPlaceholder('onu');

        // Update tab counts using map data
        if (mapCountsResult && mapCountsResult.success) {
            updateDeviceTypeCountsFromMap(allDevices, mapCountsResult.counts);
        } else {
            updateDeviceTypeCountsFromMap(allDevices, {});
        }
    } else {
        tbody.innerHTML = '<tr><td colspan="12" class="text-center text-danger">Failed to load devices</td></tr>';
        updateDeviceCount(0, 0);
        updateDeviceStats([]);
        updateDeviceTypeCountsFromMap([], {}); // Reset counts
    }
}

async function renderDevices(devices) {
    const tbody = document.getElementById('devices-tbody');
    tbody.innerHTML = '';

    // Determine appropriate colspan based on current filter type
    const colspan = (currentFilterType === 'onu') ? 12 : 6;

    if (devices.length === 0) {
        // If showing infrastructure items, show map items instead
        if (currentFilterType !== 'onu') {
            renderMapItems(currentFilterType);
            return;
        }
        tbody.innerHTML = `<tr><td colspan="${colspan}" class="text-center">No devices found</td></tr>`;
        return;
    }

    // Fetch map status for all devices in parallel
    const mapStatusPromises = devices.map(device =>
        fetchAPI('/api/get-onu-location.php?serial_number=' + encodeURIComponent(device.serial_number))
            .then(result => ({
                serial: device.serial_number,
                inMap: result && result.success && result.location && result.location.found,
                itemType: result?.location?.item_type || 'onu',
                itemId: result?.location?.onu?.id || result?.location?.server?.id || null
            }))
            .catch(() => ({ serial: device.serial_number, inMap: false, itemType: 'onu', itemId: null }))
    );

    const mapStatuses = await Promise.all(mapStatusPromises);
    const mapStatusMap = {};
    mapStatuses.forEach(status => {
        mapStatusMap[status.serial] = {
            inMap: status.inMap,
            itemType: status.itemType,
            itemId: status.itemId
        };
    });

    devices.forEach(device => {
        const row = document.createElement('tr');
        const ipAddress = extractIP(device.ip_tr069);
        const mapInfo = mapStatusMap[device.serial_number] || { inMap: false, itemType: 'onu', itemId: null };
        const isInMap = mapInfo.inMap;

        // Create clickable IP link if IP is valid
        let ipDisplay;
        if (ipAddress !== 'N/A' && ipAddress !== '') {
            ipDisplay = `<a href="http://${ipAddress}" target="_blank" rel="noopener noreferrer" title="Open ${ipAddress} in new tab">${ipAddress}</a>`;
        } else {
            ipDisplay = ipAddress;
        }

        // Connected clients count with badge
        const clientsCount = device.connected_devices_count || 0;
        let clientsBadge = '';
        if (clientsCount > 0) {
            clientsBadge = `<span class="badge bg-primary">${clientsCount}</span>`;
        } else {
            clientsBadge = `<span class="badge bg-secondary">0</span>`;
        }

        // RX Power badge with color based on signal strength
        const rxPower = parseFloat(device.rx_power);
        let rxBadgeClass = 'bg-secondary'; // Default for N/A
        let rxDisplay = device.rx_power;

        if (!isNaN(rxPower) && rxPower !== -999) {
            if (rxPower > -20.00) {
                rxBadgeClass = 'bg-success'; // Green: Good signal (above -20 dBm)
            } else if (rxPower >= -23.00) {
                rxBadgeClass = 'bg-warning'; // Yellow: Moderate signal (-20 to -23 dBm)
            } else {
                rxBadgeClass = 'bg-danger'; // Red: Weak signal (below -23 dBm)
            }
            rxDisplay = `<span class="badge ${rxBadgeClass}">${device.rx_power} dBm</span>`;
        } else {
            rxDisplay = `<span class="badge ${rxBadgeClass}">N/A</span>`;
        }

        // Map button - conditional based on registration status
        let mapButton;
        if (isInMap) {
            // Green button - opens map in new tab
            let mapUrl;
            if (mapInfo.itemType === 'mikrotik') {
                // For MikroTik devices, focus on server
                mapUrl = `/map.php?focus_type=server&focus_id=${mapInfo.itemId}`;
            } else {
                // For ONU devices, focus on ONU
                mapUrl = `/map.php?focus_type=onu&focus_serial=${encodeURIComponent(device.serial_number)}`;
            }
            mapButton = `<button class="btn btn-sm btn-success me-1" onclick="window.open('${mapUrl}', '_blank')" title="View on Map">
                <i class="bi bi-map"></i>
            </button>`;
        } else {
            // Gray button - shows alert
            mapButton = `<button class="btn btn-sm btn-secondary me-1" onclick="showNotInMapAlert('${encodeURIComponent(device.serial_number)}')" title="Not Registered in Map">
                <i class="bi bi-map"></i>
            </button>`;
        }

        row.innerHTML = `
            <td><a href="/device-detail.php?id=${encodeURIComponent(device.device_id)}">${device.serial_number}</a></td>
            <td>${device.mac_address}</td>
            <td data-sort-value="${device.product_class || ''}">${device.product_class || 'N/A'}</td>
            <td data-sort-value="${ipAddress}">${ipDisplay}</td>
            <td data-sort-value="${device.wifi_ssid}">${device.wifi_ssid}</td>
            <td data-sort-value="${device.pppoe_username || ''}">${device.pppoe_username || 'N/A'}</td>
            <td data-sort-value="${parseFloat(device.rx_power) || -999}">${rxDisplay}</td>
            <td data-sort-value="${parseFloat(device.temperature) || -999}">${device.temperature}°C</td>
            <td data-sort-value="${clientsCount}" class="text-center">${clientsBadge}</td>
            <td data-sort-value="${device.status}"><span class="badge ${device.status === 'online' ? 'online' : 'offline'}">${device.status.charAt(0).toUpperCase() + device.status.slice(1)}</span></td>
            <td>
                ${mapButton}
                <button class="btn btn-sm btn-primary" onclick="summonDeviceQuick('${device.device_id}')" title="Summon Device">
                    <i class="bi bi-lightning-charge"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Render map items (for infrastructure: Server, OLT, ODC, ODP)
function renderMapItems(itemType) {
    const tbody = document.getElementById('devices-tbody');
    tbody.innerHTML = '';

    let items = [];

    if (itemType === 'olt') {
        // OLT stored in Server properties, not as separate items
        // Extract OLT info from Servers that have olt_link configured
        allMapItems.forEach(item => {
            if (item.item_type === 'server' && item.properties && item.properties.olt_link) {
                items.push({
                    id: item.id,
                    name: item.properties.olt_link || 'OLT',
                    item_type: 'olt',
                    latitude: item.latitude,
                    longitude: item.longitude,
                    status: item.status,
                    server_name: item.name
                });
            }
        });
    } else {
        // Filter map items by type for other infrastructure
        items = allMapItems.filter(item => item.item_type === itemType);
    }

    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No items found</td></tr>';
        updateDeviceCount(0, 0);
        return;
    }

    items.forEach(item => {
        const row = document.createElement('tr');

        // Get status badge
        const status = item.status || 'unknown';
        let statusBadge = '';
        if (status === 'online') {
            statusBadge = '<span class="badge online">Online</span>';
        } else if (status === 'offline') {
            statusBadge = '<span class="badge offline">Offline</span>';
        } else {
            statusBadge = '<span class="badge bg-secondary">Unknown</span>';
        }

        // Format coordinates
        const lat = parseFloat(item.latitude).toFixed(6);
        const lng = parseFloat(item.longitude).toFixed(6);

        // For OLT, show server name in parentheses
        const displayName = itemType === 'olt' ? `${item.name} (${item.server_name})` : item.name;

        row.innerHTML = `
            <td>${displayName}</td>
            <td><span class="badge bg-primary">${itemType.toUpperCase()}</span></td>
            <td>${lat}</td>
            <td>${lng}</td>
            <td>${statusBadge}</td>
            <td>
                <button class="btn btn-sm btn-success" onclick="window.open('/map.php?focus_type=server&focus_id=${item.id}', '_blank')" title="View on Map">
                    <i class="bi bi-map"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });

    updateDeviceCount(items.length, items.length);
}

function updateDeviceCount(shown, total) {
    const countElement = document.getElementById('device-count');
    if (shown === total) {
        countElement.textContent = `Showing ${total} item${total !== 1 ? 's' : ''}`;
    } else {
        countElement.textContent = `Showing ${shown} of ${total} item${total !== 1 ? 's' : ''}`;
    }
}

function updateDeviceStats(devices, showStats = true) {
    const statsContainer = document.getElementById('device-stats-badges');

    if (!showStats) {
        statsContainer.innerHTML = '';
        return;
    }

    const total = devices.length;
    const online = devices.filter(d => d.status === 'online').length;
    const offline = total - online;

    statsContainer.innerHTML = `
        <span class="badge bg-secondary">Total [${total}]</span>
        <span class="badge bg-success">Online [${online}]</span>
        <span class="badge bg-danger">Offline [${offline}]</span>
    `;
}

// Update device type counts in tab badges using map data
function updateDeviceTypeCountsFromMap(devices, mapCounts) {
    // Count ONU from devices (by product_class)
    let onuCount = 0;
    devices.forEach(device => {
        const productClass = (device.product_class || '').toLowerCase();
        if (productClass.includes('f609') || productClass.includes('f660') ||
            productClass.includes('gm219') || productClass.includes('xpon') ||
            productClass.includes('ont') || productClass.includes('onu')) {
            onuCount++;
        }
    });

    const counts = {
        onu: onuCount, // From devices product_class
        odp: mapCounts.odp || 0, // From map
        odc: mapCounts.odc || 0, // From map
        olt: mapCounts.olt || 0, // From map
        server: mapCounts.server || 0 // From map
    };

    // Update badges
    document.getElementById('count-onu').textContent = counts.onu;
    document.getElementById('count-odp').textContent = counts.odp;
    document.getElementById('count-odc').textContent = counts.odc;
    document.getElementById('count-olt').textContent = counts.olt;
    document.getElementById('count-server').textContent = counts.server;
}

// Generate table header based on device type
function generateTableHeader(type) {
    const tableHeader = document.getElementById('table-header');

    if (type === 'onu') {
        // ONU devices table header (GenieACS devices)
        tableHeader.innerHTML = `
            <tr>
                <th>SN</th>
                <th>MAC</th>
                <th class="sortable" onclick="sortTable('product_class')" style="cursor: pointer;">
                    Tipe <i class="bi bi-chevron-expand sort-icon"></i>
                </th>
                <th class="sortable" onclick="sortTable('ip')" style="cursor: pointer;">
                    IP <i class="bi bi-chevron-expand sort-icon"></i>
                </th>
                <th class="sortable" onclick="sortTable('ssid')" style="cursor: pointer;">
                    SSID <i class="bi bi-chevron-expand sort-icon"></i>
                </th>
                <th class="sortable" onclick="sortTable('pppoe_username')" style="cursor: pointer;">
                    PPPoE <i class="bi bi-chevron-expand sort-icon"></i>
                </th>
                <th class="sortable" onclick="sortTable('rx_power')" style="cursor: pointer;">
                    Rx <i class="bi bi-chevron-expand sort-icon"></i>
                </th>
                <th class="sortable" onclick="sortTable('temperature')" style="cursor: pointer;">
                    Temp <i class="bi bi-chevron-expand sort-icon"></i>
                </th>
                <th class="sortable" onclick="sortTable('connected_clients')" style="cursor: pointer;">
                    Client <i class="bi bi-chevron-expand sort-icon"></i>
                </th>
                <th class="sortable" onclick="sortTable('status')" style="cursor: pointer;">
                    Status <i class="bi bi-chevron-expand sort-icon"></i>
                </th>
                <th>Action</th>
            </tr>
        `;
    } else {
        // Infrastructure items table header (Map items: Server, OLT, ODC, ODP)
        tableHeader.innerHTML = `
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        `;
    }
}

// Filter devices by type using map data
function filterByType(type) {
    currentFilterType = type;

    // Generate appropriate table header
    generateTableHeader(type);

    // Clear search box when switching tabs
    document.getElementById('search-input').value = '';

    // Update search placeholder based on tab
    updateSearchPlaceholder(type);

    // Reset sort state
    currentSortColumn = null;
    currentSortDirection = 'asc';
    resetSortIcons();

    if (type === 'onu') {
        // For ONU: filter by product_class (all ONU devices, not only registered in map)
        const filteredDevices = allDevices.filter(device => {
            const productClass = (device.product_class || '').toLowerCase();
            return productClass.includes('f609') || productClass.includes('f660') ||
                   productClass.includes('gm219') || productClass.includes('xpon') ||
                   productClass.includes('ont') || productClass.includes('onu');
        });
        renderDevices(filteredDevices);
        updateDeviceCount(filteredDevices.length, allDevices.length);
        // Show stats for ONU tab
        updateDeviceStats(filteredDevices, true);
    } else {
        // For ODP, ODC, OLT, Server: show map items
        renderMapItems(type);
        // Hide stats for infrastructure tabs
        updateDeviceStats([], false);
    }
}

function extractIP(ipString) {
    if (!ipString || ipString === 'N/A') return 'N/A';
    const match = ipString.match(/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/);
    return match ? match[1] : 'N/A';
}

// Update search placeholder based on current tab
function updateSearchPlaceholder(type) {
    const searchInput = document.getElementById('search-input');
    if (type === 'onu') {
        searchInput.placeholder = 'Search by Serial Number or MAC Address...';
    } else {
        searchInput.placeholder = 'Search by Name...';
    }
}

// Search functionality
function filterDevices() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase().trim();

    // Get devices based on current tab
    let baseDevices = allDevices;
    if (currentFilterType === 'onu') {
        // For ONU: filter by product_class (all ONU devices, not only registered in map)
        baseDevices = allDevices.filter(device => {
            const productClass = (device.product_class || '').toLowerCase();
            return productClass.includes('f609') || productClass.includes('f660') ||
                   productClass.includes('gm219') || productClass.includes('xpon') ||
                   productClass.includes('ont') || productClass.includes('onu');
        });
    } else {
        // For ODP, ODC, OLT, Server: use map data
        const mapItemDeviceIds = new Set();

        allMapItems.forEach(item => {
            if (item.item_type === currentFilterType) {
                // For Server, match by mikrotik_device_id in properties
                if (currentFilterType === 'server' && item.properties && item.properties.mikrotik_device_id) {
                    mapItemDeviceIds.add(item.properties.mikrotik_device_id);
                }
            }
        });

        // Filter devices that match map items
        baseDevices = allDevices.filter(device => {
            return mapItemDeviceIds.has(device.device_id);
        });
    }

    if (searchTerm === '') {
        if (currentFilterType === 'onu') {
            renderDevices(baseDevices);
            updateDeviceCount(baseDevices.length, allDevices.length);
            updateDeviceStats(baseDevices, true);
        } else {
            renderMapItems(currentFilterType);
            updateDeviceStats([], false);
        }
        return;
    }

    // Different search logic based on tab type
    if (currentFilterType === 'onu') {
        // ONU: search by Serial Number or MAC Address
        const filteredDevices = baseDevices.filter(device => {
            const serialNumber = (device.serial_number || '').toLowerCase();
            const macAddress = (device.mac_address || '').toLowerCase();

            return serialNumber.includes(searchTerm) || macAddress.includes(searchTerm);
        });

        renderDevices(filteredDevices);
        updateDeviceCount(filteredDevices.length, allDevices.length);
        updateDeviceStats(filteredDevices, true);
    } else {
        // Infrastructure: search by Name
        let items = [];

        if (currentFilterType === 'olt') {
            // OLT stored in Server properties
            allMapItems.forEach(item => {
                if (item.item_type === 'server' && item.properties && item.properties.olt_link) {
                    const oltName = (item.properties.olt_link || '').toLowerCase();
                    const serverName = (item.name || '').toLowerCase();

                    if (oltName.includes(searchTerm) || serverName.includes(searchTerm)) {
                        items.push({
                            id: item.id,
                            name: item.properties.olt_link || 'OLT',
                            item_type: 'olt',
                            latitude: item.latitude,
                            longitude: item.longitude,
                            status: item.status,
                            server_name: item.name
                        });
                    }
                }
            });
        } else {
            // Filter map items by type and name
            items = allMapItems.filter(item => {
                if (item.item_type !== currentFilterType) return false;
                const itemName = (item.name || '').toLowerCase();
                return itemName.includes(searchTerm);
            });
        }

        // Render filtered items manually
        const tbody = document.getElementById('devices-tbody');
        tbody.innerHTML = '';

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No items found</td></tr>';
            updateDeviceCount(0, 0);
            return;
        }

        items.forEach(item => {
            const row = document.createElement('tr');

            // Get status badge
            const status = item.status || 'unknown';
            let statusBadge = '';
            if (status === 'online') {
                statusBadge = '<span class="badge online">Online</span>';
            } else if (status === 'offline') {
                statusBadge = '<span class="badge offline">Offline</span>';
            } else {
                statusBadge = '<span class="badge bg-secondary">Unknown</span>';
            }

            // Format coordinates
            const lat = parseFloat(item.latitude).toFixed(6);
            const lng = parseFloat(item.longitude).toFixed(6);

            // For OLT, show server name in parentheses
            const displayName = currentFilterType === 'olt' ? `${item.name} (${item.server_name})` : item.name;

            row.innerHTML = `
                <td>${displayName}</td>
                <td><span class="badge bg-primary">${currentFilterType.toUpperCase()}</span></td>
                <td>${lat}</td>
                <td>${lng}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-success" onclick="window.open('/map.php?focus_type=server&focus_id=${item.id}', '_blank')" title="View on Map">
                        <i class="bi bi-map"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });

        updateDeviceCount(items.length, items.length);
    }
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
            case 'pppoe_username':
                valueA = (a.pppoe_username || '').toLowerCase();
                valueB = (b.pppoe_username || '').toLowerCase();
                break;
            case 'rx_power':
                valueA = parseFloat(a.rx_power) || -999;
                valueB = parseFloat(b.rx_power) || -999;
                break;
            case 'temperature':
                valueA = parseFloat(a.temperature) || -999;
                valueB = parseFloat(b.temperature) || -999;
                break;
            case 'connected_clients':
                valueA = parseInt(a.connected_devices_count) || 0;
                valueB = parseInt(b.connected_devices_count) || 0;
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
        'pppoe_username': 6,
        'rx_power': 7,
        'temperature': 8,
        'connected_clients': 9,
        'status': 10
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

function showNotInMapAlert(serialNumber) {
    document.getElementById('not-in-map-serial').textContent = decodeURIComponent(serialNumber);
    const modal = new bootstrap.Modal(document.getElementById('notInMapModal'), {
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
