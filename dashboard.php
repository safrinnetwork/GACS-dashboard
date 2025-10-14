<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

// Check if GenieACS is configured
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
    <!-- Stats Cards -->
    <div class="stats-grid" id="stats-container">
        <div class="stat-card primary">
            <div class="stat-info">
                <h3 id="stat-total">-</h3>
                <p>Total Devices</p>
            </div>
            <div class="stat-icon">
                <i class="bi bi-router"></i>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-info">
                <h3 id="stat-online">-</h3>
                <p>Online</p>
            </div>
            <div class="stat-icon">
                <i class="bi bi-check-circle"></i>
            </div>
        </div>

        <div class="stat-card danger">
            <div class="stat-info">
                <h3 id="stat-offline">-</h3>
                <p>Offline</p>
            </div>
            <div class="stat-icon">
                <i class="bi bi-x-circle"></i>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-info">
                <h3 id="stat-uptime">-</h3>
                <p>Avg Uptime</p>
            </div>
            <div class="stat-icon">
                <i class="bi bi-clock-history"></i>
            </div>
        </div>
    </div>

    <!-- Device Overview & Uplink -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-bar-chart"></i> Device Overview
                    <button class="btn btn-sm btn-primary float-end" onclick="loadDashboardData()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div style="max-width: 300px; margin: 0 auto;">
                        <canvas id="deviceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-reception-4"></i> Uplink Signal Strength
                    <button class="btn btn-sm btn-primary float-end" onclick="loadUplinkData()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div style="max-width: 300px; margin: 0 auto;">
                        <canvas id="uplinkChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Devices -->
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-activity"></i> Recent Device Activity
                </div>
                <div class="card-body">
                    <div id="recent-devices">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
let deviceChart = null;
let uplinkChart = null;

async function loadDashboardData() {
    try {
        const result = await fetchAPI('/api/dashboard-stats.php');

        if (result && result.success) {
            const stats = result.stats;

            document.getElementById('stat-total').textContent = stats.total;
            document.getElementById('stat-online').textContent = stats.online;
            document.getElementById('stat-offline').textContent = stats.offline;

            // Calculate percentage
            const onlinePercentage = stats.total > 0 ? Math.round((stats.online / stats.total) * 100) : 0;
            document.getElementById('stat-uptime').textContent = onlinePercentage + '%';

            // Update chart
            updateChart(stats);
        } else {
            showToast('Gagal memuat data dashboard', 'danger');
        }
    } catch (error) {
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.error('Error loading dashboard:', error);
        }
    }
}

function updateChart(stats) {
    const ctx = document.getElementById('deviceChart').getContext('2d');

    if (deviceChart) {
        deviceChart.destroy();
    }

    deviceChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Online', 'Offline'],
            datasets: [{
                data: [stats.online, stats.offline],
                backgroundColor: [
                    'rgba(28, 200, 138, 0.8)',
                    'rgba(231, 74, 59, 0.8)'
                ],
                borderColor: [
                    'rgba(28, 200, 138, 1)',
                    'rgba(231, 74, 59, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Device Status Distribution'
                }
            }
        }
    });
}

async function loadUplinkData() {
    try {
        const result = await fetchAPI('/api/uplink-stats.php');

        if (result && result.success) {
            updateUplinkChart(result.data);
        } else {
            showToast('Gagal memuat data uplink', 'danger');
        }
    } catch (error) {
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.error('Error loading uplink:', error);
        }
    }
}

function updateUplinkChart(data) {
    const ctx = document.getElementById('uplinkChart').getContext('2d');

    if (uplinkChart) {
        uplinkChart.destroy();
    }

    uplinkChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Excellent', 'Good', 'Fair', 'Poor', 'No Signal'],
            datasets: [{
                data: [data.excellent, data.good, data.fair, data.poor, data.no_signal],
                backgroundColor: [
                    'rgba(28, 200, 138, 0.8)',  // Excellent - green
                    'rgba(52, 152, 219, 0.8)',  // Good - blue
                    'rgba(241, 196, 15, 0.8)',  // Fair - yellow
                    'rgba(231, 76, 60, 0.8)',   // Poor - red
                    'rgba(149, 165, 166, 0.8)'  // No signal - gray
                ],
                borderColor: [
                    'rgba(28, 200, 138, 1)',
                    'rgba(52, 152, 219, 1)',
                    'rgba(241, 196, 15, 1)',
                    'rgba(231, 76, 60, 1)',
                    'rgba(149, 165, 166, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        padding: 5,
                        font: {
                            size: 9
                        }
                    }
                },
                title: {
                    display: true,
                    text: 'PON Signal Distribution'
                }
            }
        }
    });
}

async function loadRecentDevices() {
    const container = document.getElementById('recent-devices');
    container.innerHTML = '<div class="spinner"></div>';

    try {
        const result = await fetchAPI('/api/recent-devices.php');

        if (result && result.success) {
            const devices = result.devices;

            if (devices.length === 0) {
                container.innerHTML = '<p class="text-center text-muted">No recent device activity</p>';
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-hover"><thead><tr>';
            html += '<th>Serial Number</th>';
            html += '<th>Product Class</th>';
            html += '<th>IP Address</th>';
            html += '<th>WiFi SSID</th>';
            html += '<th>Status</th>';
            html += '<th>Last Inform</th>';
            html += '</tr></thead><tbody>';

            devices.forEach(device => {
                const statusBadge = device.status === 'online'
                    ? '<span class="badge online">Online</span>'
                    : '<span class="badge offline">Offline</span>';

                html += '<tr>';
                html += `<td><a href="/device-detail.php?id=${encodeURIComponent(device.device_id)}">${device.serial_number}</a></td>`;
                html += `<td>${device.product_class}</td>`;
                html += `<td>${device.ip_address}</td>`;
                html += `<td>${device.wifi_ssid}</td>`;
                html += `<td>${statusBadge}</td>`;
                html += `<td>${device.last_inform}</td>`;
                html += '</tr>';
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="text-center text-danger">Failed to load recent devices</p>';
        }
    } catch (error) {
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.error('Error loading recent devices:', error);
        }
        container.innerHTML = '<p class="text-center text-danger">Error loading data</p>';
    }
}

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($genieacsConfigured): ?>
        loadDashboardData();
        loadUplinkData();
        loadRecentDevices();
        // Auto refresh every 30 seconds
        setInterval(() => {
            loadDashboardData();
            loadUplinkData();
            loadRecentDevices();
        }, 30000);
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
