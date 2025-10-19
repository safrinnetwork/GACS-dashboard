# GACS Dashboard v1.1.0-beta

<div align="center">

![Status](https://img.shields.io/badge/Status-Beta-yellow)
![Version](https://img.shields.io/badge/Version-1.0.0--beta-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)

**GenieACS Network Monitoring Dashboard dengan Visualisasi Topologi PON**

Dashboard berbasis web yang powerful untuk memonitor dan memvisualisasikan topologi jaringan GenieACS dengan real-time monitoring, editable polylines, dan integrasi Telegram.

[Fitur](#-fitur) • [Instalasi](#-quick-installation) • [Dokumentasi](#-documentation) • [Screenshots](#-screenshots)

</div>

---

## ➤ Telegram Support

- https://t.me/+QDz9bvRUZ95hZGY1

## 📋 Table of Contents

- [About](#-about)
- [Features](#-fitur)
- [Requirements](#-requirements)
- [Quick Installation](#-quick-installation)
- [Configuration](#-configuration)
- [Telegram Bot](#-telegram-bot-features)
- [Cron Jobs](#-automated-tasks-cron-jobs)
- [Production Deployment](#-production-deployment)
- [Screenshots](#-screenshots)
- [Troubleshooting](#-troubleshooting)
- [Documentation](#-documentation)
- [License](#-license)

---

## 🎯 About

**GACS Dashboard** adalah platform monitoring jaringan fiber optik PON yang terintegrasi dengan GenieACS. Menyediakan visualisasi topologi interaktif, monitoring real-time, dan notifikasi Telegram untuk manajemen infrastruktur jaringan.

**Key Highlights:**

- 🗺️ Interactive network topology map dengan editable connection lines
- 📊 Real-time device monitoring (ONU/ONT)
- 🤖 Telegram bot dengan role-based access control
- ⚡ PON power calculator untuk optical budget
- 📱 Responsive design untuk mobile & desktop

> **Note:** Project ini dalam tahap **beta testing**. Silakan laporkan bug yang ditemukan.

---

## ✨ Fitur

### Core Features

- ✅ **Real-time Device Monitoring** - Status ONU/ONT secara real-time
- ✅ **Interactive Network Topology Map** - Visualisasi PON hierarchy dengan drag & drop
- ✅ **Editable Connection Lines** - Customize jalur koneksi dengan waypoints
- ✅ **PON Power Calculator** - Hitung optical power budget otomatis
- ✅ **GenieACS Integration** - Integrasi penuh dengan GenieACS TR-069 API
- ✅ **MikroTik API Support** - Monitor status dari MikroTik router
- ✅ **Telegram Bot** - Multi-user bot dengan 11 granular permissions

### PON Topology Support

- **Server** → **ISP** → **MikroTik** → **OLT** → **ODC** → **ODP** → **ONU/ONT**
- Support splitter 1:2 hingga 1:64 + custom ratio (20:80, 30:70, 50:50)
- GPS coordinates untuk setiap device
- Auto power calculation melalui hierarchy

### Telegram Bot Features ( BUG )

- 🤖 **Interactive Menu** - Inline keyboard untuk semua fungsi
- 🔐 **Role-Based Access** - 3 roles (Admin, Operator, Viewer) dengan 11 permissions
- 📊 **Device Management** - View, search, filter, summon devices
- 📶 **WiFi Configuration** - Edit SSID/Password via multi-step wizard
- 🗺️ **GPS Location** - Share device location dengan Google Maps link
- 📈 **Reports** - Daily/weekly automated reports dengan scheduling
- 🔔 **Notifications** - Subscribe per-device untuk status alerts

---

## 🖥️ Requirements

**Server:**

- Web server: Apache 2.4+ (dengan mod_rewrite) atau Nginx 1.18+
- PHP: 7.4+ (8.0+ recommended)
- MySQL/MariaDB: 5.7+ / 10.3+
- Composer

**PHP Extensions:**

```
php-mysqli, php-json, php-curl, php-mbstring, php-xml
```

**External Services:**

- GenieACS instance (untuk device management)
- MikroTik Router (optional, untuk network status)
- Telegram Bot (optional, untuk notifications)

---

## 🚀 Quick Installation

### Step 1: Upload & Extract

```bash
# Upload file ZIP ke hosting
unzip gacs-dashboard.zip -d /var/www/html/gacs
cd /var/www/html/gacs
```

### Step 2: Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### Step 3: Create Database

```bash
# Via MySQL command line
mysql -u root -p -e "CREATE DATABASE gacs_production CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -u root -p gacs_production < database.sql

# Atau via phpMyAdmin:
# 1. Create database: gacs_production
# 2. Import: database.sql
```

### Step 4: Configure

```bash
# Copy template files
cp config/database.php.example config/database.php
cp config/config.php.example config/config.php

# Edit database credentials
nano config/database.php
```

Update dengan credentials Anda:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'gacs_production');
```

Edit app URL:

```bash
nano config/config.php
```

```php
define('APP_URL', 'https://your-domain.com');
```

### Step 5: Set Permissions

```bash
# Set ownership (adjust user for your hosting)
chown -R www-data:www-data /var/www/html/gacs

# Set permissions
find /var/www/html/gacs -type d -exec chmod 755 {} \;
find /var/www/html/gacs -type f -exec chmod 644 {} \;

# Secure sensitive files
chmod 600 /var/www/html/gacs/config/database.php
chmod 600 /var/www/html/gacs/config/config.php
```

### Step 6: Test Installation

1. Buka browser: `https://your-domain.com`
2. Login dengan kredensial default:
   - Username: `user1234`
   - Password: `mostech`
3. ⚠️ **SEGERA GANTI PASSWORD DEFAULT!**

---

## ⚙️ Configuration

### 1. GenieACS Integration

1. Login → **Configuration** → **GenieACS** tab
2. Enter: Host, Port (7557), Username, Password
3. Click **Test Connection** → **Save**

### 2. MikroTik API

1. Configuration → **MikroTik** tab
2. Enter: Host, Port (8728), Username, Password
3. Test & Save

### 3. Telegram Bot

1. Configuration → **Telegram** tab
2. Enter: Bot Token (dari @BotFather), Chat ID
3. Test & Save
4. Set webhook:

```bash
curl "https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://your-domain.com/webhook/telegram.php"
```

5. Create admin user:

```sql
INSERT INTO telegram_users (chat_id, username, first_name, role, is_active)
VALUES ('YOUR_CHAT_ID', 'username', 'Your Name', 'admin', 1);
```

---

## 🤖 Telegram Bot Features ( BUG )

### Main Menu Commands

```
/start, /menu     - Show main menu
/help             - Command list
/stats            - Dashboard statistics
/list             - Browse all devices (10 per page)
/status <id>      - Device details
/search <keyword> - Search devices
/summon <id>      - Force device connection request
/subscriptions    - View your subscriptions
```

### Admin Commands

```
/whoami                   - View your role & permissions
/users                    - List all users
/user <chat_id>           - View user details
/setrole <chat_id> <role> - Change user role (admin/operator/viewer)
/activate <chat_id>       - Activate user
/deactivate <chat_id>     - Deactivate user
```

### Report Commands

```
/report daily             - Generate daily report now
/report weekly            - Generate weekly report now
/schedule daily HH:MM     - Schedule daily reports
/schedule weekly <day> HH:MM - Schedule weekly reports
/schedule list            - View active schedules
```

### Role-Based Access

- **👑 Admin** - Full access (11 permissions) - user management, WiFi edit
- **⚙️ Operator** - Device management (8 permissions) - summon, subscribe, reports
- **👁️ Viewer** - Read-only (4 permissions) - view devices, statistics

**Auto-Registration:** New users auto-register as "viewer" on first interaction.

### Interactive Features

- 📊 **Device List** - Pagination (10/page) dengan Previous/Next buttons
- 📶 **WiFi Edit** - Multi-step wizard (SSID → Password → Confirm)
- 🗺️ **GPS Location** - View coordinates, Google Maps, Network Map links
- 📍 **Location Sharing** - Send actual Telegram GPS pin
- 🔔 **Notifications** - Subscribe per-device untuk status changes

---

## 📅 Automated Tasks (Cron Jobs)

### Required Cron Jobs

```bash
# Edit crontab
crontab -e

# Add these lines (adjust paths):
# Device monitoring - every 5 minutes
*/5 * * * * /usr/bin/php /path/to/gacs/cron/device-monitor.php >> /var/log/gacs-monitor.log 2>&1

# Webhook monitoring - every 5 minutes
*/5 * * * * /usr/bin/php /path/to/gacs/cron/webhook-monitor.php 2>&1 | logger -t webhook-monitor

# Database backup - daily at 2 AM
0 2 * * * /path/to/gacs/cron/backup.sh >> /var/log/gacs-backup.log 2>&1

# Scheduled reports - every hour (check for pending reports)
0 * * * * /usr/bin/php /path/to/gacs/cron/send-scheduled-reports.php >> /var/log/gacs-reports.log 2>&1
```

### Test Cron Jobs Manually

```bash
# Test device monitor
php /path/to/gacs/cron/device-monitor.php

# Test webhook monitor
php /path/to/gacs/cron/webhook-monitor.php

# Test backup
bash /path/to/gacs/cron/backup.sh
```

---

## 🏭 Production Deployment

### Pre-Deployment Checklist

```bash
# 1. Backup existing data
mysqldump -u username -p database > backup_$(date +%Y%m%d).sql
tar -czf backup_files_$(date +%Y%m%d).tar.gz /var/www/html/

# 2. Set secure permissions
chmod 600 config/database.php config/config.php
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# 3. Disable PHP error display
# Edit php.ini:
display_errors = Off
log_errors = On
```

### Security Hardening

- ✅ Enable HTTPS (Let's Encrypt recommended)
- ✅ Change default password immediately
- ✅ Configure firewall rules (UFW/iptables)
- ✅ Enable fail2ban for brute-force protection
- ✅ Restrict file access (.htaccess already configured)
- ✅ Delete example files after setup

### SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache  # For Apache
sudo apt install certbot python3-certbot-nginx   # For Nginx

# Obtain certificate
sudo certbot --apache -d your-domain.com
# OR
sudo certbot --nginx -d your-domain.com

# Auto-renewal test
sudo certbot renew --dry-run
```

### Apache Virtual Host Example

```apache
<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /var/www/html/gacs

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/your-domain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/your-domain.com/privkey.pem

    <Directory /var/www/html/gacs>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/gacs-error.log
    CustomLog ${APACHE_LOG_DIR}/gacs-access.log combined
</VirtualHost>
```

---

## 📸 Screenshots

### Login Page

![Login](./preview/login.png)

### Dashboard

![Dashboard](./preview/dashboard.png)

### Device List

![Devices](./preview/device.png)

### Device Detail - Part 1

![Device Detail 1](./preview/device_detail_1.png)

### Device Detail - Part 2

![Device Detail 2](./preview/device_detail_2.png)

### Network Topology Map

![Map](./preview/map.png)

### Configuration Menu

![Configuration](./preview/menu_konfigurasi.png)

---

## 🔧 Troubleshooting

### Database Connection Error

```bash
# Check credentials
cat config/database.php

# Test MySQL connection
mysql -u your_user -p -e "SELECT 1;"

# Verify database exists
mysql -u your_user -p -e "SHOW DATABASES;"
```

### Map Not Loading

1. Check browser console (F12) for errors
2. Verify Leaflet.Editable.js exists: `ls -la assets/js/`
3. Clear browser cache (Ctrl+Shift+R)
4. Check GenieACS connection in Configuration

### Telegram Bot Not Responding

```bash
# Check webhook status
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"

# Reset webhook
curl "https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://your-domain.com/webhook/telegram.php"

# Check webhook access
curl https://your-domain.com/webhook/telegram.php
```

### Cron Jobs Not Running

```bash
# Check cron service
systemctl status cron

# View cron logs
grep CRON /var/log/syslog

# Verify crontab
crontab -l

# Test script manually
php /path/to/gacs/cron/device-monitor.php
```

### WiFi Edit Not Working

**Possible causes:**

- User lacks permission (Admin only)
- Device offline
- GenieACS not configured

**Solution:**

1. Verify user has `device.edit_wifi` permission (Admin role)
2. Check device status (must be online)
3. Test GenieACS connection: Configuration → Test Connection
4. For queued tasks (HTTP 202), wait for device inform or reboot device

---

### Key Documentation Sections

#### Installation

- Requirements & dependencies
- Database setup & import
- Configuration files
- Permission setup
- Testing installation

#### Deployment

- Pre-deployment checklist
- Web server configuration (Apache/Nginx)
- SSL certificate setup
- Security hardening
- Post-deployment testing

#### Telegram Bot

- Setup instructions
- Main menu & commands
- Interactive features (WiFi edit, GPS location)
- Role-based permissions
- Multi-step wizards
- Report scheduling

#### Cron Jobs

- Device monitoring (status notifications)
- Webhook monitoring (auto-reset)
- Database backup (daily)
- Scheduled reports
- Log cleanup

#### Architecture

- Database schema (25 tables + 1 view)
- Core libraries (GenieACS, MikroTik, PON Calculator, Telegram Bot)
- Entry points & API endpoints
- PON topology system
- Map visualization
- Security considerations

---

## 📦 File Structure

```
gacs-dashboard/
├── api/                    # API endpoints
│   ├── map-*.php          # Map operations
│   ├── get-devices.php    # Device data
│   └── update-wifi-config.php
├── assets/
│   ├── css/               # Stylesheets
│   └── js/                # JavaScript (Leaflet.Editable.js)
├── config/                # Configuration
│   ├── config.php         # App config
│   └── database.php       # DB credentials
├── cron/                  # Cron jobs
│   ├── device-monitor.php
│   ├── webhook-monitor.php
│   └── backup.sh
├── lib/                   # Core libraries
│   ├── GenieACS.php
│   ├── MikroTikAPI.php
│   ├── PONCalculator.php
│   ├── TelegramBot.php
│   └── PermissionManager.php
├── webhook/
│   └── telegram.php       # Telegram webhook
├── views/                 # View templates
├── dashboard.php          # Main dashboard
├── devices.php            # Devices page
├── device-detail.php      # Device details
├── map.php                # Network map
├── configuration.php      # Settings
├── database.sql           # Unified schema (25 tables)
└── README.md              # This file
```

---


## 🤝 Contributing

Contributions are welcome! If you'd like to contribute:

1. Fork repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

**Guidelines:**

- Follow existing code style
- Write clear commit messages
- Test your changes thoroughly
- Update documentation if needed

---

## 📞 Support

If you encounter issues or have questions:

- 📝 **Issues:** Open issue di GitHub repository
- 📖 **Documentation:** See complete documentation files above
- 🔍 **Troubleshooting:** Check troubleshooting section
- 💬 **Community:** Join discussion di GitHub Discussions

---

## 📄 License

This project is licensed under the **MIT License** - see LICENSE file for details.

---

## 🙏 Credits

**Developed by:** Mostech

**Special Thanks:**

- GenieACS community
- Leaflet.js team
- All contributors and testers

---

<div align="center">

**Dibuat dengan ❤️ untuk Network Administrators**

[⬆ Back to Top](#gacs-dashboard-v100-beta)

---

**GACS Dashboard v1.1.0-beta** | **Status: Production Ready** ✅

</div>
