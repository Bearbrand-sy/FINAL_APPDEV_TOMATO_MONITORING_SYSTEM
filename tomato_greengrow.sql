-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 17, 2026 at 05:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tomato_greengrow`
--

-- --------------------------------------------------------

--
-- Table structure for table `delivered`
--

CREATE TABLE `delivered` (
  `id` int(11) NOT NULL,
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
  `delivered_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivered`
--

INSERT INTO `delivered` (`id`, `delivery_id`, `transport_id`, `product`, `origin`, `destination`, `driver`, `vehicle_plate`, `quantity`, `priority`, `avg_temp`, `avg_hum`, `total_readings`, `delivered_at`, `delivered_by`) VALUES
(1, 4, 'TR-005', 'Tomato', 'Farm', 'Davao Market', 'ANTONY CANETE', 'AVR 601', 30, 'High', NULL, NULL, 0, '2026-03-16 06:49:25', 'User');

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `id` int(11) NOT NULL,
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
  `dest_lng` double DEFAULT 124.6319
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`id`, `transport_id`, `product`, `quantity`, `delivery_date`, `origin`, `destination`, `driver`, `vehicle_plate`, `priority`, `notes`, `status`, `created_at`, `origin_lat`, `origin_lng`, `dest_lat`, `dest_lng`) VALUES
(1, 'TR-004', 'Tomato', 20, '2026-03-17', 'Farm', 'CDO Market', 'SANDER PEREJAN', 'AVR 600', 'High', 'FOLLOW THE SAFE RANGE TEMPERATURE AND HUMIDITY', 'In Transit', '2026-03-15 09:24:31', 8.4542, 124.6319, 8.4542, 124.6319),
(2, 'TR-004', 'Tomato', 25, '2026-03-19', 'Farm', 'CDO Market', 'SANDER PEREJAN', 'AVR 600', 'Low', 'adawda', 'Pending', '2026-03-15 09:24:58', 8.4542, 124.6319, 8.4542, 124.6319),
(3, 'TR-004', 'Tomato', 25, '2026-03-19', 'Farm', 'CDO Market', 'SANDER PEREJAN', 'AVR 600', 'Low', 'adawda', 'Pending', '2026-03-15 09:33:36', 8.4542, 124.6319, 8.4542, 124.6319),
(4, 'TR-005', 'Tomato', 30, '2026-03-16', 'Farm', 'Davao Market', 'ANTONY CANETE', 'AVR 601', 'High', 'Tarongag dala', 'Delivered', '2026-03-16 00:31:45', 8.4542, 124.6319, 8.4542, 124.6319),
(5, 'TR-006', 'Tomato', 25, '2026-03-25', 'CDO Market', 'Davao Market', 'PEDRO PENDOCO', 'AVR 602', 'High', 'tarunga ug hatod ', 'Pending', '2026-03-16 05:59:23', 8.4542, 124.6319, 8.4542, 124.6319),
(6, 'TR-007', 'Tomato', 25, '2026-03-18', 'Farm', 'Iligan Market', 'JACOB RANIN', 'AVR 604', 'High', 'Tarunga ug dala', 'In Transit', '2026-03-16 07:31:03', 8.4542, 124.6319, 8.4542, 124.6319),
(7, 'TR-008', 'Tomato', 30, '2026-03-19', 'Davao Market', 'Iligan Market', 'JACOB RANIN', 'AVR 605', 'High', '', 'Pending', '2026-03-17 03:54:50', 8.4542, 124.6319, 8.4542, 124.6319);

-- --------------------------------------------------------

--
-- Table structure for table `in_transit`
--

CREATE TABLE `in_transit` (
  `id` int(11) NOT NULL,
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
  `started_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `in_transit`
--

INSERT INTO `in_transit` (`id`, `delivery_id`, `transport_id`, `product`, `origin`, `destination`, `driver`, `vehicle_plate`, `quantity`, `priority`, `started_at`, `started_by`) VALUES
(1, 4, 'TR-005', 'Tomato', 'Farm', 'Davao Market', 'ANTONY CANETE', 'AVR 601', 30, '0', '2026-03-16 06:45:21', 'User'),
(2, 6, 'TR-007', 'Tomato', 'Farm', 'Iligan Market', 'JACOB RANIN', 'AVR 604', 25, '0', '2026-03-16 07:32:14', 'JACOB RANIN'),
(3, 1, 'TR-004', 'Tomato', 'Farm', 'CDO Market', 'SANDER PEREJAN', 'AVR 600', 20, '0', '2026-03-17 03:52:22', 'SANDER PEREJAN');

-- --------------------------------------------------------

--
-- Table structure for table `sensor_logs`
--

CREATE TABLE `sensor_logs` (
  `id` bigint(20) NOT NULL,
  `delivery_id` int(11) NOT NULL,
  `temperature` decimal(5,2) NOT NULL,
  `humidity` decimal(5,2) NOT NULL,
  `ventilation` tinyint(1) NOT NULL DEFAULT 0,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sensor_logs`
--

INSERT INTO `sensor_logs` (`id`, `delivery_id`, `temperature`, `humidity`, `ventilation`, `recorded_at`) VALUES
(1, 1, 24.50, 54.00, 0, '2026-03-16 00:48:41'),
(3, 1, 31.00, 62.10, 1, '2026-03-16 00:48:41'),
(7, 1, 33.10, 65.00, 1, '2026-03-16 00:48:41'),
(8, 2, 16.20, 50.50, 0, '2026-03-16 00:48:41');

-- --------------------------------------------------------

--
-- Table structure for table `transport_monitoring`
--

CREATE TABLE `transport_monitoring` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transport_monitoring`
--

INSERT INTO `transport_monitoring` (`id`, `transport_id`, `product`, `origin`, `destination`, `status`, `driver`, `quantity`, `temp_min`, `temp_max`, `humidity_min`, `humidity_max`, `created_at`) VALUES
(1, 'TR-001', 'Tomatoes', 'Farm', 'Davao Market', 'In Transit', 'Pedro Santos', '15 crates', 15, 30, 50, 60, '2026-03-15 06:49:53'),
(2, 'TR-002', 'Tomatoes', 'Farm', 'CDO Market', 'Arrived', 'Juan Reyes', '22 crates', 15, 30, 50, 60, '2026-03-15 06:49:53'),
(3, 'TR-003', 'Tomatoes', 'Farm', 'Butuan Market', 'In Transit', 'Mario Cruz', '18 crates', 15, 30, 50, 60, '2026-03-15 06:49:53');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `role` enum('Admin','Manager','User','Driver') DEFAULT 'User'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'Admin'),
(2, 'Manager', 'manager@gmail.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'Manager'),
(4, 'User', 'user@gmail.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'User'),
(5, 'Sander', 'sander@gmail.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'Manager'),
(6, 'SANDER PEREJAN', 'sander.driver@greengrow.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'Driver'),
(7, 'ANTONY CANETE', 'antony.driver@greengrow.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'Driver'),
(8, 'JACOB RANIN', 'jacob@gmail.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'Driver');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `delivered`
--
ALTER TABLE `delivered`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_delivery` (`delivery_id`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `in_transit`
--
ALTER TABLE `in_transit`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_delivery` (`delivery_id`);

--
-- Indexes for table `sensor_logs`
--
ALTER TABLE `sensor_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transport_monitoring`
--
ALTER TABLE `transport_monitoring`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `delivered`
--
ALTER TABLE `delivered`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `in_transit`
--
ALTER TABLE `in_transit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sensor_logs`
--
ALTER TABLE `sensor_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `transport_monitoring`
--
ALTER TABLE `transport_monitoring`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
