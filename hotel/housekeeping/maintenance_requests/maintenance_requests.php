<?php
include '../../db_connect.php'; // adjust path if needed

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Fetch all maintenance requests with room and staff info
$maintenance_sql = "
    SELECT mr.*, r.room_number, r.room_type, rs.first_name as requester_first, rs.last_name as requester_last, asg.first_name as assigned_first, asg.last_name as assigned_last
    FROM maintenance_requests mr
    JOIN rooms r ON mr.room_id = r.room_id
    LEFT JOIN staff rs ON mr.requester_staff_id = rs.staff_id
    LEFT JOIN staff asg ON mr.assigned_staff_id = asg.staff_id
    ORDER BY mr.requested_at DESC
";
$maintenance_result = $conn->query($maintenance_sql);

// Helper function to format datetime
function formatTo12Hour($datetime) {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    return date('M j, Y g:i A', strtotime($datetime));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance Requests | Housekeeping</title>
    <link rel="stylesheet" href="/hotel/homepage/index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .maintenance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .maintenance-table th, .maintenance-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .maintenance-table th {
            background: rgba(255, 255, 255, 0.1);
            font-weight: 600;
            color: #ffd700;
        }
        .maintenance-table tr:hover td {
            background: rgba(255, 255, 255, 0.05);
            transition: background 0.3s ease;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-in-progress { background: #17a2b8; color: #fff; }
        .status-completed { background: #28a745; color: #fff; }
        .priority-high { color: #dc3545; font-weight: 600; }
        .priority-medium { color: #ffc107; font-weight: 600; }
        .priority-low { color: #28a745; font-weight: 600; }
        .priority-critical { color: #dc3545; font-weight: 600; }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 18px;
            border-radius: 8px;
            background: rgba(255,255,255,0.15);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .back-btn:hover {
            background: rgba(255,255,255,0.25);
        }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="container">
            <header style="position: relative;">
                <h1><i class="fas fa-tools"></i> Maintenance Requests</h1>
                <p>View all maintenance requests submitted for rooms.</p>
                <a href="../housekeeping.php" class="back-btn" style="position: absolute; top: 0; right: 0;"><i class="fas fa-arrow-left"></i> Back</a>
            </header>

            <?php if ($maintenance_result && $maintenance_result->num_rows > 0): ?>
            <table class="maintenance-table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Room</th>
                        <th>Issue Description</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Requested By Staff ID</th>
                        <th>Requested At</th>
                        <th>Assigned Staff ID</th>
                        <th>Assigned To</th>
                        <th>Completed At</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($request = $maintenance_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int)$request['request_id']; ?></td>
                        <td>Room <?php echo htmlspecialchars($request['room_number']); ?> (<?php echo htmlspecialchars($request['room_type']); ?>)</td>
                        <td><?php echo htmlspecialchars($request['issue_description']); ?></td>
                        <td class="priority-<?php echo strtolower($request['priority']); ?>"><?php echo htmlspecialchars($request['priority']); ?></td>
                        <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $request['status'])); ?>"><?php echo htmlspecialchars(str_replace(' ', '', $request['status'])); ?></span></td>
                        <td><?php echo $request['requester_first'] ? htmlspecialchars($request['requester_first'] . ' ' . $request['requester_last']) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($request['requester_staff_id']); ?></td>
                        <td><?php echo formatTo12Hour($request['requested_at']); ?></td>
                        <td><?php echo $request['assigned_staff_id'] ? htmlspecialchars($request['assigned_staff_id']) : '-'; ?></td>
                        <td><?php echo $request['assigned_first'] ? htmlspecialchars($request['assigned_first'] . ' ' . $request['assigned_last']) : '-'; ?></td>
                        <td><?php echo formatTo12Hour($request['completed_at']); ?></td>
                        <td><?php echo $request['notes'] ? htmlspecialchars($request['notes']) : '-'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; margin-top: 50px; color: rgba(255,255,255,0.7);">No maintenance requests found.</p>
            <?php endif; ?>

            <a href="../housekeeping.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Housekeeping</a>
        </div>
    </div>
</body>
</html>