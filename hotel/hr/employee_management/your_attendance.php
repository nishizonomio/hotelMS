<?php
session_start();
include '../db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: employee_login.php");
    exit;
}

$staff_id = $_SESSION['staff_id'];

$emp_stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
$emp_stmt->bind_param("s", $staff_id);
$emp_stmt->execute();
$emp_data = $emp_stmt->get_result()->fetch_assoc();
if (!$emp_data) die("Employee not found");

$today = date('Y-m-d');
$first_day_of_month = date('Y-m-01');
$last_day_of_month = date('Y-m-t');

$att_stmt = $conn->prepare("SELECT * FROM attendance WHERE staff_id = ? AND attendance_date = ?");
$att_stmt->bind_param("ss", $staff_id, $today);
$att_stmt->execute();
$today_attendance = $att_stmt->get_result()->fetch_assoc();

$att_history_stmt = $conn->prepare("SELECT * FROM attendance WHERE staff_id = ? ORDER BY attendance_date DESC");
$att_history_stmt->bind_param("s", $staff_id);
$att_history_stmt->execute();
$attendance_history = $att_history_stmt->get_result();

$today_ot_stmt = $conn->prepare("SELECT * FROM overtime WHERE staff_id = ? AND overtime_date = ?");
$today_ot_stmt->bind_param("ss", $staff_id, $today);
$today_ot_stmt->execute();
$today_overtime = $today_ot_stmt->get_result();

$month_ot_stmt = $conn->prepare("SELECT * FROM overtime WHERE staff_id = ? AND overtime_date BETWEEN ? AND ?");
$month_ot_stmt->bind_param("sss", $staff_id, $first_day_of_month, $last_day_of_month);
$month_ot_stmt->execute();
$month_overtime = $month_ot_stmt->get_result();

$total_seconds_today = 0;
if ($today_attendance && $today_attendance['time_in'] && $today_attendance['time_out']) {
    $time_in = strtotime($today_attendance['time_in']);
    $time_out = strtotime($today_attendance['time_out']);
    if ($time_out > $time_in) $total_seconds_today += $time_out - $time_in;
}
while ($ot_row = $today_overtime->fetch_assoc()) {
    if ($ot_row['start_time'] && $ot_row['end_time']) {
        $start = strtotime($ot_row['start_time']);
        $end = strtotime($ot_row['end_time']);
        if ($end > $start) $total_seconds_today += $end - $start;
    }
}

$hours_today = floor($total_seconds_today / 3600);
$minutes_today = floor(($total_seconds_today % 3600) / 60);
$seconds_today = $total_seconds_today % 60;
$total_worked_today = sprintf("%02d:%02d:%02d", $hours_today, $minutes_today, $seconds_today);

$total_seconds_month = 0;
$att_month_stmt = $conn->prepare("SELECT * FROM attendance WHERE staff_id = ? AND attendance_date BETWEEN ? AND ?");
$att_month_stmt->bind_param("sss", $staff_id, $first_day_of_month, $last_day_of_month);
$att_month_stmt->execute();
$att_month = $att_month_stmt->get_result();

while ($row = $att_month->fetch_assoc()) {
    if ($row['time_in'] && $row['time_out']) {
        $time_in = strtotime($row['time_in']);
        $time_out = strtotime($row['time_out']);
        if ($time_out > $time_in) $total_seconds_month += $time_out - $time_in;
    }
}

while ($row = $month_overtime->fetch_assoc()) {
    if ($row['start_time'] && $row['end_time']) {
        $start = strtotime($row['start_time']);
        $end = strtotime($row['end_time']);
        if ($end > $start) $total_seconds_month += $end - $start;
    }
}

$hours_month = floor($total_seconds_month / 3600);
$minutes_month = floor(($total_seconds_month % 3600) / 60);
$seconds_month = $total_seconds_month % 60;
$total_worked_month = sprintf("%02d:%02d:%02d", $hours_month, $minutes_month, $seconds_month);

$overtime_history_stmt = $conn->prepare("SELECT * FROM overtime WHERE staff_id = ? ORDER BY overtime_date DESC");
$overtime_history_stmt->bind_param("s", $staff_id);
$overtime_history_stmt->execute();
$overtime_history = $overtime_history_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Attendance & Overtime</title>
<link rel="stylesheet" href="../css/your_attendance.css">
</head>
<body>
<div class="container">
    <a href="homepage.php" class="back-button">&larr; Back</a>
    <h1>My Attendance & Overtime</h1>

    <div class="card">
        <h2>Today's Summary (<?= $today ?>)</h2>
        <p><strong>Time In:</strong> <?= $today_attendance['time_in'] ?? '-' ?></p>
        <p><strong>Time Out:</strong> <?= $today_attendance['time_out'] ?? '-' ?></p>
        <p><strong>Total Worked Today:</strong> <?= $total_worked_today ?></p>
        <p><strong>Total Worked This Month:</strong> <?= $total_worked_month ?></p>
    </div>

    <div class="card">
        <h2>Attendance History</h2>
        <?php if($attendance_history && $attendance_history->num_rows > 0): ?>
        <table>
            <tr>
                <th>Date</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
            </tr>
            <?php while ($row = $attendance_history->fetch_assoc()): ?>
            <tr class="<?= ($row['attendance_date'] == $today) ? 'today-row' : '' ?>">
                <td><?= htmlspecialchars($row['attendance_date']) ?></td>
                <td><?= htmlspecialchars($row['time_in']) ?></td>
                <td><?= $row['time_out'] ?: '-' ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
        <p style="text-align:center;">No attendance records found.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Overtime History</h2>
        <?php if($overtime_history && $overtime_history->num_rows > 0): ?>
        <table>
            <tr>
                <th>Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Total Hours</th>
            </tr>
            <?php while ($row = $overtime_history->fetch_assoc()): ?>
            <tr class="<?= ($row['overtime_date'] == $today) ? 'today-row' : '' ?>">
                <td><?= htmlspecialchars($row['overtime_date']) ?></td>
                <td><?= htmlspecialchars($row['start_time']) ?></td>
                <td><?= htmlspecialchars($row['end_time']) ?></td>
                <td><?= htmlspecialchars($row['total_hours']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
        <p style="text-align:center;">No overtime records found.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
