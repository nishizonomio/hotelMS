-- Combined Housekeeping Schema
-- Includes all housekeeping module tables and their relationships

-- Disable foreign key checks during setup
SET FOREIGN_KEY_CHECKS=0;

use hotel;
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
  CONSTRAINT `fk_tasks_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasks_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Maintenance requests
DROP TABLE IF EXISTS `maintenance_requests`;
CREATE TABLE `maintenance_requests` (
  `request_id` INT NOT NULL AUTO_INCREMENT,
  `room_id` INT NOT NULL,
  `reported_by` INT DEFAULT NULL,
  `issue_description` TEXT NOT NULL,
  `priority` ENUM('Low','Medium','High') NOT NULL DEFAULT 'Low',
  `status` ENUM('Pending','In Progress','Resolved') NOT NULL DEFAULT 'Pending',
  `reported_date` DATE NOT NULL,
  `completed_date` DATE DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `idx_mroom` (`room_id`),
  KEY `idx_mstatus` (`status`),
  CONSTRAINT `fk_maint_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_maint_reporter` FOREIGN KEY (`reported_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room housekeeping status
DROP TABLE IF EXISTS `housekeeping_room_status`;
CREATE TABLE `housekeeping_room_status` (
  `room_id` INT NOT NULL,
  `status` VARCHAR(64) NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `last_cleaned` DATE DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`room_id`),
  CONSTRAINT `fk_status_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Supplies management
DROP TABLE IF EXISTS `supplies`;
CREATE TABLE `supplies` (
  `supply_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(80) DEFAULT 'Cleaning Supply',
  `description` TEXT DEFAULT NULL,
  `unit` VARCHAR(32) DEFAULT NULL,
  `reorder_level` INT DEFAULT 0,
  `inventory_item_id` INT NULL,
  `department_stock` INT NOT NULL DEFAULT 0,
  `last_sync_date` TIMESTAMP NULL,
  `last_sync_quantity` INT NULL,
  `local_reorder_level` INT NOT NULL DEFAULT 5,
  PRIMARY KEY (`supply_id`),
  INDEX idx_inventory_item (inventory_item_id)
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

-- Supply usage tracking
CREATE TABLE IF NOT EXISTS `supply_usage` (
    `usage_id` INT PRIMARY KEY AUTO_INCREMENT,
    `supply_id` INT NOT NULL,
    `quantity_used` INT NOT NULL,
    `used_by` INT NULL,
    `used_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT,
    `task_id` INT NULL,
    `cost_at_time` DECIMAL(10,2) NULL,
    `performance_score` INT NULL,
    FOREIGN KEY (`supply_id`) REFERENCES `supplies`(`supply_id`) ON DELETE CASCADE,
    FOREIGN KEY (`task_id`) REFERENCES `housekeeping_tasks`(`task_id`),
    INDEX `idx_task_usage` (`task_id`),
    INDEX `idx_staff_usage` (`used_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Supply requests
CREATE TABLE IF NOT EXISTS `supply_requests` (
    `request_id` INT PRIMARY KEY AUTO_INCREMENT,
    `supply_id` INT NOT NULL,
    `quantity_requested` INT NOT NULL,
    `requested_by` INT NULL,
    `requested_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('pending','approved','rejected','received') NOT NULL DEFAULT 'pending',
    `notes` TEXT,
    `task_id` INT NULL,
    `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    `approved_by` INT NULL,
    `approved_at` TIMESTAMP NULL,
    `workflow_state` VARCHAR(50) DEFAULT 'pending',
    FOREIGN KEY (`supply_id`) REFERENCES `supplies`(`supply_id`) ON DELETE CASCADE,
    FOREIGN KEY (`task_id`) REFERENCES `housekeeping_tasks`(`task_id`),
    FOREIGN KEY (`approved_by`) REFERENCES `staff`(`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Supply efficiency standards
CREATE TABLE IF NOT EXISTS `supply_task_standards` (
    `standard_id` INT PRIMARY KEY AUTO_INCREMENT,
    `task_type` VARCHAR(128) NOT NULL,
    `supply_id` INT NOT NULL,
    `expected_quantity` INT NOT NULL,
    `unit_type` VARCHAR(50) NULL,
    `notes` TEXT,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`supply_id`) REFERENCES `supplies`(`supply_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff performance tracking
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
  CONSTRAINT `fk_perf_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_perf_eval` FOREIGN KEY (`evaluator_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_perf_task` FOREIGN KEY (`task_id`) REFERENCES `housekeeping_tasks` (`task_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- Backward compatibility view
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
    DECLARE error_code VARCHAR(5);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            error_code = RETURNED_SQLSTATE;
        CALL log_integration_error(
            'housekeeping',
            'checkout_task_creation',
            CONCAT('Failed to create housekeeping task for reservation: ', NEW.reservation_id),
            error_code,
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
    DECLARE error_code VARCHAR(5);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            error_code = RETURNED_SQLSTATE;
        CALL log_integration_error(
            'maintenance',
            'create_maintenance_request',
            CONCAT('Failed to update room status for maintenance request: ', NEW.request_id),
            error_code,
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
    DECLARE error_code VARCHAR(5);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            error_code = RETURNED_SQLSTATE;
        CALL log_integration_error(
            'housekeeping',
            'task_completion',
            CONCAT('Failed to update room status after task completion: ', NEW.task_id),
            error_code,
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
    DECLARE error_code VARCHAR(5);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            error_code = RETURNED_SQLSTATE;
        CALL log_integration_error(
            'housekeeping',
            'walkin_checkout_task_creation',
            CONCAT('Failed to create housekeeping task for walk-in: ', NEW.walk_in_id),
            error_code,
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

-- Trigger to sync inventory changes to supplies
CREATE TRIGGER after_inventory_update
AFTER UPDATE ON inventory
FOR EACH ROW
BEGIN
    DECLARE housekeeping_category VARCHAR(80);
    
    -- Map inventory categories to housekeeping categories
    SET housekeeping_category = CASE 
        WHEN NEW.category = 'Cleaning & Sanitation' THEN 'cleaning'
        WHEN NEW.category = 'Laundry & Linen' THEN 'linen'
        WHEN NEW.category = 'Hotel Supplies' THEN 'toiletry'
        ELSE NULL
    END;
    
    -- Only process relevant categories
    IF housekeeping_category IS NOT NULL THEN
        -- Update existing supply if found
        UPDATE supplies 
        SET department_stock = NEW.quantity_in_stock,
            last_sync_date = NOW(),
            last_sync_quantity = NEW.quantity_in_stock
        WHERE inventory_item_id = NEW.item_id;
        
        -- Insert new supply if not exists
        IF ROW_COUNT() = 0 THEN
            INSERT INTO supplies (
                name, 
                category, 
                description,
                unit,
                inventory_item_id,
                department_stock,
                last_sync_date,
                last_sync_quantity,
                local_reorder_level
            ) VALUES (
                NEW.item_name,
                housekeeping_category,
                CONCAT('Synced from inventory item #', NEW.item_id),
                'pcs',
                NEW.item_id,
                NEW.quantity_in_stock,
                NOW(),
                NEW.quantity_in_stock,
                FLOOR(NEW.quantity_in_stock * 0.2)
            );
        END IF;
    END IF;
END //

-- Also create trigger for new inventory items
CREATE TRIGGER after_inventory_insert
AFTER INSERT ON inventory
FOR EACH ROW
BEGIN
    DECLARE housekeeping_category VARCHAR(80);
    
    -- Map inventory categories to housekeeping categories
    SET housekeeping_category = CASE 
        WHEN NEW.category = 'Cleaning & Sanitation' THEN 'cleaning'
        WHEN NEW.category = 'Laundry & Linen' THEN 'linen'
        WHEN NEW.category = 'Hotel Supplies' THEN 'toiletry'
        ELSE NULL
    END;
    
    -- Only insert relevant categories
    IF housekeeping_category IS NOT NULL THEN
        INSERT INTO supplies (
            name, 
            category, 
            description,
            unit,
            inventory_item_id,
            department_stock,
            last_sync_date,
            last_sync_quantity,
            local_reorder_level
        ) VALUES (
            NEW.item_name,
            housekeeping_category,
            CONCAT('Synced from inventory item #', NEW.item_id),
            'pcs',
            NEW.item_id,
            NEW.quantity_in_stock,
            NOW(),
            NEW.quantity_in_stock,
            FLOOR(NEW.quantity_in_stock * 0.2)
        );
    END IF;
END //

DELIMITER ;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;