-- Kitchen Module Tables
-- Generated from hotel database

-- Table structure for table `complaints`
CREATE TABLE `complaints` (
  `complaint_id` int(11) NOT NULL,
  `guest_name` varchar(255) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `complaint_text` text NOT NULL,
  `status` enum('pending','resolved','dismissed') DEFAULT 'pending',
  `date_filed` datetime DEFAULT current_timestamp(),
  `recipe_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `ingredients`
CREATE TABLE `ingredients` (
  `id` int(11) NOT NULL,
  `recipe_id` int(10) UNSIGNED NOT NULL,
  `ingredient_name` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `quantity_needed` varchar(100) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `ingredient_usage`
CREATE TABLE `ingredient_usage` (
  `usage_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(11) NOT NULL,
  `used_qty` int(11) NOT NULL,
  `used_by` varchar(100) NOT NULL,
  `date_used` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `kitchen_orders`
CREATE TABLE `kitchen_orders` (
  `order_id` int(11) NOT NULL,
  `order_type` enum('Restaurant','Room Service') NOT NULL,
  `status` enum('pending','preparing','ready','completed') DEFAULT 'pending',
  `priority` int(11) DEFAULT 1,
  `table_number` varchar(10) DEFAULT NULL,
  `room_number` varchar(10) DEFAULT NULL,
  `assigned_chef` int(11) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `item_name` text DEFAULT NULL,
  `reported_item` text DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `complain_reason` text DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `estimated_time` int(11) DEFAULT NULL COMMENT 'Time in minutes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reported_items` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `recipes`
CREATE TABLE `recipes` (
  `id` int(11) NOT NULL,
  `recipe_name` varchar(100) NOT NULL,
  `category` enum('Breakfast','Lunch','Dinner','Appetizer','Main Course','Dessert','Beverage') DEFAULT 'Main Course',
  `instructions` text NOT NULL,
  `preparation_time` int(11) DEFAULT NULL COMMENT 'Time in minutes',
  `price` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `waste`
CREATE TABLE `waste` (
  `waste_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `waste_qty` decimal(10,2) NOT NULL,
  `stock_after` decimal(10,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `removed_by` varchar(100) NOT NULL,
  `removed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `remark` varchar(255) DEFAULT NULL,
  `footprint` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Indexes and Auto Increments
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`complaint_id`);

ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`);

ALTER TABLE `ingredient_usage`
  ADD PRIMARY KEY (`usage_id`);

ALTER TABLE `kitchen_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `assigned_chef` (`assigned_chef`),
  ADD KEY `status` (`status`);

ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `waste`
  ADD PRIMARY KEY (`waste_id`);

ALTER TABLE `complaints`
  MODIFY `complaint_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ingredient_usage`
  MODIFY `usage_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `kitchen_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `waste`
  MODIFY `waste_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `recipe_name`, `category`, `instructions`, `preparation_time`, `price`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(2, 'Tinolang Manok', 'Main Course', 'Simmer chicken with ginger, papaya, and malunggay leaves.', 60, 200.00, 1, 2, '2025-09-16 05:01:59', '2025-09-16 05:04:36'),
(3, 'Sinigang na Baboy', 'Main Course', 'Cook pork in tamarind broth with vegetables.', 75, 240.00, 1, 3, '2025-09-16 05:01:59', '2025-09-16 05:04:43'),
(4, 'Bulalo', 'Main Course', 'Boil beef shank with marrow, corn, and vegetables.', 120, 320.00, 1, 4, '2025-09-16 05:01:59', '2025-09-16 05:04:47'),
(5, 'Chicken Sopas', 'Breakfast', 'Cook macaroni soup with chicken and milk.', 45, 180.00, 1, 5, '2025-09-16 05:01:59', '2025-09-16 05:04:59'),
(6, 'Pancit Canton', 'Breakfast', 'Stir-fry noodles with pork, shrimp, and vegetables.', 45, 180.00, 1, 6, '2025-09-16 05:01:59', '2025-09-16 05:05:06'),
(7, 'Pancit Bihon', 'Breakfast', 'Stir-fry bihon noodles with chicken and vegetables.', 40, 170.00, 1, 7, '2025-09-16 05:01:59', '2025-09-16 05:05:21'),
(8, 'Pancit Malabon', 'Breakfast', 'Rice noodles topped with seafood sauce and egg.', 50, 220.00, 1, 8, '2025-09-16 05:01:59', '2025-09-16 05:05:26'),
(9, 'Spaghetti Filipino Style', 'Breakfast', 'Cook sweet-style spaghetti sauce with hotdogs and cheese.', 50, 150.00, 1, 9, '2025-09-16 05:01:59', '2025-09-16 05:05:34'),
(10, 'Carbonara', 'Breakfast', 'Creamy pasta with bacon and white sauce.', 40, 220.00, 1, 10, '2025-09-16 05:01:59', '2025-09-16 05:05:39'),
(12, 'Pork Adobo', 'Main Course', 'Simmer pork belly in soy sauce, vinegar, and garlic.', 65, 220.00, 1, 12, '2025-09-16 05:01:59', '2025-09-16 05:05:50'),
(13, 'Kare-Kare', 'Main Course', 'Stew oxtail and vegetables with peanut sauce.', 90, 280.00, 1, 13, '2025-09-16 05:01:59', '2025-09-16 05:05:53'),
(14, 'Lechon Kawali', 'Main Course', 'Deep fry pork belly until crispy.', 50, 260.00, 1, 14, '2025-09-16 05:01:59', '2025-09-16 05:05:57'),
(15, 'Crispy Pata', 'Main Course', 'Deep fry pork leg until crispy.', 120, 480.00, 1, 15, '2025-09-16 05:01:59', '2025-09-16 05:06:01'),
(16, 'Beef Tapa', 'Main Course', 'Marinate beef strips and serve with rice and egg.', 40, 180.00, 1, 16, '2025-09-16 05:01:59', '2025-09-16 05:06:06'),
(17, 'Tocino', 'Dinner', 'Pan-fry sweet cured pork and serve with garlic rice.', 30, 170.00, 1, 17, '2025-09-16 05:01:59', '2025-09-16 05:06:11'),
(18, 'Longganisa', 'Dinner', 'Fry pork sausages served with rice and egg.', 30, 160.00, 1, 18, '2025-09-16 05:01:59', '2025-09-16 05:06:16'),
(19, 'Daing na Bangus', 'Lunch', 'Marinate milkfish and fry until crispy.', 35, 200.00, 1, 19, '2025-09-16 05:01:59', '2025-09-16 05:06:19'),
(20, 'Chicken Inasal', 'Lunch', 'Grill marinated chicken leg with annatto oil.', 60, 210.00, 1, 20, '2025-09-16 05:01:59', '2025-09-16 05:06:23'),
(21, 'Grilled Tilapia', 'Lunch', 'Grill seasoned tilapia wrapped in banana leaf.', 40, 220.00, 1, 21, '2025-09-16 05:01:59', '2025-09-16 05:06:26');

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`id`, `recipe_id`, `ingredient_name`, `category`, `quantity_needed`, `unit`, `notes`, `created_at`, `updated_at`) VALUES
(377, 2, 'Chicken', 'Meat', '500', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(378, 2, 'Papaya', 'Vegetable', '150', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(379, 2, 'Malunggay', 'Vegetable', '30', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(380, 3, 'Pork', 'Meat', '400', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(381, 3, 'Tamarind', 'Seasoning', '20', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(382, 3, 'Kangkong', 'Vegetable', '100', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(383, 4, 'Beef Shank', 'Meat', '800', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(384, 4, 'Corn', 'Vegetable', '3', 'pcs', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(385, 4, 'Cabbage', 'Vegetable', '150', 'pc', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(386, 5, 'Chicken', 'Meat', '300', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(387, 5, 'Macaroni', 'Grain', '50', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(388, 5, 'Milk', 'Dairy', '200', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(389, 6, 'Noodles', 'Grain', '150', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(390, 6, 'Pork', 'Meat', '150', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(391, 6, 'Shrimp', 'Seafood', '100', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(392, 7, 'Bihon Noodles', 'Grain', '150', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(393, 7, 'Chicken', 'Meat', '150', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(394, 7, 'Cabbage', 'Vegetable', '100', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(395, 8, 'Rice Noodles', 'Grain', '150', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(396, 8, 'Shrimp', 'Seafood', '100', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(397, 8, 'Egg', 'Dairy', '2', 'pcs', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(398, 9, 'Spaghetti Noodles', 'Grain', '150', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(399, 9, 'Hotdog', 'Meat', '100', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(400, 9, 'Tomato Sauce', 'Seasoning', '100', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(401, 10, 'Pasta', 'Grain', '150', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(402, 10, 'Bacon', 'Meat', '50', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(403, 10, 'Cream', 'Dairy', '100', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:21:20'),
(404, 11, 'Chicken', 'Meat', '999999', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:06:17'),
(405, 11, 'Soy Sauce', 'Seasoning', '999999', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:06:17'),
(406, 11, 'Vinegar', 'Seasoning', '999999', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:06:17'),
(407, 11, 'Garlic', 'Vegetable', '999999', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:06:17'),
(408, 12, 'Pork Belly', 'Meat', '400', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(409, 12, 'Soy Sauce', 'Seasoning', '30', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(410, 12, 'Vinegar', 'Seasoning', '30', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(411, 12, 'Garlic', 'Vegetable', '10', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(412, 13, 'Oxtail', 'Meat', '500', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(413, 13, 'Peanut Sauce', 'Seasoning', '50', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(414, 13, 'Eggplant', 'Vegetable', '100', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(415, 13, 'String Beans', 'Vegetable', '50', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(416, 13, 'Banana Heart', 'Vegetable', '50', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(417, 14, 'Pork Belly', 'Meat', '500', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(418, 14, 'Salt', 'Seasoning', '10', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(419, 14, 'Pepper', 'Seasoning', '5', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(420, 14, 'Oil', 'Seasoning', '50', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(421, 15, 'Pork Leg', 'Meat', '600', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(422, 15, 'Salt', 'Seasoning', '10', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(423, 15, 'Pepper', 'Seasoning', '5', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(424, 15, 'Oil', 'Seasoning', '50', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(425, 16, 'Beef', 'Meat', '300', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(426, 16, 'Soy Sauce', 'Seasoning', '20', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(427, 16, 'Vinegar', 'Seasoning', '10', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(428, 16, 'Garlic', 'Vegetable', '5', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(429, 17, 'Pork', 'Meat', '300', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(430, 17, 'Sugar', 'Seasoning', '20', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(431, 17, 'Salt', 'Seasoning', '5', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(432, 17, 'Garlic', 'Vegetable', '5', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(433, 18, 'Pork Sausage', 'Meat', '300', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(434, 18, 'Garlic', 'Vegetable', '5', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(435, 18, 'Salt', 'Seasoning', '5', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(436, 18, 'Pepper', 'Seasoning', '5', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(437, 19, 'Milkfish', 'Seafood', '300', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(438, 19, 'Vinegar', 'Seasoning', '10', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(439, 19, 'Garlic', 'Vegetable', '5', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(440, 19, 'Salt', 'Seasoning', '5', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(441, 20, 'Chicken Leg', 'Meat', '300', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(442, 20, 'Soy Sauce', 'Seasoning', '20', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(443, 20, 'Vinegar', 'Seasoning', '20', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(444, 20, 'Garlic', 'Vegetable', '10', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(445, 20, 'Annatto Oil', 'Seasoning', '10', 'ml', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(446, 21, 'Tilapia', 'Seafood', '300', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(447, 21, 'Salt', 'Seasoning', '10', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(448, 21, 'Pepper', 'Seasoning', '5', 'g', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03'),
(449, 21, 'Banana Leaf', 'Vegetable', '1', 'pc', NULL, '2025-10-01 18:06:17', '2025-10-01 18:22:03');

--
-- Dumping data for table `ingredient_usage`
--

INSERT INTO `ingredient_usage` (`usage_id`, `item_id`, `used_qty`, `used_by`, `date_used`) VALUES
(1, 256, 100, 'System', '2025-09-23 20:31:16'),
(2, 262, 100, 'System', '2025-09-23 20:31:16'),
(3, 256, 100, 'System', '2025-09-23 20:31:40'),
(4, 262, 100, 'System', '2025-09-23 20:31:40'),
(5, 256, 100, 'System', '2025-09-23 20:37:10'),
(6, 262, 100, 'System', '2025-09-23 20:37:10'),
(7, 256, 100, 'System', '2025-09-23 20:37:31'),
(8, 262, 100, 'System', '2025-09-23 20:37:31'),
(9, 799, 150, 'System', '2025-09-23 21:28:14'),
(10, 817, 100, 'System', '2025-09-23 21:28:14'),
(11, 799, 150, 'System', '2025-09-23 21:28:57'),
(12, 817, 100, 'System', '2025-09-23 21:28:57'),
(13, 803, 200, 'System', '2025-09-23 22:47:53'),
(14, 809, 50, 'System', '2025-09-23 22:47:53'),
(15, 812, 20, 'System', '2025-09-23 22:47:53'),
(16, 810, 150, 'System', '2025-09-23 22:47:53'),
(17, 799, 3000, 'System', '2025-09-24 12:30:21'),
(18, 800, 1550, 'System', '2025-09-24 12:30:21'),
(19, 801, 1500, 'System', '2025-09-24 12:30:21'),
(20, 803, 500, 'System', '2025-09-24 12:30:21'),
(21, 804, 450, 'System', '2025-09-24 12:30:21'),
(22, 805, 300, 'System', '2025-09-24 12:30:21'),
(23, 806, 250, 'System', '2025-09-24 12:30:21'),
(24, 807, 4, 'System', '2025-09-24 12:30:21'),
(25, 808, 100, 'System', '2025-09-24 12:30:21'),
(26, 809, 100, 'System', '2025-09-24 12:30:21'),
(27, 810, 550, 'System', '2025-09-24 12:30:21'),
(28, 811, 10, 'System', '2025-09-24 12:30:21'),
(29, 812, 60, 'System', '2025-09-24 12:30:21'),
(30, 813, 100, 'System', '2025-09-24 12:30:21'),
(31, 814, 200, 'System', '2025-09-24 12:30:21'),
(32, 816, 100, 'System', '2025-09-24 12:30:21'),
(33, 817, 201, 'System', '2025-09-24 12:30:21'),
(34, 819, 100, 'System', '2025-09-24 12:30:21'),
(35, 820, 50, 'System', '2025-09-24 12:30:21'),
(36, 821, 1, 'System', '2025-09-24 12:30:21'),
(37, 822, 50, 'System', '2025-09-24 12:30:21'),
(38, 823, 150, 'System', '2025-09-24 12:30:21'),
(39, 824, 250, 'System', '2025-09-24 12:30:21'),
(40, 826, 150, 'System', '2025-09-24 12:30:21'),
(41, 828, 350, 'System', '2025-09-24 12:30:21'),
(42, 829, 180, 'System', '2025-09-24 12:30:21'),
(43, 830, 280, 'System', '2025-09-24 12:30:21'),
(44, 831, 90, 'System', '2025-09-24 12:30:21'),
(45, 832, 80, 'System', '2025-09-24 12:30:21'),
(46, 833, 1, 'System', '2025-09-24 12:30:21'),
(47, 834, 5, 'System', '2025-09-24 12:30:21'),
(48, 835, 200, 'System', '2025-09-24 12:30:21'),
(49, 836, 200, 'System', '2025-09-24 12:30:21'),
(50, 837, 50, 'System', '2025-09-24 12:30:21'),
(51, 838, 50, 'System', '2025-09-24 12:30:21'),
(52, 839, 50, 'System', '2025-09-24 12:30:21'),
(53, 840, 100, 'System', '2025-09-24 12:30:21'),
(54, 1118, 999999, 'System', '2025-10-02 02:19:22'),
(55, 1119, 999999, 'System', '2025-10-02 02:19:22'),
(56, 1120, 999999, 'System', '2025-10-02 02:19:22'),
(57, 1128, 999999, 'System', '2025-10-02 02:19:22'),
(58, 1167, 999999, 'System', '2025-10-02 02:19:22'),
(59, 1191, 999999, 'System', '2025-10-02 02:19:22'),
(60, 1192, 999999, 'System', '2025-10-02 02:19:22'),
(61, 1193, 999999, 'System', '2025-10-02 02:19:22'),
(62, 1201, 999999, 'System', '2025-10-02 02:19:22'),
(63, 1240, 999999, 'System', '2025-10-02 02:19:22'),
(64, 1139, 150, 'System', '2025-10-02 02:24:28'),
(65, 1140, 50, 'System', '2025-10-02 02:24:28'),
(66, 1141, 100, 'System', '2025-10-02 02:24:28'),
(67, 1212, 150, 'System', '2025-10-02 02:24:28'),
(68, 1213, 50, 'System', '2025-10-02 02:24:28'),
(69, 1214, 100, 'System', '2025-10-02 02:24:28');

--
-- Dumping data for table `kitchen_orders`
--

INSERT INTO `kitchen_orders` (`order_id`, `order_type`, `status`, `priority`, `table_number`, `room_number`, `assigned_chef`, `guest_name`, `guest_id`, `item_name`, `reported_item`, `total_amount`, `notes`, `complain_reason`, `resolution`, `estimated_time`, `created_at`, `updated_at`, `reported_items`) VALUES
(33, 'Restaurant', 'ready', 1, NULL, '101', NULL, 'John Doe', 1, 'Sinigang na Baboy', NULL, 240.00, NULL, NULL, NULL, NULL, '2025-10-01 18:12:59', '2025-10-01 18:17:44', NULL),
(34, 'Room Service', 'completed', 1, NULL, '101', NULL, 'John Doe', 1, 'Sinigang na Baboy', NULL, 240.00, NULL, NULL, NULL, NULL, '2025-10-01 18:14:48', '2025-10-01 18:19:22', 'deducted'),
(35, 'Restaurant', 'pending', 1, '15', NULL, NULL, 'John Doe', 1, 'Bulalo', NULL, 320.00, NULL, NULL, NULL, NULL, '2025-10-01 18:22:18', '2025-10-01 18:22:18', NULL),
(36, 'Restaurant', 'pending', 1, '15', NULL, NULL, 'John Doe', 1, 'Pancit Bihon', NULL, 170.00, NULL, NULL, NULL, NULL, '2025-10-01 18:22:40', '2025-10-01 18:22:40', NULL),
(37, 'Restaurant', 'completed', 1, '15', NULL, NULL, 'John Doe', 1, 'Carbonara', NULL, 220.00, NULL, NULL, NULL, NULL, '2025-10-01 18:24:09', '2025-10-01 18:24:28', 'deducted'),
(38, 'Restaurant', 'pending', 1, '15', NULL, NULL, 'John Doe', 1, 'Sinigang na Baboy, Bulalo', NULL, 560.00, NULL, NULL, NULL, NULL, '2025-10-01 18:32:16', '2025-10-01 18:32:16', NULL);

--
-- Dumping data for table `waste`
--

INSERT INTO `waste` (`waste_id`, `item_id`, `waste_qty`, `stock_after`, `reason`, `removed_by`, `removed_at`, `remark`, `footprint`) VALUES
(0, 134, 10.00, 10.00, 'Expired', '', '2025-09-22 20:20:46', NULL, '2025-09-22 12:20:46'),
(0, 275, 10.00, 170.00, 'Damaged', '', '2025-09-23 20:17:46', NULL, '2025-09-23 12:17:46'),
(0, 275, 10.00, 170.00, 'Expired', '', '2025-09-23 20:18:02', NULL, '2025-09-23 12:18:02'),
(0, 275, 10.00, 170.00, 'Expired', '', '2025-09-23 20:18:12', NULL, '2025-09-23 12:18:12'),
(0, 821, 10.00, 190.00, 'Damaged', '', '2025-09-23 22:54:31', NULL, '2025-09-23 14:54:31'),
(0, 821, 10.00, 180.00, 'Damaged', '', '2025-09-23 22:57:12', NULL, '2025-09-23 14:57:12'),
(0, 821, 10.00, 170.00, 'Damaged', '', '2025-09-23 22:57:33', NULL, '2025-09-23 14:57:33'),
(0, 799, 10.00, 149990.00, 'Expired', '', '2025-09-23 22:59:35', NULL, '2025-09-23 14:59:35'),
(0, 821, 30.00, 0.00, 'Expired', '', '2025-09-24 00:07:05', NULL, '2025-09-23 16:07:05'),
(0, 801, 5.00, 0.00, 'Expired', '', '2025-09-24 00:07:22', NULL, '2025-09-23 16:07:22'),
(0, 1014, 10.00, 0.00, 'Damaged', '', '2025-09-24 20:55:30', NULL, '2025-09-24 12:55:30');