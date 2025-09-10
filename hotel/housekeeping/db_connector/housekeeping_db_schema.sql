-- Disable foreign key checks during setup
SET FOREIGN_KEY_CHECKS=0;

-- Core Tables
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

-- Error Logging
DROP TABLE IF EXISTS `integration_error_logs`;
CREATE TABLE `integration_error_logs` (
    `log_id` INT NOT NULL AUTO_INCREMENT,
    `module` VARCHAR(50) NOT NULL,
    `operation` VARCHAR(100) NOT NULL,
    `error_message` TEXT NOT NULL,
    `error_code` VARCHAR(50),
    `source_table` VARCHAR(50),
    `affected_ids` TEXT,
    `stack_trace` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    INDEX `idx_module` (`module`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stored Procedures
DELIMITER //

CREATE PROCEDURE `log_integration_error`(
    IN p_module VARCHAR(50),
    IN p_operation VARCHAR(100),
    IN p_error_message TEXT,
    IN p_error_code VARCHAR(50),
    IN p_source_table VARCHAR(50),
    IN p_affected_ids TEXT
)
BEGIN
    INSERT INTO integration_error_logs (
        module, operation, error_message, error_code, 
        source_table, affected_ids
    ) VALUES (
        p_module, p_operation, p_error_message, p_error_code, 
        p_source_table, p_affected_ids
    );
END //

-- Integration Triggers
CREATE TRIGGER after_reservation_checkout_with_logging
AFTER UPDATE ON reservations
FOR EACH ROW
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        CALL log_integration_error(
            'housekeeping',
            'checkout_task_creation',
            CONCAT('Failed to create housekeeping task for reservation: ', NEW.reservation_id),
            SQLSTATE,
            'reservations',
            NEW.reservation_id
        );
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error creating housekeeping task after checkout';
    END;

    IF NEW.status = 'checked_out' AND OLD.status != 'checked_out' THEN
        INSERT INTO housekeeping_tasks (room_id, task_date, task_type, status)
        VALUES (NEW.room_id, CURDATE(), 'Cleaning', 'Pending');
        
        UPDATE rooms SET status = 'dirty' 
        WHERE room_id = NEW.room_id;
        
        INSERT INTO housekeeping_room_status (room_id, status, last_cleaned)
        VALUES (NEW.room_id, 'Needs Cleaning', NULL)
        ON DUPLICATE KEY UPDATE 
            status = 'Needs Cleaning',
            last_cleaned = NULL;
    END IF;
END //

CREATE TRIGGER after_maintenance_request_with_logging
AFTER INSERT ON maintenance_requests
FOR EACH ROW
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        CALL log_integration_error(
            'maintenance',
            'create_maintenance_request',
            CONCAT('Failed to update room status for maintenance request: ', NEW.request_id),
            SQLSTATE,
            'maintenance_requests',
            NEW.request_id
        );
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error updating room status for maintenance';
    END;

    IF NEW.priority = 'High' THEN
        UPDATE rooms SET status = 'under maintenance' 
        WHERE room_id = NEW.room_id;
    END IF;
END //

CREATE TRIGGER after_housekeeping_complete_with_logging
AFTER UPDATE ON housekeeping_tasks
FOR EACH ROW
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        CALL log_integration_error(
            'housekeeping',
            'task_completion',
            CONCAT('Failed to update room status after task completion: ', NEW.task_id),
            SQLSTATE,
            'housekeeping_tasks',
            NEW.task_id
        );
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error updating room status after task completion';
    END;

    IF NEW.status = 'Completed' AND OLD.status != 'Completed' THEN
        UPDATE rooms SET status = 'available' 
        WHERE room_id = NEW.room_id;
        
        UPDATE housekeeping_room_status 
        SET status = 'Clean', 
            last_cleaned = CURDATE()
        WHERE room_id = NEW.room_id;
    END IF;
END //

CREATE TRIGGER after_walkin_checkout_with_logging
AFTER UPDATE ON walk_ins
FOR EACH ROW
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        CALL log_integration_error(
            'housekeeping',
            'walkin_checkout_task_creation',
            CONCAT('Failed to create housekeeping task for walk-in: ', NEW.walk_in_id),
            SQLSTATE,
            'walk_ins',
            NEW.walk_in_id
        );
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error creating housekeeping task after walk-in checkout';
    END;

    IF NEW.status = 'checked_out' AND OLD.status != 'checked_out' THEN
        INSERT INTO housekeeping_tasks (room_id, task_date, task_type, status)
        VALUES (NEW.room_id, CURDATE(), 'Cleaning', 'Pending');
        
        UPDATE rooms SET status = 'dirty' 
        WHERE room_id = NEW.room_id;
        
        INSERT INTO housekeeping_room_status (room_id, status, last_cleaned)
        VALUES (NEW.room_id, 'Needs Cleaning', NULL)
        ON DUPLICATE KEY UPDATE 
            status = 'Needs Cleaning',
            last_cleaned = NULL;
    END IF;
END //

DELIMITER ;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;