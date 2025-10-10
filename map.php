<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = 'Network Map';
$currentPage = 'map';

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
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <button class="btn btn-warning" id="toggle-pointer-btn" onclick="toggleLocationPointer()">
                        <i class="bi bi-geo-alt"></i> <span id="pointer-btn-text">Show Location Pointer</span>
                    </button>
                    <button class="btn btn-primary" onclick="showAddItemModal()">
                        <i class="bi bi-plus-lg"></i> Add Item
                    </button>
                    <button class="btn btn-success" onclick="loadMap()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button class="btn btn-info" id="auto-refresh-toggle" onclick="toggleAutoRefresh()">
                        <i class="bi bi-clock"></i> Auto-Refresh: <span id="auto-refresh-status">ON</span>
                    </button>
                    <div class="btn-group float-end">
                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleLayer('server')">
                            <i class="bi bi-server"></i> Server
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleLayer('olt')">
                            <i class="bi bi-broadcast-pin"></i> OLT
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleLayer('odc')">
                            <i class="bi bi-box"></i> ODC
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleLayer('odp')">
                            <i class="bi bi-cube"></i> ODP
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleLayer('onu')">
                            <i class="bi bi-wifi"></i> ONU
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div id="map"></div>
        </div>
    </div>
<?php endif; ?>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Network Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-add-item">
                    <div class="form-group">
                        <label>Item Type</label>
                        <select name="item_type" class="form-control" required onchange="updateItemForm(this.value)">
                            <option value="">Select Type</option>
                            <option value="server">Server</option>
                            <option value="olt">OLT</option>
                            <option value="odc">ODC</option>
                            <option value="odp">ODP</option>
                            <option value="onu">ONU</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Latitude</label>
                        <input type="number" step="0.00000001" name="latitude" class="form-control" required readonly>
                        <small class="text-muted">Koordinat otomatis terisi dari pointer di peta</small>
                    </div>
                    <div class="form-group">
                        <label>Longitude</label>
                        <input type="number" step="0.00000001" name="longitude" class="form-control" required readonly>
                        <small class="text-muted">Drag pointer di peta untuk mengubah lokasi</small>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Cara memilih lokasi:</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Klik tombol "Show Location Pointer"</strong> di atas peta untuk menampilkan marker</li>
                            <li>Klik di peta untuk memindahkan pointer</li>
                            <li>Atau drag marker pointer biru ke lokasi yang diinginkan</li>
                            <li>Koordinat otomatis terisi dari posisi pointer</li>
                        </ul>
                    </div>
                    <div id="dynamic-fields"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="addItem()">Add Item</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Network Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-edit-item">
                    <input type="hidden" name="item_id">
                    <div class="form-group">
                        <label>Item Type</label>
                        <input type="text" name="item_type" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Latitude</label>
                        <input type="number" step="0.00000001" name="latitude" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Longitude</label>
                        <input type="number" step="0.00000001" name="longitude" class="form-control" required>
                    </div>
                    <div id="edit-dynamic-fields"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="updateItem()">Update Item</button>
            </div>
        </div>
    </div>
</div>

<!-- Server Links Modal -->
<div class="modal fade" id="serverLinksModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Server Links</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-server-links">
                    <input type="hidden" name="item_id">
                    <div class="form-group">
                        <label>🌐 ISP Link (dari MikroTik Netwatch)</label>
                        <select name="isp_link" class="form-control" id="isp-link-select">
                            <option value="">No Link</option>
                        </select>
                        <small class="text-muted">Link ke ISP/Provider</small>
                    </div>
                    <div class="form-group">
                        <label>🔧 MikroTik Link (dari MikroTik Netwatch)</label>
                        <select name="mikrotik_link" class="form-control" id="mikrotik-link-select">
                            <option value="">No Link</option>
                        </select>
                        <small class="text-muted">Link ke MikroTik Router</small>
                    </div>
                    <div class="form-group">
                        <label>📡 OLT Link (dari MikroTik Netwatch)</label>
                        <select name="olt_link" class="form-control" id="olt-link-select">
                            <option value="">No Link</option>
                        </select>
                        <small class="text-muted">Link ke OLT</small>
                    </div>
                    <div class="form-group">
                        <label>⚡ PON Output Power (dBm)</label>
                        <input type="number" step="0.01" name="pon_output_power" class="form-control" placeholder="2.00">
                        <small class="text-muted">PON output power dari OLT</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveServerLinks()">Save Links</button>
            </div>
        </div>
    </div>
</div>

<style>
#map {
    height: 600px;
    width: 100%;
}

/* Custom Marker Styling */
.custom-marker {
    cursor: pointer;
    transition: all 0.3s ease;
}

.custom-marker:hover .marker-icon {
    transform: rotate(-45deg) scale(1.15);
    box-shadow: 0 6px 16px rgba(0,0,0,0.4) !important;
}

/* Pulse animation for online markers */
@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.3);
        opacity: 0.7;
    }
}

.pulse-online {
    animation: marker-bounce 0.5s ease-out;
}

@keyframes marker-bounce {
    0% {
        transform: translateY(-10px);
        opacity: 0;
    }
    50% {
        transform: translateY(5px);
    }
    100% {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Status indicator pulse */
@keyframes status-pulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    }
    50% {
        box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
    }
}

.status-indicator {
    animation: status-pulse 2s infinite !important;
}

/* Animated polyline for connections */
@keyframes dash-online {
    0% {
        stroke-dashoffset: 20;
    }
    100% {
        stroke-dashoffset: 0;
    }
}

@keyframes dash-offline {
    0% {
        stroke-dashoffset: 20;
    }
    100% {
        stroke-dashoffset: 0;
    }
}

.connection-online {
    stroke: #10b981;
    stroke-width: 3;
    stroke-dasharray: 10, 5;
    animation: dash-online 1s linear infinite;
    filter: drop-shadow(0 0 4px rgba(16, 185, 129, 0.5));
}

.connection-offline {
    stroke: #ef4444;
    stroke-width: 3;
    stroke-dasharray: 10, 5;
    animation: dash-offline 1s linear infinite;
    filter: drop-shadow(0 0 4px rgba(239, 68, 68, 0.5));
}

.connection-unknown {
    stroke: #6b7280;
    stroke-width: 2;
    stroke-dasharray: 5, 5;
    opacity: 0.5;
}

/* Leaflet popup customization */
.leaflet-popup-content-wrapper {
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
}

.leaflet-popup-tip {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Badge styling in popup */
.badge.online {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge.offline {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

/* Location Pointer Animation */
@keyframes pointer-pulse {
    0%, 100% {
        transform: rotate(-45deg) scale(1);
        box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
    }
    50% {
        transform: rotate(-45deg) scale(1.05);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.7), 0 0 0 10px rgba(102, 126, 234, 0.2);
    }
}

.location-pointer {
    transition: all 0.3s ease;
    cursor: move;
}

.location-pointer:hover {
    transform: scale(1.1);
}
</style>

<script>
let map = null;
let markers = {};
let polylines = [];
let allMapItems = []; // Global variable to store all items for chain visualization
let visibleLayers = {
    server: true,
    olt: true,
    odc: true,
    odp: true,
    onu: true
};
let autoRefreshInterval = null;
let autoRefreshEnabled = true;
let locationPointer = null; // Temporary marker for location selection
let pointerVisible = false; // Track pointer visibility state

function initMap() {
    // Default center (Indonesia)
    map = L.map('map').setView([-6.2088, 106.8456], 13);

    // Base layers
    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    });

    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
        maxZoom: 19
    });

    const hybrid = L.layerGroup([
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19
        }),
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_only_labels/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 19
        })
    ]);

    // Add default layer
    hybrid.addTo(map);

    // Layer control
    const baseLayers = {
        "OpenStreetMap": osm,
        "Satelit": satellite,
        "Hybrid (Satelit + Jalan)": hybrid
    };

    L.control.layers(baseLayers, null, { position: 'topright' }).addTo(map);

    // Click to set location pointer (when pointer is visible)
    map.on('click', function(e) {
        if (pointerVisible) {
            setLocationPointer(e.latlng);
        }
    });

    loadMap();
}

async function loadMap() {
    const itemsResult = await fetchAPI('/api/map-get-items.php');

    if (itemsResult && itemsResult.success) {
        // Clear existing markers and polylines
        Object.values(markers).forEach(marker => marker.remove());
        polylines.forEach(polyline => polyline.remove());
        markers = {};
        polylines = [];

        const items = itemsResult.items;
        allMapItems = items; // Store globally for chain visualization

        // Add markers
        items.forEach(item => {
            if (!visibleLayers[item.item_type]) return;

            // Skip ODC items that are hidden (created with Server)
            const properties = item.properties || {};
            if (item.item_type === 'odc' && properties.hidden_marker) {
                return; // Don't create marker, but item exists in database for ODP parent
            }

            const icon = getItemIcon(item.item_type, item.status);
            const marker = L.marker([item.latitude, item.longitude], {
                icon: icon,
                draggable: true
            })
                .addTo(map);

            // Bind popup with sync content
            marker.bindPopup(getItemPopupContent(item));

            // Update popup when opened for ONU, ODP, ODC, and Server (async details)
            marker.on('popupopen', function(e) {
                if (item.item_type === 'onu' && item.genieacs_device_id) {
                    loadONUDeviceDetails(item);
                } else if (item.item_type === 'odp') {
                    loadODPPortDetails(item);
                } else if (item.item_type === 'odc') {
                    loadODCPortDetails(item);
                } else if (item.item_type === 'server') {
                    showServerChain(item);
                    loadServerPONPorts(item);
                }
            });

            // Don't auto-hide chain on popupclose to allow clicking chain items
            // Chain will be hidden when another server is clicked or manually

            marker.on('dragend', function(e) {
                const newPos = e.target.getLatLng();
                updateItemPosition(item.id, newPos.lat, newPos.lng);
            });

            // Store item data in marker
            marker.itemData = item;
            markers[item.id] = marker;
        });

        // Draw connections between parent-child items
        items.forEach(item => {
            if (item.parent_id && markers[item.id]) {
                let parentMarker = markers[item.parent_id];
                const childMarker = markers[item.id];

                // If parent doesn't have marker (hidden ODC), find grandparent (Server)
                if (!parentMarker) {
                    const parentItem = items.find(i => i.id === item.parent_id);
                    if (parentItem && parentItem.parent_id) {
                        parentMarker = markers[parentItem.parent_id]; // Use grandparent (Server)
                    }
                }

                // Draw polyline if we have both markers
                if (parentMarker) {
                    // Determine connection status based on both parent and child
                    let connectionStatus = 'unknown';
                    if (parentMarker.itemData.status === 'online' && childMarker.itemData.status === 'online') {
                        connectionStatus = 'online';
                    } else if (parentMarker.itemData.status === 'offline' || childMarker.itemData.status === 'offline') {
                        connectionStatus = 'offline';
                    }

                    const polyline = L.polyline(
                        [parentMarker.getLatLng(), childMarker.getLatLng()],
                        {
                            className: `connection-${connectionStatus}`,
                            weight: 3
                        }
                    ).addTo(map);

                    polylines.push(polyline);
                }
            }
        });
    }
}

function getItemIcon(type, status) {
    const icons = {
        server: { icon: 'fa-server', color: '#4e73df', bg: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
        olt: { icon: 'fa-broadcast-tower', color: '#1cc88a', bg: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' },
        odc: { icon: 'fa-box', color: '#36b9cc', bg: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
        odp: { icon: 'fa-cube', color: '#f6c23e', bg: 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)' },
        onu: { icon: 'fa-wifi', color: '#e74a3b', bg: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)' }
    };

    const config = icons[type] || { icon: 'fa-circle', color: '#858796', bg: '#858796' };
    const statusColor = status === 'online' ? '#10b981' : status === 'offline' ? '#ef4444' : '#6b7280';
    const pulseClass = status === 'online' ? 'pulse-online' : '';

    return L.divIcon({
        html: `
            <div class="custom-marker ${pulseClass}" style="position: relative;">
                <div class="marker-icon" style="
                    background: ${config.bg};
                    width: 40px;
                    height: 40px;
                    border-radius: 50% 50% 50% 0;
                    transform: rotate(-45deg);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
                    border: 3px solid ${statusColor};
                    transition: all 0.3s ease;
                ">
                    <i class="fas ${config.icon}" style="
                        font-size: 16px;
                        color: white;
                        transform: rotate(45deg);
                        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
                    "></i>
                </div>
                ${status === 'online' ? `
                    <div class="status-indicator" style="
                        position: absolute;
                        top: -2px;
                        right: -2px;
                        width: 12px;
                        height: 12px;
                        background: ${statusColor};
                        border: 2px solid white;
                        border-radius: 50%;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                        animation: pulse 2s infinite;
                    "></div>
                ` : ''}
            </div>
        `,
        className: '',
        iconSize: [40, 40],
        iconAnchor: [20, 40]
    });
}

function getItemPopupContent(item) {
    let content = `
        <div style="min-width: 220px;">
            <h6><strong>${item.name}</strong></h6>
            <p class="mb-1"><small>Type: <strong>${item.item_type.toUpperCase()}</strong></small></p>
            <p class="mb-1"><small>Status: <span class="badge ${item.status === 'online' ? 'online' : 'offline'}">${item.status}</span></small></p>
    `;

    // For Server, show child items management and PON ports
    if (item.item_type === 'server') {
        // Properties already parsed by API, no need to JSON.parse again
        const properties = item.properties || {};
        const config = item.config || {};
        const ponPorts = config.pon_ports || {};

        content += `<hr style="margin: 8px 0;">`;
        content += `
            <p class="mb-1"><small><strong>🌐 ISP Link:</strong> ${properties.isp_link || 'Not set'}</small></p>
            <p class="mb-1"><small><strong>🔧 MikroTik Device:</strong> ${properties.mikrotik_device_id || 'Not set'}</small></p>
            <p class="mb-1"><small><strong>📡 OLT Link:</strong> ${properties.olt_link || 'Not set'}</small></p>
        `;

        // Show PON ports if available
        if (Object.keys(ponPorts).length > 0) {
            content += `
                <hr style="margin: 6px 0;">
                <p class="mb-1"><small><strong>⚡ PON Output Power per Port:</strong></small></p>
                <div id="server-pon-ports-${item.id}" style="max-height: 100px; overflow-y: auto; font-size: 10px;">
                    <div class="text-center">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            `;
        }

        content += `
            <hr style="margin: 6px 0;">
            <button class="btn btn-sm btn-info w-100 mb-2" onclick="manageServerLinks(${item.id})">
                <i class="bi bi-gear"></i> Manage Links
            </button>
            <button class="btn btn-sm btn-secondary w-100" onclick="hideServerChain()">
                <i class="bi bi-x-lg"></i> Hide Chain
            </button>
        `;
    }

    // For ODC, show PON calculator info
    if (item.item_type === 'odc' && item.config) {
        const config = item.config;
        content += `<hr style="margin: 8px 0;">`;
        content += `
            <p class="mb-1"><small><strong>🔌 PON Port:</strong> ${config.server_pon_port || 'N/A'}</small></p>
            <p class="mb-1"><small><strong>📊 Port Count:</strong> ${config.port_count || 'N/A'}</small></p>
            <p class="mb-1"><small><strong>⚡ Input Power:</strong> ${config.calculated_power || 'N/A'} dBm</small></p>
        `;

        // Show output power per port with ODP names (async loading)
        content += `
            <hr style="margin: 6px 0;">
            <p class="mb-1"><small><strong>🔌 Output Power per Port:</strong></small></p>
            <div id="odc-ports-${item.id}" style="max-height: 100px; overflow-y: auto; font-size: 10px;">
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        `;
    }

    // For ODP, show PON calculator info with splitter
    if (item.item_type === 'odp' && item.config) {
        const config = item.config;
        content += `<hr style="margin: 8px 0;">`;

        // Show parent ODC info
        if (item.parent_id && config.parent_odc_name) {
            content += `
                <p class="mb-1"><small><strong>📦 Parent ODC:</strong> ${config.parent_odc_name}</small></p>
                <p class="mb-1"><small><strong>🔌 ODC Port:</strong> ${config.odc_port || 'N/A'}</small></p>
                <hr style="margin: 6px 0;">
            `;
        }

        content += `
            <p class="mb-1"><small><strong>📊 Port Count:</strong> ${config.port_count || 'N/A'}</small></p>
            <p class="mb-1"><small><strong>🔀 Use Splitter:</strong> ${config.use_splitter == 1 ? 'Yes' : 'No'}</small></p>
        `;

        if (config.use_splitter == 1) {
            content += `<p class="mb-1"><small><strong>📐 Splitter Ratio:</strong> ${config.splitter_ratio || 'N/A'}</small></p>`;
        }

        content += `<p class="mb-1"><small><strong>⚡ Calculated Power:</strong> ${config.calculated_power || 'N/A'} dBm</small></p>`;

        // Show power per port (real RX from ONU or calculated)
        if (config.calculated_power || config.port_rx_power) {
            const defaultPower = parseFloat(config.calculated_power).toFixed(2);
            const portRxPower = config.port_rx_power || {};
            const portSerialNumber = config.port_serial_number || {};
            const portDeviceId = config.port_device_id || {};

            content += `
                <hr style="margin: 6px 0;">
                <p class="mb-1"><small><strong>🔌 Power per Port:</strong></small></p>
                <div style="max-height: 120px; overflow-y: auto; font-size: 10px;">
            `;

            for (let i = 1; i <= (config.port_count || 8); i++) {
                let rxPower, badge, serialInfo;

                if (portRxPower[i]) {
                    // Port has ONU connected - show real RX power
                    rxPower = `${portRxPower[i]} dBm`;
                    badge = '🟢';

                    // Create clickable link for serial number
                    if (portSerialNumber[i] && portDeviceId[i]) {
                        const deviceLink = `/device-detail.php?id=${encodeURIComponent(portDeviceId[i])}`;
                        serialInfo = ` / <a href="${deviceLink}" target="_blank" style="color: #0d6efd; text-decoration: underline;">${portSerialNumber[i]}</a>`;
                    } else if (portSerialNumber[i]) {
                        serialInfo = ` / ${portSerialNumber[i]}`;
                    } else {
                        serialInfo = '';
                    }
                } else {
                    // Port empty - show calculated power
                    rxPower = `${defaultPower} dBm (calc)`;
                    badge = '⚪';
                    serialInfo = '';
                }

                content += `<div style="padding: 2px 0;">${badge} Port ${i}: ${rxPower}${serialInfo}</div>`;
            }

            content += `</div>`;
        }
    }

    // For OLT, show output power and attenuation
    if (item.item_type === 'olt' && item.config) {
        const config = item.config;
        content += `<hr style="margin: 8px 0;">`;
        content += `
            <p class="mb-1"><small><strong>⚡ Output Power:</strong> ${config.output_power || '2'} dBm</small></p>
            <p class="mb-1"><small><strong>📉 Attenuation:</strong> ${config.attenuation_db || '0'} dB</small></p>
            <p class="mb-1"><small><strong>🔗 OLT Link:</strong> ${config.olt_link || 'Not set'}</small></p>
        `;
    }

    // For ONU, show loading placeholder (will be filled when popup opens)
    if (item.item_type === 'onu' && item.genieacs_device_id) {
        content += `<hr style="margin: 8px 0;">`;
        content += `<div id="onu-details-${item.id}"><small><i class="bi bi-arrow-repeat"></i> Loading device info...</small></div>`;
    }

    // Add action buttons (escape name to prevent JavaScript errors)
    const escapedName = item.name.replace(/'/g, "\\'").replace(/"/g, '\\"');
    content += `
        <hr style="margin: 8px 0;">
        <div class="btn-group btn-group-sm w-100">
            <button class="btn btn-sm btn-primary" onclick="editItem(${item.id})">
                <i class="bi bi-pencil"></i> Edit
            </button>
            <button class="btn btn-sm btn-danger" onclick="confirmDeleteItem(${item.id}, '${escapedName}')">
                <i class="bi bi-trash"></i> Delete
            </button>
        </div>
    `;

    content += `</div>`;
    return content;
}

async function loadONUDeviceDetails(item) {
    const result = await fetchAPI(`/api/get-device-detail.php?device_id=${encodeURIComponent(item.genieacs_device_id)}`);
    const detailsDiv = document.getElementById(`onu-details-${item.id}`);

    if (result && result.success && detailsDiv) {
        const device = result.device;
        const config = item.config || {};

        // Get parent hierarchy info
        let odpInfo = 'N/A', odcInfo = 'N/A';
        const itemsResult = await fetchAPI('/api/map-get-items.php');
        if (itemsResult && itemsResult.success) {
            const parentODP = itemsResult.items.find(i => i.id == item.parent_id);
            if (parentODP) {
                odpInfo = parentODP.name;
                const parentODC = itemsResult.items.find(i => i.id == parentODP.parent_id);
                if (parentODC) odcInfo = parentODC.name;
            }
        }

        const serialNumberLink = device.serial_number && device.serial_number !== 'N/A'
            ? `<a href="/device-detail.php?id=${encodeURIComponent(item.genieacs_device_id)}" target="_blank" style="color: #0d6efd; text-decoration: underline;">${device.serial_number}</a>`
            : 'N/A';

        detailsDiv.innerHTML = `
            <p class="mb-1"><small><strong>🔢 Serial Number:</strong> ${serialNumberLink}</small></p>
            <p class="mb-1"><small><strong>📶 RX Power:</strong> ${device.rx_power} dBm</small></p>
            <p class="mb-1"><small><strong>🌡️ Temperature:</strong> ${device.temperature}°C</small></p>
            <p class="mb-1"><small><strong>📡 WiFi SSID:</strong> ${device.wifi_ssid}</small></p>
            <p class="mb-1"><small><strong>🔌 IP TR069:</strong> ${extractIP(device.ip_tr069)}</small></p>
            <hr style="margin: 6px 0;">
            <p class="mb-1"><small><strong>📍 ODP:</strong> ${odpInfo}</small></p>
            <p class="mb-1"><small><strong>🔌 ODP Port:</strong> ${config.odp_port || 'N/A'}</small></p>
            <p class="mb-1"><small><strong>📦 ODC:</strong> ${odcInfo}</small></p>
            <p class="mb-1"><small><strong>👤 Customer:</strong> ${config.customer_name || 'N/A'}</small></p>
        `;
    } else if (detailsDiv) {
        detailsDiv.innerHTML = '<small class="text-danger">Failed to load device info</small>';
    }
}

async function loadODPPortDetails(item) {
    // Refresh ODP item details to get latest port RX power data
    const result = await fetchAPI(`/api/map-get-item-detail.php?item_id=${item.id}`);

    if (result && result.success && result.item) {
        const updatedItem = result.item;

        // Update marker popup with fresh data
        const marker = markers[item.id];
        if (marker) {
            marker.itemData = updatedItem;
            marker.setPopupContent(getItemPopupContent(updatedItem));
        }
    }
}

async function loadODCPortDetails(item) {
    try {
        const portsElement = document.getElementById(`odc-ports-${item.id}`);
        if (!portsElement) return;

        const config = item.config || {};
        const portCount = parseInt(config.port_count) || 4;
        const calculatedPower = parseFloat(config.calculated_power) || 0;
        const powerPerPort = calculatedPower.toFixed(2);

        // Find all ODPs that are children of this ODC
        const odpItems = allMapItems.filter(odpItem =>
            odpItem.item_type === 'odp' &&
            odpItem.parent_id == item.id
        );

        // Create port mapping: ODP port -> ODP name
        const portOdpMap = {};
        odpItems.forEach(odp => {
            if (odp.config && odp.config.odc_port) {
                portOdpMap[odp.config.odc_port] = odp.name;
            }
        });

        // Generate HTML
        let portsHtml = '';
        for (let i = 1; i <= portCount; i++) {
            const odpName = portOdpMap[i];
            if (odpName) {
                portsHtml += `<div style="padding: 2px 0;">Port ${i}: ${powerPerPort} dBm / ${odpName}</div>`;
            } else {
                portsHtml += `<div style="padding: 2px 0;">Port ${i}: ${powerPerPort} dBm</div>`;
            }
        }

        portsElement.innerHTML = portsHtml;
    } catch (error) {
        console.error('Error loading ODC port details:', error);
        const portsElement = document.getElementById(`odc-ports-${item.id}`);
        if (portsElement) {
            portsElement.innerHTML = '<div class="text-danger">Error loading ports</div>';
        }
    }
}

// Global variables for server chain visualization
let chainMarkers = [];
let chainPolylines = [];

function showServerChain(serverItem) {
    // Clear any existing chain
    hideServerChain();

    const properties = serverItem.properties || {};
    const serverMarker = markers[serverItem.id];
    if (!serverMarker) return;

    const serverLatLng = serverMarker.getLatLng();
    const offsetLat = -0.0002; // ~22 meters south for each item
    const chain = [];

    // Build chain array from server properties
    if (properties.isp_link) {
        chain.push({
            type: 'isp',
            name: 'ISP',
            value: properties.isp_link,
            icon: 'fa-globe',
            color: '#1cc88a',
            host: properties.isp_link
        });
    }
    if (properties.mikrotik_device_id) {
        chain.push({
            type: 'mikrotik',
            name: 'MikroTik',
            value: 'Loading...',
            icon: 'fa-network-wired',
            color: '#36b9cc',
            deviceId: properties.mikrotik_device_id
        });
    }
    if (properties.olt_link) {
        chain.push({
            type: 'olt',
            name: 'OLT',
            value: properties.olt_link,
            icon: 'fa-broadcast-tower',
            color: '#f6c23e',
            host: properties.olt_link,
            serverId: serverItem.id, // Store server ID to find ODCs
            ponPorts: serverItem.config?.pon_ports || {}
        });
    }

    // Check if ODC exists as child of this server
    // Find ODC by parent_id (more reliable than odc_id in properties)
    const odcChild = allMapItems.find(item =>
        item.item_type === 'odc' &&
        item.parent_id == serverItem.id &&
        item.properties &&
        item.properties.hidden_marker === true
    );

    if (odcChild && odcChild.config) {
        chain.push({
            type: 'odc',
            name: odcChild.name,
            value: 'ODC',
            icon: 'fa-box',
            color: '#e74a3b',
            odcId: odcChild.id, // Store ODC ID for async loading
            odcData: {
                port_count: odcChild.config.port_count || 4,
                pon_port: odcChild.config.server_pon_port,
                calculated_power: odcChild.config.calculated_power || 0
            }
        });
    }

    // Create markers and polylines for chain
    let prevLatLng = serverLatLng;

    chain.forEach((item, index) => {
        const itemLatLng = L.latLng(
            serverLatLng.lat + (offsetLat * (index + 1)),
            serverLatLng.lng
        );

        // Create custom div icon
        const iconHtml = `
            <div style="
                background: ${item.color};
                border: 2px solid white;
                border-radius: 50%;
                width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            ">
                <i class="fas ${item.icon}" style="color: white; font-size: 16px;"></i>
            </div>
        `;

        const customIcon = L.divIcon({
            html: iconHtml,
            className: 'chain-marker',
            iconSize: [36, 36],
            iconAnchor: [18, 18]
        });

        // Create marker
        const chainMarker = L.marker(itemLatLng, { icon: customIcon }).addTo(map);

        // Create unique ID for this chain marker
        const chainMarkerId = `chain-${item.type}-${index}`;

        // Create initial popup content with placeholders
        let popupContent = `
            <div id="${chainMarkerId}" style="min-width: 250px;">
                <h6><strong>${item.name}</strong></h6>
                <hr style="margin: 6px 0;">
        `;

        // Add type-specific content with loading placeholders
        if (item.type === 'isp') {
            popupContent += `
                <p class="mb-1"><small><strong>🌐 Host:</strong> ${item.host}</small></p>
                <p class="mb-1"><small><strong>📍 IP Address:</strong> <span id="${chainMarkerId}-ip">Loading...</span></small></p>
                <p class="mb-1"><small><strong>📊 Status:</strong> <span id="${chainMarkerId}-status">Loading...</span></small></p>
            `;
        } else if (item.type === 'mikrotik') {
            popupContent += `
                <p class="mb-1"><small><strong>📱 Serial Number:</strong> <span id="${chainMarkerId}-serial">Loading...</span></small></p>
                <p class="mb-1"><small><strong>📍 IP Address:</strong> <span id="${chainMarkerId}-ip">Loading...</span></small></p>
                <p class="mb-1"><small><strong>📊 Status:</strong> <span id="${chainMarkerId}-status">Loading...</span></small></p>
            `;
        } else if (item.type === 'olt') {
            popupContent += `
                <p class="mb-1"><small><strong>🌐 Host:</strong> ${item.host}</small></p>
                <p class="mb-1"><small><strong>📍 IP Address:</strong> <span id="${chainMarkerId}-ip">Loading...</span></small></p>
                <p class="mb-1"><small><strong>📊 Status:</strong> <span id="${chainMarkerId}-status">Loading...</span></small></p>
                <hr style="margin: 6px 0;">
                <p class="mb-1"><small><strong>🔌 PON Ports:</strong></small></p>
                <div id="${chainMarkerId}-ports" style="max-height: 120px; overflow-y: auto; font-size: 10px;">
                    Loading PON port mapping...
                </div>
            `;
        } else if (item.type === 'odc' && item.odcData) {
            const odcData = item.odcData;
            const powerPerPort = parseFloat(odcData.calculated_power).toFixed(2);

            popupContent += `
                <p class="mb-1"><small><strong>🔌 PON Port:</strong> ${odcData.pon_port}</small></p>
                <p class="mb-1"><small><strong>📊 Port Count:</strong> ${odcData.port_count}</small></p>
                <p class="mb-1"><small><strong>⚡ Input Power:</strong> ${odcData.calculated_power} dBm</small></p>
                <hr style="margin: 6px 0;">
                <p class="mb-1"><small><strong>🔌 Output Power per Port:</strong></small></p>
                <div id="${chainMarkerId}-ports" style="max-height: 100px; overflow-y: auto; font-size: 10px;">
                    Loading ODP mapping...
                </div>
            `;
        }

        popupContent += `</div>`;

        // Bind popup
        chainMarker.bindPopup(popupContent);

        // Add event listener to load details when popup opens
        if (item.type === 'isp' && item.host) {
            chainMarker.on('popupopen', function() {
                loadChainISPDetails(item.host, chainMarkerId);
            });
        } else if (item.type === 'mikrotik' && item.deviceId) {
            chainMarker.on('popupopen', function() {
                loadChainDeviceDetails(item.deviceId, chainMarkerId, 'mikrotik');
            });
        } else if (item.type === 'olt' && item.host) {
            chainMarker.on('popupopen', function() {
                loadChainOLTDetails(item.host, chainMarkerId, item.serverId, item.ponPorts);
            });
        } else if (item.type === 'odc' && item.odcId) {
            chainMarker.on('popupopen', function() {
                loadChainODCPortMapping(item.odcId, chainMarkerId, item.odcData);
            });
        }

        chainMarkers.push(chainMarker);

        // Create polyline from previous item
        const polyline = L.polyline([prevLatLng, itemLatLng], {
            color: '#4e73df',
            weight: 3,
            opacity: 0.7,
            dashArray: '10, 5'
        }).addTo(map);

        chainPolylines.push(polyline);
        prevLatLng = itemLatLng;
    });
}

function hideServerChain() {
    // Remove all chain markers
    chainMarkers.forEach(marker => map.removeLayer(marker));
    chainMarkers = [];

    // Remove all chain polylines
    chainPolylines.forEach(polyline => map.removeLayer(polyline));
    chainPolylines = [];
}

// Load ISP details from MikroTik Netwatch
async function loadChainISPDetails(host, markerId) {
    try {
        const result = await fetchAPI('/api/map-get-netwatch.php');
        if (result && result.success && result.netwatch) {
            const netwatch = result.netwatch.find(nw => nw.host === host);
            if (netwatch) {
                const ipElement = document.getElementById(`${markerId}-ip`);
                const statusElement = document.getElementById(`${markerId}-status`);

                if (ipElement) ipElement.textContent = netwatch.host;
                if (statusElement) {
                    const statusIcon = netwatch.status === 'up' ? '🟢' : '🔴';
                    const statusText = netwatch.status === 'up' ? 'Online' : 'Offline';
                    statusElement.innerHTML = `${statusIcon} ${statusText}`;
                }
            }
        }
    } catch (error) {
        console.error('Error loading ISP details:', error);
    }
}

// Load MikroTik device details from GenieACS
async function loadChainDeviceDetails(deviceId, markerId, type) {
    try {
        const result = await fetchAPI(`/api/get-device-detail.php?device_id=${deviceId}`);
        if (result && result.success && result.device) {
            const device = result.device;

            const serialElement = document.getElementById(`${markerId}-serial`);
            const ipElement = document.getElementById(`${markerId}-ip`);
            const statusElement = document.getElementById(`${markerId}-status`);

            if (serialElement) serialElement.textContent = device.serial_number || 'N/A';
            if (ipElement) ipElement.textContent = device.ip_address || 'N/A';
            if (statusElement) {
                const statusIcon = device.status === 'online' ? '🟢' : '🔴';
                const statusText = device.status === 'online' ? 'Online' : 'Offline';
                statusElement.innerHTML = `${statusIcon} ${statusText}`;
            }
        }
    } catch (error) {
        console.error('Error loading device details:', error);
    }
}

// Load OLT details from MikroTik Netwatch
async function loadChainOLTDetails(host, markerId, serverId, ponPorts) {
    try {
        const result = await fetchAPI('/api/map-get-netwatch.php');
        if (result && result.success && result.netwatch) {
            const netwatch = result.netwatch.find(nw => nw.host === host);
            if (netwatch) {
                const ipElement = document.getElementById(`${markerId}-ip`);
                const statusElement = document.getElementById(`${markerId}-status`);

                if (ipElement) ipElement.textContent = netwatch.host;
                if (statusElement) {
                    const statusIcon = netwatch.status === 'up' ? '🟢' : '🔴';
                    const statusText = netwatch.status === 'up' ? 'Online' : 'Offline';
                    statusElement.innerHTML = `${statusIcon} ${statusText}`;
                }
            }
        }

        // Load PON port mapping with ODCs
        const portsElement = document.getElementById(`${markerId}-ports`);
        if (portsElement && ponPorts) {
            // Find all ODCs that are children of this server (both hidden and standalone)
            const odcItems = allMapItems.filter(item =>
                item.item_type === 'odc' &&
                item.parent_id == serverId
            );

            // Create port mapping
            const portOdcMap = {};
            odcItems.forEach(odc => {
                if (odc.config && odc.config.server_pon_port) {
                    portOdcMap[odc.config.server_pon_port] = odc.name;
                }
            });

            // Generate HTML
            let portsHtml = '';
            Object.keys(ponPorts).sort((a, b) => parseInt(a) - parseInt(b)).forEach(portNum => {
                const power = ponPorts[portNum];
                const odcName = portOdcMap[portNum];
                if (odcName) {
                    portsHtml += `<div style="padding: 2px 0;">PON ${portNum}: ${power} dBm / ${odcName}</div>`;
                } else {
                    portsHtml += `<div style="padding: 2px 0;">PON ${portNum}: ${power} dBm</div>`;
                }
            });

            portsElement.innerHTML = portsHtml || 'No PON ports configured';
        }
    } catch (error) {
        console.error('Error loading OLT details:', error);
    }
}

// Load ODC port mapping with connected ODPs
async function loadChainODCPortMapping(odcId, markerId, odcData) {
    try {
        const portsElement = document.getElementById(`${markerId}-ports`);
        if (!portsElement) return;

        // Find all ODPs that are children of this ODC
        const odpItems = allMapItems.filter(item =>
            item.item_type === 'odp' &&
            item.parent_id == odcId
        );

        // Create port mapping
        const portOdpMap = {};
        odpItems.forEach(odp => {
            if (odp.config && odp.config.odc_port) {
                portOdpMap[odp.config.odc_port] = odp.name;
            }
        });

        // Generate HTML
        const powerPerPort = parseFloat(odcData.calculated_power).toFixed(2);
        let portsHtml = '';

        for (let i = 1; i <= odcData.port_count; i++) {
            const odpName = portOdpMap[i];
            if (odpName) {
                portsHtml += `<div style="padding: 2px 0;">Port ${i}: ${powerPerPort} dBm / ${odpName}</div>`;
            } else {
                portsHtml += `<div style="padding: 2px 0;">Port ${i}: ${powerPerPort} dBm</div>`;
            }
        }

        portsElement.innerHTML = portsHtml || 'No ports configured';
    } catch (error) {
        console.error('Error loading ODC port mapping:', error);
    }
}

async function loadServerPONPorts(serverItem) {
    try {
        const config = serverItem.config || {};
        const ponPorts = config.pon_ports || {};

        if (Object.keys(ponPorts).length === 0) {
            return; // No PON ports configured
        }

        const portsElement = document.getElementById(`server-pon-ports-${serverItem.id}`);
        if (!portsElement) {
            return; // Element not found
        }

        // Find all ODCs that are children of this server (both hidden and standalone)
        const odcItems = allMapItems.filter(item =>
            item.item_type === 'odc' &&
            item.parent_id == serverItem.id
        );

        // Create port mapping: PON port number -> ODC name
        const portOdcMap = {};
        odcItems.forEach(odc => {
            if (odc.config && odc.config.server_pon_port) {
                portOdcMap[odc.config.server_pon_port] = odc.name;
            }
        });

        // Generate HTML with ODC names
        let portsHtml = '';
        Object.keys(ponPorts).sort((a, b) => parseInt(a) - parseInt(b)).forEach(portNum => {
            const power = ponPorts[portNum];
            const odcName = portOdcMap[portNum];

            if (odcName) {
                portsHtml += `<div style="padding: 2px 0;">PON ${portNum}: ${power} dBm / ${odcName}</div>`;
            } else {
                portsHtml += `<div style="padding: 2px 0;">PON ${portNum}: ${power} dBm</div>`;
            }
        });

        portsElement.innerHTML = portsHtml || 'No ports configured';
    } catch (error) {
        console.error('Error loading Server PON ports:', error);
    }
}

function extractIP(ipString) {
    if (!ipString || ipString === 'N/A') return 'N/A';
    const match = ipString.match(/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/);
    return match ? match[1] : 'N/A';
}

function showAddItemModal() {
    // If pointer is not visible, show it first
    if (!pointerVisible) {
        toggleLocationPointer();
    }

    // Update form with current pointer coordinates
    if (locationPointer) {
        updateFormCoordinates(locationPointer.getLatLng());
    }

    const modal = new bootstrap.Modal(document.getElementById('addItemModal'));
    modal.show();
}

function toggleLocationPointer() {
    const btn = document.getElementById('toggle-pointer-btn');
    const btnText = document.getElementById('pointer-btn-text');

    if (pointerVisible) {
        // Hide pointer
        removeLocationPointer();
        pointerVisible = false;
        btn.classList.remove('btn-danger');
        btn.classList.add('btn-warning');
        btnText.textContent = 'Show Location Pointer';
    } else {
        // Show pointer at map center
        const mapCenter = map.getCenter();
        setLocationPointer(mapCenter);
        pointerVisible = true;
        btn.classList.remove('btn-warning');
        btn.classList.add('btn-danger');
        btnText.textContent = 'Hide Location Pointer';
    }
}

function setLocationPointer(latlng) {
    // Remove existing pointer if any
    if (locationPointer) {
        map.removeLayer(locationPointer);
    }

    // Create custom pointer icon
    const pointerIcon = L.divIcon({
        html: `
            <div style="position: relative;">
                <div style="
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    width: 48px;
                    height: 48px;
                    border-radius: 50% 50% 50% 0;
                    transform: rotate(-45deg);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
                    border: 3px solid #fff;
                    animation: pointer-pulse 1.5s infinite;
                ">
                    <i class="bi bi-geo-alt" style="
                        font-size: 20px;
                        color: white;
                        transform: rotate(45deg);
                    "></i>
                </div>
                <div style="
                    position: absolute;
                    top: -8px;
                    right: -8px;
                    background: #10b981;
                    color: white;
                    padding: 2px 6px;
                    border-radius: 8px;
                    font-size: 10px;
                    font-weight: bold;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                ">POINTER</div>
            </div>
        `,
        className: 'location-pointer',
        iconSize: [48, 48],
        iconAnchor: [24, 48]
    });

    // Create draggable marker
    locationPointer = L.marker(latlng, {
        icon: pointerIcon,
        draggable: true,
        zIndexOffset: 1000 // Keep it on top
    }).addTo(map);

    // Update form coordinates
    updateFormCoordinates(latlng);

    // Update coordinates on drag
    locationPointer.on('dragend', function(e) {
        const newPos = e.target.getLatLng();
        updateFormCoordinates(newPos);
    });

    // Show popup with instructions
    locationPointer.bindPopup(`
        <div style="text-align: center;">
            <strong>📍 Pilih Lokasi</strong><br>
            <small>Klik peta atau drag marker ini<br>untuk memilih lokasi item</small>
        </div>
    `).openPopup();
}

function updateFormCoordinates(latlng) {
    const form = document.querySelector('#form-add-item');
    if (form) {
        form.latitude.value = latlng.lat.toFixed(8);
        form.longitude.value = latlng.lng.toFixed(8);
    }
}

function removeLocationPointer() {
    if (locationPointer) {
        map.removeLayer(locationPointer);
        locationPointer = null;
    }
    pointerVisible = false;

    // Update button state
    const btn = document.getElementById('toggle-pointer-btn');
    const btnText = document.getElementById('pointer-btn-text');
    if (btn && btnText) {
        btn.classList.remove('btn-danger');
        btn.classList.add('btn-warning');
        btnText.textContent = 'Show Location Pointer';
    }
}

async function updateItemForm(type) {
    const dynamicFields = document.getElementById('dynamic-fields');

    // Load all items for parent selection
    const itemsResult = await fetchAPI('/api/map-get-items.php');
    let allItems = [];
    if (itemsResult && itemsResult.success) {
        allItems = itemsResult.items;
    }

    switch(type) {
        case 'server':
            // Load netwatch for ISP and OLT links
            const netwatchResultServer = await fetchAPI('/api/map-get-netwatch.php');
            let netwatchOptionsServer = '<option value="">No Link</option>';
            if (netwatchResultServer && netwatchResultServer.success && netwatchResultServer.netwatch) {
                netwatchResultServer.netwatch.forEach(nw => {
                    netwatchOptionsServer += `<option value="${nw.host}">${nw.host} - ${nw.comment || 'No comment'}</option>`;
                });
            }

            // Load available GenieACS devices for MikroTik only
            const genieacsDevicesResult = await fetchAPI('/api/get-devices.php');
            let genieacsOptionsServer = '<option value="">No Device</option>';
            if (genieacsDevicesResult && genieacsDevicesResult.success && genieacsDevicesResult.devices) {
                genieacsDevicesResult.devices.forEach(device => {
                    const statusIcon = device.status === 'online' ? '🟢' : '🔴';
                    const serialNumber = device.serial_number || device.device_id;
                    genieacsOptionsServer += `<option value="${device.device_id}">${statusIcon} ${serialNumber} - ${device.ip_address || 'N/A'}</option>`;
                });
            }

            dynamicFields.innerHTML = `
                <div class="form-group">
                    <label>🌐 ISP Link (dari MikroTik Netwatch)</label>
                    <select name="isp_link" class="form-control">
                        ${netwatchOptionsServer}
                    </select>
                    <small class="text-muted">Link ke ISP/Provider (opsional)</small>
                </div>
                <div class="form-group">
                    <label>🔧 MikroTik Device (dari GenieACS)</label>
                    <select name="mikrotik_device_id" class="form-control">
                        ${genieacsOptionsServer}
                    </select>
                    <small class="text-muted">Device MikroTik Router dari GenieACS (opsional)</small>
                </div>
                <div class="form-group">
                    <label>📡 OLT Link (dari MikroTik Netwatch)</label>
                    <select name="olt_link" class="form-control">
                        ${netwatchOptionsServer}
                    </select>
                    <small class="text-muted">Link ke OLT untuk monitoring (opsional)</small>
                </div>
                <div class="form-group">
                    <label>🔢 Jumlah PON Ports</label>
                    <input type="number" id="pon_port_count" class="form-control" value="4" min="1" max="16" onchange="generatePonPortFields(this.value)">
                    <small class="text-muted">Jumlah port PON di OLT (1-16)</small>
                </div>
                <div id="pon-ports-container"></div>

                <hr class="my-3">
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="add_odc_checkbox" onchange="toggleODCSection(this.checked)">
                    <label class="form-check-label" for="add_odc_checkbox">
                        <strong>➕ Tambahkan ODC secara bersamaan</strong>
                    </label>
                    <small class="form-text text-muted d-block">Opsional: Buat ODC child item sekaligus saat membuat Server</small>
                </div>

                <div id="odc-section" style="display: none;">
                    <div class="card">
                        <div class="card-header bg-light">
                            <strong>📦 Konfigurasi ODC</strong>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Nama ODC <span class="text-danger">*</span></label>
                                <input type="text" name="odc_name" class="form-control" placeholder="Contoh: ODC-1">
                                <small class="text-muted">Nama untuk item ODC</small>
                            </div>
                            <div class="form-group">
                                <label>PON Port dari Server <span class="text-danger">*</span></label>
                                <select name="odc_pon_port" class="form-control" id="odc-pon-port-select">
                                    <option value="">Pilih PON Port</option>
                                </select>
                                <small class="text-muted">Port PON server yang terhubung ke ODC</small>
                            </div>
                            <div class="form-group">
                                <label>ODC Port Count</label>
                                <input type="number" name="odc_port_count" class="form-control" value="4" min="1">
                                <small class="text-muted">Jumlah port di ODC</small>
                            </div>
                            <div class="alert alert-info">
                                <small><i class="bi bi-info-circle"></i> ODC akan dibuat di lokasi yang sama dengan Server (1 titik koordinat). Power per port ODC akan otomatis dihitung dari PON Port yang dipilih.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-2">
                    <small><i class="bi bi-info-circle"></i> Server dapat memiliki child items: OLT dan ODC yang dapat dikonfigurasi setelah item dibuat atau bersamaan.</small>
                </div>
            `;

            // Generate default PON port fields (4 ports)
            setTimeout(() => {
                generatePonPortFields(4);
                updateODCPonPortOptions(4);
            }, 100);
            break;

        case 'olt':
            // Load netwatch for OLT link selection
            const netwatchResult = await fetchAPI('/api/map-get-netwatch.php');
            let netwatchOptions = '<option value="">No Link</option>';
            if (netwatchResult && netwatchResult.success && netwatchResult.netwatch) {
                netwatchResult.netwatch.forEach(nw => {
                    netwatchOptions += `<option value="${nw.host}">${nw.host} - ${nw.comment || 'No comment'}</option>`;
                });
            }

            // Get all servers for parent selection
            let serverOptions = '<option value="">No Parent (Standalone)</option>';
            allItems.filter(item => item.item_type === 'server').forEach(item => {
                const statusBadge = item.status === 'online' ? '🟢' : item.status === 'offline' ? '🔴' : '⚪';
                serverOptions += `<option value="${item.id}">${statusBadge} ${item.name}</option>`;
            });

            dynamicFields.innerHTML = `
                <div class="form-group">
                    <label>Parent Server</label>
                    <select name="parent_id" class="form-control">
                        ${serverOptions}
                    </select>
                    <small class="text-muted">Pilih Server sebagai parent (opsional)</small>
                </div>
                <div class="form-group">
                    <label>📡 Jumlah PON Port</label>
                    <select name="pon_count" class="form-control" onchange="updatePONFields(this.value)">
                        <option value="1">1 PON</option>
                        <option value="2">2 PON</option>
                        <option value="4">4 PON</option>
                        <option value="8">8 PON</option>
                        <option value="16">16 PON</option>
                    </select>
                    <small class="text-muted">Jumlah PON port pada OLT</small>
                </div>
                <div id="pon-power-fields">
                    <div class="form-group">
                        <label>⚡ PON 1 - Output Power (dBm)</label>
                        <input type="number" step="0.01" name="pon_power_1" class="form-control" value="9" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>🔗 OLT Link (dari MikroTik Netwatch)</label>
                    <select name="olt_link" class="form-control">
                        ${netwatchOptions}
                    </select>
                    <small class="text-muted">Link untuk monitoring status OLT</small>
                </div>
            `;
            break;

        case 'odc':
            const ponPortsResult = await fetchAPI('/api/map-get-pon-ports.php');
            let ponPortOptions = '<option value="">Pilih OLT PON Port</option>';

            if (ponPortsResult && ponPortsResult.success && ponPortsResult.ports) {
                ponPortsResult.ports.forEach(port => {
                    ponPortOptions += `<option value="${port.id}">${port.olt_name} - PON ${port.pon_number} (${port.output_power} dBm)</option>`;
                });
            }

            dynamicFields.innerHTML = `
                ${!ponPortsResult || !ponPortsResult.ports || ponPortsResult.ports.length === 0 ? `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Belum ada Server PON tersedia. Buat Server terlebih dahulu.
                    </div>
                ` : ''}
                <div class="form-group">
                    <label>Parent Server PON Port <span class="text-danger">*</span></label>
                    <select name="olt_pon_port_id" id="olt-pon-port-select" class="form-control" required>
                        ${ponPortOptions}
                    </select>
                    <small class="text-muted">Pilih PON port dari Server sebagai parent</small>
                </div>
                <div class="form-group">
                    <label>Port Count</label>
                    <input type="number" name="port_count" class="form-control" value="4" required min="1">
                </div>
            `;
            break;

        case 'odp':
            // Get all ODCs and ODPs with custom ratio for parent selection
            const odcItems = allItems.filter(item => item.item_type === 'odc');
            const odpItemsWithCustomRatio = allItems.filter(item =>
                item.item_type === 'odp' &&
                item.config?.splitter_ratio &&
                ['20:80', '30:70', '50:50'].includes(item.config.splitter_ratio)
            );

            let parentOptions = '<option value="">Pilih Parent (ODC atau ODP)</option>';

            // Add ODC group
            if (odcItems.length > 0) {
                parentOptions += '<optgroup label="ODC (Optical Distribution Cabinet)">';
                odcItems.forEach(item => {
                    const statusBadge = item.status === 'online' ? '🟢' : item.status === 'offline' ? '🔴' : '⚪';
                    parentOptions += `<option value="${item.id}" data-type="odc">${statusBadge} ${item.name}</option>`;
                });
                parentOptions += '</optgroup>';
            }

            // Add ODP group with custom ratio
            if (odpItemsWithCustomRatio.length > 0) {
                parentOptions += '<optgroup label="ODP (Splitter Custom Ratio tersedia)">';
                odpItemsWithCustomRatio.forEach(item => {
                    const statusBadge = item.status === 'online' ? '🟢' : item.status === 'offline' ? '🔴' : '⚪';
                    const ratio = item.config.splitter_ratio;
                    // Extract the larger percentage (port yang tersisa)
                    const availablePort = ratio.split(':')[1] + '%';
                    parentOptions += `<option value="${item.id}" data-type="odp" data-ratio="${ratio}">${statusBadge} ${item.name} (Port ${availablePort} tersisa)</option>`;
                });
                parentOptions += '</optgroup>';
            }

            const noParentAvailable = odcItems.length === 0 && odpItemsWithCustomRatio.length === 0;

            dynamicFields.innerHTML = `
                ${noParentAvailable ? `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Belum ada ODC atau ODP dengan custom ratio tersedia. Silakan buat ODC atau ODP dengan ratio 20:80, 30:70, atau 50:50 terlebih dahulu.
                    </div>
                ` : ''}
                <div class="form-group">
                    <label>Parent (ODC/ODP) <span class="text-danger">*</span></label>
                    <select name="parent_id" id="odp-parent-select" class="form-control" required ${noParentAvailable ? 'disabled' : ''} onchange="handleODPParentChange(this)">
                        ${parentOptions}
                    </select>
                    <small class="text-muted">Pilih ODC atau ODP dengan custom ratio sebagai parent</small>
                </div>
                <div class="form-group" id="odc-port-group" style="display: none;">
                    <label>ODC Port <span class="text-danger">*</span></label>
                    <input type="number" name="odc_port" class="form-control" min="1" placeholder="Nomor port di ODC">
                    <small class="text-muted">Port ODC yang terhubung ke ODP ini</small>
                </div>
                <div class="form-group" id="odp-port-group" style="display: none;">
                    <label>Parent ODP Port <span class="text-danger">*</span></label>
                    <select name="parent_odp_port" class="form-control">
                        <option value="">Pilih port parent ODP</option>
                    </select>
                    <small class="text-muted">Port parent ODP yang akan digunakan (dari custom ratio)</small>
                </div>
                <div class="form-group">
                    <label>Port Count</label>
                    <input type="number" name="port_count" class="form-control" value="8" required min="1">
                    <small class="text-muted">Jumlah port ODP</small>
                </div>
                <div class="form-group">
                    <label>Use Splitter</label>
                    <select name="use_splitter" class="form-control" onchange="toggleSplitterRatio(this.value)">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>
                <div class="form-group" id="splitter-ratio-group" style="display: none;">
                    <label>Splitter Ratio</label>
                    <select name="splitter_ratio" class="form-control">
                        <option value="1:2">1:2 (3.5 dB)</option>
                        <option value="1:4">1:4 (7.0 dB)</option>
                        <option value="1:8" selected>1:8 (10.5 dB)</option>
                        <option value="1:16">1:16 (14.0 dB)</option>
                        <option value="1:32">1:32 (17.5 dB)</option>
                        <option value="20:80">20:80 (16.8 dB)</option>
                        <option value="30:70">30:70 (13.5 dB)</option>
                        <option value="50:50">50:50 (10.0 dB)</option>
                    </select>
                </div>
                <div class="alert alert-info mt-2">
                    <small><i class="bi bi-calculator"></i> PON Calculator akan otomatis menghitung power setelah input.</small>
                </div>
            `;
            break;

        case 'onu':
            // Load available GenieACS devices and ODP list
            const devicesResult = await fetchAPI('/api/map-get-available-devices.php');
            const odpResult = await fetchAPI('/api/map-get-odp-ports.php');

            let deviceOptions = '<option value="">Pilih Device GenieACS</option>';
            if (devicesResult && devicesResult.success) {
                devicesResult.devices.forEach(dev => {
                    const statusBadge = dev.status === 'Online' ? '🟢' : '🔴';
                    deviceOptions += `<option value="${dev.device_id}">${statusBadge} ${dev.serial_number} (${dev.device_id})</option>`;
                });
            }

            let odpOptions = '<option value="">Pilih ODP</option>';
            if (odpResult && odpResult.success) {
                odpResult.odp_list.forEach(odp => {
                    const statusBadge = odp.status === 'online' ? '🟢' : odp.status === 'offline' ? '🔴' : '⚪';
                    odpOptions += `<option value="${odp.id}">${statusBadge} ${odp.name} (${odp.available_ports.length}/${odp.port_count} ports available)</option>`;
                });
            }

            dynamicFields.innerHTML = `
                <div class="form-group">
                    <label>Customer Name <span class="text-danger">*</span></label>
                    <input type="text" name="customer_name" class="form-control" required>
                    <small class="text-muted">Nama pelanggan</small>
                </div>
                <div class="form-group">
                    <label>GenieACS Device <span class="text-danger">*</span></label>
                    <select name="genieacs_device_id" class="form-control" required>
                        ${deviceOptions}
                    </select>
                    <small class="text-muted">Pilih ONU dari GenieACS (device yang sudah assigned tidak muncul)</small>
                </div>
                <div class="form-group">
                    <label>Parent ODP <span class="text-danger">*</span></label>
                    <select name="parent_odp" class="form-control" required onchange="loadODPPorts(this.value)">
                        ${odpOptions}
                    </select>
                </div>
                <div class="form-group" id="odp-port-group">
                    <label>ODP Port <span class="text-danger">*</span></label>
                    <select name="odp_port" class="form-control" required>
                        <option value="">Pilih ODP dulu</option>
                    </select>
                    <small class="text-muted">Port yang tersedia di ODP</small>
                </div>
            `;
            break;

        default:
            dynamicFields.innerHTML = '';
    }
}

function toggleSplitterRatio(value) {
    const splitterGroup = document.getElementById('splitter-ratio-group');
    if (value === '1') {
        splitterGroup.style.display = 'block';
    } else {
        splitterGroup.style.display = 'none';
    }
}

async function handleODPParentChange(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const parentType = selectedOption.getAttribute('data-type');
    const parentId = selectElement.value;
    const odcPortGroup = document.getElementById('odc-port-group');
    const odpPortGroup = document.getElementById('odp-port-group');
    const odpPortSelect = document.querySelector('select[name="parent_odp_port"]');

    if (parentType === 'odc') {
        // Show ODC port field, hide ODP port field
        odcPortGroup.style.display = 'block';
        odpPortGroup.style.display = 'none';
        document.querySelector('input[name="odc_port"]').required = true;
        odpPortSelect.required = false;

        // Fetch used ODC ports and show warning
        if (parentId) {
            const result = await fetchAPI(`/api/map-get-used-ports.php?parent_id=${parentId}&parent_type=odc`);
            if (result && result.success && result.used_ports.length > 0) {
                const odcPortInput = document.querySelector('input[name="odc_port"]');
                const warningDiv = document.getElementById('odc-port-warning') || document.createElement('div');
                warningDiv.id = 'odc-port-warning';
                warningDiv.className = 'alert alert-warning mt-2';
                warningDiv.innerHTML = `<small><i class="bi bi-exclamation-triangle"></i> <b>Port yang sudah digunakan:</b> ${result.used_ports.join(', ')}</small>`;

                if (!document.getElementById('odc-port-warning')) {
                    odcPortInput.parentElement.appendChild(warningDiv);
                }
            }
        }

    } else if (parentType === 'odp') {
        // Show ODP port field, hide ODC port field
        odcPortGroup.style.display = 'none';
        odpPortGroup.style.display = 'block';
        document.querySelector('input[name="odc_port"]').required = false;
        odpPortSelect.required = true;

        // Fetch used ODP ports
        let usedPorts = [];
        if (parentId) {
            const result = await fetchAPI(`/api/map-get-used-ports.php?parent_id=${parentId}&parent_type=odp`);
            if (result && result.success) {
                usedPorts = result.used_ports;
            }
        }

        // Populate ODP port options based on ratio
        const ratio = selectedOption.getAttribute('data-ratio');
        const ratioValues = ratio.split(':');

        const port1 = `${ratioValues[0]}%`;
        const port2 = `${ratioValues[1]}%`;
        const port1Used = usedPorts.includes(port1);
        const port2Used = usedPorts.includes(port2);

        odpPortSelect.innerHTML = `
            <option value="">Pilih port parent ODP</option>
            <option value="${port1}" ${port1Used ? 'disabled' : ''}>
                ${ratioValues[0]}% (Port lebih kecil) ${port1Used ? '❌ Sudah digunakan' : ''}
            </option>
            <option value="${port2}" ${port2Used ? 'disabled' : ''} ${!port2Used && !port1Used ? 'selected' : ''}>
                ${ratioValues[1]}% (Port tersisa - Recommended) ${port2Used ? '❌ Sudah digunakan' : ''}
            </option>
        `;

        // Show warning if all ports are used
        if (port1Used && port2Used) {
            showToast('Semua port parent ODP sudah digunakan. Pilih parent lain.', 'warning');
        }

    } else {
        // No parent selected
        odcPortGroup.style.display = 'none';
        odpPortGroup.style.display = 'none';
        document.querySelector('input[name="odc_port"]').required = false;
        odpPortSelect.required = false;

        // Remove warning if exists
        const warning = document.getElementById('odc-port-warning');
        if (warning) warning.remove();
    }
}

function generatePonPortFields(portCount) {
    const container = document.getElementById('pon-ports-container');
    if (!container) return;

    let html = '<div class="card mt-2"><div class="card-body"><h6>⚡ PON Output Power per Port</h6>';

    for (let i = 1; i <= portCount; i++) {
        html += `
            <div class="form-group">
                <label>Port ${i} Output Power (dBm)</label>
                <input type="number" step="0.01" name="pon_port_${i}_power" class="form-control" value="2.00" placeholder="2.00">
            </div>
        `;
    }

    html += '</div></div>';
    container.innerHTML = html;

    // Update ODC PON port options
    updateODCPonPortOptions(portCount);
}

function updateODCPonPortOptions(portCount) {
    const select = document.getElementById('odc-pon-port-select');
    if (!select) return;

    let options = '<option value="">Pilih PON Port</option>';
    for (let i = 1; i <= portCount; i++) {
        options += `<option value="${i}">PON Port ${i}</option>`;
    }
    select.innerHTML = options;
}

function toggleODCSection(isChecked) {
    const odcSection = document.getElementById('odc-section');
    if (odcSection) {
        odcSection.style.display = isChecked ? 'block' : 'none';
    }
}

async function loadODPPorts(odpId) {
    if (!odpId) {
        document.querySelector('[name="odp_port"]').innerHTML = '<option value="">Pilih ODP dulu</option>';
        return;
    }

    const odpResult = await fetchAPI('/api/map-get-odp-ports.php');
    if (odpResult && odpResult.success) {
        const odp = odpResult.odp_list.find(o => o.id == odpId);
        if (odp) {
            let portOptions = '';
            if (odp.available_ports.length === 0) {
                portOptions = '<option value="">Tidak ada port tersedia</option>';
            } else {
                odp.available_ports.forEach(port => {
                    portOptions += `<option value="${port}">Port ${port}</option>`;
                });
            }
            document.querySelector('[name="odp_port"]').innerHTML = portOptions;
        }
    }
}

async function addItem() {
    const form = document.getElementById('form-add-item');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    showLoading();

    const result = await fetchAPI('/api/map-add-item.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });

    hideLoading();

    if (result && result.success) {
        showToast('Item berhasil ditambahkan', 'success');
        bootstrap.Modal.getInstance(document.getElementById('addItemModal')).hide();
        form.reset();
        // Keep pointer visible after adding item (don't remove it)
        loadMap();
    } else {
        showToast(result.message || 'Gagal menambahkan item', 'danger');
    }
}

async function updateItemPosition(itemId, lat, lng) {
    const result = await fetchAPI('/api/map-update-position.php', {
        method: 'POST',
        body: JSON.stringify({
            item_id: itemId,
            latitude: lat,
            longitude: lng
        })
    });

    if (result && result.success) {
        showToast('Position updated', 'success');
    }
}

function toggleLayer(type) {
    visibleLayers[type] = !visibleLayers[type];
    loadMap();
}

async function editItem(itemId) {
    const result = await fetchAPI(`/api/map-get-item-detail.php?item_id=${itemId}`);

    if (result && result.success) {
        const item = result.item;
        const form = document.getElementById('form-edit-item');

        form.item_id.value = item.id;
        form.item_type.value = item.item_type.toUpperCase();
        form.name.value = item.name;
        form.latitude.value = item.latitude;
        form.longitude.value = item.longitude;

        // Load type-specific fields
        await updateEditItemForm(item);

        const modal = new bootstrap.Modal(document.getElementById('editItemModal'));
        modal.show();
    } else {
        showToast('Failed to load item details', 'danger');
    }
}

async function updateEditItemForm(item) {
    const dynamicFields = document.getElementById('edit-dynamic-fields');

    switch(item.item_type) {
        case 'olt':
            dynamicFields.innerHTML = `
                <div class="form-group">
                    <label>OLT Output Power (dBm)</label>
                    <input type="number" step="0.01" name="output_power" class="form-control" value="${item.config?.output_power || 2}" required>
                    <small class="text-muted">Default: 2 dBm</small>
                </div>
                <div class="form-group">
                    <label>Attenuation (dB)</label>
                    <input type="number" step="0.01" name="attenuation_db" class="form-control" value="${item.config?.attenuation_db || 0}" required>
                </div>
                <div class="form-group">
                    <label>OLT Link</label>
                    <input type="text" name="olt_link" class="form-control" value="${item.config?.olt_link || ''}">
                </div>
            `;
            break;
        case 'odc':
            dynamicFields.innerHTML = `
                <div class="form-group">
                    <label>Port Count</label>
                    <input type="number" name="port_count" class="form-control" value="${item.config?.port_count || 4}" required>
                </div>
            `;
            break;
        case 'odp':
            dynamicFields.innerHTML = `
                <div class="form-group">
                    <label>ODC Port</label>
                    <input type="number" name="odc_port" class="form-control" value="${item.config?.odc_port || ''}" min="1" placeholder="Nomor port di ODC">
                    <small class="text-muted">Port ODC yang terhubung ke ODP ini</small>
                </div>
                <div class="form-group">
                    <label>Port Count</label>
                    <input type="number" name="port_count" class="form-control" value="${item.config?.port_count || 8}" required>
                </div>
                <div class="form-group">
                    <label>Use Splitter</label>
                    <select name="use_splitter" class="form-control">
                        <option value="0" ${item.config?.use_splitter == 0 ? 'selected' : ''}>No</option>
                        <option value="1" ${item.config?.use_splitter == 1 ? 'selected' : ''}>Yes</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Splitter Ratio</label>
                    <input type="text" name="splitter_ratio" class="form-control" value="${item.config?.splitter_ratio || '1:8'}">
                </div>
            `;
            break;
        case 'onu':
            dynamicFields.innerHTML = `
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" name="customer_name" class="form-control" value="${item.customer_name || ''}" required>
                </div>
                <div class="alert alert-info">
                    <small><i class="bi bi-info-circle"></i> GenieACS device dan ODP port tidak bisa diubah. Hapus dan buat ulang jika perlu mengubah.</small>
                </div>
            `;
            break;
        default:
            dynamicFields.innerHTML = '';
    }
}

async function updateItem() {
    const form = document.getElementById('form-edit-item');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    showLoading();

    const result = await fetchAPI('/api/map-update-item.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });

    hideLoading();

    if (result && result.success) {
        showToast('Item berhasil diupdate', 'success');
        bootstrap.Modal.getInstance(document.getElementById('editItemModal')).hide();
        loadMap();
    } else {
        showToast(result.message || 'Gagal mengupdate item', 'danger');
    }
}

function confirmDeleteItem(itemId, itemName) {
    if (confirm(`Apakah Anda yakin ingin menghapus "${itemName}"?\n\nPeringatan: Semua child items akan ikut terhapus!`)) {
        deleteItem(itemId);
    }
}

async function deleteItem(itemId) {
    showLoading();

    const result = await fetchAPI('/api/map-delete-item.php', {
        method: 'POST',
        body: JSON.stringify({ item_id: itemId })
    });

    hideLoading();

    if (result && result.success) {
        showToast('Item berhasil dihapus', 'success');
        loadMap();
    } else {
        showToast(result.message || 'Gagal menghapus item', 'danger');
    }
}

async function manageServerLinks(itemId) {
    // Load current server data
    const itemResult = await fetchAPI(`/api/map-get-item-detail.php?item_id=${itemId}`);
    if (!itemResult || !itemResult.success) {
        showToast('Failed to load server data', 'danger');
        return;
    }

    const item = itemResult.item;
    // Properties already parsed by API, no need to JSON.parse again
    const properties = item.properties || {};

    // Load netwatch data
    const netwatchResult = await fetchAPI('/api/map-get-netwatch.php');
    let netwatchOptions = '<option value="">No Link</option>';
    if (netwatchResult && netwatchResult.success && netwatchResult.netwatch) {
        netwatchResult.netwatch.forEach(nw => {
            netwatchOptions += `<option value="${nw.host}">${nw.host} - ${nw.comment || 'No comment'}</option>`;
        });
    }

    // Populate form
    const form = document.getElementById('form-server-links');
    form.item_id.value = itemId;

    document.getElementById('isp-link-select').innerHTML = netwatchOptions;
    document.getElementById('mikrotik-link-select').innerHTML = netwatchOptions;
    document.getElementById('olt-link-select').innerHTML = netwatchOptions;

    // Set current values
    if (properties.isp_link) {
        document.querySelector('[name="isp_link"]').value = properties.isp_link;
    }
    if (properties.mikrotik_link) {
        document.querySelector('[name="mikrotik_link"]').value = properties.mikrotik_link;
    }
    if (properties.olt_link) {
        document.querySelector('[name="olt_link"]').value = properties.olt_link;
    }
    // Set PON output power (default to 2 if not set)
    document.querySelector('[name="pon_output_power"]').value = properties.pon_output_power || 2;

    const modal = new bootstrap.Modal(document.getElementById('serverLinksModal'));
    modal.show();
}

async function saveServerLinks() {
    const form = document.getElementById('form-server-links');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    showLoading();

    const result = await fetchAPI('/api/map-update-server-links.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });

    hideLoading();

    if (result && result.success) {
        showToast('Server links updated', 'success');
        bootstrap.Modal.getInstance(document.getElementById('serverLinksModal')).hide();
        loadMap();
    } else {
        showToast(result.message || 'Failed to update links', 'danger');
    }
}

function updatePONFields(ponCount) {
    const container = document.getElementById('pon-power-fields');
    let html = '';

    for (let i = 1; i <= parseInt(ponCount); i++) {
        html += `
            <div class="form-group">
                <label>⚡ PON ${i} - Output Power (dBm)</label>
                <input type="number" step="0.01" name="pon_power_${i}" class="form-control" value="9" required>
            </div>
        `;
    }

    container.innerHTML = html;
}

function toggleAutoRefresh() {
    autoRefreshEnabled = !autoRefreshEnabled;
    const statusSpan = document.getElementById('auto-refresh-status');
    const toggleBtn = document.getElementById('auto-refresh-toggle');

    if (autoRefreshEnabled) {
        statusSpan.textContent = 'ON';
        toggleBtn.classList.remove('btn-secondary');
        toggleBtn.classList.add('btn-info');
        startAutoRefresh();
    } else {
        statusSpan.textContent = 'OFF';
        toggleBtn.classList.remove('btn-info');
        toggleBtn.classList.add('btn-secondary');
        stopAutoRefresh();
    }
}

function startAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    autoRefreshInterval = setInterval(() => {
        if (autoRefreshEnabled) {
            loadMap();
        }
    }, 30000); // 30 seconds
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($genieacsConfigured): ?>
        initMap();
        startAutoRefresh(); // Start auto-refresh on page load
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
