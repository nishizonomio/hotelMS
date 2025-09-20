<?php
namespace Housekeeping\Supplies\DB;

class InventoryLogger {
    private $logFile;
    
    public function __construct(string $logFile = null) {
        $this->logFile = $logFile ?? __DIR__ . '/../logs/inventory.log';
        
        // Create logs directory if it doesn't exist
        $logsDir = dirname($this->logFile);
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
    }
    
    /**
     * Log an inventory operation
     */
    public function logOperation(string $operation, array $details, ?string $error = null): void {
        $timestamp = date('Y-m-d H:i:s');
        $status = $error ? 'ERROR' : 'SUCCESS';
        $message = [
            'timestamp' => $timestamp,
            'operation' => $operation,
            'status' => $status,
            'details' => $details
        ];
        
        if ($error) {
            $message['error'] = $error;
        }
        
        $logEntry = json_encode($message) . "\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Get recent log entries
     */
    public function getRecentLogs(int $limit = 50): array {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $logs = array_filter(array_map('trim', file($this->logFile)));
        $logs = array_map('json_decode', $logs, array_fill(0, count($logs), true));
        return array_slice(array_reverse($logs), 0, $limit);
    }
}