<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-server fa-2x mb-2"></i>
            <h3><?php echo APP_NAME; ?></h3>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="/dashboard.php" class="<?php echo ($currentPage ?? '') === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/devices.php" class="<?php echo ($currentPage ?? '') === 'devices' ? 'active' : ''; ?>">
                    <i class="fas fa-hdd"></i>
                    <span>Devices</span>
                </a>
            </li>
            <li>
                <a href="/map.php" class="<?php echo ($currentPage ?? '') === 'map' ? 'active' : ''; ?>">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>Map</span>
                </a>
            </li>
            <li>
                <a href="/configuration.php" class="<?php echo ($currentPage ?? '') === 'configuration' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Configuration</span>
                </a>
            </li>
            <li>
                <a href="/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <h4><?php echo $pageTitle ?? APP_NAME; ?></h4>
            <div class="user-info">
                <span><i class="fas fa-user"></i> <?php echo $_SESSION['username'] ?? 'User'; ?></span>
            </div>
        </div>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
