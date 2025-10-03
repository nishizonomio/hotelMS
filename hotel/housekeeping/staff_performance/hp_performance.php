<?php
include '../../db_connect.php';

session_start();

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Handle date filter
$start_date = $_POST['start_date'] ?? date('Y-m-d');
$end_date = $_POST['end_date'] ?? date('Y-m-d');

$date_filter = " AND DATE(ht.assigned_at) BETWEEN ? AND ?";
$date_params = [$start_date, $end_date];

// Housekeeping positions
$housekeeping_positions = [
    'Linen Room Attendant',
    'Laundry Supervisor',
    'Public Area Attendant',
    'Assistant Housekeeper',
    'Room Attendant'
];
$placeholders = "'" . implode("','", $housekeeping_positions) . "'";

// Fetch housekeeping staff
$staff_sql = "SELECT staff_id, first_name, last_name, position_name FROM staff WHERE position_name IN ($placeholders) ORDER BY first_name, last_name";
$staff_result = $conn->query($staff_sql);

// Function to calculate performance metrics
function getStaffPerformance($conn, $staff_id, $date_filter, $date_params) {
    // Total tasks assigned
    $assigned_sql = "SELECT COUNT(*) as total FROM housekeeping_tasks ht WHERE staff_id = ?" . $date_filter;
    $assigned_stmt = $conn->prepare($assigned_sql);
    $assigned_stmt->bind_param("s" . str_repeat("s", count($date_params)), $staff_id, ...$date_params);
    $assigned_stmt->execute();
    $assigned_result = $assigned_stmt->get_result();
    $assigned = $assigned_result->fetch_assoc()['total'];
    $assigned_stmt->close();

    // Total tasks completed
    $completed_sql = "SELECT COUNT(*) as total FROM housekeeping_tasks ht WHERE staff_id = ? AND task_status = 'completed'" . $date_filter;
    $completed_stmt = $conn->prepare($completed_sql);
    $completed_stmt->bind_param("s" . str_repeat("s", count($date_params)), $staff_id, ...$date_params);
    $completed_stmt->execute();
    $completed_result = $completed_stmt->get_result();
    $completed = $completed_result->fetch_assoc()['total'];
    $completed_stmt->close();

    // Total tasks in progress
    $in_progress_sql = "SELECT COUNT(*) as total FROM housekeeping_tasks ht WHERE staff_id = ? AND task_status = 'in progress'" . $date_filter;
    $in_progress_stmt = $conn->prepare($in_progress_sql);
    $in_progress_stmt->bind_param("s" . str_repeat("s", count($date_params)), $staff_id, ...$date_params);
    $in_progress_stmt->execute();
    $in_progress_result = $in_progress_stmt->get_result();
    $in_progress = $in_progress_result->fetch_assoc()['total'];
    $in_progress_stmt->close();

    // Total task hours (sum of completion times in hours for tasks completed on the selected dates)
    $time_date_filter = str_replace('ht.assigned_at', 'ht.end_time', $date_filter);
    $total_hours_sql = "SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time) / 60.0) as total_hours FROM housekeeping_tasks ht WHERE staff_id = ? AND task_status = 'completed' AND start_time IS NOT NULL AND end_time IS NOT NULL" . $time_date_filter;
    $total_hours_stmt = $conn->prepare($total_hours_sql);
    $total_hours_stmt->bind_param("s" . str_repeat("s", count($date_params)), $staff_id, ...$date_params);
    $total_hours_stmt->execute();
    $total_hours_result = $total_hours_stmt->get_result();
    $total_hours = $total_hours_result->fetch_assoc()['total_hours'];
    $total_hours_decimal = $total_hours ? $total_hours : 0;
    $hours = floor($total_hours_decimal);
    $minutes = floor(($total_hours_decimal - $hours) * 60);
    $seconds = round((($total_hours_decimal - $hours) * 60 - $minutes) * 60);
    $total_hours_formatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    $total_hours_stmt->close();

    // Maintenance requests submitted
    $maintenance_filter = '';
    if (!empty($date_params)) {
        $maintenance_filter = " AND DATE(requested_at) BETWEEN ? AND ?";
    }
    $maintenance_sql = "SELECT COUNT(*) as total FROM maintenance_requests WHERE requester_staff_id = ?" . $maintenance_filter;
    $maintenance_stmt = $conn->prepare($maintenance_sql);
    if (!empty($date_params)) {
        $maintenance_stmt->bind_param("s" . str_repeat("s", count($date_params)), $staff_id, ...$date_params);
    } else {
        $maintenance_stmt->bind_param("s", $staff_id);
    }
    $maintenance_stmt->execute();
    $maintenance_result = $maintenance_stmt->get_result();
    $maintenance = $maintenance_result->fetch_assoc()['total'];
    $maintenance_stmt->close();

    // Total items used
    $items_sql = "SELECT SUM(hti.quantity_needed) as total FROM hp_tasks_items hti JOIN housekeeping_tasks ht ON hti.task_id = ht.task_id WHERE ht.staff_id = ?" . $date_filter;
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("s" . str_repeat("s", count($date_params)), $staff_id, ...$date_params);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $items_used = $items_result->fetch_assoc()['total'] ?? 0;
    $items_stmt->close();

    return [
        'assigned' => $assigned,
        'completed' => $completed,
        'in_progress' => $in_progress,
        'total_hours_formatted' => $total_hours_formatted,
        'maintenance' => $maintenance,
        'items_used' => $items_used
    ];
}

// Prepare data for chart
$staff_data = [];
$staff_names = [];
$completed_counts = [];

if ($staff_result && $staff_result->num_rows > 0) {
    while ($staff = $staff_result->fetch_assoc()) {
        $performance = getStaffPerformance($conn, $staff['staff_id'], $date_filter, $date_params);
        $staff['performance'] = $performance;
        $staff_data[] = $staff;
        $staff_names[] = $staff['first_name'] . ' ' . $staff['last_name'];
        $completed_counts[] = $performance['completed'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Performance Tracking | Housekeeping</title>
    <link rel="stylesheet" href="/hotel/homepage/index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .performance-table {
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
        .performance-table th, .performance-table td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .performance-table th {
            background: rgba(255, 255, 255, 0.1);
            font-weight: 600;
            color: #ffd700;
        }
        .performance-table tr:hover td {
            background: rgba(255, 255, 255, 0.05);
            transition: background 0.3s ease;
        }
        .chart-container {
            margin: 30px 0;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
        }
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
        .high-performance { color: #28a745; font-weight: 600; }
        .medium-performance { color: #ffc107; font-weight: 600; }
        .low-performance { color: #dc3545; font-weight: 600; }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="container">
            <header style="position: relative;">
                <h1><i class="fas fa-chart-line"></i> Staff Performance Tracking</h1>
                <p>Monitor and analyze housekeeping staff performance metrics.</p>
                <a href="../housekeeping.php" class="back-btn" style="position: absolute; top: 0; right: 0;"><i class="fas fa-arrow-left"></i> Back</a>
            </header>

            <!-- Date Filter Form -->
            <form method="POST" style="margin: 20px 0; text-align: center;">
                <label for="start_date" style="color: #fff; margin-right: 10px;">Start Date:</label>
                <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>" style="padding: 8px; border-radius: 4px; border: 1px solid #444; background: #2c2c2c; color: #fff;">
                <label for="end_date" style="color: #fff; margin: 0 10px;">End Date:</label>
                <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>" style="padding: 8px; border-radius: 4px; border: 1px solid #444; background: #2c2c2c; color: #fff;">
                <button type="submit" style="padding: 8px 16px; background: #ffd700; color: #000; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">Filter</button>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" style="margin-left: 10px; color: #ffd700; text-decoration: none;">Clear Filter</a>
            </form>


            <!-- Performance Table -->
            <?php if (!empty($staff_data)): ?>
            <table class="performance-table">
                <thead>
                    <tr>
                        <th>Staff Name</th>
                        <th>Position</th>
                        <th>Tasks Assigned</th>
                        <th>Tasks In Progress</th>
                        <th>Tasks Completed</th>
                        <th>Task Time (hrs)</th>
                        <th>Maintenance Requests</th>
                        <th>Items Used</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff_data as $staff): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($staff['position_name']); ?></td>
                        <td><?php echo $staff['performance']['assigned']; ?></td>
                        <td><?php echo $staff['performance']['in_progress']; ?></td>
                        <td><?php echo $staff['performance']['completed']; ?></td>
                        <td><?php echo $staff['performance']['total_hours_formatted']; ?></td>
                        <td><?php echo $staff['performance']['maintenance']; ?></td>
                        <td><?php echo $staff['performance']['items_used']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; margin-top: 50px; color: rgba(255,255,255,0.7);">No housekeeping staff found.</p>
            <?php endif; ?>

            <a href="../housekeeping.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Housekeeping</a>
        </div>
    </div>

</body>
</html>

<?php $conn->close(); ?>