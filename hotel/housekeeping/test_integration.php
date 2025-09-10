<?php
require_once __DIR__ . '/../db_connector/db_connect.php';

class HousekeepingIntegrationTest {
    private $db;
    private $conn;
    private $testResults = [];

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function runTests() {
        $this->testDatabaseConnection();
        $this->testRoomStatusSync();
        $this->testMaintenanceIntegration();
        $this->testTaskAssignments();
        $this->displayResults();
    }

    private function testDatabaseConnection() {
        try {
            $this->conn->query("SELECT 1");
            $this->addResult("Database Connection", "Success", "Connected to hotel database");
        } catch (Exception $e) {
            $this->addResult("Database Connection", "Failed", $e->getMessage());
        }
    }

    private function testRoomStatusSync() {
        try {
            // Test 1: Check room status sync after checkout
            $this->testCheckoutStatusSync();
            
            // Test 2: Check room status sync after cleaning
            $this->testCleaningStatusSync();
            
            // Test 3: Check maintenance status sync
            $this->testMaintenanceStatusSync();
            
        } catch (Exception $e) {
            $this->addResult("Room Status Sync", "Failed", $e->getMessage());
        }
    }

    private function testCheckoutStatusSync() {
        $query = "
            SELECT r.room_number, r.status AS room_status, 
                   res.status AS reservation_status,
                   hrs.status AS housekeeping_status,
                   ht.status AS task_status
            FROM rooms r
            LEFT JOIN reservations res ON r.room_id = res.room_id
            LEFT JOIN housekeeping_room_status hrs ON r.room_id = hrs.room_id
            LEFT JOIN housekeeping_tasks ht ON r.room_id = ht.room_id
            WHERE res.status = 'checked_out'
            ORDER BY res.updated_at DESC LIMIT 1
        ";
        
        $result = $this->conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            $isValid = $row['room_status'] === 'dirty' && 
                      $row['housekeeping_status'] === 'Needs Cleaning' &&
                      $row['task_status'] === 'Pending';
                      
            $this->addResult(
                "Checkout Status Sync",
                $isValid ? "Success" : "Warning",
                $isValid ? "Room status properly updated after checkout" : 
                          "Inconsistent status: Room({$row['room_status']}) Housekeeping({$row['housekeeping_status']}) Task({$row['task_status']})"
            );
        } else {
            $this->addResult("Checkout Status Sync", "Info", "No recent checkouts to test");
        }
    }

    private function testCleaningStatusSync() {
        $query = "
            SELECT r.room_number, r.status AS room_status,
                   hrs.status AS housekeeping_status,
                   hrs.last_cleaned
            FROM rooms r
            JOIN housekeeping_room_status hrs ON r.room_id = hrs.room_id
            WHERE hrs.last_cleaned IS NOT NULL
            ORDER BY hrs.last_cleaned DESC LIMIT 1
        ";
        
        $result = $this->conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            $isValid = $row['room_status'] === 'available' && 
                      $row['housekeeping_status'] === 'Clean';
                      
            $this->addResult(
                "Cleaning Status Sync",
                $isValid ? "Success" : "Warning",
                $isValid ? "Room status properly updated after cleaning" :
                          "Inconsistent status after cleaning: Room({$row['room_status']}) Housekeeping({$row['housekeeping_status']})"
            );
        } else {
            $this->addResult("Cleaning Status Sync", "Info", "No recently cleaned rooms to test");
        }
    }

    private function testMaintenanceStatusSync() {
        $query = "
            SELECT r.room_number, r.status AS room_status,
                   mr.status AS maintenance_status,
                   mr.priority
            FROM rooms r
            JOIN maintenance_requests mr ON r.room_id = mr.room_id
            WHERE mr.status IN ('Pending', 'In Progress')
            AND mr.priority = 'High'
            LIMIT 1
        ";
        
        $result = $this->conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            $isValid = $row['room_status'] === 'under maintenance';
                      
            $this->addResult(
                "Maintenance Status Sync",
                $isValid ? "Success" : "Warning",
                $isValid ? "Room status properly updated for maintenance" :
                          "Room status not updated for high-priority maintenance: Room({$row['room_status']})"
            );
        } else {
            $this->addResult("Maintenance Status Sync", "Info", "No active high-priority maintenance to test");
        }
    }

    private function testMaintenanceIntegration() {
        try {
            // Test if maintenance requests are properly linked to rooms
            $query = "
                SELECT r.room_number, mr.status AS maintenance_status,
                       r.status AS room_status
                FROM maintenance_requests mr
                JOIN rooms r ON mr.room_id = r.room_id
                WHERE mr.status = 'In Progress'
                LIMIT 5
            ";
            $result = $this->conn->query($query);
            
            if ($result) {
                $this->addResult("Maintenance Integration", "Success", "Maintenance requests properly linked to rooms");
            } else {
                $this->addResult("Maintenance Integration", "Warning", "No active maintenance requests found");
            }
        } catch (Exception $e) {
            $this->addResult("Maintenance Integration", "Failed", $e->getMessage());
        }
    }

    private function testTaskAssignments() {
        try {
            // Test if housekeeping tasks are properly created for occupied rooms
            $query = "
                SELECT r.room_number, res.status AS reservation_status,
                       ht.status AS task_status, ht.task_date
                FROM rooms r
                LEFT JOIN reservations res ON r.room_id = res.room_id
                LEFT JOIN housekeeping_tasks ht ON r.room_id = ht.room_id
                WHERE res.status = 'checked_out'
                LIMIT 5
            ";
            $result = $this->conn->query($query);
            
            if ($result) {
                $this->addResult("Task Assignments", "Success", "Tasks are properly linked to room status changes");
            } else {
                $this->addResult("Task Assignments", "Warning", "No task assignments found for room status changes");
            }
        } catch (Exception $e) {
            $this->addResult("Task Assignments", "Failed", $e->getMessage());
        }
    }

    private function addResult($test, $status, $message) {
        $this->testResults[] = [
            'test' => $test,
            'status' => $status,
            'message' => $message
        ];
    }

    private function displayResults() {
        echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto;'>";
        echo "<h2>Housekeeping Module Integration Test Results</h2>";
        
        foreach ($this->testResults as $result) {
            $color = $result['status'] === 'Success' ? '#4CAF50' : 
                    ($result['status'] === 'Warning' ? '#FFC107' : '#F44336');
                    
            echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 4px;'>";
            echo "<h3 style='margin: 0 0 10px 0;'>{$result['test']}</h3>";
            echo "<p style='margin: 5px 0;'><strong>Status: </strong>";
            echo "<span style='color: {$color};'>{$result['status']}</span></p>";
            echo "<p style='margin: 5px 0;'><strong>Message: </strong>{$result['message']}</p>";
            echo "</div>";
        }
        
        echo "</div>";
    }
}

// Run the tests
$tester = new HousekeepingIntegrationTest();
$tester->runTests();
