<?php
include '../db.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$department = isset($_GET['department']) ? trim($_GET['department']) : '';
$position = isset($_GET['position']) ? trim($_GET['position']) : '';

$where = ["s.position_name NOT IN ('CEO','COO')"];
$params = [];
$types = '';

if ($search) {
    $where[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.staff_id LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}
if ($department) { $where[] = "s.department_id = ?"; $params[] = $department; $types .= 'i'; }
if ($position) { $where[] = "s.position_name = ?"; $params[] = $position; $types .= 's'; }

$sql = "
SELECT s.staff_id, s.first_name, s.last_name, s.photo,
COALESCE(oa_count.attended_count,0) AS attended_count
FROM staff s
LEFT JOIN (
    SELECT staff_id, COUNT(*) AS attended_count
    FROM orientation_attendance
    WHERE status='Attended'
    GROUP BY staff_id
) oa_count ON s.staff_id = oa_count.staff_id
WHERE ".implode(" AND ", $where)."
ORDER BY attended_count ASC, s.last_name, s.first_name
";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$employees = [];
while ($row = $result->fetch_assoc()) {
    $letter = strtoupper(substr($row['last_name'], 0, 1));
    if (!isset($employees[$letter])) $employees[$letter] = [];
    $employees[$letter][] = $row;
}
ksort($employees);

$departments = $conn->query("SELECT * FROM departments ORDER BY department_name")->fetch_all(MYSQLI_ASSOC);
$positions = $conn->query("SELECT DISTINCT position_name FROM staff WHERE position_name NOT IN ('CEO','COO') ORDER BY position_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Orientation List</title>
<link rel="stylesheet" href="../css/orientation.css">
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">
</head>
<body>
<div class="container">
<a href="hr_employee_management.php" class="back-button"><i class="fas fa-arrow-left"></i> Back</a>
<h1>Employees Pending Orientation</h1>

<form method="get" class="search-bar">
<input type="text" name="search" placeholder="Search by name or employee ID..." value="<?= htmlspecialchars($search) ?>">
<select name="department">
<option value="">All Departments</option>
<?php foreach ($departments as $d): ?>
<option value="<?= $d['department_id'] ?>" <?= $department == $d['department_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['department_name']) ?></option>
<?php endforeach; ?>
</select>
<select name="position">
<option value="">All Positions</option>
<?php foreach ($positions as $p): ?>
<option value="<?= htmlspecialchars($p['position_name']) ?>" <?= $position == $p['position_name'] ? 'selected' : '' ?>><?= htmlspecialchars($p['position_name']) ?></option>
<?php endforeach; ?>
</select>
<button type="submit"><i class="fas fa-search"></i> Search</button>
<a href="orientation.php" class="reset-btn"><i class="fas fa-undo"></i> Reset</a>
</form>

<?php if (!empty($employees)): ?>
<?php foreach ($employees as $letter => $list): ?>
<div class="group-letter"><?= $letter ?></div>
<ul class="employee-list">
<?php foreach ($list as $employee):

$image_path = $employee['photo'] ? "/hotel/hr/uploads/".$employee['photo'] : '';
if (!empty($employee['photo']) && file_exists($_SERVER['DOCUMENT_ROOT']."/hotel/hr/uploads/".$employee['photo'])) {
    $avatar = "<img src='".htmlspecialchars($image_path)."' alt='Employee Image' class='employee-photo'>";
} else {
    $color = '#800000';
    $initials = strtoupper(substr($employee['first_name'],0,1) . substr($employee['last_name'],0,1));
    $avatar = "<div class='avatar' style='width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:$color;color:#fff;font-size:24px;font-weight:bold;'>$initials</div>";
}
?>
<li>
<?= $avatar ?>
<a href="orientation_view.php?staff_id=<?= $employee['staff_id'] ?>">
<?= htmlspecialchars($employee['first_name'].' '.$employee['last_name']) ?>
</a>
</li>
<?php endforeach; ?>
</ul>
<?php endforeach; ?>
<?php else: ?>
<p>No employees found.</p>
<?php endif; ?>
</div>
</body>
</html>
