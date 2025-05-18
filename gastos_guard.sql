-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2025 at 09:52 AM
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
-- Database: `gastos_guard`
--

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `budget_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`budget_id`, `user_id`, `category_id`, `title`, `amount`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(1, 4, 9, 'Eme lang', 531.00, '2025-05-09', '2025-05-17', '2025-05-09 06:20:19', '2025-05-12 11:28:51'),
(2, 4, 4, 'ho', 200.00, '2025-05-01', '2025-05-14', '2025-05-09 08:50:24', '2025-05-12 07:01:07'),
(7, 3, 9, 'TRIAL', 4231.00, '2025-05-10', '2025-05-13', '2025-05-10 10:59:54', '2025-05-12 10:38:24'),
(9, 5, 6, 'For Sports', 20000.00, '2025-05-17', '2025-05-31', '2025-05-17 07:28:51', '2025-05-17 07:31:21'),
(10, 5, 9, 'Random', 5000.00, '2025-05-17', '2025-05-31', '2025-05-17 07:36:27', '2025-05-17 07:36:27'),
(11, 5, 2, 'Groceries', 15000.00, '2025-05-17', '2025-05-31', '2025-05-17 07:38:48', '2025-05-17 07:38:48');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `date_spent` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `user_id`, `category_id`, `amount`, `description`, `date_spent`, `created_at`, `updated_at`) VALUES
(2, 3, 1, 14322.00, 'hee', '2025-05-08', '2025-05-08 04:41:02', '2025-05-12 10:37:57'),
(3, 3, 9, 235.00, 'Make up', '2025-05-09', '2025-05-09 03:29:41', '2025-05-12 10:37:58'),
(4, 3, 7, 300.00, 'Tumbler', '2025-05-09', '2025-05-09 03:30:13', '2025-05-09 03:30:13'),
(5, 3, 2, 400.00, 'Samgyup', '2025-05-09', '2025-05-09 03:30:42', '2025-05-09 03:30:42'),
(6, 3, 9, 400.00, 'Flower', '2025-05-09', '2025-05-09 03:31:16', '2025-05-12 10:37:58'),
(8, 4, 9, 200.00, 'hii', '2025-05-16', '2025-05-09 07:57:49', '2025-05-12 11:16:23'),
(9, 4, 9, 20.00, 'hhhh', '2025-05-09', '2025-05-09 07:58:52', '2025-05-12 10:37:58'),
(11, 5, 6, 5000.00, 'Shoes', '2025-05-17', '2025-05-17 07:29:26', '2025-05-17 11:30:01'),
(12, 5, 6, 4000.00, 'Clothes', '2025-05-17', '2025-05-17 07:30:04', '2025-05-17 07:30:04'),
(13, 5, 9, 1000.00, 'Slime', '2025-05-17', '2025-05-17 07:37:17', '2025-05-17 07:37:17'),
(14, 5, 2, 2000.00, 'Chicken and Pork', '2025-05-17', '2025-05-17 07:39:11', '2025-05-17 07:39:11'),
(17, 5, 9, 300.00, 'Dry Leaves', '2025-05-18', '2025-05-18 05:45:35', '2025-05-18 05:45:35'),
(18, 5, 6, 500.00, 'Knee Pads', '2025-05-18', '2025-05-18 06:11:58', '2025-05-18 06:11:58');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`category_id`, `name`, `icon`, `color`, `is_default`) VALUES
(1, 'Housing', 'fa-home', '#4CAF50', 1),
(2, 'Food', 'fa-utensils', '#FFC107', 1),
(3, 'Transportation', 'fa-bus', '#2196F3', 1),
(4, 'Entertainment', 'fa-film', '#9C27B0', 1),
(5, 'Education', 'fa-graduation-cap', '#FF5722', 1),
(6, 'Shopping', 'fa-shopping-bag', '#607D8B', 1),
(7, 'Utilities', 'fa-bolt', '#795548', 1),
(8, 'Healthcare', 'fa-heartbeat', '#F44336', 1),
(9, 'Other', 'fa-ellipsis-h', '#9E9E9E', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `user_type` enum('student','admin') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `current_balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `full_name`, `user_type`, `created_at`, `updated_at`, `last_login`, `profile_image`, `active`, `current_balance`) VALUES
(1, 'admin', 'admin@gastosguard.com', '$2y$10$mkW4POd.FiYRqzdpGsVTXuNzRgT4Ln4gYravlNOGOyck3RWYDRT0W', 'System Administrator', 'admin', '2025-05-02 01:48:47', '2025-05-18 07:51:37', '2025-05-18 07:51:37', NULL, 1, 0.00),
(3, 'hello', 'silveriotwelxii@gmail.com', '$2y$10$AA1VLroC33kqxQhokqRDQOg6y.fICioIDCusRq1vCKrYNWM1ZDgUq', 'Hello Hi', 'student', '2025-05-04 02:54:37', '2025-05-18 01:56:52', '2025-05-17 07:18:25', NULL, 1, 7793.00),
(4, 'try', 'try@gmail.com', '$2y$10$HnPahWYPe5qdtxW0er/wi.g6yhW/oK3NvV897g7MyPMoWPUd/zQZ2', 'try lang', 'student', '2025-05-06 13:38:48', '2025-05-18 01:35:07', '2025-05-12 11:28:31', NULL, 1, 2125.00),
(5, 'test', 'test@gmail.com', '$2y$10$klayQnWEKcXx/hqsNJa4POXEIHya8LwPDWeTHKubrpExl9ZW9swAe', 'Test Try', 'student', '2025-05-17 07:26:58', '2025-05-18 07:51:59', '2025-05-18 07:51:59', NULL, 1, 701200.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`budget_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `budget_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budgets_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`category_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
