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
                    <button class="btn btn-primary" onclick="showAddItemModal()">
                        <i class="bi bi-plus-lg"></i> Add Item
                    </button>
                    <button class="btn btn-success" onclick="loadMap()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button class="btn btn-info" id="auto-refresh-toggle" onclick="toggleAutoRefresh()">
                        <i class="bi bi-clock"></i> Auto-Refresh: <span id="auto-refresh-status">ON</span>
                    </button>
                    <div class="d-flex gap-2 float-end">
                        <!-- Server Indicator (not toggleable) -->
                        <div class="badge bg-primary d-flex align-items-center gap-1 px-3 py-2" style="font-size: 0.875rem;">
                            <i class="bi bi-server"></i>
                            <span>Server: <strong id="server-count">0</strong></span>
                        </div>

                        <!-- Layer Toggle Buttons -->
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleLayer('olt')">
                                <i class="bi bi-broadcast-pin"></i> OLT <span class="badge bg-secondary" id="olt-count">0</span>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleLayer('odc')">
                                <i class="bi bi-box"></i> ODC <span class="badge bg-secondary" id="odc-count">0</span>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleLayer('odp')">
                                <i class="bi bi-cube"></i> ODP <span class="badge bg-secondary" id="odp-count">0</span>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleLayer('onu')">
                                <i class="bi bi-wifi"></i> ONU <span class="badge bg-secondary" id="onu-count">0</span>
                            </button>
                        </div>
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

    <!-- Context Menu untuk Polylines -->
    <div id="polyline-context-menu" class="context-menu" style="display: none; position: absolute; z-index: 9999; background: white; border: 1px solid #ccc; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); min-width: 150px;">
        <div class="context-menu-item" onclick="enablePolylineEdit()">
            <i class="bi bi-pencil"></i> Edit Jalur
        </div>
        <div class="context-menu-item" onclick="savePolylineWaypoints()">
            <i class="bi bi-save"></i> Simpan Waypoints
        </div>
        <div class="context-menu-item" onclick="resetPolylineToStraight()">
            <i class="bi bi-arrow-counterclockwise"></i> Reset ke Garis Lurus
        </div>
        <div class="context-menu-item" onclick="closeContextMenu()">
            <i class="bi bi-x-lg"></i> Tutup
        </div>
    </div>
<?php endif; ?>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle"></i> Add Network Item
                </h5>
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
                            <li><strong>Klik di peta</strong> untuk menampilkan pointer lokasi</li>
                            <li>Klik lagi di peta untuk memindahkan pointer ke lokasi lain</li>
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
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square"></i> Edit Network Item
                </h5>
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
                <h5 class="modal-title">
                    <i class="bi bi-diagram-3"></i> Manage Server Links
                </h5>
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
                        <label>🔧 MikroTik Device (dari GenieACS)</label>
                        <select name="mikrotik_device_id" class="form-control" id="mikrotik-device-select">
                            <option value="">No Device</option>
                        </select>
                        <small class="text-muted">Device MikroTik Router dari GenieACS</small>
                    </div>
                    <div class="form-group">
                        <label>📡 OLT Link (dari MikroTik Netwatch)</label>
                        <select name="olt_link" class="form-control" id="olt-link-select">
                            <option value="">No Link</option>
                        </select>
                        <small class="text-muted">Link ke OLT</small>
                    </div>
                    <div id="pon-output-power-container">
                        <!-- PON output power fields will be dynamically generated here -->
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

/* Animated polyline for connections - Enhanced */
@keyframes dash-flow-online {
    0% {
        stroke-dashoffset: 72;
        opacity: 0.9;
    }
    50% {
        opacity: 1;
    }
    100% {
        stroke-dashoffset: 0;
        opacity: 0.9;
    }
}

@keyframes dash-flow-offline {
    0% {
        stroke-dashoffset: 72;
        opacity: 0.7;
    }
    25% {
        opacity: 0.9;
    }
    50% {
        opacity: 0.7;
    }
    75% {
        opacity: 0.9;
    }
    100% {
        stroke-dashoffset: 0;
        opacity: 0.7;
    }
}

@keyframes pulse-glow-online {
    0%, 100% {
        filter: drop-shadow(0 0 2px rgba(16, 185, 129, 0.6));
    }
    50% {
        filter: drop-shadow(0 0 6px rgba(16, 185, 129, 1)) drop-shadow(0 0 12px rgba(16, 185, 129, 0.5));
    }
}

@keyframes pulse-glow-offline {
    0%, 100% {
        filter: drop-shadow(0 0 2px rgba(239, 68, 68, 0.6));
    }
    50% {
        filter: drop-shadow(0 0 6px rgba(239, 68, 68, 1)) drop-shadow(0 0 12px rgba(239, 68, 68, 0.5));
    }
}

/* Leaflet.Editable - Vertex markers (titik edit polyline) - ULTRA VISIBLE */
.leaflet-vertex-icon,
.leaflet-marker-icon.leaflet-vertex-icon,
.leaflet-div-icon.leaflet-vertex-icon,
.leaflet-editing-icon.leaflet-vertex-icon,
.leaflet-marker-icon.leaflet-div-icon.leaflet-editing-icon.leaflet-vertex-icon,
div.leaflet-vertex-icon {
    width: 30px !important;
    height: 30px !important;
    margin-left: -15px !important;
    margin-top: -15px !important;
    background: #3B82F6 !important;
    border: 5px solid #fff !important;
    border-radius: 50% !important;
    box-shadow: 0 0 0 3px #3B82F6, 0 0 0 6px rgba(255,255,255,0.8), 0 4px 15px rgba(0,0,0,0.6) !important;
    cursor: move !important;
    transition: transform 0.2s ease !important;
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
    z-index: 999999 !important;
    position: absolute !important;
    animation: vertex-pulse 1.5s ease-in-out infinite !important;
}

/* Pulse animation untuk vertex marker */
@keyframes vertex-pulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 3px #3B82F6, 0 0 0 6px rgba(255,255,255,0.8), 0 4px 15px rgba(0,0,0,0.6);
    }
    50% {
        transform: scale(1.1);
        box-shadow: 0 0 0 3px #3B82F6, 0 0 0 10px rgba(59, 130, 246, 0.3), 0 4px 15px rgba(0,0,0,0.6);
    }
}

.leaflet-vertex-icon:hover {
    transform: scale(1.3) !important;
    z-index: 1000000 !important;
    animation: none !important;
    background: #60A5FA !important;
}

/* Middle marker (titik tengah untuk menambah vertex baru) - SUPER VISIBLE */
.leaflet-middle-icon,
.leaflet-marker-icon.leaflet-middle-icon,
.leaflet-div-icon.leaflet-middle-icon,
.leaflet-editing-icon.leaflet-middle-icon,
.leaflet-marker-icon.leaflet-div-icon.leaflet-editing-icon.leaflet-middle-icon,
div.leaflet-middle-icon {
    width: 22px !important;
    height: 22px !important;
    margin-left: -11px !important;
    margin-top: -11px !important;
    cursor: crosshair !important;
    transition: transform 0.2s ease !important;
    display: block !important;
    opacity: 0.9 !important;
    visibility: visible !important;
    pointer-events: auto !important;
    z-index: 99999 !important;
    position: relative !important;
}

/* Inner circle untuk middle marker - KUNING TERANG */
.leaflet-middle-icon > div {
    width: 22px !important;
    height: 22px !important;
    background: #FBBF24 !important;
    border: 4px solid #fff !important;
}

.leaflet-middle-icon:hover {
    opacity: 1 !important;
    transform: scale(1.25) !important;
    z-index: 100000 !important;
}

/* Apply animation to main connection lines */
.connection-online,
.connection-online > *,
path.connection-online,
svg .connection-online {
    animation: dash-flow-online 1.5s linear infinite, pulse-glow-online 2s ease-in-out infinite !important;
}

.connection-offline,
.connection-offline > *,
path.connection-offline,
svg .connection-offline {
    animation: dash-flow-offline 1s linear infinite, pulse-glow-offline 1.5s ease-in-out infinite !important;
}

/* Legacy - keep for backward compatibility */

/* Shadow layer styling */
.connection-shadow-online,
.connection-shadow-offline,
.connection-shadow-unknown {
    pointer-events: none;
}

/* Border layer styling */
.connection-border-online,
.connection-border-offline,
.connection-border-unknown {
    pointer-events: none;
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

/* Context Menu Styling */
.context-menu {
    padding: 4px 0;
    font-size: 14px;
}

.context-menu-item {
    padding: 8px 16px;
    cursor: pointer;
    transition: background 0.2s;
}

.context-menu-item:hover {
    background: #f0f0f0;
}

.context-menu-item i {
    margin-right: 8px;
    width: 16px;
    display: inline-block;
}

/* Editable Polyline Styling */
.leaflet-editable-polyline {
    cursor: pointer;
}

.leaflet-editable-polyline:hover {
    opacity: 1 !important;
    filter: brightness(1.2);
}

/* Waypoint Markers */
.waypoint-marker {
    background: #667eea;
    border: 2px solid white;
    border-radius: 50%;
    width: 12px;
    height: 12px;
    cursor: move;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.waypoint-marker:hover {
    background: #764ba2;
    transform: scale(1.3);
}

/* Modal Backdrop & Content Styling */
.modal-backdrop {
    display: none !important;
}

.modal-backdrop.show {
    display: none !important;
}

.modal.fade .modal-dialog {
    transition: transform 0.3s ease-out, opacity 0.3s ease-out !important;
}

.modal.show .modal-dialog {
    transform: none !important;
}

.modal-dialog {
    margin: 2rem auto !important;
    max-width: 540px !important;
    display: flex !important;
    align-items: center !important;
    min-height: calc(100vh - 4rem) !important;
}

.modal-content {
    border-radius: 16px !important;
    box-shadow:
        0 0 0 1px rgba(0, 0, 0, 0.05),
        0 10px 30px rgba(0, 0, 0, 0.3),
        0 25px 65px rgba(0, 0, 0, 0.4) !important;
    border: none !important;
    overflow: hidden !important;
    width: 100% !important;
    position: relative !important;
}

.modal-header {
    /* Use global modal header styles from style.css */
}

.modal-header .modal-title {
    /* Use global modal title styles from style.css */
}

.modal-header .btn-close {
    /* Use global btn-close styles */
}

.modal-header .btn-close:hover {
    /* Use global btn-close hover styles */
}

.modal-body {
    padding: 1.5rem !important;
    max-height: 65vh;
    overflow-y: auto;
    background: white;
}

.modal-body::-webkit-scrollbar {
    width: 8px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.modal-footer {
    border-top: 1px solid #e5e7eb !important;
    padding: 1rem 1.5rem !important;
    background-color: #f9fafb !important;
}

/* Form Group Spacing */
.modal-body .form-group {
    margin-bottom: 1.25rem;
}

.modal-body .form-group label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
    display: block;
}

.modal-body .form-control {
    border-radius: 8px;
    border: 1px solid #d1d5db;
    padding: 0.625rem 0.875rem;
    transition: all 0.2s;
}

.modal-body .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modal-body .alert {
    border-radius: 10px;
    border: none;
}

.modal-body .alert-info {
    background-color: #eff6ff;
    color: #1e40af;
}

.modal-body .card {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.modal-body .card-header {
    border-radius: 12px 12px 0 0;
    border-bottom: 1px solid #e5e7eb;
}
</style>

<script>
let map = null;
let markers = {};
let polylines = [];
let polylineData = {}; // Store polyline data with connection info
let allMapItems = []; // Global variable to store all items for displaying chain info in popup
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
let currentEditingPolyline = null; // Track currently editing polyline
let waypoints = {}; // Store waypoints per connection

function initMap() {
    // Default center (Indonesia)
    map = L.map('map').setView([-6.2088, 106.8456], 13);

    // Initialize Leaflet.Editable with retry mechanism
    let editableRetries = 0;
    const maxRetries = 20; // Increased retries

    const initEditable = () => {
        if (typeof L.Editable !== 'undefined' && typeof L.Editable === 'function') {
            try {
                // Override default iconSize - USE CSS FOR STYLING
                // Don't use html parameter as it may conflict with Leaflet.Editable
                map.editTools = new L.Editable(map, {
                    vertexIcon: L.divIcon({
                        iconSize: [30, 30],
                        iconAnchor: [15, 15],
                        className: 'leaflet-div-icon leaflet-vertex-icon'
                    }),
                    middleIcon: L.divIcon({
                        iconSize: [22, 22],
                        iconAnchor: [11, 11],
                        className: 'leaflet-div-icon leaflet-middle-icon'
                    })
                });
                return true;
            } catch (error) {
                editableRetries++;
                if (editableRetries < maxRetries) {
                    setTimeout(initEditable, 150);
                }
                return false;
            }
        } else {
            editableRetries++;
            if (editableRetries < maxRetries) {
                setTimeout(initEditable, 150);
            }
            return false;
        }
    };

    // Start initialization with short delay to allow script loading
    setTimeout(initEditable, 200);

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

    // Click to set location pointer (always show on map click)
    map.on('click', function(e) {
        // Always show pointer when clicking on empty map area
        setLocationPointer(e.latlng);
        pointerVisible = true;
    });

    loadMap();
}

async function loadMap() {
    // CRITICAL: Load waypoints FIRST before drawing polylines
    await loadConnectionWaypoints();

    const itemsResult = await fetchAPI('/api/map-get-items.php');

    if (itemsResult && itemsResult.success) {
        // Clear existing markers and polylines
        Object.values(markers).forEach(marker => marker.remove());
        polylines.forEach(polyline => polyline.remove());
        markers = {};
        polylines = [];

        const items = itemsResult.items;
        allMapItems = items; // Store globally for displaying chain info in popup

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

            // Bind popup with sync content and prevent auto-close
            marker.bindPopup(getItemPopupContent(item), {
                autoClose: false,
                closeOnClick: false,
                closeOnEscapeKey: false,
                maxWidth: 350,
                keepInView: true
            });

            // Prevent map click event when clicking on marker
            marker.on('click', function(e) {
                L.DomEvent.stopPropagation(e);
            });

            // Update popup when opened for ONU, ODP, ODC, and Server (async details)
            marker.on('popupopen', function(e) {
                if (item.item_type === 'onu' && item.genieacs_device_id) {
                    loadONUDeviceDetails(item);
                } else if (item.item_type === 'odp') {
                    loadODPPortDetails(item);
                } else if (item.item_type === 'odc') {
                    loadODCPortDetails(item);
                } else if (item.item_type === 'server') {
                    loadServerChainInfo(item);
                    loadServerPONPorts(item);
                }
            });

            // Don't auto-hide chain on popupclose to allow clicking chain items

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
                    let polylineColor, polylineWeight, polylineDashArray, polylineOpacity;

                    if (parentMarker.itemData.status === 'online' && childMarker.itemData.status === 'online') {
                        connectionStatus = 'online';
                        polylineColor = '#10b981';
                        polylineWeight = 10;
                        polylineDashArray = '18, 18'; // Garis 18px, spasi 18px - sangat jelas
                        polylineOpacity = 0.9;
                    } else if (parentMarker.itemData.status === 'offline' || childMarker.itemData.status === 'offline') {
                        connectionStatus = 'offline';
                        polylineColor = '#ef4444';
                        polylineWeight = 10;
                        polylineDashArray = '18, 18'; // Garis 18px, spasi 18px - sangat jelas
                        polylineOpacity = 0.9;
                    } else {
                        connectionStatus = 'unknown';
                        polylineColor = '#6b7280';
                        polylineWeight = 10;
                        polylineDashArray = '12, 20'; // Garis pendek, spasi sangat lebar
                        polylineOpacity = 0.4;
                    }

                    // Load waypoints for this connection if exists
                    const connectionKey = `${item.parent_id}-${item.id}`;
                    let coords = [parentMarker.getLatLng(), childMarker.getLatLng()];

                    // Check if waypoints exist for this connection
                    if (waypoints[connectionKey] && waypoints[connectionKey].length > 0) {
                        // Insert waypoints between start and end
                        const waypointLatLngs = waypoints[connectionKey].map(wp => L.latLng(wp.lat, wp.lng));
                        coords = [parentMarker.getLatLng(), ...waypointLatLngs, childMarker.getLatLng()];
                    }

                    // Layer 1: Black shadow (bottom layer)
                    const shadowPolyline = L.polyline(coords, {
                        color: '#000000',
                        weight: polylineWeight + 2,
                        opacity: 0.3,
                        dashArray: polylineDashArray,
                        smoothFactor: 0,
                        className: `connection-shadow-${connectionStatus}`
                    }).addTo(map);

                    // Prevent pointer on shadow layer
                    shadowPolyline.on('click', function(e) {
                        L.DomEvent.stopPropagation(e);
                    });
                    shadowPolyline.on('contextmenu', function(e) {
                        L.DomEvent.stopPropagation(e);
                    });

                    polylines.push(shadowPolyline);

                    // Layer 2: White border (middle layer)
                    const borderPolyline = L.polyline(coords, {
                        color: '#ffffff',
                        weight: polylineWeight + 1,
                        opacity: 0.8,
                        dashArray: polylineDashArray,
                        smoothFactor: 0,
                        className: `connection-border-${connectionStatus}`
                    }).addTo(map);

                    // Prevent pointer on border layer
                    borderPolyline.on('click', function(e) {
                        L.DomEvent.stopPropagation(e);
                    });
                    borderPolyline.on('contextmenu', function(e) {
                        L.DomEvent.stopPropagation(e);
                    });

                    polylines.push(borderPolyline);

                    // Layer 3: Colored line (top layer) - Make it editable
                    const polyline = L.polyline(coords, {
                        color: polylineColor,
                        weight: polylineWeight,
                        opacity: polylineOpacity,
                        dashArray: polylineDashArray,
                        smoothFactor: 0,
                        className: `connection-${connectionStatus} leaflet-editable-polyline`
                    }).addTo(map);

                    // Store connection info in polyline
                    polyline.connectionData = {
                        parentId: item.parent_id,
                        childId: item.id,
                        connectionKey: connectionKey,
                        parentMarker: parentMarker,
                        childMarker: childMarker
                    };

                    // Prevent map click when clicking on polyline (to avoid showing location pointer)
                    polyline.on('click', function(e) {
                        L.DomEvent.stopPropagation(e);
                    });

                    // Add right-click context menu for polyline editing
                    polyline.on('contextmenu', function(e) {
                        L.DomEvent.stopPropagation(e);
                        showPolylineContextMenu(e, polyline);
                    });

                    // Apply animation directly to the path element
                    if (connectionStatus === 'online') {
                        const pathElement = polyline.getElement();
                        if (pathElement) {
                            pathElement.style.animation = 'dash-flow-online 1.5s linear infinite, pulse-glow-online 2s ease-in-out infinite';
                        }
                    } else if (connectionStatus === 'offline') {
                        const pathElement = polyline.getElement();
                        if (pathElement) {
                            pathElement.style.animation = 'dash-flow-offline 1s linear infinite, pulse-glow-offline 1.5s ease-in-out infinite';
                        }
                    }

                    polylines.push(polyline);

                    // Store polyline reference for later access
                    polylineData[connectionKey] = {
                        mainPolyline: polyline,
                        shadowPolyline: shadowPolyline,
                        borderPolyline: borderPolyline,
                        parentId: item.parent_id,
                        childId: item.id
                    };
                }
            }
        });
    }

    // Update item counters after loading map
    updateItemCounters();
}

// Update item count badges for each layer type
function updateItemCounters() {
    const counts = {
        server: 0,
        olt: 0,
        odc: 0,
        odp: 0,
        onu: 0
    };

    // Count items by type
    allMapItems.forEach(item => {
        if (item.item_type === 'server') {
            counts.server++;
            // Count OLT if server has olt_link configured
            if (item.properties && item.properties.olt_link && item.properties.olt_link.trim() !== '') {
                counts.olt++;
            }
        } else if (counts.hasOwnProperty(item.item_type)) {
            counts[item.item_type]++;
        }
    });

    // Update counter elements
    document.getElementById('server-count').textContent = counts.server;
    document.getElementById('olt-count').textContent = counts.olt;
    document.getElementById('odc-count').textContent = counts.odc;
    document.getElementById('odp-count').textContent = counts.odp;
    document.getElementById('onu-count').textContent = counts.onu;
}

function getItemIcon(type, status) {
    const icons = {
        server: { icon: 'bi-hdd-network', color: '#4e73df', bg: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
        olt: { icon: 'bi-broadcast', color: '#1cc88a', bg: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' },
        odc: { icon: 'bi-box-seam', color: '#36b9cc', bg: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
        odp: { icon: 'bi-box', color: '#f6c23e', bg: 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)' },
        onu: { icon: 'bi-router', color: '#e74a3b', bg: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)' },
        mikrotik: { icon: 'bi-diagram-3', color: '#5a5c69', bg: 'linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%)' }
    };

    const config = icons[type] || { icon: 'bi-circle-fill', color: '#858796', bg: '#858796' };
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
                    <i class="bi ${config.icon}" style="
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

        // Chain info section (will be loaded asynchronously)
        content += `
            <div id="server-chain-info-${item.id}" style="margin: 8px 0;">
                <div class="text-center">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading chain info...</span>
                    </div>
                    <small class="d-block text-muted">Loading connections...</small>
                </div>
            </div>
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
                <i class="bi bi-gear"></i> Edit Server Links
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
        const portsElement = document.getElementById(`odc-ports-${item.id}`);
        if (portsElement) {
            portsElement.innerHTML = '<div class="text-danger">Error loading ports</div>';
        }
    }
}

async function loadServerChainInfo(serverItem) {
    try {
        const infoElement = document.getElementById(`server-chain-info-${serverItem.id}`);
        if (!infoElement) {
            return; // Element not found
        }

        const properties = serverItem.properties || {};
        const config = serverItem.config || {};

        // Find all ODCs that are children of this server
        const odcItems = allMapItems.filter(item =>
            item.item_type === 'odc' &&
            item.parent_id == serverItem.id
        );

        let chainHtml = '';

        // ISP Info
        if (properties.isp_link) {
            chainHtml += `
                <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px; margin-bottom: 8px; background: #f8fafc;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: #10b981;">🌐 ISP Link</strong>
                            <p class="mb-0"><small>Host: ${properties.isp_link}</small></p>
                            <p class="mb-0"><small id="isp-status-${serverItem.id}">
                                <span class="spinner-border spinner-border-sm" style="width: 0.8rem; height: 0.8rem;"></span>
                                Loading...
                            </small></p>
                        </div>
                        <button class="btn btn-sm btn-primary" onclick="manageServerLinks(${serverItem.id})" style="padding: 4px 8px;">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        // MikroTik Info
        if (properties.mikrotik_device_id) {
            chainHtml += `
                <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px; margin-bottom: 8px; background: #f8fafc;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: #6366f1;">🔧 MikroTik Device</strong>
                            <p class="mb-0"><small>Device ID: ${properties.mikrotik_device_id.split('-')[2] || properties.mikrotik_device_id}</small></p>
                            <p class="mb-0"><small id="mikrotik-info-${serverItem.id}">
                                <span class="spinner-border spinner-border-sm" style="width: 0.8rem; height: 0.8rem;"></span>
                                Loading...
                            </small></p>
                        </div>
                        <button class="btn btn-sm btn-primary" onclick="manageServerLinks(${serverItem.id})" style="padding: 4px 8px;">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        // OLT Info
        if (properties.olt_link) {
            chainHtml += `
                <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px; margin-bottom: 8px; background: #f8fafc;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: #1cc88a;">📡 OLT Link</strong>
                            <p class="mb-0"><small>Host: ${properties.olt_link}</small></p>
                            <p class="mb-0"><small id="olt-status-${serverItem.id}">
                                <span class="spinner-border spinner-border-sm" style="width: 0.8rem; height: 0.8rem;"></span>
                                Loading...
                            </small></p>
                        </div>
                        <button class="btn btn-sm btn-primary" onclick="manageServerLinks(${serverItem.id})" style="padding: 4px 8px;">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        // ODC Info
        if (odcItems.length > 0) {
            chainHtml += `
                <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px; margin-bottom: 8px; background: #f8fafc;">
                    <strong style="color: #36b9cc;">📦 Connected ODCs (${odcItems.length})</strong>
                    <div style="margin-top: 6px;">
            `;

            odcItems.forEach(odc => {
                const statusBadge = odc.status === 'online' ? '🟢' : odc.status === 'offline' ? '🔴' : '⚪';
                const ponPort = odc.config?.server_pon_port || 'N/A';
                chainHtml += `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border-bottom: 1px solid #e2e8f0;">
                        <small>${statusBadge} ${odc.name} (PON ${ponPort})</small>
                        <button class="btn btn-sm btn-secondary" onclick="editItem(${odc.id})" style="padding: 2px 6px; font-size: 0.75rem;">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                `;
            });

            chainHtml += `
                    </div>
                </div>
            `;
        }

        // Show "Not configured" if nothing is set
        if (!properties.isp_link && !properties.mikrotik_device_id && !properties.olt_link && odcItems.length === 0) {
            chainHtml = '<p class="text-muted mb-0"><small><i class="bi bi-info-circle"></i> No connections configured. Click "Edit Server Links" to add.</small></p>';
        }

        infoElement.innerHTML = chainHtml;

        // NOW load async data - AFTER HTML is inserted into DOM
        if (properties.isp_link) {
            loadISPStatus(properties.isp_link, serverItem.id);
        }
        if (properties.mikrotik_device_id) {
            loadMikroTikInfo(properties.mikrotik_device_id, serverItem.id);
        }
        if (properties.olt_link) {
            loadOLTStatus(properties.olt_link, serverItem.id);
        }

    } catch (error) {
        const infoElement = document.getElementById(`server-chain-info-${serverItem.id}`);
        if (infoElement) {
            infoElement.innerHTML = '<p class="text-danger mb-0"><small>Error loading connections</small></p>';
        }
    }
}

// Helper functions to load status async
async function loadISPStatus(host, serverId) {
    const statusElement = document.getElementById(`isp-status-${serverId}`);
    if (!statusElement) return;

    try {
        const netwatchResult = await fetchAPI('/api/map-get-netwatch.php');

        if (netwatchResult && netwatchResult.success && netwatchResult.netwatch) {
            const netwatch = netwatchResult.netwatch.find(nw => nw.host === host);
            if (netwatch) {
                const status = netwatch.status === 'up' ? 'online' : 'offline';
                const statusBadge = status === 'online' ? '🟢' : '🔴';
                statusElement.innerHTML = `Status: ${statusBadge} ${status.toUpperCase()}`;
            } else {
                statusElement.innerHTML = 'Status: ⚪ Not in netwatch';
            }
        } else {
            const message = netwatchResult?.message || 'MikroTik not configured';
            statusElement.innerHTML = `Status: ⚠️ ${message}`;
        }
    } catch (error) {
        statusElement.innerHTML = 'Status: ⚠️ Error';
    }
}

async function loadMikroTikInfo(deviceId, serverId) {
    const infoElement = document.getElementById(`mikrotik-info-${serverId}`);
    if (!infoElement) return;

    try {
        const devicesResult = await fetchAPI('/api/get-devices.php');

        if (devicesResult && devicesResult.success && devicesResult.devices) {
            const device = devicesResult.devices.find(d => d.device_id === deviceId);
            if (device) {
                const status = device.status === 'online' ? 'online' : 'offline';
                const statusBadge = status === 'online' ? '🟢' : '🔴';
                infoElement.innerHTML = `Status: ${statusBadge} ${status.toUpperCase()}`;
            } else {
                infoElement.innerHTML = 'Status: ⚪ Not in GenieACS';
            }
        } else {
            const message = devicesResult?.message || 'GenieACS not configured';
            infoElement.innerHTML = `Status: ⚠️ ${message}`;
        }
    } catch (error) {
        infoElement.innerHTML = 'Status: ⚠️ Error';
    }
}

async function loadOLTStatus(host, serverId) {
    const statusElement = document.getElementById(`olt-status-${serverId}`);
    if (!statusElement) return;

    try {
        const netwatchResult = await fetchAPI('/api/map-get-netwatch.php');

        if (netwatchResult && netwatchResult.success && netwatchResult.netwatch) {
            const netwatch = netwatchResult.netwatch.find(nw => nw.host === host);
            if (netwatch) {
                const status = netwatch.status === 'up' ? 'online' : 'offline';
                const statusBadge = status === 'online' ? '🟢' : '🔴';
                statusElement.innerHTML = `Status: ${statusBadge} ${status.toUpperCase()}`;
            } else {
                statusElement.innerHTML = 'Status: ⚪ Not in netwatch';
            }
        } else {
            const message = netwatchResult?.message || 'MikroTik not configured';
            statusElement.innerHTML = `Status: ⚠️ ${message}`;
        }
    } catch (error) {
        statusElement.innerHTML = 'Status: ⚠️ Error';
    }
}

async function loadServerPONPorts(serverItem) {
    try {
        const portsElement = document.getElementById(`server-pon-ports-${serverItem.id}`);
        if (!portsElement) return;

        const config = serverItem.config || {};
        const ponPorts = config.pon_ports || {};

        if (Object.keys(ponPorts).length === 0) {
            portsElement.innerHTML = '<small class="text-muted">No PON ports configured</small>';
            return;
        }

        let portsHtml = '';
        const sortedPorts = Object.entries(ponPorts).sort((a, b) => parseInt(a[0]) - parseInt(b[0]));

        for (const [portNum, power] of sortedPorts) {
            portsHtml += `<div style="padding: 2px 0;">Port ${portNum}: ${power} dBm</div>`;
        }

        portsElement.innerHTML = portsHtml;
    } catch (error) {
        const portsElement = document.getElementById(`server-pon-ports-${serverItem.id}`);
        if (portsElement) {
            portsElement.innerHTML = '<div class="text-danger">Error loading PON ports</div>';
        }
    }
}

function extractIP(ipString) {
    if (!ipString || ipString === 'N/A') return 'N/A';
    const match = ipString.match(/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/);
    return match ? match[1] : 'N/A';
}

function showAddItemModal() {
    // Update form with current pointer coordinates if pointer exists
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
}

async function updateItemForm(type) {
    const dynamicFields = document.getElementById('dynamic-fields');

    // Show loading indicator
    dynamicFields.innerHTML = '<div class="text-center py-3"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading form fields...</p></div>';

    // Load all items for parent selection
    const itemsResult = await fetchAPI('/api/map-get-items.php');
    let allItems = [];
    if (itemsResult && itemsResult.success) {
        allItems = itemsResult.items;
    }

    switch(type) {
        case 'server':
            // Load netwatch and devices in parallel for faster loading
            const [netwatchResultServer, genieacsDevicesResult] = await Promise.all([
                fetchAPI('/api/map-get-netwatch.php'),
                fetchAPI('/api/get-devices.php')
            ]);

            // Process netwatch options
            let netwatchOptionsServer = '<option value="">No Link</option>';
            if (netwatchResultServer && netwatchResultServer.success && netwatchResultServer.netwatch) {
                netwatchResultServer.netwatch.forEach(nw => {
                    netwatchOptionsServer += `<option value="${nw.host}">${nw.host} - ${nw.comment || 'No comment'}</option>`;
                });
            }

            // Process GenieACS devices options
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
                    <input type="number" id="pon_port_count" name="pon_port_count" class="form-control" value="4" min="1" max="16" onchange="generatePonPortFields(this.value); updateODCPonPortOptions(this.value);">
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

            // Add ODP group with custom ratio - check port availability for each
            if (odpItemsWithCustomRatio.length > 0) {
                // Fetch used ports for all ODPs
                const odpPortAvailability = await Promise.all(
                    odpItemsWithCustomRatio.map(async item => {
                        const ratio = item.config.splitter_ratio;
                        const ratioValues = ratio.split(':');
                        const availablePort = `${ratioValues[1]}%`;

                        // Fetch used ports for this ODP
                        const result = await fetchAPI(`/api/map-get-used-ports.php?parent_id=${item.id}&parent_type=odp`);
                        const usedPorts = result && result.success ? result.used_ports : [];
                        const isPortUsed = usedPorts.includes(availablePort);

                        return {
                            item: item,
                            ratio: ratio,
                            availablePort: availablePort,
                            isPortUsed: isPortUsed
                        };
                    })
                );

                parentOptions += '<optgroup label="ODP (Splitter Custom Ratio tersedia)">';
                odpPortAvailability.forEach(({ item, ratio, availablePort, isPortUsed }) => {
                    let statusBadge, disabledAttr, label;

                    if (isPortUsed) {
                        // Port sudah penuh - tampilkan dengan indikator abu-abu dan disabled
                        statusBadge = '⚫';
                        disabledAttr = 'disabled';
                        label = `${item.name} (Port ${availablePort} - Sudah penuh)`;
                    } else {
                        // Port masih tersedia - tampilkan dengan status online/offline
                        statusBadge = item.status === 'online' ? '🟢' : item.status === 'offline' ? '🔴' : '⚪';
                        disabledAttr = '';
                        label = `${item.name} (Port ${availablePort} tersisa)`;
                    }

                    parentOptions += `<option value="${item.id}" data-type="odp" data-ratio="${ratio}" ${disabledAttr}>${statusBadge} ${label}</option>`;
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
                    <select name="odc_port" id="odc-port-select" class="form-control">
                        <option value="">Pilih ODC terlebih dahulu</option>
                    </select>
                    <small class="text-muted">Port ODC yang tersedia (belum terpakai)</small>
                </div>
                <div class="form-group" id="odp-port-group" style="display: none;">
                    <label>Parent ODP Port <span class="text-danger">*</span></label>
                    <select name="parent_odp_port" class="form-control">
                        <option value="">Pilih port parent ODP</option>
                    </select>
                    <small class="text-muted">Port cascading dari parent ODP (hanya port dengan % lebih besar yang tersedia)</small>
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
                        <option value="20:80">20:80 (7.0 dB)</option>
                        <option value="30:70">30:70 (5.2 dB)</option>
                        <option value="50:50">50:50 (3.0 dB)</option>
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
    const odcPortSelect = document.getElementById('odc-port-select');
    const odpPortSelect = document.querySelector('select[name="parent_odp_port"]');

    if (parentType === 'odc') {
        // Show ODC port field, hide ODP port field
        odcPortGroup.style.display = 'block';
        odpPortGroup.style.display = 'none';
        odcPortSelect.required = true;
        odpPortSelect.required = false;

        // Fetch used ODC ports and populate available ports
        if (parentId) {
            const result = await fetchAPI(`/api/map-get-used-ports.php?parent_id=${parentId}&parent_type=odc`);

            // Get ODC config to know total port count
            const itemsResult = await fetchAPI('/api/map-get-items.php');
            const odcItem = itemsResult.items.find(item => item.id == parentId);
            const portCount = odcItem?.config?.port_count || 4;

            const usedPorts = (result && result.success) ? result.used_ports : [];

            // Generate available ports
            let portOptions = '<option value="">Pilih Port ODC</option>';
            for (let i = 1; i <= portCount; i++) {
                const isUsed = usedPorts.includes(i);
                if (!isUsed) {
                    portOptions += `<option value="${i}">Port ${i} (Tersedia)</option>`;
                } else {
                    portOptions += `<option value="${i}" disabled>Port ${i} (Sudah digunakan)</option>`;
                }
            }

            odcPortSelect.innerHTML = portOptions;

            // Show info about available ports
            const availableCount = portCount - usedPorts.length;
            const warningDiv = document.getElementById('odc-port-warning') || document.createElement('div');
            warningDiv.id = 'odc-port-warning';

            if (availableCount === 0) {
                warningDiv.className = 'alert alert-danger mt-2';
                warningDiv.innerHTML = `<small><i class="bi bi-exclamation-circle"></i> <b>Semua port sudah digunakan!</b> Pilih ODC lain atau tambah ODC baru.</small>`;
            } else if (usedPorts.length > 0) {
                warningDiv.className = 'alert alert-info mt-2';
                warningDiv.innerHTML = `<small><i class="bi bi-info-circle"></i> <b>${availableCount} port tersedia</b> dari ${portCount} total port.</small>`;
            } else {
                warningDiv.className = 'alert alert-success mt-2';
                warningDiv.innerHTML = `<small><i class="bi bi-check-circle"></i> <b>Semua ${portCount} port tersedia!</b></small>`;
            }

            if (!document.getElementById('odc-port-warning')) {
                odcPortSelect.parentElement.appendChild(warningDiv);
            }
        }

    } else if (parentType === 'odp') {
        // Show ODP port field, hide ODC port field
        odcPortGroup.style.display = 'none';
        odpPortGroup.style.display = 'block';
        odcPortSelect.required = false;
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
        // Logic: Port kecil (20%, 30%, 50%) sudah digunakan sebagai input ODP itu sendiri
        // Yang tersedia hanya port besar (80%, 70%, 50%) untuk cascading
        const ratio = selectedOption.getAttribute('data-ratio');
        const ratioValues = ratio.split(':');

        // Port dengan persentase lebih besar (index 1) adalah yang tersedia untuk cascading
        const availablePort = `${ratioValues[1]}%`;
        const portUsed = usedPorts.includes(availablePort);

        odpPortSelect.innerHTML = `
            <option value="">Pilih port parent ODP</option>
            <option value="${availablePort}" ${portUsed ? 'disabled' : ''} ${!portUsed ? 'selected' : ''}>
                ${ratioValues[1]}% (Port tersisa untuk cascading) ${portUsed ? '❌ Sudah digunakan' : '✅ Tersedia'}
            </option>
        `;

        // Show warning if port is used
        if (portUsed) {
            showToast('Port cascading parent ODP sudah digunakan. Pilih parent lain.', 'warning');

            // Add info alert
            const warningDiv = document.getElementById('odp-port-warning') || document.createElement('div');
            warningDiv.id = 'odp-port-warning';
            warningDiv.className = 'alert alert-danger mt-2';
            warningDiv.innerHTML = `<small><i class="bi bi-exclamation-circle"></i> <b>Port ${availablePort} sudah digunakan!</b> Parent ODP ini sudah tidak bisa digunakan untuk cascading. Pilih ODP lain.</small>`;

            if (!document.getElementById('odp-port-warning')) {
                odpPortSelect.parentElement.appendChild(warningDiv);
            }
        } else {
            // Show success info
            const infoDiv = document.getElementById('odp-port-warning') || document.createElement('div');
            infoDiv.id = 'odp-port-warning';
            infoDiv.className = 'alert alert-success mt-2';
            infoDiv.innerHTML = `<small><i class="bi bi-check-circle"></i> <b>Port ${availablePort} tersedia!</b> Port ${ratioValues[0]}% sudah digunakan sebagai input ODP parent ini.</small>`;

            if (!document.getElementById('odp-port-warning')) {
                odpPortSelect.parentElement.appendChild(infoDiv);
            }
        }

    } else {
        // No parent selected
        odcPortGroup.style.display = 'none';
        odpPortGroup.style.display = 'none';
        odcPortSelect.required = false;
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
        // Remove pointer after adding item
        removeLocationPointer();
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

    // If deleting a server, immediately clear chain markers for instant feedback
    const itemToDelete = markers[itemId]?.itemData;
    if (itemToDelete && itemToDelete.item_type === 'server') {
    }

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
    const config = item.config || {};
    const ponPorts = config.pon_ports || {};

    // Load netwatch data for ISP and OLT links
    const netwatchResult = await fetchAPI('/api/map-get-netwatch.php');
    let netwatchOptions = '<option value="">No Link</option>';
    if (netwatchResult && netwatchResult.success && netwatchResult.netwatch) {
        netwatchResult.netwatch.forEach(nw => {
            netwatchOptions += `<option value="${nw.host}">${nw.host} - ${nw.comment || 'No comment'}</option>`;
        });
    }

    // Load GenieACS devices for MikroTik
    const genieacsDevicesResult = await fetchAPI('/api/get-devices.php');
    let genieacsOptions = '<option value="">No Device</option>';
    if (genieacsDevicesResult && genieacsDevicesResult.success && genieacsDevicesResult.devices) {
        genieacsDevicesResult.devices.forEach(device => {
            const statusIcon = device.status === 'online' ? '🟢' : '🔴';
            const serialNumber = device.serial_number || device.device_id;
            genieacsOptions += `<option value="${device.device_id}">${statusIcon} ${serialNumber} - ${device.ip_address || 'N/A'}</option>`;
        });
    }

    // Populate form
    const form = document.getElementById('form-server-links');
    form.item_id.value = itemId;

    document.getElementById('isp-link-select').innerHTML = netwatchOptions;
    document.getElementById('mikrotik-device-select').innerHTML = genieacsOptions;
    document.getElementById('olt-link-select').innerHTML = netwatchOptions;

    // Set current values
    if (properties.isp_link) {
        document.querySelector('[name="isp_link"]').value = properties.isp_link;
    }
    if (properties.mikrotik_device_id) {
        document.querySelector('[name="mikrotik_device_id"]').value = properties.mikrotik_device_id;
    }
    if (properties.olt_link) {
        document.querySelector('[name="olt_link"]').value = properties.olt_link;
    }

    // Generate PON output power fields
    const ponOutputContainer = document.getElementById('pon-output-power-container');
    let ponHtml = '<div class="form-group"><label>⚡ PON Output Power per Port (dBm)</label>';

    const ponPortCount = Object.keys(ponPorts).length || 4; // Default 4 if no ports
    for (let i = 1; i <= ponPortCount; i++) {
        const currentPower = ponPorts[i] || '2.00';
        ponHtml += `
            <div class="input-group mb-2">
                <span class="input-group-text" style="width: 80px;">Port ${i}</span>
                <input type="number" step="0.01" name="pon_port_${i}_power" class="form-control" value="${currentPower}" placeholder="2.00">
                <span class="input-group-text">dBm</span>
            </div>
        `;
    }
    ponHtml += '<small class="text-muted">Output power untuk setiap PON port</small></div>';
    ponOutputContainer.innerHTML = ponHtml;

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

// Pause auto-refresh temporarily (for editing)
function pauseAutoRefresh() {
    if (autoRefreshEnabled) {
        stopAutoRefresh();
        // Store that we paused it (not user disabled it)
        window.autoRefreshWasPaused = true;
    }
}

// Resume auto-refresh after editing
function resumeAutoRefresh() {
    // Only resume if it was paused by pauseAutoRefresh(), not if user manually disabled it
    if (window.autoRefreshWasPaused && autoRefreshEnabled) {
        startAutoRefresh();
        window.autoRefreshWasPaused = false;
    }
}

// Focus on a specific map item
function focusOnMapItem(itemType, itemId) {
    // Find the item in allMapItems
    const item = allMapItems.find(i => i.id == itemId && i.item_type === itemType);

    if (!item) {
        return;
    }

    // Pan and zoom to the item
    const lat = parseFloat(item.latitude);
    const lng = parseFloat(item.longitude);

    if (!isNaN(lat) && !isNaN(lng)) {
        // Smooth pan to location
        map.flyTo([lat, lng], 18, {
            duration: 1.5
        });

        // Find and open popup for this item after animation
        setTimeout(() => {
            if (markers[item.id]) {
                markers[item.id].openPopup();
            }
        }, 1600);
    }
}

// ============================================================================
// EDITABLE POLYLINES & WAYPOINTS FUNCTIONS
// ============================================================================

// Show context menu for polyline editing
function showPolylineContextMenu(e, polyline) {
    const contextMenu = document.getElementById('polyline-context-menu');
    contextMenu.style.display = 'block';
    contextMenu.style.left = e.originalEvent.pageX + 'px';
    contextMenu.style.top = e.originalEvent.pageY + 'px';

    // Store reference to current polyline
    window.currentEditingPolyline = polyline;
}

// Close context menu
function closeContextMenu() {
    const contextMenu = document.getElementById('polyline-context-menu');
    contextMenu.style.display = 'none';

    // If there's an editing polyline, disable edit mode and resume auto-refresh
    if (window.currentEditingPolyline) {
        const polyline = window.currentEditingPolyline;
        if (polyline.editEnabled && polyline.editEnabled()) {
            polyline.disableEdit();
            resumeAutoRefresh();
        }
    }

    window.currentEditingPolyline = null;
}

// Hide context menu when clicking elsewhere
document.addEventListener('click', function(e) {
    const contextMenu = document.getElementById('polyline-context-menu');
    if (contextMenu && !contextMenu.contains(e.target)) {
        closeContextMenu();
    }
});

// Enable polyline editing mode
function enablePolylineEdit() {
    if (!window.currentEditingPolyline) {
        showToast('Tidak ada polyline yang dipilih', 'warning');
        closeContextMenu();
        return;
    }

    const polyline = window.currentEditingPolyline;
    const connectionData = polyline.connectionData;

    if (!connectionData) {
        showToast('Data koneksi tidak ditemukan', 'error');
        closeContextMenu();
        return;
    }

    // Check if Leaflet.Editable is available
    if (!map || !map.editTools) {
        showToast('Edit mode belum siap. Silakan refresh halaman.', 'error');
        closeContextMenu();
        return;
    }

    // Check if already in edit mode
    if (polyline.editEnabled && polyline.editEnabled()) {
        polyline.disableEdit();

        // Disconnect marker observer
        if (window.markerObserver) {
            window.markerObserver.disconnect();
            window.markerObserver = null;
        }

        // Resume auto-refresh when exiting edit mode
        resumeAutoRefresh();

        showToast('Edit mode dinonaktifkan.', 'info');
    } else {
        // Enable editing using Leaflet.Editable
        try {
            // PAUSE auto-refresh to prevent interruption during editing
            pauseAutoRefresh();

            // Verify map.editTools exists
            if (!map.editTools) {
                console.error('Leaflet.Editable not initialized');
                showToast('Edit mode belum siap. Silakan refresh halaman dan coba lagi.', 'error');
                closeContextMenu();
                return;
            }

            // Verify polyline has enableEdit method
            if (typeof polyline.enableEdit !== 'function') {
                console.error('Polyline does not have enableEdit method', polyline);
                showToast('Polyline tidak mendukung edit mode.', 'error');
                closeContextMenu();
                return;
            }

            // Enable edit mode on polyline directly
            const editor = polyline.enableEdit();

            // CRITICAL FIX: Force vertex marker creation if Leaflet.Editable didn't create them
            setTimeout(() => {
                const layersInEditLayer = editor.editLayer ? editor.editLayer.getLayers().length : 0;

                // Force creation if no markers exist
                if (layersInEditLayer === 0) {
                    const latlngs = polyline.getLatLngs();

                    // Manually set editor as enabled if needed
                    if (!editor._enabled) {
                        editor._enabled = true;
                    }

                    // Ensure editLayer is added to map (critical for marker visibility)
                    if (!map.hasLayer(editor.editLayer)) {
                        editor.editLayer.addTo(map);
                    }

                    // Ensure tools.editLayer is added to map
                    if (editor.tools && editor.tools.editLayer && !map.hasLayer(editor.tools.editLayer)) {
                        editor.tools.editLayer.addTo(map);
                    }

                    // Force create markers for each latlng
                    for (let i = 0; i < latlngs.length; i++) {
                        const marker = editor.addVertexMarker(latlngs[i], latlngs);

                        // Ensure marker is in editLayer
                        if (!editor.editLayer.hasLayer(marker)) {
                            editor.editLayer.addLayer(marker);
                        }

                        // Force marker.onAdd() to create icon if not exists
                        if (!marker._icon && marker.onAdd && map) {
                            marker.onAdd(map);
                        }
                    }
                }
            }, 200);

            // Apply custom styling to vertex markers for better visibility
            const forceMarkerVisibility = () => {
                const vertexMarkers = document.querySelectorAll('.leaflet-vertex-icon');
                const middleMarkers = document.querySelectorAll('.leaflet-middle-icon');

                // Force inline styles on every vertex marker
                vertexMarkers.forEach((marker) => {
                    marker.style.cssText = `
                        width: 30px !important;
                        height: 30px !important;
                        margin-left: -15px !important;
                        margin-top: -15px !important;
                        background: #3B82F6 !important;
                        border: 5px solid #fff !important;
                        border-radius: 50% !important;
                        box-shadow: 0 0 0 3px #3B82F6, 0 0 0 6px rgba(255,255,255,0.8), 0 4px 15px rgba(0,0,0,0.6) !important;
                        cursor: move !important;
                        display: block !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                        pointer-events: auto !important;
                        z-index: 999999 !important;
                        position: absolute !important;
                    `;
                });

                // Force inline styles on every middle marker
                middleMarkers.forEach((marker) => {
                    marker.style.cssText = `
                        width: 22px !important;
                        height: 22px !important;
                        margin-left: -11px !important;
                        margin-top: -11px !important;
                        background: #FBBF24 !important;
                        border: 4px solid #fff !important;
                        border-radius: 50% !important;
                        cursor: crosshair !important;
                        display: block !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                        pointer-events: auto !important;
                        z-index: 99999 !important;
                        position: absolute !important;
                    `;
                });
            };

            // Apply immediately
            setTimeout(forceMarkerVisibility, 100);
            // Apply again to catch any delayed creation
            setTimeout(forceMarkerVisibility, 500);
            // Apply one more time to be absolutely sure
            setTimeout(forceMarkerVisibility, 1000);

            // Also use MutationObserver to catch markers as they're added
            // Store globally so we can disconnect it later
            if (window.markerObserver) {
                window.markerObserver.disconnect();
            }

            window.markerObserver = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.classList && (
                            node.classList.contains('leaflet-vertex-icon') ||
                            node.classList.contains('leaflet-middle-icon')
                        )) {
                            forceMarkerVisibility();
                        }
                    });
                });
            });

            // Observe the map pane for new markers
            const mapPane = document.querySelector('.leaflet-map-pane');
            if (mapPane) {
                window.markerObserver.observe(mapPane, { childList: true, subtree: true });
            }

            showToast('Edit mode aktif! Drag titik-titik untuk mengubah jalur. Klik garis untuk menambah titik waypoint. Auto-refresh dinonaktifkan sementara.', 'success');

            // Add event listeners to sync shadow and border polylines when main polyline is edited
            const syncPolylineLayers = function() {
                if (connectionData && polylineData[connectionData.connectionKey]) {
                    const latlngs = polyline.getLatLngs();
                    const shadowPolyline = polylineData[connectionData.connectionKey].shadowPolyline;
                    const borderPolyline = polylineData[connectionData.connectionKey].borderPolyline;

                    if (shadowPolyline) {
                        shadowPolyline.setLatLngs(latlngs);
                    }
                    if (borderPolyline) {
                        borderPolyline.setLatLngs(latlngs);
                    }
                }
            };

            // Remove old event listeners first to avoid duplicates
            polyline.off('editable:vertex:drag');
            polyline.off('editable:vertex:dragend');
            polyline.off('editable:vertex:new');
            polyline.off('editable:vertex:deleted');

            // Sync on vertex drag
            polyline.on('editable:vertex:drag', syncPolylineLayers);
            polyline.on('editable:vertex:dragend', syncPolylineLayers);

            // Sync on new vertex added
            polyline.on('editable:vertex:new', syncPolylineLayers);

            // Sync on vertex deleted
            polyline.on('editable:vertex:deleted', syncPolylineLayers);
        } catch (error) {
            showToast('Gagal mengaktifkan edit mode', 'error');
        }
    }

    closeContextMenu();
}

// Save polyline waypoints to database
async function savePolylineWaypoints() {
    if (!window.currentEditingPolyline) {
        showToast('Tidak ada polyline yang dipilih', 'warning');
        closeContextMenu();
        return;
    }

    const polyline = window.currentEditingPolyline;
    const connectionData = polyline.connectionData;

    if (!connectionData) {
        showToast('Data koneksi tidak ditemukan', 'danger');
        closeContextMenu();
        return;
    }

    // Get all latlngs from polyline (excluding first and last which are markers)
    const latlngs = polyline.getLatLngs();
    const waypoints = [];

    // Skip first (parent marker) and last (child marker) points
    for (let i = 1; i < latlngs.length - 1; i++) {
        waypoints.push({
            lat: latlngs[i].lat,
            lng: latlngs[i].lng
        });
    }

    showLoading();

    const result = await fetchAPI('/api/map-save-waypoints.php', {
        method: 'POST',
        body: JSON.stringify({
            parent_id: connectionData.parentId,
            child_id: connectionData.childId,
            waypoints: waypoints
        })
    });

    hideLoading();

    if (result && result.success) {
        showToast(`Waypoints berhasil disimpan (${waypoints.length} titik)`, 'success');

        // Update global waypoints storage
        waypoints[connectionData.connectionKey] = waypoints;

        // Update polyline display immediately without reload
        const newCoords = [
            connectionData.parentMarker.getLatLng(),
            ...latlngs.slice(1, -1), // Keep the waypoints
            connectionData.childMarker.getLatLng()
        ];

        // Update all three layers (shadow, border, main)
        if (polylineData[connectionData.connectionKey]) {
            const { shadowPolyline, borderPolyline, mainPolyline } = polylineData[connectionData.connectionKey];
            if (shadowPolyline) shadowPolyline.setLatLngs(newCoords);
            if (borderPolyline) borderPolyline.setLatLngs(newCoords);
            if (mainPolyline) mainPolyline.setLatLngs(newCoords);
        }

        // Disable edit mode after saving
        if (polyline.editEnabled && polyline.editEnabled()) {
            polyline.disableEdit();
        }

        // Resume auto-refresh after saving
        resumeAutoRefresh();
    } else {
        showToast(result.message || 'Gagal menyimpan waypoints', 'danger');
    }

    closeContextMenu();
}

// Reset polyline to straight line (remove all waypoints)
async function resetPolylineToStraight() {
    if (!window.currentEditingPolyline) {
        showToast('Tidak ada polyline yang dipilih', 'warning');
        closeContextMenu();
        return;
    }

    if (!confirm('Apakah Anda yakin ingin menghapus semua waypoints dan kembali ke garis lurus?')) {
        closeContextMenu();
        return;
    }

    const polyline = window.currentEditingPolyline;
    const connectionData = polyline.connectionData;

    if (!connectionData) {
        showToast('Data koneksi tidak ditemukan', 'danger');
        closeContextMenu();
        return;
    }

    showLoading();

    const result = await fetchAPI('/api/map-save-waypoints.php', {
        method: 'POST',
        body: JSON.stringify({
            parent_id: connectionData.parentId,
            child_id: connectionData.childId,
            waypoints: [] // Empty array = reset to straight line
        })
    });

    hideLoading();

    if (result && result.success) {
        showToast('Waypoints berhasil dihapus, garis dikembalikan ke bentuk lurus', 'success');

        // Clear waypoints from global storage
        delete waypoints[connectionData.connectionKey];

        // Reset polyline to straight line immediately
        const straightCoords = [
            connectionData.parentMarker.getLatLng(),
            connectionData.childMarker.getLatLng()
        ];

        // Update all three layers (shadow, border, main)
        if (polylineData[connectionData.connectionKey]) {
            const { shadowPolyline, borderPolyline, mainPolyline } = polylineData[connectionData.connectionKey];
            if (shadowPolyline) shadowPolyline.setLatLngs(straightCoords);
            if (borderPolyline) borderPolyline.setLatLngs(straightCoords);
            if (mainPolyline) mainPolyline.setLatLngs(straightCoords);
        }

        // Disable edit mode after reset
        if (polyline.editEnabled && polyline.editEnabled()) {
            polyline.disableEdit();
        }

        // Resume auto-refresh after reset
        resumeAutoRefresh();
    } else {
        showToast(result.message || 'Gagal menghapus waypoints', 'danger');
    }

    closeContextMenu();
}

// Load waypoints from database when map loads
async function loadConnectionWaypoints() {
    try {
        const result = await fetchAPI('/api/map-get-waypoints.php');

        if (result && result.success && result.waypoints) {
            // Store waypoints in global variable
            waypoints = {};

            result.waypoints.forEach(conn => {
                const connectionKey = `${conn.from_item_id}-${conn.to_item_id}`;
                waypoints[connectionKey] = conn.path_coordinates || [];

            });
        }
    } catch (error) {
        // Silent fail - waypoints are optional
    }
}

document.addEventListener('DOMContentLoaded', async function() {
    <?php if ($genieacsConfigured): ?>
        initMap();

        startAutoRefresh(); // Start auto-refresh on page load

        // Check if there's a focus parameter in URL
        const urlParams = new URLSearchParams(window.location.search);
        const focusType = urlParams.get('focus_type');
        const focusId = urlParams.get('focus_id');

        if (focusType && focusId) {
            // Wait for map items to load, then focus on the item
            setTimeout(() => {
                focusOnMapItem(focusType, focusId);
            }, 1500);
        }
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>

<!-- Load Leaflet.Editable after Leaflet.js is loaded (from footer) -->
<script src="/assets/js/Leaflet.Editable.js"></script>

<!-- Re-initialize map after Leaflet.Editable is loaded -->
<script>
// Leaflet.Editable initialization handled by map
</script>
