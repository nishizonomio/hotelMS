<?php
session_start();
include '../db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: employee_login.php");
    exit;
}

$staff_id = $_SESSION['staff_id'];

$orientations = $conn->query("SELECT * FROM orientations ORDER BY date ASC, time ASC");

$attendance_stmt = $conn->prepare("SELECT orientation_id, status FROM orientation_attendance WHERE staff_id=?");
$attendance_stmt->bind_param("s", $staff_id);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
$attended_orientations = [];
while ($row = $attendance_result->fetch_assoc()) {
    $attended_orientations[$row['orientation_id']] = $row['status'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Orientation Schedule</title>
<link rel="stylesheet" href="../css/attend_orientation.css">
</head>
<body>
<a href="homepage.php" class="back-button">&larr; Back</a>
<div class="container">
<h1>Orientation Schedule</h1>

<?php if($orientations && $orientations->num_rows > 0): ?>
    <?php while($row = $orientations->fetch_assoc()): ?>
        <div class="orientation">
            <h2><?= htmlspecialchars($row['title']) ?></h2>
            <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?: '-' ?></p>
            <p><strong>Date:</strong> <?= $row['date'] ?> <strong>Time:</strong> <?= $row['time'] ?></p>
            <?php 
            $status = $attended_orientations[$row['id']] ?? 'Pending';
            ?>
            <span class="status <?= $status === 'Attended' ? 'attended' : 'not-attended' ?>">
                <?= $status === 'Attended' ? 'Attended' : 'Not Attended' ?>
            </span>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p style="text-align:center;">No orientations scheduled.</p>
<?php endif; ?>

</div>
</body>
</html>
