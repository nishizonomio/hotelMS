DROP TABLE IF EXISTS `grn`;
CREATE TABLE `grn` (
  `grn_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity_received` int(11) NOT NULL,
  `inspected_by` varchar(255) NOT NULL,
  `condition_status` enum('Good','Damaged','Expired') NOT NULL,
  `notes` text DEFAULT NULL,
  `date_received` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `quantity_in_stock` int(11) NOT NULL DEFAULT 0,
  `used_qty` int(11) DEFAULT 0,
  `wasted_qty` int(11) DEFAULT 0,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `inspected_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`item_id`, `item_name`, `category`, `quantity_in_stock`, `used_qty`, `wasted_qty`, `unit_price`, `inspected_by`, `created_at`, `updated_at`) VALUES
(1, 'Toilet Paper Rolls', 'Hotel Supplies', 150, 25, 2, 45.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(2, 'Bath Towels', 'Laundry & Linen', 75, 15, 1, 350.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(3, 'Coffee Beans (1kg)', 'Foods & Beverages', 20, 8, 0, 580.00, 'Linda Cruz', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(4, 'All-Purpose Cleaner', 'Cleaning & Sanitation', 45, 12, 1, 125.00, 'Tony Mendoza', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(5, 'Printer Paper (Ream)', 'Office Supplies', 30, 5, 0, 250.00, 'Mark Rivera', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(6, 'Bed Sheets (Queen)', 'Laundry & Linen', 40, 8, 2, 450.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(7, 'Hand Soap Dispensers', 'Cleaning & Sanitation', 25, 3, 0, 180.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(8, 'Light Bulbs (LED)', 'Utility Products', 60, 15, 3, 95.00, 'Tony Mendoza', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(9, 'Shampoo Bottles', 'Hotel Supplies', 80, 20, 1, 85.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(10, 'Kitchen Knives Set', 'Kitchen Equipment', 12, 0, 0, 1250.00, 'Tony Mendoza', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(11, 'Vacuum Cleaner Bags', 'Cleaning & Sanitation', 35, 8, 0, 75.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(12, 'Pillows', 'Laundry & Linen', 50, 10, 2, 280.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(13, 'Floor Wax', 'Cleaning & Sanitation', 18, 4, 0, 320.00, 'Tony Mendoza', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(14, 'Receipt Rolls', 'Office Supplies', 100, 25, 1, 35.00, 'Linda Cruz', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(16, 'Bottled Water', 'Foods & Beverages', 50, 0, 0, 25.00, 'System', '2025-09-12 02:57:07', '2025-09-12 02:54:35'),
(17, 'Soft Drinks / Soda', 'Foods & Beverages', 40, 0, 0, 45.00, 'System', '2025-09-12 02:57:07', '2025-09-12 02:54:35'),
(18, 'Juice', 'Foods & Beverages', 30, 0, 0, 55.00, 'System', '2025-09-12 02:57:07', '2025-09-12 02:54:35'),
(19, 'Beer (Bottled)', 'Foods & Beverages', 19, 0, 0, 80.00, 'System', '2025-09-14 17:59:25', '2025-09-12 02:54:35'),
(20, 'Wine (Red / White)', 'Foods & Beverages', 60, 0, 0, 350.00, 'System', '2025-09-12 02:57:07', '2025-09-12 02:54:35'),
(21, 'Whiskey / Vodka Shots', 'Foods & Beverages', 25, 0, 0, 120.00, 'System', '2025-09-12 02:57:07', '2025-09-12 02:54:35'),
(22, 'Sparkling Water', 'Foods & Beverages', 20, 0, 0, 60.00, 'System', '2025-09-12 02:57:07', '2025-09-12 02:54:35'),
(23, 'Chips / Crisps', 'Foods & Beverages', 55, 0, 0, 40.00, 'System', '2025-09-12 02:57:07', '2025-09-12 02:54:35'),
(24, 'Nuts / Mixed Nuts', 'Foods & Beverages', 65, 0, 0, 70.00, 'System', '2025-09-12 02:57:07', '2025-09-12 02:54:35'),
(25, 'Chocolate Bar', 'Foods & Beverages', 60, 0, 0, 45.00, 'System', '2025-09-12 02:57:07', '2025-09-12 02:54:35'),
(26, 'Cookies / Biscuits', 'Foods & Beverages', 45, 0, 0, 55.00, 'System', '2025-09-12 02:57:07', '2025-09-12 02:54:35'),
(27, 'Candy / Mints', 'Foods & Beverages', 80, 0, 0, 30.00, 'System', '2025-09-12 02:57:07', '2025-09-12 02:54:35'),
(28, 'Mixed Salad Greens', 'Vegetables', 100, 0, 0, 150.00, 'John Doe', '2025-09-14 17:28:50', '2025-09-14 17:26:26'),
(29, 'Cucumber', 'Vegetables', 100, 0, 0, 30.00, 'John Doe', '2025-09-14 17:28:50', '2025-09-14 17:26:26'),
(30, 'Tomatoes', 'Vegetables', 100, 0, 0, 40.00, 'John Doe', '2025-09-14 17:28:50', '2025-09-14 17:26:26'),
(31, 'Carrot', 'Vegetables', 100, 0, 0, 20.00, 'John Doe', '2025-09-14 17:28:50', '2025-09-14 17:26:26'),
(32, 'Red Onion', 'Vegetables', 100, 0, 0, 25.00, 'John Doe', '2025-09-14 17:28:50', '2025-09-14 17:26:26'),
(33, 'Bell Pepper', 'Vegetables', 100, 0, 0, 50.00, 'John Doe', '2025-09-14 17:28:50', '2025-09-14 17:26:26'),
(34, 'Radishes', 'Vegetables', 100, 0, 0, 35.00, 'John Doe', '2025-09-14 17:28:50', '2025-09-14 17:26:26'),
(35, 'Sweet Corn Kernels', 'Vegetables', 100, 0, 0, 45.00, 'John Doe', '2025-09-14 17:28:50', '2025-09-14 17:26:26'),
(36, 'Olive Oil', 'Condiments', 100, 0, 0, 300.00, 'John Doe', '2025-09-14 17:28:50', '2025-09-14 17:26:26'),
(37, 'Lemon Juice', 'Condiments', 100, 0, 0, 60.00, 'John Doe', '2025-09-14 17:28:50', '2025-09-14 17:26:26'),
(38, 'Honey', 'Condiments', 100, 0, 0, 200.00, 'John Doe', '2025-09-14 17:28:50', '2025-09-14 17:26:26'),
(39, 'Salt', 'Condiments', 100, 0, 0, 10.00, 'John Doe', '2025-09-14 17:28:50', '2025-09-14 17:26:26'),
(40, 'Black Pepper', 'Condiments', 100, 0, 0, 25.00, 'John Doe', '2025-09-14 17:28:50', '2025-09-14 17:26:26'),
(41, 'Romaine Lettuce', 'Vegetables', 100, 0, 0, 120.00, 'John Doe', '2025-09-14 18:21:01', '2025-09-14 18:21:01'),
(42, 'Croutons', 'Condiments', 100, 0, 0, 50.00, 'John Doe', '2025-09-14 18:21:01', '2025-09-14 18:21:01'),
(43, 'Parmesan Cheese', 'Dairy', 100, 0, 0, 200.00, 'John Doe', '2025-09-14 18:21:01', '2025-09-14 18:21:01'),
(44, 'Mayonnaise', 'Condiments', 100, 0, 0, 150.00, 'John Doe', '2025-09-14 18:21:01', '2025-09-14 18:21:01'),
(45, 'Olive Oil', 'Condiments', 100, 0, 0, 300.00, 'John Doe', '2025-09-14 18:21:01', '2025-09-14 18:21:01'),
(46, 'Lemon Juice', 'Condiments', 100, 0, 0, 60.00, 'John Doe', '2025-09-14 18:21:01', '2025-09-14 18:21:01'),
(47, 'Dijon Mustard', 'Condiments', 100, 0, 0, 80.00, 'John Doe', '2025-09-14 18:21:01', '2025-09-14 18:21:01'),
(48, 'Worcestershire Sauce', 'Condiments', 100, 0, 0, 90.00, 'John Doe', '2025-09-14 18:21:01', '2025-09-14 18:21:01'),
(49, 'Garlic', 'Vegetables', 100, 0, 0, 20.00, 'John Doe', '2025-09-14 18:21:01', '2025-09-14 18:21:01'),
(50, 'Anchovy Fillets', 'Seafood', 100, 0, 0, 180.00, 'John Doe', '2025-09-14 18:21:01', '2025-09-14 18:21:01'),
(51, 'Salt', 'Condiments', 100, 0, 0, 10.00, 'John Doe', '2025-09-14 18:21:01', '2025-09-14 18:21:01'),
(52, 'Black Pepper', 'Condiments', 100, 0, 0, 25.00, 'John Doe', '2025-09-14 18:21:01', '2025-09-14 18:21:01'),
(53, 'Pork Belly', 'Meat', 100, 0, 0, 300.00, 'John Doe', '2025-09-14 18:27:05', '2025-09-14 18:27:05'),
(54, 'Soy Sauce', 'Condiments', 100, 0, 0, 80.00, 'John Doe', '2025-09-14 18:27:05', '2025-09-14 18:27:05'),
(55, 'Vinegar', 'Condiments', 100, 0, 0, 50.00, 'John Doe', '2025-09-14 18:27:05', '2025-09-14 18:27:05'),
(56, 'Water', 'Condiments', 100, 0, 0, 0.00, 'John Doe', '2025-09-14 18:27:05', '2025-09-14 18:27:05'),
(57, 'Onion', 'Vegetables', 100, 0, 0, 25.00, 'John Doe', '2025-09-14 18:27:05', '2025-09-14 18:27:05'),
(58, 'Garlic', 'Vegetables', 100, 0, 0, 20.00, 'John Doe', '2025-09-14 18:27:05', '2025-09-14 18:27:05'),
(59, 'Bay Leaves', 'Condiments', 100, 0, 0, 15.00, 'John Doe', '2025-09-14 18:27:05', '2025-09-14 18:27:05'),
(60, 'Black Peppercorns', 'Condiments', 100, 0, 0, 30.00, 'John Doe', '2025-09-14 18:27:05', '2025-09-14 18:27:05'),
(61, 'Cooking Oil', 'Condiments', 100, 0, 0, 120.00, 'John Doe', '2025-09-14 18:27:05', '2025-09-14 18:27:05'),
(62, 'Sugar', 'Condiments', 100, 0, 0, 15.00, 'John Doe', '2025-09-14 18:27:05', '2025-09-14 18:27:05'),
(63, 'Salt', 'Condiments', 100, 0, 0, 10.00, 'John Doe', '2025-09-14 18:27:05', '2025-09-14 18:27:05'),
(64, 'Salmon Fillet', 'Seafood', 100, 0, 0, 500.00, 'John Doe', '2025-09-14 18:44:32', '2025-09-14 18:44:32'),
(65, 'Dried Herbs', 'Condiments', 100, 0, 0, 50.00, 'John Doe', '2025-09-14 18:44:32', '2025-09-14 18:44:32'),
(66, 'Dark Chocolate', 'Baking', 100, 0, 0, 250.00, 'John Doe', '2025-09-14 18:49:47', '2025-09-14 18:49:47'),
(67, 'Butter', 'Dairy', 100, 0, 0, 200.00, 'John Doe', '2025-09-14 18:49:47', '2025-09-14 18:49:47'),
(68, 'Eggs', 'Dairy', 100, 0, 0, 15.00, 'John Doe', '2025-09-14 18:49:47', '2025-09-14 18:49:47'),
(69, 'All-purpose Flour', 'Baking', 100, 0, 0, 50.00, 'John Doe', '2025-09-14 18:49:47', '2025-09-14 18:49:47'),
(70, 'Vanilla Extract', 'Condiments', 100, 0, 0, 120.00, 'John Doe', '2025-09-14 18:49:47', '2025-09-14 18:49:47'),
(71, 'Ground Coffee', 'Beverages', 100, 0, 0, 300.00, 'John Doe', '2025-09-14 18:59:47', '2025-09-14 18:59:47'),
(72, 'Milk', 'Dairy', 100, 0, 0, 80.00, 'John Doe', '2025-09-14 18:59:47', '2025-09-14 18:59:47');
INSERT INTO `inventory` (`item_id`, `item_name`, `category`, `quantity_in_stock`, `used_qty`, `wasted_qty`, `unit_price`, `inspected_by`, `created_at`, `updated_at`) VALUES
(73, 'Curtains', 'Laundry & Linen', 100, 15, 1, 150.00, 'Grace Santos', '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(74, 'Mats', 'Laundry & Linen', 100, 8, 0, 75.00, 'Linda Cruz', '2025-09-09 08:42:18', '2025-09-09 08:42:18');

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `po_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_date` date NOT NULL,
  `status` enum('Pending','Approved','Rejected','Received','Completed','partially_received','cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`po_id`, `supplier_id`, `po_number`, `item_name`, `category`, `quantity`, `unit_price`, `total_amount`, `order_date`, `status`, `created_at`, `updated_at`) VALUES
(7, 2, 'PO-20250909-210', 'bedsheets', 'Laundry & Linen', 50, NULL, 6500.00, '2025-09-09', 'Pending', '2025-09-09 08:45:55', '2025-09-09 08:45:55');

DROP TABLE IF EXISTS `stock_usage`;
CREATE TABLE `stock_usage` (
  `usage_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `used_qty` int(11) NOT NULL,
  `used_by` varchar(100) NOT NULL,
  `date_used` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_usage`
--

INSERT INTO `stock_usage` (`usage_id`, `item_id`, `used_qty`, `used_by`, `date_used`) VALUES
(1, 1, 15, 'Housekeeping', '2024-09-01 08:30:00'),
(2, 2, 8, 'Housekeeping', '2024-09-01 10:15:00'),
(3, 3, 3, 'POS', '2024-09-02 06:45:00'),
(4, 4, 5, 'Housekeeping', '2024-09-02 14:20:00'),
(5, 5, 2, 'POS', '2024-09-03 09:00:00'),
(6, 6, 4, 'Housekeeping', '2024-09-03 16:30:00'),
(7, 7, 2, 'Housekeeping', '2024-09-04 11:45:00'),
(8, 8, 8, 'Maintenance', '2024-09-04 13:15:00'),
(9, 9, 12, 'Housekeeping', '2024-09-05 07:30:00'),
(10, 11, 4, 'Housekeeping', '2024-09-05 15:00:00'),
(11, 12, 6, 'Housekeeping', '2024-09-06 12:20:00'),
(12, 13, 2, 'Maintenance', '2024-09-06 14:45:00'),
(13, 14, 15, 'POS', '2024-09-07 08:00:00'),
(14, 1, 10, 'Housekeeping', '2024-09-08 09:30:00'),
(16, 19, 1, 'Minibar', '2025-09-15 01:59:25');

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `email`, `phone`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CleanCorp Supplies', 'Maria Santos', 'maria@cleancorp.com', '+63-917-123-4567', '123 Makati Ave, Makati City, Metro Manila', 1, '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(2, 'FreshFood Distributors', 'Juan Dela Cruz', 'juan@freshfood.ph', '+63-918-234-5678', '456 Quezon Blvd, Quezon City, Metro Manila', 1, '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(3, 'HotelMax Equipment', 'Sarah Johnson', 'sarah@hotelmax.com', '+63-919-345-6789', '789 BGC Drive, Taguig City, Metro Manila', 1, '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(4, 'Metro Office Solutions', 'Robert Kim', 'robert@metrooffice.ph', '+63-920-456-7890', '321 Ortigas Center, Pasig City, Metro Manila', 1, '2025-09-09 08:42:18', '2025-09-09 08:42:18'),
(5, 'Premium Linens Co.', 'Ana Rodriguez', 'ana@premiumlinens.com', '+63-921-567-8901', '654 Shaw Blvd, Mandaluyong City, Metro Manila', 1, '2025-09-09 08:42:18', '2025-09-09 08:42:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `grn`
--
ALTER TABLE `grn`
  ADD PRIMARY KEY (`grn_id`),
  ADD KEY `po_id` (`po_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `item_name` (`item_name`),
  ADD KEY `category` (`category`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`po_id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `stock_usage`
--
ALTER TABLE `stock_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD KEY `is_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `grn`
--
ALTER TABLE `grn`
  MODIFY `grn_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `po_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_usage`
--
ALTER TABLE `stock_usage`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `grn`
--
ALTER TABLE `grn`
  ADD CONSTRAINT `grn_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_usage`
--
ALTER TABLE `stock_usage`
  ADD CONSTRAINT `stock_usage_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`) ON DELETE CASCADE;

--
