-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 15, 2025 at 11:35 AM
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
-- Database: `conquer_gym`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `class_id` int(11) UNSIGNED DEFAULT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','attended','no-show') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `class_id`, `booking_date`, `notes`, `status`) VALUES
(1, 4, 1, '2025-12-13 05:47:43', NULL, 'confirmed'),
(2, 4, 2, '2025-12-13 05:47:43', NULL, 'confirmed'),
(3, 5, 3, '2025-12-13 05:47:43', NULL, 'confirmed'),
(4, 6, 4, '2025-12-13 05:47:43', NULL, 'confirmed'),
(5, 7, 1, '2025-12-13 15:56:27', '', 'pending'),
(6, 18, 4, '2025-12-14 08:32:07', '', 'pending'),
(7, 7, 8, '2025-12-15 06:16:35', '', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) UNSIGNED NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `trainer_id` int(11) UNSIGNED DEFAULT NULL,
  `schedule` datetime NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `duration` varchar(20) DEFAULT NULL,
  `max_capacity` int(11) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `current_enrollment` int(11) DEFAULT 0,
  `class_type` varchar(50) DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT NULL,
  `intensity_level` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','cancelled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `trainer_id`, `schedule`, `duration_minutes`, `duration`, `max_capacity`, `location`, `current_enrollment`, `class_type`, `difficulty_level`, `intensity_level`, `description`, `status`) VALUES
(1, 'Morning Yoga', 2, '2025-12-14 13:47:43', 60, '60 min', 20, 'Main Studio', 15, 'yoga', 'beginner', 'beginner', 'Join our amazing Morning Yoga class!', 'active'),
(2, 'HIIT Blast', 1, '2025-12-15 13:47:43', 45, '45 min', 15, 'Main Studio', 12, 'hiit', 'intermediate', 'intermediate', 'Join our amazing HIIT Blast class!', 'active'),
(3, 'Strength Training', 2, '2025-12-16 13:47:43', 60, '60 min', 10, 'Main Studio', 8, 'strength', 'advanced', 'advanced', 'Join our amazing Strength Training class!', 'active'),
(4, 'Cardio Kickboxing', 1, '2025-12-17 13:47:43', 50, '50 min', 25, 'Main Studio', 20, 'cardio', 'intermediate', 'intermediate', 'Join our amazing Cardio Kickboxing class!', 'active'),
(5, 'CrossFit WOD', 2, '2025-12-18 13:47:43', 60, '60 min', 15, 'Main Studio', 10, 'crossfit', 'advanced', 'advanced', 'Join our amazing CrossFit WOD class!', 'active'),
(6, 'Strength Training', NULL, '2025-12-24 12:00:00', 60, NULL, 20, 'Pasil', 0, 'Strength', 'advanced', NULL, 'Join now', 'active'),
(7, 'Jogging', NULL, '2025-12-31 01:00:00', 60, NULL, 20, 'Ibabao', 0, 'Cardio', 'beginner', NULL, 'Runner', 'active'),
(8, 'Resistance Training', 4, '2025-12-15 15:00:00', 60, NULL, 20, 'Conquer Gym', 0, 'Pilates', 'advanced', NULL, 'padako lawas ', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('new','read','replied','closed') DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `submitted_at`, `status`) VALUES
(1, 'Alice Johnson', 'alice@email.com', '555-0123', 'Membership Inquiry', 'I would like to know more about your family plans...', '2025-12-13 05:47:43', 'read'),
(2, 'Michael Brown', 'michael@email.com', '555-0124', 'Personal Training', 'Looking for a trainer specialized in weight loss...', '2025-12-13 05:47:43', 'new'),
(3, 'Sarah Miller', 'sarah.m@email.com', '555-0125', 'Class Schedule', 'When are your evening yoga classes?', '2025-12-13 05:47:43', 'replied');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) UNSIGNED NOT NULL,
  `equipment_name` varchar(100) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `status` enum('active','maintenance','retired') DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `equipment_name`, `brand`, `purchase_date`, `last_maintenance`, `next_maintenance`, `status`, `location`) VALUES
(1, 'Treadmill Pro 5000', 'LifeFitness', '2023-01-15', '2023-12-01', '2024-06-01', 'active', 'Cardio Zone'),
(2, 'Leg Press Machine', 'Hammer Strength', '2022-05-20', '2023-11-15', '2024-05-15', 'active', 'Strength Area'),
(3, 'Multi-Station Gym', 'Cybex', '2021-08-10', '2023-10-30', '2024-04-30', 'maintenance', 'Functional Zone'),
(4, 'Dumbbell Set (5-50kg)', 'Rogue', '2023-03-05', '2023-12-10', '2024-06-10', 'active', 'Free Weights'),
(5, 'Treadmill', 'LifeFitness', '2025-12-15', '2025-12-28', '2026-06-28', 'active', 'Cardio Zone');

-- --------------------------------------------------------

--
-- Table structure for table `gym_members`
--

CREATE TABLE `gym_members` (
  `ID` int(11) UNSIGNED NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Age` int(3) NOT NULL,
  `MembershipPlan` varchar(50) NOT NULL,
  `ContactNumber` varchar(15) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `JoinDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `MembershipStatus` enum('Active','Inactive','Suspended') DEFAULT 'Active',
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_members`
--

INSERT INTO `gym_members` (`ID`, `Name`, `Age`, `MembershipPlan`, `ContactNumber`, `Email`, `JoinDate`, `MembershipStatus`, `deleted_at`) VALUES
(1, 'John Doe', 28, 'Legend', '555-0101', 'john@email.com', '2025-12-13 05:47:43', 'Active', NULL),
(2, 'Jane Smith', 32, 'Champion', '555-0102', 'jane@email.com', '2025-12-13 05:47:43', 'Active', NULL),
(3, 'Bob Wilson', 45, 'Warrior', '555-0103', 'bob@email.com', '2025-12-13 05:47:43', 'Active', NULL),
(4, 'Jireh Dominguez', 25, 'legend', '2349342', 'jireh@gmail.com', '2025-12-13 05:49:03', 'Active', NULL),
(5, 'Kokey', 25, 'legend', '1356345', 'kokey@1.com', '2025-12-13 06:50:50', 'Active', NULL),
(6, 'Loke', 25, 'Legend', '245345', 'loki@gmail.com', '2025-12-14 00:46:51', 'Active', NULL),
(7, 'Wowie', 25, 'Warrior', '245924', 'wowi@gmail.com', '2025-12-14 00:55:24', 'Active', NULL),
(8, 'Lomi', 25, 'Champion', '2304023', 'lomi@gmail.com', '2025-12-14 00:56:25', 'Active', NULL),
(9, 'Irene Marie', 25, 'Legend', '1395349', 'irene@gmail.com', '2025-12-14 08:30:01', 'Active', NULL),
(10, 'Adik', 25, 'Legend', '302429', 'adik@gmail.com', '2025-12-15 02:57:40', 'Active', '2025-12-15 10:20:46'),
(11, 'Jairus', 30, 'Legend', '32942349', 'jairus@gmail.com', '2025-12-15 05:33:33', 'Active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `transaction_id` varchar(100) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_details` text DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmation_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_method` enum('credit_card','debit_card','paypal','bank_transfer','cash') DEFAULT NULL,
  `status` enum('completed','pending','failed','refunded') DEFAULT NULL,
  `subscription_period` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `amount`, `payment_date`, `transaction_id`, `reference_number`, `receipt_image`, `notes`, `payment_details`, `confirmed_by`, `confirmation_date`, `created_at`, `updated_at`, `payment_method`, `status`, `subscription_period`) VALUES
(1, 4, 49.99, '2025-12-13 05:47:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-15 10:27:15', '2025-12-15 10:27:15', 'credit_card', 'completed', 'Monthly'),
(2, 5, 79.99, '2025-12-13 05:47:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-15 10:27:15', '2025-12-15 10:27:15', 'debit_card', 'completed', 'Monthly'),
(3, 6, 29.99, '2025-12-13 05:47:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-15 10:27:15', '2025-12-15 10:27:15', 'paypal', 'completed', 'Monthly'),
(4, 4, 49.99, '2025-12-13 05:47:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-15 10:27:15', '2025-12-15 10:27:15', 'credit_card', 'completed', 'Monthly'),
(5, 5, 79.99, '2025-12-13 05:47:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-15 10:27:15', '2025-12-15 10:27:15', 'debit_card', 'completed', 'Monthly'),
(6, 1, 49.99, '2025-12-10 10:27:24', 'TXN20251215001', NULL, NULL, 'Monthly payment', NULL, NULL, NULL, '2025-12-15 10:27:24', '2025-12-15 10:27:24', 'credit_card', 'completed', 'Monthly Membership'),
(7, 2, 79.99, '2025-12-12 10:27:24', 'TXN20251215002', NULL, NULL, 'GCash payment - Ref: GC123456', NULL, NULL, NULL, '2025-12-15 10:27:24', '2025-12-15 10:27:24', '', 'completed', 'Champion Membership'),
(8, 3, 29.99, '2025-12-14 10:27:24', 'TXN20251215003', NULL, NULL, 'Cash payment at reception', NULL, NULL, NULL, '2025-12-15 10:27:24', '2025-12-15 10:27:24', 'cash', 'pending', 'Basic Membership'),
(9, 4, 49.99, '2025-12-15 10:27:24', 'TXN20251215004', NULL, NULL, 'PayMaya payment - Ref: PM789012', NULL, NULL, NULL, '2025-12-15 10:27:24', '2025-12-15 10:27:24', '', 'pending', 'Monthly Membership'),
(10, 5, 79.99, '2025-12-13 10:27:24', 'TXN20251215005', NULL, NULL, 'BDO Transfer - Ref: BDO2024001', NULL, NULL, NULL, '2025-12-15 10:27:24', '2025-12-15 10:27:24', 'bank_transfer', 'completed', 'Champion Membership'),
(11, 1, 49.99, '2025-12-10 10:32:44', 'TXN20241215001', NULL, NULL, 'Monthly payment', NULL, NULL, NULL, '2025-12-15 10:32:44', '2025-12-15 10:32:44', 'credit_card', 'completed', 'Monthly Membership'),
(12, 1, 49.99, '2025-12-13 10:32:44', 'TXN20241215002', NULL, NULL, 'GCash payment - waiting for confirmation', NULL, NULL, NULL, '2025-12-15 10:32:44', '2025-12-15 10:32:44', '', 'pending', 'Monthly Membership'),
(13, 2, 79.99, '2025-12-08 10:32:44', 'TXN20241215003', NULL, NULL, 'Bank transfer from BPI', NULL, NULL, NULL, '2025-12-15 10:32:44', '2025-12-15 10:32:44', 'bank_transfer', 'completed', 'Champion Membership'),
(14, 3, 29.99, '2025-12-14 10:32:44', 'TXN20241215004', NULL, NULL, 'Cash payment at reception', NULL, NULL, NULL, '2025-12-15 10:32:44', '2025-12-15 10:32:44', 'cash', 'pending', 'Basic Membership'),
(15, 4, 49.99, '2025-12-12 10:32:44', 'TXN20241215005', NULL, NULL, 'PayMaya payment', NULL, NULL, NULL, '2025-12-15 10:32:44', '2025-12-15 10:32:44', '', 'completed', 'Monthly Membership');

-- --------------------------------------------------------

--
-- Table structure for table `success_stories`
--

CREATE TABLE `success_stories` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `before_image` varchar(255) DEFAULT NULL,
  `after_image` varchar(255) DEFAULT NULL,
  `story_text` text NOT NULL,
  `weight_loss` decimal(5,2) DEFAULT NULL,
  `months_taken` int(3) DEFAULT NULL,
  `trainer_id` int(11) UNSIGNED DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `success_stories`
--

INSERT INTO `success_stories` (`id`, `user_id`, `title`, `before_image`, `after_image`, `story_text`, `weight_loss`, `months_taken`, `trainer_id`, `is_featured`, `created_at`, `approved`) VALUES
(1, 4, 'Lost 50lbs in 6 months!', NULL, NULL, 'Thanks to CONQUER Gym and my amazing trainer Mark, I transformed my life...', 50.50, 6, 2, 0, '2025-12-13 05:47:43', 1),
(2, 5, 'From Couch to 5K in 3 months', NULL, NULL, 'Sarah helped me build confidence and stamina I never knew I had...', 30.20, 3, 3, 0, '2025-12-13 05:47:43', 1),
(3, 6, 'Gained Strength, Lost Body Fat', NULL, NULL, 'The combination of strength training and proper nutrition changed everything...', 25.70, 4, 2, 0, '2025-12-13 05:47:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `specialty` varchar(100) NOT NULL,
  `certification` varchar(200) DEFAULT NULL,
  `years_experience` int(3) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`id`, `user_id`, `specialty`, `certification`, `years_experience`, `bio`, `rating`, `total_reviews`, `deleted_at`) VALUES
(1, 2, 'Strength & Conditioning', 'NASM Certified, CrossFit Level 2', 10, 'Former professional athlete with 10+ years training experience', 4.80, 50, NULL),
(2, 3, 'Yoga & Mobility', 'RYT 500, ACE Certified', 8, 'Specialized in yoga therapy and mobility training', 4.90, 45, NULL),
(4, 23, 'Yoga', 'ISSA Certified', 11, 'Gay before straight now', 1.00, 0, '2025-12-15 10:43:39');

-- --------------------------------------------------------

--
-- Stand-in structure for view `trainer_details`
-- (See below for the actual view)
--
CREATE TABLE `trainer_details` (
`trainer_id` int(11) unsigned
,`specialty` varchar(100)
,`certification` varchar(200)
,`years_experience` int(3)
,`bio` text
,`rating` decimal(3,2)
,`total_reviews` int(11)
,`user_id` int(11) unsigned
,`username` varchar(50)
,`email` varchar(100)
,`full_name` varchar(100)
,`created_at` timestamp
,`last_login` timestamp
,`is_active` tinyint(1)
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `user_type` enum('member','trainer','admin') DEFAULT 'member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `user_type`, `created_at`, `last_login`, `is_active`, `deleted_at`) VALUES
(1, 'admin', 'admin@conquergym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', '2025-12-13 05:47:43', '2025-12-15 09:49:44', 1, NULL),
(2, 'markj', 'mark@conquergym.com', '$2y$10$Nl5S1QkNnwLmW/5e9bD0P.XeV1w.TP8h9KQJg6L8s7M4nA2zV3xY6u', 'Mark Johnson', 'trainer', '2025-12-13 05:47:43', NULL, 1, NULL),
(3, 'sarahc', 'sarah@conquergym.com', '$2y$10$Nl5S1QkNnwLmW/5e9bD0P.XeV1w.TP8h9KQJg6L8s7M4nA2zV3xY6u', 'Sarah Chen', 'trainer', '2025-12-13 05:47:43', NULL, 1, NULL),
(4, 'john_doe', 'john@email.com', '$2y$10$Nl5S1QkNnwLmW/5e9bD0P.XeV1w.TP8h9KQJg6L8s7M4nA2zV3xY6u', 'John Doe', 'member', '2025-12-13 05:47:43', NULL, 1, NULL),
(5, 'jane_smith', 'jane@email.com', '$2y$10$Nl5S1QkNnwLmW/5e9bD0P.XeV1w.TP8h9KQJg6L8s7M4nA2zV3xY6u', 'Jane Smith', 'member', '2025-12-13 05:47:43', NULL, 1, NULL),
(6, 'bob_wilson', 'bob@email.com', '$2y$10$Nl5S1QkNnwLmW/5e9bD0P.XeV1w.TP8h9KQJg6L8s7M4nA2zV3xY6u', 'Bob Wilson', 'member', '2025-12-13 05:47:43', NULL, 1, NULL),
(7, 'jireh', 'jireh@gmail.com', '$2y$10$fNyYkhWkpVuE6ayvCYygFe/VW9IXhj1njBfeA0kz1gPBW96n7wv9q', 'Jireh Dominguez', 'member', '2025-12-13 05:49:03', '2025-12-15 09:46:43', 1, NULL),
(8, 'kokey', 'kokey@1.com', '$2y$10$2mwCZzFPbXjAkc6MhyCmKumzQWicPhI79KLSXCbJpKiQEPT7Bgt6i', 'Kokey', 'member', '2025-12-13 06:50:50', '2025-12-13 06:57:24', 1, NULL),
(15, 'loke', 'loki@gmail.com', '$2y$10$aeU82eWuLbzik8UfZM3RsOqjLbU3FYNbFwRMXD4/hTN.wshxMxPZK', 'Loke', 'member', '2025-12-14 00:46:51', '2025-12-14 00:51:03', 1, NULL),
(16, 'wowie', 'wowi@gmail.com', '$2y$10$jyX4yhy8p4r1NuOuEfmHTOq3lg8Z2p5pJyFYgA/Wt8vaPZv9tY2T6', 'Wowie', 'member', '2025-12-14 00:55:24', NULL, 1, NULL),
(17, 'lomi', 'lomi@gmail.com', '$2y$10$JtYlIiglSSWDc3BcA2f8yOcWEzPppa.1sjI7vx3cNV2ycQjeb4Ofu', 'Lomi', 'member', '2025-12-14 00:56:25', NULL, 1, NULL),
(18, 'irene', 'irene@gmail.com', '$2y$10$vjCymCkX/DMmSzVg7Mgh5ugu0E4.pwAaaAzpsH1A4rW0EY/FwSfj.', 'Irene Marie', 'member', '2025-12-14 08:30:01', NULL, 1, NULL),
(19, 'adik', 'adik@gmail.com', '$2y$10$TUMualbQJDYiJ45zN2V3ZeVhBA9118svIhorOtpTYxzQDOlc4IBxS', 'Adik', 'member', '2025-12-15 02:57:40', NULL, 1, '2025-12-15 10:20:46'),
(20, 'anthon', 'anthon@gmail.com', '$2y$10$W6AM56j7YQkROtPAipgAo.JeHrdnAC30RL/jtPEETD6WVRwNWyV2q', 'Anthon Pilapil', 'trainer', '2025-12-15 03:00:38', NULL, 0, NULL),
(22, 'jairus', 'jairus@gmail.com', '$2y$10$phfL.iNakDKJskXtNb.ra.pbwXJKBvEillxKI5PXM7BWsJhD5.Mq2', 'Jairus', 'member', '2025-12-15 05:33:33', '2025-12-15 05:33:50', 1, NULL),
(23, 'john', 'john@gmail.com', '$2y$10$34d60rQLsoCpW5trxZp16ej7E1Kp.tu5T57ohChOnZbJW4qrsY7O2', 'John Benedick', 'trainer', '2025-12-15 05:36:16', NULL, 1, '2025-12-15 10:43:39'),
(24, 'test', 'test@gmail.com', '$2y$10$CQBKzQYO2OicfW0xfbWep.b6CF3dUeRF9l1AZywhGPKNd08b5YZCS', 'Test', 'trainer', '2025-12-15 09:03:43', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Structure for view `trainer_details`
--
DROP TABLE IF EXISTS `trainer_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `trainer_details`  AS SELECT `t`.`id` AS `trainer_id`, `t`.`specialty` AS `specialty`, `t`.`certification` AS `certification`, `t`.`years_experience` AS `years_experience`, `t`.`bio` AS `bio`, `t`.`rating` AS `rating`, `t`.`total_reviews` AS `total_reviews`, `u`.`id` AS `user_id`, `u`.`username` AS `username`, `u`.`email` AS `email`, `u`.`full_name` AS `full_name`, `u`.`created_at` AS `created_at`, `u`.`last_login` AS `last_login`, `u`.`is_active` AS `is_active` FROM (`trainers` `t` join `users` `u` on(`t`.`user_id` = `u`.`id`)) WHERE `u`.`user_type` = 'trainer' AND `u`.`is_active` = 1 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bookings_user` (`user_id`,`status`),
  ADD KEY `idx_bookings_class` (`class_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainer_id` (`trainer_id`),
  ADD KEY `idx_classes_schedule` (`schedule`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gym_members`
--
ALTER TABLE `gym_members`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `idx_members_email` (`Email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `idx_payments_user` (`user_id`,`payment_date`),
  ADD KEY `idx_payments_user_id` (`user_id`),
  ADD KEY `idx_payments_status` (`status`),
  ADD KEY `idx_payments_payment_date` (`payment_date`);

--
-- Indexes for table `success_stories`
--
ALTER TABLE `success_stories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `trainer_id` (`trainer_id`),
  ADD KEY `idx_stories_featured` (`is_featured`,`approved`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gym_members`
--
ALTER TABLE `gym_members`
  MODIFY `ID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `success_stories`
--
ALTER TABLE `success_stories`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `success_stories`
--
ALTER TABLE `success_stories`
  ADD CONSTRAINT `success_stories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `success_stories_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `trainers`
--
ALTER TABLE `trainers`
  ADD CONSTRAINT `trainers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
