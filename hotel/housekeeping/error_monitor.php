<?php
require_once 'db_connect.php';
require_once 'IntegrationErrorLogger.php';

$db = new Database();
$logger = new IntegrationErrorLogger($db);

// Handle error log cleanup if requested
if (isset($_POST['cleanup']) && isset($_POST['days'])) {
    $logger->cleanOldLogs((int)$_POST['days']);
}

$stats = $logger->getErrorStats();
$recentErrors = $logger->getRecentErrors(20);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integration Error Monitor</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .error-list {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
        }

        .error-entry {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 6px;
            background: #fff5f5;
            border-left: 4px solid #dc3545;
        }

        .cleanup-form {
            margin-top: 20px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: #007bff;
            color: white;
        }

        .btn:hover {
            background: #0056b3;
        }

        .error-count {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
        }

        .timestamp {
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Integration Error Monitor</h1>
            <p>Monitoring integration errors between Housekeeping and Front Desk modules</p>
        </div>

        <div class="stats-grid">
            <?php foreach ($stats as $stat): ?>
            <div class="stat-card">
                <h3><?= htmlspecialchars($stat['module']) ?></h3>
                <div class="error-count"><?= $stat['error_count'] ?></div>
                <p>Errors in last 24 hours</p>
                <p>Last error: <?= $stat['last_error'] ?></p>
                <p>Affected operations: <?= $stat['affected_operations'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="error-list">
            <h2>Recent Errors</h2>
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Module</th>
                        <th>Operation</th>
                        <th>Error</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentErrors as $error): ?>
                    <tr>
                        <td class="timestamp"><?= $error['created_at'] ?></td>
                        <td><?= htmlspecialchars($error['module']) ?></td>
                        <td><?= htmlspecialchars($error['operation']) ?></td>
                        <td><?= htmlspecialchars($error['error_message']) ?></td>
                        <td><?= htmlspecialchars($error['source_table']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="cleanup-form">
            <h3>Cleanup Old Logs</h3>
            <form method="POST">
                <label>
                    Keep logs for last 
                    <input type="number" name="days" value="30" min="1" max="365" required>
                    days
                </label>
                <button type="submit" name="cleanup" class="btn">Clean Old Logs</button>
            </form>
        </div>
    </div>
</body>
</html>
