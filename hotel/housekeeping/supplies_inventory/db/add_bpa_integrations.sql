-- Update supply_usage to connect with tasks and staff performance
ALTER TABLE supply_usage
ADD COLUMN task_id INT NULL,
ADD COLUMN cost_at_time DECIMAL(10,2) NULL,
ADD COLUMN performance_score INT NULL,
ADD FOREIGN KEY (task_id) REFERENCES housekeeping_tasks(task_id),
ADD INDEX idx_task_usage (task_id),
ADD INDEX idx_staff_usage (used_by);

-- Add supply efficiency tracking
CREATE TABLE supply_task_standards (
    standard_id INT PRIMARY KEY AUTO_INCREMENT,
    task_type VARCHAR(128) NOT NULL,
    supply_id INT NOT NULL,
    expected_quantity INT NOT NULL,
    unit_type VARCHAR(50) NULL,
    notes TEXT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supply_id) REFERENCES supplies(supply_id)
);

-- Add supply request workflow
ALTER TABLE supply_requests
ADD COLUMN task_id INT NULL,
ADD COLUMN priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
ADD COLUMN approved_by INT NULL,
ADD COLUMN approved_at TIMESTAMP NULL,
ADD COLUMN workflow_state VARCHAR(50) DEFAULT 'pending',
ADD FOREIGN KEY (task_id) REFERENCES housekeeping_tasks(task_id),
ADD FOREIGN KEY (approved_by) REFERENCES staff(staff_id);