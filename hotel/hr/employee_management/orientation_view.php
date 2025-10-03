<?php
include '../db.php';

if (!isset($_GET['staff_id'])) {
    echo "No employee selected.";
    exit;
}

$staff_id = $_GET['staff_id'];

$stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id=? AND position_name NOT IN ('CEO','COO')");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
if (!$employee) die("Employee not found");

$conn->query("
CREATE TABLE IF NOT EXISTS orientation_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) NOT NULL,
    orientation_id INT NOT NULL,
    status ENUM('Pending','Attended') DEFAULT 'Pending',
    attended_at DATETIME DEFAULT NULL,
    UNIQUE KEY unique_employee_orientation (staff_id, orientation_id)
)
");

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['orientation_id'], $_POST['status'])) {
    $orientation_id = $_POST['orientation_id'];
    $status = $_POST['status'];
    $attended_at = ($status==='Attended') ? date('Y-m-d H:i:s') : NULL;

    $stmt = $conn->prepare("
        INSERT INTO orientation_attendance (staff_id, orientation_id, status, attended_at)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status=?, attended_at=?
    ");
    $stmt->bind_param("sissss", $staff_id, $orientation_id, $status, $attended_at, $status, $attended_at);
    $stmt->execute();
}

$orientations = $conn->query("SELECT * FROM orientations ORDER BY date, time")->fetch_all(MYSQLI_ASSOC);

$image_path = $employee['photo'] ? "/hotel/hr/uploads/".$employee['photo'] : '';
if (!empty($employee['photo']) && file_exists($_SERVER['DOCUMENT_ROOT']."/hotel/hr/uploads/".$employee['photo'])) {
    $image_tag = "<img src='".htmlspecialchars($image_path)."' alt='Employee Image' class='employee-photo'>";
} else {
    $color = '#800000';
    $initials = strtoupper(substr($employee['first_name'],0,1) . substr($employee['last_name'],0,1));
    $image_tag = "<div class='avatar' style='width:120px;height:120px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:$color;color:#fff;font-size:48px;font-weight:bold;'>$initials</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Orientation for <?= $employee['first_name'].' '.$employee['last_name'] ?></title>
<link rel="stylesheet" href="../css/orientation_view.css">
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">
</head>
<body>
<div class="container">
<a href="orientation.php" class="back-button"><i class="fas fa-arrow-left"></i> Back</a>
<div class="profile-header">
<div class="profile-details">
<h1><?= $employee['first_name'].' '.$employee['last_name'] ?></h1>
<p><strong>Employee ID:</strong> <?= $employee['staff_id'] ?></p>
<p><strong>Position:</strong> <?= $employee['position_name'] ?></p>
<p><strong>Email:</strong> <?= $employee['email'] ?></p>
<p><strong>Phone:</strong> <?= $employee['phone'] ?></p>
</div>
<?= $image_tag ?>
</div>

<h2>Orientations</h2>
<table>
<thead>
<tr>
<th>Title</th>
<th>Date</th>
<th>Time</th>
<th>Status</th>
<th>Update</th>
</tr>
</thead>
<tbody>
<?php foreach ($orientations as $o):
    $stmt = $conn->prepare("SELECT status FROM orientation_attendance WHERE staff_id=? AND orientation_id=?");
    $stmt->bind_param("si", $staff_id, $o['id']);
    $stmt->execute();
    $status = $stmt->get_result()->fetch_assoc()['status'] ?? 'Pending';
?>
<tr>
<td><?= htmlspecialchars($o['title']) ?></td>
<td><?= htmlspecialchars($o['date']) ?></td>
<td><?= htmlspecialchars($o['time']) ?></td>
<td><?= $status ?></td>
<td>
<form method="post" style="display:inline-block;">
<input type="hidden" name="orientation_id" value="<?= $o['id'] ?>">
<select name="status">
<option value="Pending" <?= $status==='Pending'?'selected':'' ?>>Pending</option>
<option value="Attended" <?= $status==='Attended'?'selected':'' ?>>Attended</option>
</select>
<button type="submit">Save</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</body>
</html>
