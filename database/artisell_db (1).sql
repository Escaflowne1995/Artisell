-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2025 at 08:15 AM
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
-- Database: `artisell_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`) VALUES
(6, 2, 4, 1),
(11, 2, 1, 1),
(12, 5, 4, 1),
(13, 5, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `shipping_name` varchar(100) DEFAULT NULL,
  `shipping_address` varchar(255) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_zip` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `shipping_name`, `shipping_address`, `shipping_city`, `shipping_zip`, `payment_method`, `status`, `created_at`, `order_date`, `total_amount`) VALUES
(1, 3, 25.00, 'Coby Avery', 'Cillum enim in deser', 'Non repudiandae temp', '51556', 'cod', 'Pending', '2025-04-03 07:12:26', NULL, 25.00),
(2, 3, 45.00, 'Felix Mcclain', 'Nihil eiusmod veniam', 'Optio provident ve', '54401', 'paypal', 'Pending', '2025-04-03 07:16:59', NULL, 45.00),
(3, 1, 40.00, 'Odette Durham', 'Magnam et fugit non', 'Sed non eos consequu', '45337', 'gcash', 'Pending', '2025-04-03 07:18:38', NULL, 40.00),
(4, 1, 23.00, 'Daisy Opaw', 'Bisag asa', 'Mingla', '6046', 'gcash', 'Pending', '2025-05-06 12:03:44', NULL, 23.00),
(5, 1, 0.00, 'Daisy Opaw', 'Bisag asa', 'Mingla', '6046', 'cod', 'Pending', '2025-05-06 12:03:55', NULL, 0.00),
(6, 1, 18.00, 'Daisy Opaw', 'Bisag asa', 'Mingla', '6046', 'cod', 'Pending', '2025-05-08 15:20:36', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 25.00),
(2, 2, 2, 3, 15.00),
(3, 3, 1, 1, 25.00),
(4, 3, 2, 1, 15.00),
(5, 3, 29, 1, 0.00),
(6, 4, 2, 1, 15.00),
(7, 4, 8, 1, 8.00),
(8, 6, 8, 1, 8.00),
(9, 6, 13, 1, 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(100) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `city` varchar(100) DEFAULT NULL,
  `vendor_id` int(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `feature` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category`, `image`, `created_at`, `city`, `vendor_id`, `stock`, `is_featured`, `feature`) VALUES
(1, 'Handmade Basket', 'A beautiful handmade basket.', 25.00, 'minglanilla', 'https://angelsarms.org/wp-content/uploads/2020/09/shop_11-1.jpg', '2025-03-19 13:07:46', NULL, 1, 25, 1, NULL),
(2, 'Traditional Mat', 'A traditional mat made from local materials.', 15.00, 'minglanilla', 'https://m.media-amazon.com/images/I/71hwtEwHhhL._AC_UF894,1000_QL80_.jpg', '2025-03-19 13:07:46', NULL, 1, 15, 0, NULL),
(3, 'Artisan Jewelry', 'Unique artisan jewelry.', 30.00, 'minglanilla', 'https://queencitycebu.com/wp-content/uploads/2022/09/susan.jpg', '2025-03-19 13:07:46', NULL, 1, 42, 1, NULL),
(4, 'Minglanilla Handwoven Bag', 'A stylish handwoven bag made from local materials.', 35.00, 'minglanilla', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTONuIQi32Btviw_xfzOV9YRWotpeXuAsP8QljAKUUWUg&s&ec=72940544', '2025-03-19 13:12:32', NULL, 1, 20, 1, NULL),
(5, 'Minglanilla Rattan Chair', 'A comfortable rattan chair perfect for your home.', 120.00, 'minglanilla', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR3W9lsDO4rbJmZzb1yG3sGZyzGfc_fypvCDddVEhnOzQ&s&ec=72940544', '2025-03-19 13:12:32', NULL, 1, 47, 1, NULL),
(6, 'Minglanilla Decorative Plate', 'Beautifully crafted decorative plate for your dining table.', 20.00, 'minglanilla', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSFSJV1bYFbLyM5DptVOB4ctyCDGQL3uf4nNjQFGQNFsA&s&ec=72940544', '2025-03-19 13:12:32', NULL, 1, 46, 0, NULL),
(7, 'Aloquinsan Bamboo Utensils', 'Eco-friendly bamboo utensils for your kitchen.', 15.00, 'aloquinsan', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ1EZ0pjH2Cqy3yrf4DpA4C3TZajtXzQut5ZG4ibM3wDA&s&ec=72940544', '2025-03-19 13:12:32', NULL, 1, 27, 0, NULL),
(8, 'Aloquinsan Handcrafted Soap', 'Natural handcrafted soap made from local ingredients.', 8.00, 'aloquinsan', 'https://ecowarriorph.com/wp-content/uploads/2020/09/soap-1-scaled.jpg', '2025-03-19 13:12:32', NULL, 1, 46, 0, NULL),
(9, 'Aloquinsan Woven Basket', 'A traditional woven basket for storage or decoration.', 25.00, 'aloquinsan', 'https://api.deepai.org/job-view-file/d55f162f-3d70-4499-b659-dabf322b517c/outputs/output.jpg', '2025-03-19 13:12:32', NULL, 1, 46, 0, NULL),
(10, 'Catmon Clay Pot', 'Handmade clay pot for cooking and serving.', 30.00, 'catmon', 'path/to/catmon_pot.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(11, 'Catmon Coconut Candies', 'Delicious coconut candies made from fresh coconuts.', 5.00, 'catmon', 'path/to/catmon_candies.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(12, 'Catmon Traditional Hat', 'A traditional hat made from local materials.', 12.00, 'catmon', 'path/to/catmon_hat.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(13, 'Dumanjug Torta', 'Famous Dumanjug Torta, a local delicacy.', 10.00, 'dumanjug', 'path/to/dumanjug_torta.jpg', '2025-03-19 13:12:32', NULL, 1, 13, 0, NULL),
(14, 'Dumanjug Handwoven Mat', 'A beautiful handwoven mat for your home.', 40.00, 'dumanjug', 'https://api.deepai.org/job-view-file/c2da71fd-498a-481f-98a1-88fea58fd44f/outputs/output.jpg', '2025-03-19 13:12:32', NULL, 1, 23, 0, NULL),
(15, 'Dumanjug Native Basket', 'A native basket perfect for carrying goods.', 18.00, 'dumanjug', 'https://api.deepai.org/job-view-file/da1ec28f-434b-4666-b28c-37114a15b4af/outputs/output.jpg', '2025-03-19 13:12:32', NULL, 1, 39, 0, NULL),
(16, 'Santander Fresh Seafood', 'Freshly caught seafood from Santander.', 50.00, 'santander', 'https://api.deepai.org/job-view-file/323db2be-eb6a-417f-aa74-82f7c744fb10/outputs/output.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(17, 'Santander Handcrafted Souvenirs', 'Unique handcrafted souvenirs from Santander.', 15.00, 'santander', 'https://api.deepai.org/job-view-file/3e713f33-e3fa-4b2a-9f66-ef838f8bd9f3/outputs/output.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(18, 'Santander Local Spices', 'A selection of local spices for your cooking.', 7.00, 'santander', 'path/to/santander_spices.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(19, 'Alcoy Coconut Jam', 'Delicious coconut jam made from fresh coconuts.', 6.00, 'alcoy', 'path/to/alcoy_jam.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(20, 'Alcoy Handwoven Bags', 'Stylish handwoven bags made by local artisans.', 30.00, 'alcoy', 'path/to/alcoy_bags.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(21, 'Alcoy Beach Towels', 'Soft and absorbent beach towels for your trips.', 20.00, 'alcoy', 'path/to/alcoy_towels.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(22, 'Moalboal Shell Crafts', 'Beautiful shell crafts made by local artisans.', 25.00, 'moalboal', 'path/to/moalboal_shells.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(23, 'Moalboal Dried Fish', 'Traditional dried fish, a local delicacy.', 12.00, 'moalboal', 'path/to/moalboal_fish.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(24, 'Moalboal Beach Mats', 'Comfortable mats for your beach outings.', 15.00, 'moalboal', 'path/to/moalboal_mats.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(25, 'Borbon Takyong', 'Delicious Takyong (land snails) delicacy from Borbon.', 8.00, 'borbon', 'path/to/borbon_takyong.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(26, 'Borbon Handcrafted Baskets', 'Unique handcrafted baskets from Borbon.', 20.00, 'borbon', 'path/to/borbon_baskets.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(27, 'Borbon Local Produce', 'Fresh local produce from Borbon farmers.', 10.00, 'borbon', 'path/to/borbon_produce.jpg', '2025-03-19 13:12:32', NULL, 1, 0, 0, NULL),
(28, 'Handwoven Basket', 'Beautifully crafted basket made from natural fibers', 0.00, 'crafts', 'images/basket.jpg', '2025-04-02 15:05:27', 'minglanilla', 1, 45, 0, NULL),
(29, 'Honey Cake', 'Delicious homemade cake with natural honey', 0.00, 'delicacies', 'images/honeycake.jpg', '2025-04-02 15:05:27', 'catmon', 1, 32, 0, NULL),
(30, 'Wooden Sculpture', 'Intricate hand-carved wooden sculpture', 0.00, 'crafts', 'images/sculpture.jpg', '2025-04-02 15:05:27', 'moalboal', 1, 35, 0, NULL),
(31, 'Spiced Nuts', 'Roasted nuts with traditional spices', 0.00, 'delicacies', 'images/spicednuts.jpg', '2025-04-02 15:05:27', 'borbon', 1, 50, 0, NULL),
(32, 'Test Product', 'A sample product', 100.00, 'crafts', 'uploads/test.jpg', '2025-04-02 16:25:25', 'cebu', 1, 16, 0, NULL),
(33, 'LAPOK', 'SADAS', 1.03, 'FOODS', 'uploads/67ed65fb89471.png', '2025-04-02 16:29:47', 'BISAGASA', 1, 34, 0, NULL),
(39, 'Cleo Flynn', 'Aut nisi veniam ill', 903.00, 'Adipisci enim fugiat', 'uploads/images.jpg', '2025-04-03 15:24:53', 'Anim accusantium ex', 2, 40, 0, NULL),
(40, 'Keiko Potter', 'Totam incidunt sequ', 88.00, 'Eu dolore quo et bea', 'uploads/ERD.png', '2025-04-03 15:30:19', 'Sit officia omnis et', 2, 47, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer','vendor') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT 'images/default-profile.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `profile_picture`) VALUES
(1, 'customer', 'customer@gmail.com', '$2y$10$cQOZKMk1zJGmU81.l/roaOaxzjnbjhcey44CewVnJ2jUbBIfS9z5a', 'customer', '2025-03-15 11:41:44', 'images/default-profile.jpg'),
(2, 'vendor', 'vendor@gmail.com', '$2y$10$75IfGrYHRTwmWXCBTyQyNe0zfe228OrHtsT2a8EJvbGpzch6h25t2', 'vendor', '2025-03-19 12:42:01', 'https://cdn-images.dzcdn.net/images/cover/83843448ffbeed9acb8c52d1365b0c4d/0x1900-000000-80-0-0.jpg'),
(3, 'Admin', 'admin@gmail.com', '$2y$10$d08bs5N.mbettz82Z5FbCeVstV98oxZA6oyN0un7qPhpZiDavqRZ.', 'admin', '2025-04-02 13:52:17', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQhvCCckDla7UC8gQ0FBOfUp1H7n9Y0hqeI4Q&s'),
(4, 'vendor1', 'vendor1@gmail.com', '$2y$10$7W6wZP29J10SFGWPzxEmcOtPFiyZZKTxJ/j6qhhVpR7yVwY/HLeiS', 'vendor', '2025-04-03 14:19:54', 'https://media.istockphoto.com/id/1206907529/photo/burrow-with-the-view-from-the-hole-towards-the-sky-as-a-special-symbol-for-planting-mouse.jpg?s=612x612&w=0&k=20&c=bDIcTOPGqo13A23qrMBgClLVnDnXnbL7mWV3XYKbIv0='),
(5, 'Pable', 'Pable@gmail.com', '$2y$10$WaqSwBW/dq1T70Xgg/B6dupMU/GcHaJI5x7yO419iBrd8lgDtXbHq', 'customer', '2025-05-19 04:45:38', 'images/default-profile.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vendor_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `user_id`, `vendor_name`, `created_at`) VALUES
(3, 2, 'vendor', '2025-05-19 04:22:34'),
(4, 4, 'vendor1', '2025-05-19 04:22:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
