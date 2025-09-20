<?php
class Database {
    private string $host;
    private string $user;
    private string $pass;
    private string $dbName;
    public ?mysqli $conn = null;

    public function __construct() {
        $this->host = '127.0.0.1';
        $this->user = 'root';
        $this->pass = '';
        $this->dbName = 'hotel';  // Using main hotel database

        // Make mysqli throw exceptions on errors
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            // Connect to hotel database directly
            $this->conn = new mysqli($this->host, $this->user, $this->pass, 'hotel');
            // set charset
            $this->conn->set_charset('utf8mb4');
            // Maintain public property for backward compatibility
            // (some files still reference $db->conn)
            // $this->conn is already public now
        } catch (Throwable $e) {
            // rethrow to allow caller to handle the error
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /** Return the mysqli connection */
    public function getConnection(): mysqli {
        if ($this->conn === null) throw new RuntimeException('No database connection');
        return $this->conn;
    }

    /** Return the configured database name */
    public function getDbName(): string {
        return $this->dbName;
    }

    /** Perform a lightweight ping/health check */
    public function ping(): bool {
        try {
            return $this->getConnection()->ping();
        } catch (Throwable $e) {
            return false;
        }
    }

    /** Close the connection */
    public function close(): void {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
    }
}

?>

<?php
// Backwards compatible globals for older housekeeping scripts that expect
// a $db or $conn variable after including this file. We only create them
// when they don't already exist to avoid duplicate connections.
if (!isset($db)) {
    try {
        $db = new Database();
    } catch (Throwable $e) {
        // If connection cannot be established, some scripts expect a die-style
        // failure similar to the old connector. Provide a clear message.
        die('Database connection failed: ' . $e->getMessage());
    }
}

if (!isset($conn)) {
    try {
        $conn = $db->getConnection();
    } catch (Throwable $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}

// Ensure housekeeping-specific tables exist. If not, try to create them
// from the bundled schema file. All tables will be created in the 'hotel' database.
function ensure_housekeeping_schema(mysqli $conn): void {
    // Check for housekeeping tasks table in the hotel database
    $checkTable = 'housekeeping_tasks';
    try {
        $res = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($checkTable) . "'");
        if ($res && $res->num_rows > 0) {
            return; // schema already present in hotel database
        }
    } catch (Throwable $e) {
        // If SHOW TABLES fails, continue to attempt schema creation
    }

    $schemaPath = __DIR__ . '/combined_housekeeping_schema.sql';
    if (!file_exists($schemaPath)) {
        // nothing we can do here
        return;
    }

    $sql = file_get_contents($schemaPath);
    if ($sql === false) return;

    // Try to run the file with multi_query first
    try {
        if ($conn->multi_query($sql)) {
            // consume results
            do { /* advance through results */ } while ($conn->more_results() && $conn->next_result());
        } else {
            // multi_query failed; fall back to running statements one-by-one
            error_log('Housekeeping multi_query failed, falling back to per-statement install: ' . $conn->error);
            // Temporarily disable foreign key checks to allow creation order flexibility
            $conn->query('SET FOREIGN_KEY_CHECKS=0');
            // Split statements by semicolon (permissive) to handle different line endings
            $stmts = array_filter(array_map('trim', preg_split('/;\s*/', $sql)));
            foreach ($stmts as $stmt) {
                if ($stmt === '') continue;
                // Ensure statement ends with semicolon removed
                try {
                    if (!$conn->query($stmt)) {
                        error_log('Housekeeping schema statement failed: ' . $conn->error . ' -- STATEMENT: ' . substr($stmt,0,200));
                    }
                } catch (Throwable $e) {
                    error_log('Exception executing housekeeping schema statement: ' . $e->getMessage());
                }
            }
            $conn->query('SET FOREIGN_KEY_CHECKS=1');
        }
            // Verify target table exists; if not, try to extract its CREATE statement and run it
            try {
                $res2 = $conn->query("SHOW TABLES LIKE 'housekeeping_room_status'");
                if (!($res2 && $res2->num_rows > 0)) {
                    // try to extract CREATE TABLE for housekeeping_room_status from schema file
                    if (preg_match('/CREATE TABLE `housekeeping_room_status`\s*\((.*?)\)\s*ENGINE=/si', $sql, $m)) {
                        $body = trim($m[1]);
                        $create = "CREATE TABLE housekeeping_room_status (" . $body . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                        try {
                            $conn->query('SET FOREIGN_KEY_CHECKS=0');
                            if (!$conn->query($create)) {
                                error_log('Failed to create housekeeping_room_status from schema extract: ' . $conn->error);
                            }
                            $conn->query('SET FOREIGN_KEY_CHECKS=1');
                        } catch (Throwable $e) {
                            error_log('Exception creating housekeeping_room_status: ' . $e->getMessage());
                        }
                    } else {
                        // last resort: create a minimal compatible table
                        $fallback = "CREATE TABLE IF NOT EXISTS housekeeping_room_status (
                            room_id INT NOT NULL PRIMARY KEY,
                            status VARCHAR(64) NOT NULL,
                            remarks TEXT DEFAULT NULL,
                            last_cleaned DATE DEFAULT NULL,
                            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                        try {
                            if (!$conn->query($fallback)) {
                                error_log('Failed to create fallback housekeeping_room_status: ' . $conn->error);
                            }
                        } catch (Throwable $e) {
                            error_log('Exception creating fallback housekeeping_room_status: ' . $e->getMessage());
                        }
                    }
                }
            } catch (Throwable $e) {
                error_log('Exception verifying/creating housekeeping_room_status: ' . $e->getMessage());
            }
    } catch (Throwable $e) {
        error_log('Exception while installing housekeeping schema via multi_query: ' . $e->getMessage());
        // Last resort: attempt per-statement execution
        $conn->query('SET FOREIGN_KEY_CHECKS=0');
        $stmts = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
        foreach ($stmts as $stmt) {
            if ($stmt === '') continue;
            try {
                if (!$conn->query($stmt)) {
                    error_log('Housekeeping schema statement failed after exception: ' . $conn->error . ' -- STATEMENT: ' . substr($stmt,0,200));
                }
            } catch (Throwable $e2) {
                error_log('Exception executing housekeeping schema statement after exception: ' . $e2->getMessage());
            }
        }
        $conn->query('SET FOREIGN_KEY_CHECKS=1');
    }
}

// Run schema installer (non-destructive if already present)
ensure_housekeeping_schema($conn);
