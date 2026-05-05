-- =====================================================
-- Complete Database Setup for Tomato GreenGrow System
-- Database: tomato_greengrow
-- =====================================================

-- Create and use the database
CREATE DATABASE IF NOT EXISTS `tomato_greengrow`;
USE `tomato_greengrow`;

-- =====================================================
-- Table: users (with Google login support)
-- =====================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `role` enum('Admin','Manager','User','Driver') DEFAULT 'User',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`),
  KEY `idx_google_id` (`google_id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Insert sample users (passwords are 'password123' hashed)
-- =====================================================
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@gmail.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'Admin', NOW()),
(2, 'Manager User', 'manager@gmail.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'Manager', NOW()),
(3, 'Driver User', 'driver@gmail.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'Driver', NOW()),
(4, 'Regular User', 'user@gmail.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'User', NOW());

-- =====================================================
-- Table: deliveries
-- =====================================================
DROP TABLE IF EXISTS `deliveries`;
CREATE TABLE `deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transport_id` varchar(20) DEFAULT NULL,
  `product` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `origin` varchar(50) DEFAULT NULL,
  `destination` varchar(50) DEFAULT NULL,
  `driver` varchar(100) DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `priority` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `origin_lat` double DEFAULT 8.4542,
  `origin_lng` double DEFAULT 124.6319,
  `dest_lat` double DEFAULT 8.4542,
  `dest_lng` double DEFAULT 124.6319,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Insert sample deliveries
-- =====================================================
INSERT INTO `deliveries` (`id`, `transport_id`, `product`, `quantity`, `delivery_date`, `origin`, `destination`, `driver`, `vehicle_plate`, `priority`, `notes`, `status`, `created_at`) VALUES
(1, 'TR-004', 'Tomato', 20, CURDATE() + INTERVAL 1 DAY, 'Farm', 'CDO Market', 'SANDER PEREJAN', 'AVR 600', 'High', 'FOLLOW THE SAFE RANGE TEMPERATURE AND HUMIDITY', 'In Transit', NOW()),
(2, 'TR-004', 'Tomato', 25, CURDATE() + INTERVAL 3 DAY, 'Farm', 'CDO Market', 'SANDER PEREJAN', 'AVR 600', 'Low', 'Handle with care', 'Pending', NOW()),
(3, 'TR-005', 'Tomato', 30, CURDATE(), 'Farm', 'Davao Market', 'ANTONY CANETE', 'AVR 601', 'High', 'Urgent delivery', 'Delivered', NOW()),
(4, 'TR-006', 'Tomato', 25, CURDATE() + INTERVAL 9 DAY, 'CDO Market', 'Davao Market', 'PEDRO PENDOCO', 'AVR 602', 'High', 'Express delivery', 'Pending', NOW()),
(5, 'TR-007', 'Tomato', 25, CURDATE() + INTERVAL 2 DAY, 'Farm', 'Iligan Market', 'JACOB RANIN', 'AVR 604', 'High', 'Priority delivery', 'In Transit', NOW());

-- =====================================================
-- Table: in_transit
-- =====================================================
DROP TABLE IF EXISTS `in_transit`;
CREATE TABLE `in_transit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_id` int(11) NOT NULL,
  `transport_id` varchar(20) NOT NULL,
  `product` varchar(100) NOT NULL,
  `origin` varchar(100) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `driver` varchar(100) DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `priority` varchar(20) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `started_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_delivery` (`delivery_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Insert sample in_transit records
-- =====================================================
INSERT INTO `in_transit` (`id`, `delivery_id`, `transport_id`, `product`, `origin`, `destination`, `driver`, `vehicle_plate`, `quantity`, `priority`, `started_at`, `started_by`) VALUES
(1, 1, 'TR-004', 'Tomato', 'Farm', 'CDO Market', 'SANDER PEREJAN', 'AVR 600', 20, 'High', NOW(), 'SANDER PEREJAN'),
(2, 5, 'TR-007', 'Tomato', 'Farm', 'Iligan Market', 'JACOB RANIN', 'AVR 604', 25, 'High', NOW(), 'JACOB RANIN');

-- =====================================================
-- Table: delivered
-- =====================================================
DROP TABLE IF EXISTS `delivered`;
CREATE TABLE `delivered` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_id` int(11) NOT NULL,
  `transport_id` varchar(20) NOT NULL,
  `product` varchar(100) NOT NULL,
  `origin` varchar(100) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `driver` varchar(100) DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `priority` varchar(20) DEFAULT NULL,
  `avg_temp` decimal(5,2) DEFAULT NULL,
  `avg_hum` decimal(5,2) DEFAULT NULL,
  `total_readings` int(11) DEFAULT 0,
  `delivered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivered_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_delivery` (`delivery_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Insert sample delivered records
-- =====================================================
INSERT INTO `delivered` (`id`, `delivery_id`, `transport_id`, `product`, `origin`, `destination`, `driver`, `vehicle_plate`, `quantity`, `priority`, `delivered_at`, `delivered_by`) VALUES
(1, 3, 'TR-005', 'Tomato', 'Farm', 'Davao Market', 'ANTONY CANETE', 'AVR 601', 30, 'High', NOW(), 'ANTONY CANETE');

-- =====================================================
-- Table: sensor_logs
-- =====================================================
DROP TABLE IF EXISTS `sensor_logs`;
CREATE TABLE `sensor_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `delivery_id` int(11) NOT NULL,
  `temperature` decimal(5,2) NOT NULL,
  `humidity` decimal(5,2) NOT NULL,
  `ventilation` tinyint(1) NOT NULL DEFAULT 0,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_delivery_id` (`delivery_id`),
  KEY `idx_recorded_at` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Insert sample sensor logs
-- =====================================================
INSERT INTO `sensor_logs` (`id`, `delivery_id`, `temperature`, `humidity`, `ventilation`, `recorded_at`) VALUES
(1, 1, 24.50, 54.00, 0, NOW()),
(2, 1, 25.30, 55.20, 0, DATE_ADD(NOW(), INTERVAL 1 HOUR)),
(3, 1, 26.10, 56.00, 1, DATE_ADD(NOW(), INTERVAL 2 HOUR)),
(4, 5, 23.80, 52.50, 0, NOW()),
(5, 5, 24.20, 53.00, 0, DATE_ADD(NOW(), INTERVAL 1 HOUR));

-- =====================================================
-- Table: transport_monitoring
-- =====================================================
DROP TABLE IF EXISTS `transport_monitoring`;
CREATE TABLE `transport_monitoring` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transport_id` varchar(20) NOT NULL,
  `product` varchar(100) NOT NULL,
  `origin` varchar(100) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `status` varchar(50) NOT NULL,
  `driver` varchar(100) NOT NULL,
  `quantity` varchar(50) NOT NULL,
  `temp_min` int(11) DEFAULT 15,
  `temp_max` int(11) DEFAULT 30,
  `humidity_min` int(11) DEFAULT 50,
  `humidity_max` int(11) DEFAULT 60,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Insert sample transport monitoring records
-- =====================================================
INSERT INTO `transport_monitoring` (`id`, `transport_id`, `product`, `origin`, `destination`, `status`, `driver`, `quantity`, `created_at`) VALUES
(1, 'TR-001', 'Tomatoes', 'Farm', 'Davao Market', 'In Transit', 'Pedro Santos', '15 crates', NOW()),
(2, 'TR-002', 'Tomatoes', 'Farm', 'CDO Market', 'Arrived', 'Juan Reyes', '22 crates', NOW()),
(3, 'TR-003', 'Tomatoes', 'Farm', 'Butuan Market', 'In Transit', 'Mario Cruz', '18 crates', NOW());

-- =====================================================
-- Verify all tables were created
-- =====================================================
SHOW TABLES;

-- =====================================================
-- Check users table structure (should have google_id and last_login)
-- =====================================================
DESCRIBE `users`;

-- =====================================================
-- Display all users
-- =====================================================
SELECT id, name, email, role, last_login, created_at FROM users;

-- =====================================================
-- Sample queries for testing Google login
-- =====================================================

-- Check if a user exists by email (used by Google login)
SELECT * FROM users WHERE email = 'user@gmail.com';

-- Update last login time (used when user logs in)
UPDATE users SET last_login = NOW() WHERE id = 1;

-- Update google_id for a user (used after Google login)
UPDATE users SET google_id = 'test_google_id_123' WHERE id = 1;

--ADD GOOGLE LOGIN--

-- Use the database
USE `tomato_greengrow`;

-- Add Google login columns to users table
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `google_id` VARCHAR(255) NULL AFTER `password`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `last_login` DATETIME NULL AFTER `google_id`;

-- Add indexes
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_google_id` (`google_id`);
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_email` (`email`);