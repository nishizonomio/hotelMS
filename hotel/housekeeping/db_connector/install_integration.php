<?php
require_once 'db_connect.php';

class IntegrationSetup {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function installTriggers() {
        try {
            // Read the SQL file
            $sql = file_get_contents(__DIR__ . '/integration_triggers.sql');
            
            // Split into individual statements
            $statements = explode('DELIMITER //', $sql);
            
            foreach ($statements as $statement) {
                if (trim($statement)) {
                    // Remove DELIMITER ;
                    $statement = str_replace('DELIMITER ;', '', $statement);
                    
                    // Execute each statement
                    $this->conn->multi_query($statement);
                    
                    // Clear results
                    while ($this->conn->more_results() && $this->conn->next_result()) {
                        $dummy = $this->conn->store_result();
                    }
                }
            }
            
            return ['success' => true, 'message' => 'Integration triggers installed successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error installing triggers: ' . $e->getMessage()];
        }
    }

    public function verifyIntegration() {
        $tests = [
            'triggers' => $this->verifyTriggers(),
            'tables' => $this->verifyTables(),
            'constraints' => $this->verifyConstraints()
        ];

        return $tests;
    }

    private function verifyTriggers() {
        try {
            $query = "SHOW TRIGGERS WHERE `Table` IN ('reservations', 'walk_ins', 'housekeeping_tasks', 'maintenance_requests')";
            $result = $this->conn->query($query);
            $triggers = [];
            
            while ($row = $result->fetch_assoc()) {
                $triggers[] = $row['Trigger'];
            }

            $required = [
                'after_reservation_checkout',
                'after_walkin_checkout',
                'after_maintenance_request',
                'after_housekeeping_complete',
                'after_maintenance_resolve'
            ];

            $missing = array_diff($required, $triggers);

            return [
                'success' => empty($missing),
                'message' => empty($missing) ? 'All required triggers are installed' : 'Missing triggers: ' . implode(', ', $missing)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error verifying triggers: ' . $e->getMessage()];
        }
    }

    private function verifyTables() {
        $required = [
            'rooms',
            'reservations',
            'walk_ins',
            'housekeeping_tasks',
            'maintenance_requests',
            'housekeeping_room_status'
        ];

        try {
            $tables = [];
            $result = $this->conn->query("SHOW TABLES");
            
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }

            $missing = array_diff($required, $tables);

            return [
                'success' => empty($missing),
                'message' => empty($missing) ? 'All required tables exist' : 'Missing tables: ' . implode(', ', $missing)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error verifying tables: ' . $e->getMessage()];
        }
    }

    private function verifyConstraints() {
        try {
            $constraints = [];
            $tables = ['housekeeping_tasks', 'maintenance_requests', 'housekeeping_room_status'];
            
            foreach ($tables as $table) {
                $query = "
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = '$table'
                    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                ";
                
                $result = $this->conn->query($query);
                while ($row = $result->fetch_assoc()) {
                    $constraints[] = $row['CONSTRAINT_NAME'];
                }
            }

            $required = ['fk_tasks_room', 'fk_maint_room', 'fk_status_room'];
            $missing = array_diff($required, $constraints);

            return [
                'success' => empty($missing),
                'message' => empty($missing) ? 'All required constraints exist' : 'Missing constraints: ' . implode(', ', $missing)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error verifying constraints: ' . $e->getMessage()];
        }
    }
}

// Display results
if (php_sapi_name() !== 'cli') {
    echo '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px;">';
    echo '<h1>Integration Setup Results</h1>';
}

$setup = new IntegrationSetup();

// Install triggers
$triggerResult = $setup->installTriggers();
if (php_sapi_name() !== 'cli') {
    echo '<div style="margin: 20px 0; padding: 15px; border-radius: 4px; background-color: ' . 
         ($triggerResult['success'] ? '#dff0d8' : '#f2dede') . ';">';
    echo '<h3>Trigger Installation</h3>';
    echo '<p>' . htmlspecialchars($triggerResult['message']) . '</p>';
    echo '</div>';
}

// Verify integration
$verificationResults = $setup->verifyIntegration();
if (php_sapi_name() !== 'cli') {
    foreach ($verificationResults as $test => $result) {
        echo '<div style="margin: 20px 0; padding: 15px; border-radius: 4px; background-color: ' . 
             ($result['success'] ? '#dff0d8' : '#f2dede') . ';">';
        echo '<h3>' . ucfirst($test) . ' Verification</h3>';
        echo '<p>' . htmlspecialchars($result['message']) . '</p>';
        echo '</div>';
    }
    echo '</div>';
}
?>
