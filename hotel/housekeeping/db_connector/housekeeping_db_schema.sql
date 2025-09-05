DROP TABLE IF EXISTS `housekeeping_tasks`;
CREATE TABLE `housekeeping_tasks` (
  `task_id` INT NOT NULL AUTO_INCREMENT,
  `room_id` INT NOT NULL,
  `staff_id` INT DEFAULT NULL,
  `task_date` DATE NOT NULL,
  `task_type` VARCHAR(128) NOT NULL,
  `status` ENUM('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
  `remarks` TEXT DEFAULT NULL,
  PRIMARY KEY (`task_id`),
  KEY `idx_room` (`room_id`),
  KEY `idx_staff` (`staff_id`),
  KEY `idx_status` (`status`),
  -- Foreign keys now reference tables assumed to exist in the same database
  CONSTRAINT `fk_tasks_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasks_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Maintenance requests (note: DB uses 'Resolved' as completed marker; code maps to 'Completed')
DROP TABLE IF EXISTS `maintenance_requests`;
CREATE TABLE `maintenance_requests` (
  `request_id` INT NOT NULL AUTO_INCREMENT,
  `room_id` INT NOT NULL,
  `reported_by` INT DEFAULT NULL, -- staff_id or null
  `issue_description` TEXT NOT NULL,
  `priority` ENUM('Low','Medium','High') NOT NULL DEFAULT 'Low',
  `status` ENUM('Pending','In Progress','Resolved') NOT NULL DEFAULT 'Pending',
  `reported_date` DATE NOT NULL,
  `completed_date` DATE DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `idx_mroom` (`room_id`),
  KEY `idx_mstatus` (`status`),
  -- Foreign keys now reference tables assumed to exist in the same database
  CONSTRAINT `fk_maint_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_maint_reporter` FOREIGN KEY (`reported_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room housekeeping status (current state of a room)
DROP TABLE IF EXISTS `housekeeping_room_status`;
CREATE TABLE `housekeeping_room_status` (
  `room_id` INT NOT NULL,
  `status` VARCHAR(64) NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `last_cleaned` DATE DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`room_id`),
  -- Foreign key now references table assumed to exist in the same database
  CONSTRAINT `fk_status_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Supplies inventory (basic)
DROP TABLE IF EXISTS `supplies`;
CREATE TABLE `supplies` (
  `supply_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(80) DEFAULT 'Cleaning Supply',
  `description` TEXT DEFAULT NULL,
  `unit` VARCHAR(32) DEFAULT NULL,
  `reorder_level` INT DEFAULT 0,
  PRIMARY KEY (`supply_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `supply_stock`;
CREATE TABLE `supply_stock` (
  `stock_id` INT NOT NULL AUTO_INCREMENT,
  `supply_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 0,
  `last_received` DATE DEFAULT NULL,
  PRIMARY KEY (`stock_id`),
  KEY `idx_supply` (`supply_id`),
  CONSTRAINT `fk_stock_supply` FOREIGN KEY (`supply_id`) REFERENCES `supplies` (`supply_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff performance / evaluations
DROP TABLE IF EXISTS `staff_performance`;
CREATE TABLE `staff_performance` (
  `perf_id` INT NOT NULL AUTO_INCREMENT,
  `task_id` INT DEFAULT NULL,
  `staff_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `tasks_completed` INT NOT NULL DEFAULT 0,
  `avg_time_minutes` DECIMAL(6,2) DEFAULT NULL,
  `quality_rating` DECIMAL(3,2) DEFAULT NULL,
  `feedback` TEXT DEFAULT NULL,
  `evaluator_id` INT DEFAULT NULL,
  PRIMARY KEY (`perf_id`),
  KEY `idx_perf_staff` (`staff_id`),
  -- Foreign keys now reference tables assumed to exist in the same database
  CONSTRAINT `fk_perf_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_perf_eval` FOREIGN KEY (`evaluator_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL,
  -- Add foreign key to housekeeping_tasks for task_id
  CONSTRAINT `fk_perf_task` FOREIGN KEY (`task_id`) REFERENCES `housekeeping_tasks` (`task_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Minimal users table (if you need auth integration)
-- This table is also likely part of the main project's authentication system.
-- If 'users' is managed by the main project, remove this section.
-- If 'users' is specific to housekeeping staff logins, keep it but ensure it's clear.
-- For now, assuming it's part of the main project, so REMOVING it.
-- If you need a separate 'users' table for housekeeping, you'd keep it here.
-- DROP TABLE IF EXISTS `users`;
-- CREATE TABLE `users` (
--   `user_id` INT NOT NULL AUTO_INCREMENT,
--   `username` VARCHAR(150) NOT NULL UNIQUE,
--   `password_hash` VARCHAR(255) NOT NULL,
--   `staff_id` INT DEFAULT NULL,
--   `role` VARCHAR(80) DEFAULT 'user',
--   PRIMARY KEY (`user_id`),
--   CONSTRAINT `fk_users_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Useful views for backward compatibility with older PHP code expecting 'maintenance_id' and 'issue'
DROP VIEW IF EXISTS `vw_maintenance_requests`;
CREATE VIEW `vw_maintenance_requests` AS
SELECT
  request_id AS maintenance_id,
  room_id,
  reported_by,
  issue_description AS issue,
  priority,
  status,
  reported_date,
  completed_date,
  remarks
FROM maintenance_requests;

-- Sample seed data (optional) — uncomment to insert
-- IMPORTANT: If 'rooms' and 'staff' are managed by the main project,
-- these INSERT statements should be in the main project's seed data,
-- or you need to ensure the IDs match existing data.
-- For now, commenting them out as they are likely part of the main project's data.
-- INSERT INTO `rooms` (room_number, room_type, floor) VALUES ('101','Deluxe',1),('102','Standard',1),('201','Suite',2);
-- INSERT INTO `staff` (first_name,last_name,role,email) VALUES ('Alice','Smith','Housekeeping','alice@example.com'),('Bob','Tan','Maintenance','bob@example.com');
-- INSERT INTO `maintenance_requests` (room_id, reported_by, issue_description, priority, status, reported_date) VALUES (1,1,'Leaky faucet','Low','Pending','2025-08-01');

-- End of schema
SET FOREIGN_KEY_CHECKS=1;