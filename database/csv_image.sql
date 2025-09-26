-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 26, 2025 at 05:11 AM
-- Server version: 8.0.30
-- PHP Version: 8.2.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `csv_image`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discounts`
--

CREATE TABLE `discounts` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('percentage','fixed','buy_x_get_y') COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `usage_count` int NOT NULL DEFAULT '0',
  `per_user_limit` int DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `conditions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `discounts`
--

INSERT INTO `discounts` (`id`, `name`, `code`, `type`, `value`, `min_order_amount`, `max_discount_amount`, `usage_limit`, `usage_count`, `per_user_limit`, `starts_at`, `expires_at`, `is_active`, `conditions`, `created_at`, `updated_at`) VALUES
(1, 'Kenneth Avery', 'Quia eos sint porr', 'fixed', 47.00, 27.00, 27.00, 41, 1, 54, '2025-09-25 11:11:00', '2025-09-30 11:01:00', 1, NULL, '2025-09-25 12:13:43', '2025-09-25 23:32:18');

-- --------------------------------------------------------

--
-- Table structure for table `discount_audits`
--

CREATE TABLE `discount_audits` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `discount_id` bigint UNSIGNED NOT NULL,
  `user_discount_id` bigint UNSIGNED NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_amount` decimal(10,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `final_amount` decimal(10,2) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `order_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `discount_audits`
--

INSERT INTO `discount_audits` (`id`, `user_id`, `discount_id`, `user_discount_id`, `action`, `original_amount`, `discount_amount`, `final_amount`, `metadata`, `order_reference`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 13, 'assigned', NULL, NULL, NULL, '{\"timestamp\": \"2025-09-25T17:52:33.889999Z\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0\"}', NULL, '2025-09-25 12:22:33', '2025-09-25 12:22:33'),
(3, 1, 1, 13, 'applied', 100.00, 27.00, 73.00, '{\"timestamp\": \"2025-09-26T05:02:18.537933Z\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0\"}', '2', '2025-09-25 23:32:18', '2025-09-25 23:32:18');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE `images` (
  `id` bigint UNSIGNED NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `file_size` bigint NOT NULL,
  `width` int NOT NULL,
  `height` int NOT NULL,
  `checksum` varchar(255) NOT NULL,
  `upload_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `images`
--

INSERT INTO `images` (`id`, `filename`, `file_path`, `mime_type`, `file_size`, `width`, `height`, `checksum`, `upload_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'img_303.jpg', 'uploads/images/2025/09/24/xAefIAgwXd3cm3LdjQIlzxvqMf78VW5JPx5du9i7.jpg', 'image/jpeg', 11332, 850, 478, '3ccec526ae5288eabaf8da4326c8f2e3', 1, 1, '2025-09-24 13:09:43', '2025-09-24 13:09:43'),
(2, 'img_161.png', 'uploads/images/2025/09/24/Gw4UBm9ag4Fsn6lS4n4Qgk2Z8KxvUSQ1XbsmA7GV.png', 'image/png', 381518, 1920, 2256, '4066595464f13b5c53187d5a3f6a6111', 2, 1, '2025-09-24 13:09:45', '2025-09-24 13:09:45');

-- --------------------------------------------------------

--
-- Table structure for table `image_variants`
--

CREATE TABLE `image_variants` (
  `id` bigint UNSIGNED NOT NULL,
  `image_id` bigint UNSIGNED NOT NULL,
  `variant_name` varchar(255) NOT NULL,
  `width` int NOT NULL,
  `height` int NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_size` bigint NOT NULL,
  `checksum` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `image_variants`
--

INSERT INTO `image_variants` (`id`, `image_id`, `variant_name`, `width`, `height`, `file_path`, `filename`, `file_size`, `checksum`, `created_at`, `updated_at`) VALUES
(1, 1, 'thumbnail', 256, 144, 'uploads/images/2025/09/variants/1/xAefIAgwXd3cm3LdjQIlzxvqMf78VW5JPx5du9i7_thumbnail.jpg', 'img_303_thumbnail.jpg', 3256, '933e33f77c1985f6b241d253fce3308a', '2025-09-24 13:09:43', '2025-09-24 13:09:43'),
(2, 1, 'medium', 512, 288, 'uploads/images/2025/09/variants/1/xAefIAgwXd3cm3LdjQIlzxvqMf78VW5JPx5du9i7_medium.jpg', 'img_303_medium.jpg', 8250, '0e9b9dc3d12091fb1befaa68fc84ff63', '2025-09-24 13:09:43', '2025-09-24 13:09:43'),
(3, 1, 'large', 850, 478, 'uploads/images/2025/09/variants/1/xAefIAgwXd3cm3LdjQIlzxvqMf78VW5JPx5du9i7_large.jpg', 'img_303_large.jpg', 17025, '4344cda9d4cac2849f321bb401efcd6e', '2025-09-24 13:09:43', '2025-09-24 13:09:43'),
(4, 2, 'thumbnail', 218, 256, 'uploads/images/2025/09/variants/2/Gw4UBm9ag4Fsn6lS4n4Qgk2Z8KxvUSQ1XbsmA7GV_thumbnail.png', 'img_161_thumbnail.png', 15898, 'fc4997029d397da7781ae720995ac178', '2025-09-24 13:09:45', '2025-09-24 13:09:45'),
(5, 2, 'medium', 436, 512, 'uploads/images/2025/09/variants/2/Gw4UBm9ag4Fsn6lS4n4Qgk2Z8KxvUSQ1XbsmA7GV_medium.png', 'img_161_medium.png', 46574, '8cd7667b266fc743a0322274f9444f23', '2025-09-24 13:09:46', '2025-09-24 13:09:46'),
(6, 2, 'large', 871, 1024, 'uploads/images/2025/09/variants/2/Gw4UBm9ag4Fsn6lS4n4Qgk2Z8KxvUSQ1XbsmA7GV_large.png', 'img_161_large.png', 146493, '4bf858f18f728e5421358e57a3c67e29', '2025-09-24 13:09:48', '2025-09-24 13:09:48');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_01_01_000001_create_discounts_table', 2),
(5, '2025_01_01_000002_create_user_discounts_table', 2),
(6, '2025_01_01_000003_create_discount_audits_table', 2),
(7, '2025_09_25_175000_update_discount_audits_table_allow_null_amounts', 3);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int NOT NULL DEFAULT '0',
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attributes` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `primary_image_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `description`, `price`, `stock_quantity`, `category`, `brand`, `attributes`, `is_active`, `primary_image_id`, `created_at`, `updated_at`) VALUES
(1, 'SKU-00001', 'Movement Key', '', 187.61, 0, '', '', '{\"image_filename\": \"img_303.jpg\"}', 1, NULL, '2025-09-24 12:51:57', '2025-09-24 12:51:57'),
(2, 'SKU-00002', 'Run Exactly', '', 335.15, 0, '', '', '{\"image_filename\": \"img_161.jpg\"}', 1, NULL, '2025-09-24 12:51:57', '2025-09-24 12:51:57');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('11TP5set6GRfS0RbA4xjQSJ8cLiWLUTmmvK3X5pN', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibjN5cDRXYldsbHNmd3F0Rmh5OG9ha2hzNXBIb1lVN3J2MUhqcjNiRCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC91cGxvYWQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1758732400),
('84zzvzyS1JNu2CcSXUoLI0QbYFhQNEcuUio5Qekm', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiNzQzbHJ6dWdiMXd4UE1meWhoaVFyT1NJMWQ5RE9PS0xmU0ExdHFlUiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1758732904),
('GESj70ftRlmr9o5qwcoYF0pg9xoGfducLHJWwYU8', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQ1pBT294UGJzYnhsQ3RjWmh6T3JCd0pCbHdBek1kQUprN2hFaWxjWiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDI6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4MC9hcGkvdXBsb2FkL2RldGFpbHMvMSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1758776428),
('M6HErpULRFonhXHoCyKF2m6eB7FiZAnSprHXxuEe', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiazFUS0pUVnVkSFZYREZ6QkRoRU1xTkRLYmlXc0piNHJXOUFyME5rVSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly9sb2NhbGhvc3Q6NzAwMC9kaXNjb3VudHMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1758822757),
('NT4IrikVkeYQbnmZvATzUfdgnkHs2UiU6a9PVrxk', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiU0Z0VnZFQlM3bXpGVTFyaWFEd1pIQXNiZDhCTnc0MUZmY2s2SXRsZCI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjQyOiJodHRwOi8vbG9jYWxob3N0OjgwMDAvYXBpL3VwbG9hZC9kZXRhaWxzLzIiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1758740454),
('owlXeH9TbtGEsQ4keVDcHaxaRfxzf9XzekpWKhEC', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoiQlJhdEFId01JWDJqbGI5eVl5QUR5dE1SVm5hR05jbzBRVlZOWXp1YiI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjI3OiJodHRwOi8vbG9jYWxob3N0OjgwODAvbG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YToxOntpOjA7czo3OiJzdWNjZXNzIjt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6Nzoic3VjY2VzcyI7czo1MToiV2VsY29tZSBiYWNrISBZb3UgaGF2ZSBiZWVuIHN1Y2Nlc3NmdWxseSBsb2dnZWQgaW4uIjt9', 1758776515),
('rkjffL6GrAI1aGISQWUGxXgcSdsrtTnVP5dViOqy', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoicXRkbHZmRHVlc1FMZHR1ajh4ZzVvQVFoY2hGUGllVDI3MENVYlNqZyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDI6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4MC9hcGkvdXBsb2FkL2RldGFpbHMvMSI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1758776594),
('XYI2kTrSZNUQo1H3393Eo3bJIitdaM9ujJ7woF5r', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', 'YTozOntzOjY6Il9mbGFzaCI7YToyOntzOjM6Im5ldyI7YTowOnt9czozOiJvbGQiO2E6MDp7fX1zOjY6Il90b2tlbiI7czo0MDoiQjNEcXZLb0FTcUhoYXpqbXRRakZYMVVnYzZvM2U5NjdJZ2JGZWhINiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4MC9sb2dpbiI7fX0=', 1758863022),
('Zlzeu5igCmbiwvc4vyZIodEK51zqXG2asIaXLW6o', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiY3hRdXBWZUpuZGxlOUdNT3ZETmZZbVF3NzNOSG5FZFlxRHhQTFNWMCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMzoiaHR0cDovL2xvY2FsaG9zdDo4MDAwL3VwbG9hZC90ZXN0Ijt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1758735386);

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` bigint UNSIGNED NOT NULL,
  `upload_id` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `file_size` bigint NOT NULL,
  `checksum` varchar(255) NOT NULL,
  `total_chunks` int NOT NULL,
  `uploaded_chunks` int DEFAULT '0',
  `chunk_checksums` json DEFAULT NULL,
  `status` enum('pending','uploading','completed','failed','cancelled') DEFAULT 'pending',
  `error_message` text,
  `user_id` bigint UNSIGNED NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `uploads`
--

INSERT INTO `uploads` (`id`, `upload_id`, `original_filename`, `file_path`, `mime_type`, `file_size`, `checksum`, `total_chunks`, `uploaded_chunks`, `chunk_checksums`, `status`, `error_message`, `user_id`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 'simple_1758739183_68d43aef24727', 'img_303.jpg', 'uploads/images/2025/09/24/xAefIAgwXd3cm3LdjQIlzxvqMf78VW5JPx5du9i7.jpg', 'image/jpeg', 11332, '3ccec526ae5288eabaf8da4326c8f2e3', 1, 1, NULL, 'completed', NULL, 1, '2025-09-24 13:09:43', '2025-09-24 13:09:43', '2025-09-24 13:09:43'),
(2, 'simple_1758739185_68d43af11b3f9', 'img_161.png', 'uploads/images/2025/09/24/Gw4UBm9ag4Fsn6lS4n4Qgk2Z8KxvUSQ1XbsmA7GV.png', 'image/png', 381518, '4066595464f13b5c53187d5a3f6a6111', 1, 1, NULL, 'completed', NULL, 1, '2025-09-24 13:09:45', '2025-09-24 13:09:45', '2025-09-24 13:09:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Test User', 'test@example.com', '2025-09-24 10:42:47', '$2y$12$FySTyy.B3C0VjvE4og4LDe6W6VBssORyVO7/NMFSHxGUzuu67Enva', NULL, '2025-09-24 10:42:47', '2025-09-24 10:42:47'),
(2, 'Admin User', 'admin@example.com', '2025-09-24 10:42:47', '$2y$12$4vox7pHqV1JAJFZh1zzm4uRkdtm9ZX.xvI46acuaI7M69jISh2QpS', NULL, '2025-09-24 10:42:47', '2025-09-24 10:42:47');

-- --------------------------------------------------------

--
-- Table structure for table `user_discounts`
--

CREATE TABLE `user_discounts` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `discount_id` bigint UNSIGNED NOT NULL,
  `usage_count` int NOT NULL DEFAULT '0',
  `max_usage` int DEFAULT NULL,
  `assigned_at` datetime NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_discounts`
--

INSERT INTO `user_discounts` (`id`, `user_id`, `discount_id`, `usage_count`, `max_usage`, `assigned_at`, `expires_at`, `is_active`, `created_at`, `updated_at`) VALUES
(13, 1, 1, 1, 41, '2025-09-25 17:52:33', '2025-10-01 11:01:00', 1, '2025-09-25 12:22:33', '2025-09-25 23:32:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `discounts_code_unique` (`code`),
  ADD KEY `discounts_is_active_starts_at_expires_at_index` (`is_active`,`starts_at`,`expires_at`),
  ADD KEY `discounts_code_index` (`code`);

--
-- Indexes for table `discount_audits`
--
ALTER TABLE `discount_audits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `discount_audits_user_discount_id_foreign` (`user_discount_id`),
  ADD KEY `discount_audits_user_id_action_index` (`user_id`,`action`),
  ADD KEY `discount_audits_discount_id_action_index` (`discount_id`,`action`),
  ADD KEY `discount_audits_order_reference_index` (`order_reference`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `upload_id` (`upload_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `image_variants`
--
ALTER TABLE `image_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `upload_id` (`upload_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_discounts`
--
ALTER TABLE `user_discounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_discounts_user_id_discount_id_unique` (`user_id`,`discount_id`),
  ADD KEY `user_discounts_user_id_is_active_index` (`user_id`,`is_active`),
  ADD KEY `user_discounts_discount_id_is_active_index` (`discount_id`,`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `discounts`
--
ALTER TABLE `discounts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `discount_audits`
--
ALTER TABLE `discount_audits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `images`
--
ALTER TABLE `images`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `image_variants`
--
ALTER TABLE `image_variants`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_discounts`
--
ALTER TABLE `user_discounts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `discount_audits`
--
ALTER TABLE `discount_audits`
  ADD CONSTRAINT `discount_audits_discount_id_foreign` FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `discount_audits_user_discount_id_foreign` FOREIGN KEY (`user_discount_id`) REFERENCES `user_discounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `discount_audits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`upload_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `images_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `image_variants`
--
ALTER TABLE `image_variants`
  ADD CONSTRAINT `image_variants_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `images` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `uploads`
--
ALTER TABLE `uploads`
  ADD CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_discounts`
--
ALTER TABLE `user_discounts`
  ADD CONSTRAINT `user_discounts_discount_id_foreign` FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_discounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
