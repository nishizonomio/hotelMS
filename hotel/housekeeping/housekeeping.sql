CREATE TABLE housekeeping_tasks (
  task_id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  staff_id VARCHAR(20) NOT NULL,
  task_status ENUM('assigned','in progress','completed') DEFAULT 'assigned',
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  start_time TIMESTAMP NULL,
  end_time TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS maintenance_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    issue_description TEXT NOT NULL,
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    status VARCHAR(100),
    requested_by VARCHAR(100) NOT NULL,
    requester_staff_id VARCHAR(20) NULL,
    assigned_staff_id VARCHAR(20) NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_to VARCHAR(100) NULL,
    completed_at TIMESTAMP NULL,
    notes TEXT NULL
);

CREATE TABLE IF NOT EXISTS hp_inventory (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS hp_tasks_items (
    hp_items_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_needed INT NOT NULL DEFAULT 0,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES housekeeping_tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES hp_inventory(item_id) ON DELETE CASCADE
);

-- hp items inside inventory (nasa inventory table na to lahat)

-- Toilet Paper Rolls
-- Bath Towels
-- All-Purpose Cleaner
-- Hand Soap Dispensers
-- Toilet Paper Rolls
-- Bed Sheets (Queen)
-- Shampoo Bottles
-- Vacuum Cleaner Bags
-- Pillows
-- Floor Wax
-- Mats
-- Curtains