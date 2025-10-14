<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = 'Configuration';
$currentPage = 'configuration';

// Get existing configurations
$conn = getDBConnection();

$genieacs = $conn->query("SELECT * FROM genieacs_credentials ORDER BY id DESC LIMIT 1")->fetch_assoc();
$mikrotik = $conn->query("SELECT * FROM mikrotik_credentials ORDER BY id DESC LIMIT 1")->fetch_assoc();
$telegram = $conn->query("SELECT * FROM telegram_config ORDER BY id DESC LIMIT 1")->fetch_assoc();

include __DIR__ . '/views/layouts/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Konfigurasi kredensial untuk terhubung ke berbagai layanan.
        </div>
    </div>
</div>

<!-- Change Login Credentials -->
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-lock"></i> Ganti Kredensial Login
            </div>
            <div class="card-body">
                <form id="form-change-password">
                    <div class="form-group">
                        <label>Password Saat Ini</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Username Baru</label>
                        <input type="text" name="new_username" class="form-control"
                               value="<?php echo $_SESSION['username']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Password Baru (kosongkan jika tidak ingin mengubah)</label>
                        <input type="password" name="new_password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- GenieACS Configuration -->
<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-hdd-network"></i> Konfigurasi GenieACS
                <?php if ($genieacs && $genieacs['is_connected']): ?>
                    <span class="badge online float-end">Connected</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form id="form-genieacs">
                    <div class="form-group">
                        <label>Host</label>
                        <input type="text" name="host" class="form-control"
                               value="<?php echo $genieacs['host'] ?? '192.168.1.1'; ?>"
                               placeholder="192.168.1.1" required>
                    </div>
                    <div class="form-group">
                        <label>Port</label>
                        <input type="number" name="port" class="form-control"
                               value="<?php echo $genieacs['port'] ?? '7557'; ?>"
                               placeholder="7557" required>
                    </div>
                    <div class="form-group">
                        <label>Username (opsional)</label>
                        <input type="text" name="username" class="form-control"
                               value="<?php echo $genieacs['username'] ?? ''; ?>"
                               placeholder="Username">
                    </div>
                    <div class="form-group">
                        <label>Password (opsional)</label>
                        <input type="password" name="password" class="form-control"
                               value="<?php echo $genieacs['password'] ?? ''; ?>"
                               placeholder="Password">
                    </div>
                    <button type="submit" class="btn btn-success me-2">
                        <i class="bi bi-check-circle"></i> Test Connection
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveGenieACS()">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                    <?php if ($genieacs && $genieacs['last_test']): ?>
                        <small class="text-muted d-block mt-2">
                            Last test: <?php echo timeAgo($genieacs['last_test']); ?>
                        </small>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- MikroTik Configuration -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-ethernet"></i> Konfigurasi MikroTik
                <?php if ($mikrotik && $mikrotik['is_connected']): ?>
                    <span class="badge online float-end">Connected</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form id="form-mikrotik">
                    <div class="form-group">
                        <label>Host</label>
                        <input type="text" name="host" class="form-control"
                               value="<?php echo $mikrotik['host'] ?? ''; ?>"
                               placeholder="192.168.1.1" required>
                    </div>
                    <div class="form-group">
                        <label>Port API</label>
                        <input type="number" name="port" class="form-control"
                               value="<?php echo $mikrotik['port'] ?? '8728'; ?>"
                               placeholder="8728" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control"
                               value="<?php echo $mikrotik['username'] ?? ''; ?>"
                               placeholder="admin" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control"
                               value="<?php echo $mikrotik['password'] ?? ''; ?>"
                               placeholder="Password" required>
                    </div>
                    <button type="submit" class="btn btn-success me-2">
                        <i class="bi bi-check-circle"></i> Test Connection
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveMikroTik()">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                    <?php if ($mikrotik && $mikrotik['last_test']): ?>
                        <small class="text-muted d-block mt-2">
                            Last test: <?php echo timeAgo($mikrotik['last_test']); ?>
                        </small>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Telegram Configuration -->
<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="fab fa-telegram"></i> Konfigurasi Telegram Bot
                <?php if ($telegram && $telegram['is_connected']): ?>
                    <span class="badge online float-end">Connected</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form id="form-telegram">
                    <div class="form-group">
                        <label>Bot Token</label>
                        <input type="text" name="bot_token" class="form-control"
                               value="<?php echo $telegram['bot_token'] ?? ''; ?>"
                               placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11" required>
                        <small class="text-muted">Dapatkan dari @BotFather</small>
                    </div>
                    <div class="form-group">
                        <label>Chat ID</label>
                        <input type="text" name="chat_id" class="form-control"
                               value="<?php echo $telegram['chat_id'] ?? ''; ?>"
                               placeholder="123456789" required>
                        <small class="text-muted">Dapatkan dari @userinfobot</small>
                    </div>
                    <button type="submit" class="btn btn-success me-2">
                        <i class="bi bi-check-circle"></i> Test Connection
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveTelegram()">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                    <?php if ($telegram && $telegram['last_test']): ?>
                        <small class="text-muted d-block mt-2">
                            Last test: <?php echo timeAgo($telegram['last_test']); ?>
                        </small>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Change Password Form
document.getElementById('form-change-password').addEventListener('submit', async function(e) {
    e.preventDefault();
    showLoading();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    const result = await fetchAPI('/api/update-password.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });

    hideLoading();

    if (result && result.success) {
        showToast(result.message, 'success');
        this.reset();
    } else {
        showToast(result.message || 'Gagal mengupdate kredensial', 'danger');
    }
});

// GenieACS Form
document.getElementById('form-genieacs').addEventListener('submit', async function(e) {
    e.preventDefault();
    showLoading();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    const result = await fetchAPI('/api/test-genieacs.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });

    hideLoading();

    if (result && result.success) {
        showToast(result.message, 'success');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result?.message || 'Koneksi gagal', 'danger');
    }
});

// MikroTik Form
document.getElementById('form-mikrotik').addEventListener('submit', async function(e) {
    e.preventDefault();
    showLoading();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    const result = await fetchAPI('/api/test-mikrotik.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });

    hideLoading();

    if (result && result.success) {
        showToast(result.message, 'success');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result?.message || 'Koneksi gagal', 'danger');
    }
});

// Telegram Form
document.getElementById('form-telegram').addEventListener('submit', async function(e) {
    e.preventDefault();
    showLoading();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    const result = await fetchAPI('/api/test-telegram.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });

    hideLoading();

    if (result && result.success) {
        showToast(result.message, 'success');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result?.message || 'Koneksi gagal', 'danger');
    }
});

// Save GenieACS Configuration
async function saveGenieACS() {
    const form = document.getElementById('form-genieacs');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    showLoading();

    const result = await fetchAPI('/api/save-genieacs.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });

    hideLoading();

    if (result && result.success) {
        showToast(result.message, 'success');
    } else {
        showToast(result.message || 'Gagal menyimpan konfigurasi', 'danger');
    }
}

// Save MikroTik Configuration
async function saveMikroTik() {
    const form = document.getElementById('form-mikrotik');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    showLoading();

    const result = await fetchAPI('/api/save-mikrotik.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });

    hideLoading();

    if (result && result.success) {
        showToast(result.message, 'success');
    } else {
        showToast(result.message || 'Gagal menyimpan konfigurasi', 'danger');
    }
}

// Save Telegram Configuration
async function saveTelegram() {
    const form = document.getElementById('form-telegram');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    showLoading();

    const result = await fetchAPI('/api/save-telegram.php', {
        method: 'POST',
        body: JSON.stringify(data)
    });

    hideLoading();

    if (result && result.success) {
        showToast(result.message, 'success');
    } else {
        showToast(result.message || 'Gagal menyimpan konfigurasi', 'danger');
    }
}
</script>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
