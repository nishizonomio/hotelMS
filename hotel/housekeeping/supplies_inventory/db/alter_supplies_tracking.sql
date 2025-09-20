-- Add inventory reference and local tracking to supplies table
ALTER TABLE supplies
ADD COLUMN inventory_item_id INT NULL,
ADD COLUMN department_stock INT NOT NULL DEFAULT 0,
ADD COLUMN last_sync_date TIMESTAMP NULL,
ADD COLUMN last_sync_quantity INT NULL,
ADD COLUMN local_reorder_level INT NOT NULL DEFAULT 5,
ADD INDEX idx_inventory_item (inventory_item_id);

-- Add usage tracking table for housekeeping
CREATE TABLE IF NOT EXISTS supply_usage (
    usage_id INT PRIMARY KEY AUTO_INCREMENT,
    supply_id INT NOT NULL,
    quantity_used INT NOT NULL,
    used_by INT NULL,  -- staff_id if available
    used_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (supply_id) REFERENCES supplies(supply_id) ON DELETE CASCADE
);

-- Add supply requests table for housekeeping
CREATE TABLE IF NOT EXISTS supply_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    supply_id INT NOT NULL,
    quantity_requested INT NOT NULL,
    requested_by INT NULL,  -- staff_id if available
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','approved','rejected','received') NOT NULL DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (supply_id) REFERENCES supplies(supply_id) ON DELETE CASCADE
);