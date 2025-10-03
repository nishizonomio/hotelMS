<?php
session_start();
include '../db.php';
if (!isset($_SESSION['staff_id'])) header("Location: employee_login.php");
$staff_id = $_SESSION['staff_id'];

$stmt = $conn->prepare("
SELECT p.*, IFNULL(SUM(o.hours * o.rate),0) as overtime_total 
FROM payroll p 
LEFT JOIN overtime o ON p.staff_id=o.staff_id 
WHERE p.staff_id=? 
GROUP BY p.id
");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Payroll</title>
<link rel="stylesheet" href="employee_login.css">
</head>
<body>
<div class="container">
<h2>Payroll for <?= htmlspecialchars($staff_id) ?></h2>
<table border="1" cellpadding="8" cellspacing="0">
<tr><th>Month</th><th>Base Salary</th><th>Overtime</th><th>Total</th></tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= $row['month'] ?></td>
<td><?= $row['base_salary'] ?></td>
<td><?= $row['overtime_total'] ?></td>
<td><?= $row['base_salary'] + $row['overtime_total'] ?></td>
</tr>
<?php endwhile; ?>
</table>
<br>
<a href="online_profile.php">Back to Dashboard</a>
</div>
</body>
</html>
