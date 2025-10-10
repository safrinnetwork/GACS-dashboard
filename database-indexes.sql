-- Database Performance Optimization Indexes
-- Run these to improve query performance

-- Indexes for map_items table
ALTER TABLE `map_items` ADD INDEX `idx_parent_id` (`parent_id`);
ALTER TABLE `map_items` ADD INDEX `idx_item_type` (`item_type`);
ALTER TABLE `map_items` ADD INDEX `idx_status` (`status`);
ALTER TABLE `map_items` ADD INDEX `idx_genieacs_device_id` (`genieacs_device_id`);
ALTER TABLE `map_items` ADD INDEX `idx_parent_type` (`parent_id`, `item_type`);

-- Indexes for config tables
ALTER TABLE `olt_config` ADD INDEX `idx_map_item_id` (`map_item_id`);
ALTER TABLE `odc_config` ADD INDEX `idx_map_item_id` (`map_item_id`);
ALTER TABLE `odp_config` ADD INDEX `idx_map_item_id` (`map_item_id`);
ALTER TABLE `onu_config` ADD INDEX `idx_map_item_id` (`map_item_id`);

-- Indexes for server_pon_ports
ALTER TABLE `server_pon_ports` ADD INDEX `idx_map_item_id` (`map_item_id`);
ALTER TABLE `server_pon_ports` ADD INDEX `idx_port_number` (`port_number`);

-- Indexes for credentials tables
ALTER TABLE `genieacs_credentials` ADD INDEX `idx_is_connected` (`is_connected`);
ALTER TABLE `mikrotik_credentials` ADD INDEX `idx_is_connected` (`is_connected`);

-- Indexes for map_connections
ALTER TABLE `map_connections` ADD INDEX `idx_from_item_id` (`from_item_id`);
ALTER TABLE `map_connections` ADD INDEX `idx_to_item_id` (`to_item_id`);

-- Composite indexes for common query patterns
ALTER TABLE `map_items` ADD INDEX `idx_parent_type_status` (`parent_id`, `item_type`, `status`);
ALTER TABLE `onu_config` ADD INDEX `idx_parent_port` (`odp_port`);

-- Show index usage statistics
SHOW INDEX FROM `map_items`;
SHOW INDEX FROM `olt_config`;
SHOW INDEX FROM `odc_config`;
SHOW INDEX FROM `odp_config`;
SHOW INDEX FROM `onu_config`;
SHOW INDEX FROM `server_pon_ports`;
