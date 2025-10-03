<?php
include '../db.php';

$date = $_GET['date'] ?? date('Y-m-d');

if (isset($_POST['add_overtime'])) {
    $staff_id = $_POST['staff_id'];
    $hours = (int)$_POST['hours'];

    $stmt = $conn->prepare("INSERT INTO overtime (staff_id, overtime_date, hours) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE hours=?");
    $stmt->bind_param("ssii", $staff_id, $date, $hours, $hours);
    $stmt->execute();
    header("Location: overtime.php?date=$date");
    exit;
}

$employees = $conn->query("SELECT * FROM staff WHERE position_name NOT IN ('CEO','COO') ORDER BY first_name, last_name");

$overtime_records = [];
$res = $conn->query("SELECT * FROM overtime WHERE overtime_date='$date'");
while ($row = $res->fetch_assoc()) {
    $overtime_records[$row['staff_id']] = $row['hours'];
}

$past_overtime = $conn->query("
    SELECT o.overtime_date, s.staff_id, s.first_name, s.last_name, o.hours
    FROM overtime o 
    JOIN staff s ON o.staff_id = s.staff_id
    WHERE s.position_name NOT IN ('CEO','COO')
    ORDER BY o.overtime_date DESC, s.first_name, s.last_name
");

$attendance = $conn->query("
    SELECT a.attendance_date, s.staff_id, s.first_name, s.last_name,
           a.start_time, a.end_time, TIMESTAMPDIFF(HOUR, a.start_time, a.end_time) AS total_hours
    FROM attendance a
    JOIN staff s ON a.staff_id = s.staff_id
    WHERE s.position_name NOT IN ('CEO','COO') AND a.attendance_date='$date'
    ORDER BY s.first_name, s.last_name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Overtime and Attendance</title>
<link rel="stylesheet" href="../css/overtime.css">
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">
</head>
<body>
<div class="container">
    <a href="hr_employee_management.php" class="back-button"><i class="fas fa-arrow-left"></i> Back</a>
    <h1>Employee Overtime and Attendance</h1>

    <form method="get" class="date-selector">
        <label for="date">Select Date: </label>
        <input type="date" name="date" id="date" value="<?= $date ?>">
        <button type="submit">Go</button>
    </form>

    <h2>Log Overtime for <?= $date ?></h2>
    <table>
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Hours</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($emp = $employees->fetch_assoc()): 
            $hours = $overtime_records[$emp['staff_id']] ?? 0;
        ?>
            <tr>
                <td><?= $emp['staff_id'] ?></td>
                <td><?= $emp['first_name'].' '.$emp['last_name'] ?></td>
                <td><?= $hours ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="staff_id" value="<?= $emp['staff_id'] ?>">
                        <input type="number" name="hours" value="<?= $hours ?>" min="0" step="1" style="width:60px;">
                        <button type="submit" name="add_overtime">Save</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h2>Past Overtime Records</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Hours</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $past_overtime->fetch_assoc()): ?>
            <tr>
                <td><?= $row['overtime_date'] ?></td>
                <td><?= $row['staff_id'] ?></td>
                <td><?= $row['first_name'].' '.$row['last_name'] ?></td>
                <td><?= $row['hours'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>


</div>
</body>
</html>
