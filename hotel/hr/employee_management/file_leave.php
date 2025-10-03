<?php
include '../db.php';
session_start();
if(!isset($_SESSION['staff_id'])){
    header("Location: employee_login.php");
    exit;
}
$staff_id = $_SESSION['staff_id'];
$leave_reasons = ['Vacation','Sick','Maternity/Paternity','Emergency','Personal','Training','Other'];
$errors = [];
if (isset($_POST['file_leave'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];
    if ($end_date < $start_date) {
        $errors[] = "End date cannot be earlier than start date.";
    }
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO leave_requests (staff_id, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("ssss", $staff_id, $start_date, $end_date, $reason);
        $stmt->execute();
        $stmt->close();
        header("Location: file_leave.php");
        exit;
    }
}
$leave_result = $conn->query("SELECT l.id, s.first_name, s.last_name, s.staff_id, s.position_name, l.start_date, l.end_date, l.status, l.reason 
                              FROM leave_requests l 
                              JOIN staff s ON l.staff_id = s.staff_id
                              WHERE l.staff_id='$staff_id'
                              ORDER BY l.start_date DESC");
$emp_res = $conn->query("SELECT first_name, last_name FROM staff WHERE staff_id='$staff_id' LIMIT 1");
$emp_data = $emp_res ? $emp_res->fetch_assoc() : ['first_name'=>'','last_name'=>''];
$emp_name = trim($emp_data['first_name'].' '.$emp_data['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>File Leave</title>
<link rel="stylesheet" href="../css/file_leave.css">
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">
</head>
<body>
<a href="homepage.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
<div class="container">
<h1>File Leave Request</h1>
<?php if (!empty($errors)): foreach($errors as $err): ?>
<div style="color:red;margin-bottom:10px;"><?= htmlspecialchars($err) ?></div>
<?php endforeach; endif; ?>
<form method="POST">
<label>Employee Name</label>
<input type="text" value="<?= htmlspecialchars($emp_name) ?>" readonly>
<label>Start Date</label>
<input type="date" name="start_date" required>
<label>End Date</label>
<input type="date" name="end_date" required>
<label>Reason</label>
<select name="reason" required>
<option value="">Select Reason</option>
<?php foreach($leave_reasons as $reason_option): ?>
<option value="<?= $reason_option ?>"><?= $reason_option ?></option>
<?php endforeach; ?>
</select>
<button type="submit" name="file_leave">Submit Leave Request</button>
</form>
<h1>Your Leave Requests</h1>
<?php if ($leave_result && $leave_result->num_rows > 0): ?>
<table>
<thead>
<tr>
<th>Start Date</th>
<th>End Date</th>
<th>Reason</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php while($row = $leave_result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['start_date']) ?></td>
<td><?= htmlspecialchars($row['end_date']) ?></td>
<td><?= htmlspecialchars($row['reason']) ?></td>
<td><span class="status <?= $row['status'] ?>"><?= $row['status'] ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php else: ?>
<p>No leave requests found.</p>
<?php endif; ?>
</div>
</body>
</html>
