CREATE TABLE `lounge_orders` (
  `order_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `table_number` varchar(10) DEFAULT NULL,
  `item` varchar(255) NOT NULL,
  `order_type` enum('dine_in','takeaway') DEFAULT 'dine_in',
  `total_amount` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','preparing','ready','served','cancelled') DEFAULT 'pending',
  `staff_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `lounge_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` enum('Beverages','Cocktails','Appetizers','Main Course','Desserts','Snacks') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `special_instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 





CREATE TABLE `room_dining_orders` (
  `order_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `order_type` enum('breakfast','lunch','dinner','snacks','beverages') NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_time` datetime DEFAULT NULL,
  `status` enum('pending','preparing','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  `staff_id` int(11) NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `room_dining_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` enum('Breakfast','Lunch','Dinner','Snacks','Beverages','Desserts') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `special_instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;







CREATE TABLE `restaurant_orders` (
  `order_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `table_number` varchar(10) DEFAULT NULL,
  `order_type` enum('dine_in','takeaway','buffet') DEFAULT 'dine_in',
  `total_amount` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','preparing','ready','served','cancelled') DEFAULT 'pending',
  `staff_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Table structure for table `restaurant_order_items`
--

CREATE TABLE `restaurant_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` enum('Appetizers','Main Course','Desserts','Beverages','Sides','Others') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `special_instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Indexes and AUTO_INCREMENT for new restaurant POS tables
ALTER TABLE `restaurant_orders`
  ADD PRIMARY KEY (`order_id`);
ALTER TABLE `restaurant_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);
ALTER TABLE `restaurant_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `restaurant_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Optional: Add foreign key from restaurant_order_items to restaurant_orders
-- Place this near other constraints if needed during import
-- ALTER TABLE `restaurant_order_items`
--   ADD CONSTRAINT `restaurant_items_order_fk` FOREIGN KEY (`order_id`) REFERENCES `restaurant_orders` (`order_id`) ON DELETE CASCADE;

-- ---------------------------------------------
-- Restaurant POS enhancements (status/totals/payment/txn)
-- ---------------------------------------------
-- Extend status values and add financial/payment fields
ALTER TABLE `restaurant_orders`
  MODIFY COLUMN `status` enum('pending','in_progress','served','paid','cancelled','refunded') DEFAULT 'pending';

ALTER TABLE `restaurant_orders`
  ADD COLUMN `subtotal_amount` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `order_type`,
  ADD COLUMN `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `subtotal_amount`,
  ADD COLUMN `payment_method` enum('cash','card','room_charge','gcash','other') DEFAULT 'cash' AFTER `status`,
  ADD COLUMN `transaction_id` varchar(64) DEFAULT NULL AFTER `payment_method`;

-- Keep legacy `total_amount` for compatibility; ensure position after tax
ALTER TABLE `restaurant_orders`
  MODIFY COLUMN `total_amount` decimal(10,2) NOT NULL AFTER `tax_amount`;

-- Optional unique transaction id for reconciliation
ALTER TABLE `restaurant_orders`
  ADD UNIQUE KEY `uniq_restaurant_txn` (`transaction_id`);


CREATE TABLE IF NOT EXISTS giftshop_order_items (
			id INT(11) NOT NULL AUTO_INCREMENT,
			order_id INT(11) NOT NULL,
			item_name VARCHAR(255) NOT NULL,
			quantity INT(11) NOT NULL,
			unit_price DECIMAL(10,2) NOT NULL,
			total_price DECIMAL(10,2) NOT NULL,
			special_instructions TEXT DEFAULT NULL,
			PRIMARY KEY (id),
			KEY order_id_idx (order_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



    CREATE TABLE IF NOT EXISTS giftshop_orders (
			order_id INT(11) NOT NULL AUTO_INCREMENT,
			guest_id INT(11) NOT NULL,
			total_amount DECIMAL(10,2) NOT NULL,
			order_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			status ENUM('to_be_billed','paid') DEFAULT 'to_be_billed',
			staff_id VARCHAR(20) NOT NULL,
			notes TEXT DEFAULT NULL,
			subtotal_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			payment_method ENUM('cash','card','gcash','other') DEFAULT 'cash',
			transaction_id VARCHAR(64) DEFAULT NULL,
			PRIMARY KEY (order_id),
			UNIQUE KEY uniq_giftshop_txn (transaction_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



    CREATE TABLE `minibar_consumption` (
  `consumption_id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `consumed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`consumption_id`),
  KEY `guest_id` (`guest_id`),
  KEY `item_id` (`item_id`),
  KEY `staff_id` (`staff_id`),
  KEY `room_number` (`room_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `minibar_refill_logs`
--

CREATE TABLE `minibar_refill_logs` (
  `refill_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` varchar(10) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_added` int(11) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `refilled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`refill_id`),
  KEY `item_id` (`item_id`),
  KEY `staff_id` (`staff_id`),
  KEY `room_number` (`room_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `minibar_inventory_alerts`
--

CREATE TABLE `minibar_inventory_alerts` (
  `alert_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `alert_type` enum('low_stock','expiry','out_of_stock') NOT NULL,
  `room_number` varchar(10) DEFAULT NULL,
  `threshold_quantity` int(11) DEFAULT NULL,
  `current_quantity` int(11) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `alert_message` text NOT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`alert_id`),
  KEY `item_id` (`item_id`),
  KEY `alert_type` (`alert_type`),
  KEY `is_resolved` (`is_resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
