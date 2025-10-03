<?php
include '../db.php';

$date = $_GET['date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

if (isset($_POST['punch']) && isset($_POST['local_time'])) {
    $staff_id = $_POST['staff_id'];
    $current_time = $_POST['local_time'];

    $res = $conn->prepare("SELECT * FROM attendance WHERE staff_id=? AND attendance_date=? LIMIT 1");
    $res->bind_param("ss", $staff_id, $date);
    $res->execute();
    $result = $res->get_result()->fetch_assoc();

    if ($result) {
        $time_in = $result['time_in'];
        $time_out = $result['time_out'];

        if (empty($time_in)) $time_in = $current_time;
        else $time_out = $current_time;

        $stmt = $conn->prepare("UPDATE attendance SET time_in=?, time_out=?, status='Present' WHERE staff_id=? AND attendance_date=?");
        $stmt->bind_param("ssss", $time_in, $time_out, $staff_id, $date);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO attendance (staff_id, attendance_date, status, time_in) VALUES (?, ?, 'Present', ?)");
        $stmt->bind_param("sss", $staff_id, $date, $current_time);
        $stmt->execute();
    }
    header("Location: attendance.php?date=$date&search=$search");
    exit;
}

$sql = "SELECT * FROM staff WHERE position_name NOT IN ('CEO','COO') AND (first_name LIKE ? OR last_name LIKE ?) ORDER BY first_name, last_name";
$stmt = $conn->prepare($sql);
$search_param = "%$search%";
$stmt->bind_param("ss", $search_param, $search_param);
$stmt->execute();
$employees = $stmt->get_result();

$attendance_records = [];
$res = $conn->query("SELECT * FROM attendance WHERE attendance_date='$date'");
while ($row = $res->fetch_assoc()) {
    $attendance_records[$row['staff_id']] = ['status'=>$row['status'], 'time_in'=>$row['time_in'], 'time_out'=>$row['time_out']];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Attendance</title>
<link rel="stylesheet" href="../css/attendance.css">

<script>
function updateTime() {
    const now = new Date();
    let hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2,'0');
    const seconds = String(now.getSeconds()).padStart(2,'0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    const formattedTime = `${hours}:${minutes}:${seconds} ${ampm}`;

    document.getElementById('localTime').textContent = formattedTime;

    const hiddenInputs = document.querySelectorAll('input[name="local_time"]');
    hiddenInputs.forEach(input => input.value = formattedTime);
}
setInterval(updateTime, 1000);
window.onload = updateTime;
</script>
</head>
<body>
<div class="container">
    <a href="hr_employee_management.php" class="back-button">← Back</a>
    <h1>Employee Attendance</h1>
    <div class="current-time">Current Local Time: <span id="localTime"></span></div>

    <form method="get" class="date-selector">
        <label for="date">Select Date: </label>
        <input type="date" name="date" id="date" value="<?= $date ?>">
        <label for="search">Search: </label>
        <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Go</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Status</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($emp = $employees->fetch_assoc()): 
            $record = $attendance_records[$emp['staff_id']] ?? ['status'=>'Absent','time_in'=>'','time_out'=>''];
        ?>
            <tr>
                <td><?= $emp['staff_id'] ?></td>
                <td><?= $emp['first_name'].' '.$emp['last_name'] ?></td>
                <td><?= $record['status'] ?></td>
                <td><?= $record['time_in'] ?></td>
                <td><?= $record['time_out'] ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="staff_id" value="<?= $emp['staff_id'] ?>">
                        <input type="hidden" name="local_time">
                        <button type="submit" name="punch">
                            <?php if(empty($record['time_in'])) echo "Punch In"; 
                                  elseif(empty($record['time_out'])) echo "Punch Out"; 
                                  else echo "Update"; ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
