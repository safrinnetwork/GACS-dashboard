-- GACS Dashboard Database Schema
-- Database: gacs-dev

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `gacs-dev` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE `gacs-dev`;

-- Table: users (untuk login)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default user (user1234 / mostech)
-- Password: mostech
INSERT INTO `users` (`username`, `password`) VALUES
('user1234', '$2y$12$PUTPynxAVyLJxonzsO/TWeGwdyahOve5kJrbdEaddI32p.ZXifESe');

-- Table: configurations (untuk menyimpan semua konfigurasi)
CREATE TABLE IF NOT EXISTS `configurations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `config_key` VARCHAR(100) NOT NULL UNIQUE,
  `config_value` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: genieacs_credentials
CREATE TABLE IF NOT EXISTS `genieacs_credentials` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `host` VARCHAR(255) NOT NULL,
  `port` INT DEFAULT 7557,
  `username` VARCHAR(100),
  `password` VARCHAR(255),
  `is_connected` TINYINT(1) DEFAULT 0,
  `last_test` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: mikrotik_credentials
CREATE TABLE IF NOT EXISTS `mikrotik_credentials` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `host` VARCHAR(255) NOT NULL,
  `port` INT DEFAULT 8728,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `is_connected` TINYINT(1) DEFAULT 0,
  `last_test` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: telegram_config
CREATE TABLE IF NOT EXISTS `telegram_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bot_token` VARCHAR(255) NOT NULL,
  `chat_id` VARCHAR(100) NOT NULL,
  `is_connected` TINYINT(1) DEFAULT 0,
  `last_test` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: map_items (untuk menyimpan item di map)
CREATE TABLE IF NOT EXISTS `map_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_type` ENUM('server', 'isp', 'mikrotik', 'olt', 'odc', 'odp', 'onu') NOT NULL,
  `parent_id` INT NULL,
  `name` VARCHAR(255) NOT NULL,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `genieacs_device_id` VARCHAR(255) NULL,
  `status` ENUM('online', 'offline', 'unknown') DEFAULT 'unknown',
  `properties` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`parent_id`) REFERENCES `map_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: map_connections (untuk menyimpan garis koneksi antar item)
CREATE TABLE IF NOT EXISTS `map_connections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `from_item_id` INT NOT NULL,
  `to_item_id` INT NOT NULL,
  `connection_type` ENUM('online', 'offline') DEFAULT 'online',
  `path_coordinates` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`from_item_id`) REFERENCES `map_items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`to_item_id`) REFERENCES `map_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: olt_config (untuk menyimpan konfigurasi OLT)
CREATE TABLE IF NOT EXISTS `olt_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `map_item_id` INT NOT NULL,
  `output_power` DECIMAL(5, 2) DEFAULT 2.00,
  `pon_count` INT DEFAULT 1,
  `attenuation_db` DECIMAL(5, 2) DEFAULT 0.00,
  `olt_link` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`map_item_id`) REFERENCES `map_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: server_pon_ports (untuk menyimpan PON ports di Server)
CREATE TABLE IF NOT EXISTS `server_pon_ports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `map_item_id` INT NOT NULL,
  `port_number` INT NOT NULL,
  `output_power` DECIMAL(5, 2) DEFAULT 2.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`map_item_id`) REFERENCES `map_items`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_server_port` (`map_item_id`, `port_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: olt_pon_ports (untuk menyimpan PON ports di OLT)
CREATE TABLE IF NOT EXISTS `olt_pon_ports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `olt_item_id` INT NOT NULL,
  `pon_number` INT NOT NULL,
  `output_power` DECIMAL(5, 2) DEFAULT 9.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`olt_item_id`) REFERENCES `map_items`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_olt_pon` (`olt_item_id`, `pon_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: odc_config (untuk menyimpan konfigurasi ODC)
CREATE TABLE IF NOT EXISTS `odc_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `map_item_id` INT NOT NULL,
  `olt_pon_port_id` INT NULL,
  `server_pon_port` INT NULL,
  `port_count` INT NOT NULL,
  `parent_attenuation_db` DECIMAL(5, 2) DEFAULT 0.00,
  `calculated_power` DECIMAL(5, 2),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`map_item_id`) REFERENCES `map_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: odp_config (untuk menyimpan konfigurasi ODP)
CREATE TABLE IF NOT EXISTS `odp_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `map_item_id` INT NOT NULL,
  `odc_port` INT NULL,
  `input_power` DECIMAL(5, 2) NULL,
  `parent_odp_port` VARCHAR(10) NULL,
  `port_count` INT NOT NULL,
  `use_splitter` TINYINT(1) DEFAULT 0,
  `splitter_ratio` VARCHAR(20),
  `calculated_power` DECIMAL(5, 2),
  `port_rx_power` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`map_item_id`) REFERENCES `map_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Note: parent_odp_port stores the selected port from parent ODP (e.g., "20%" or "80%")
-- input_power stores power BEFORE splitter (used for cascading ODP calculations)
-- calculated_power stores power AFTER splitter (displayed to user)
-- This is used when cascading ODP (ODP with ODP parent using custom ratio)

-- Table: onu_config (untuk menyimpan konfigurasi ONU)
CREATE TABLE IF NOT EXISTS `onu_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `map_item_id` INT NOT NULL,
  `odp_port` INT,
  `customer_name` VARCHAR(255),
  `genieacs_device_id` VARCHAR(255) UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`map_item_id`) REFERENCES `map_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: device_monitoring (untuk tracking device status history)
CREATE TABLE IF NOT EXISTS `device_monitoring` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `device_id` VARCHAR(255) NOT NULL,
  `status` ENUM('online', 'offline') NOT NULL,
  `notified` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index untuk performa
CREATE INDEX idx_device_id ON device_monitoring(device_id);
CREATE INDEX idx_created_at ON device_monitoring(created_at);
CREATE INDEX idx_genieacs_device_id ON map_items(genieacs_device_id);
