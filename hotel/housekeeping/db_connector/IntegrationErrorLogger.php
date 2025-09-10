<?php

class IntegrationErrorLogger {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $db->getConnection();
    }

    /**
     * Log an error that occurred during integration
     */
    public function logError($module, $operation, $errorMessage, $errorCode = null, $sourceTable = null, $affectedIds = null) {
        try {
            $sql = "INSERT INTO integration_error_logs (module, operation, error_message, error_code, source_table, affected_ids) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssss", 
                $module, 
                $operation, 
                $errorMessage, 
                $errorCode, 
                $sourceTable, 
                $affectedIds
            );
            
            return $stmt->execute();
        } catch (Exception $e) {
            // If we can't log to database, log to file as fallback
            $this->logToFile($module, $operation, $errorMessage, $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent errors for monitoring
     */
    public function getRecentErrors($limit = 50, $module = null) {
        $sql = "SELECT * FROM integration_error_logs ";
        if ($module) {
            $sql .= "WHERE module = ? ";
        }
        $sql .= "ORDER BY created_at DESC LIMIT ?";

        try {
            $stmt = $this->conn->prepare($sql);
            if ($module) {
                $stmt->bind_param("si", $module, $limit);
            } else {
                $stmt->bind_param("i", $limit);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get error statistics
     */
    public function getErrorStats() {
        $sql = "SELECT 
                    module,
                    COUNT(*) as error_count,
                    MAX(created_at) as last_error,
                    COUNT(DISTINCT operation) as affected_operations
                FROM integration_error_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY module";

        try {
            $result = $this->conn->query($sql);
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Fallback logging to file if database logging fails
     */
    private function logToFile($module, $operation, $errorMessage, $additionalError) {
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logFile = $logDir . '/integration_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = sprintf(
            "[%s] Module: %s | Operation: %s | Error: %s | Additional Error: %s\n",
            $timestamp,
            $module,
            $operation,
            $errorMessage,
            $additionalError
        );

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Clean old error logs
     */
    public function cleanOldLogs($daysToKeep = 30) {
        try {
            $sql = "DELETE FROM integration_error_logs 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $daysToKeep);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
}
