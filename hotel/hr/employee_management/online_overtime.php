<?php
session_start();
include '../db.php';
if (!isset($_SESSION['staff_id'])) header("Location: employee_login.php");
$staff_id = $_SESSION['staff_id'];
$stmt = $conn->prepare("SELECT * FROM overtime WHERE staff_id=? ORDER BY date DESC");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Overtime</title>
<link rel="stylesheet" href="employee_login.css">
</head>
<body>
<div class="container">
<h2>Overtime Requests for <?= htmlspecialchars($staff_id) ?></h2>
<table border="1" cellpadding="8" cellspacing="0">
<tr><th>Date</th><th>Hours</th><th>Rate</th><th>Status</th></tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= $row['date'] ?></td>
<td><?= $row['hours'] ?></td>
<td><?= $row['rate'] ?></td>
<td><?= $row['status'] ?></td>
</tr>
<?php endwhile; ?>
</table>
<br>
<a href="online_profile.php">Back to Dashboard</a>
</div>
</body>
</html>
