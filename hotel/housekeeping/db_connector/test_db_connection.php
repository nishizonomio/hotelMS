<?php
require_once __DIR__ . '/db_connect.php';

echo "Running DB connection test...\n";

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Ping
    $ping = $db->ping() ? 'OK' : 'FAILED';
    echo "Ping: $ping\n";

    // Run a simple query - check current database and a sample table if exists
    $res = $conn->query("SELECT DATABASE() AS dbname");
    $row = $res->fetch_assoc();
    echo "Connected to DB: " . ($row['dbname'] ?? 'unknown') . "\n";

    // Optional existence check for housekeeping_tasks or maintenance_requests
    $tablesToCheck = ['housekeeping_tasks', 'maintenance_requests', 'rooms'];
    foreach ($tablesToCheck as $t) {
        $exists = false;
        try {
            $r = $conn->query("SELECT 1 FROM `{$t}` LIMIT 1");
            $exists = true;
        } catch (Throwable $e) {
            $exists = false;
        }
        echo "Table {$t}: " . ($exists ? 'FOUND' : 'MISSING') . "\n";
    }

    $db->close();
    echo "DB test completed successfully.\n";
} catch (Throwable $e) {
    echo "DB test failed: " . $e->getMessage() . "\n";
    exit(1);
}

?>