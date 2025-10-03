<?php
include '../db.php';

$staff = [];
$res = $conn->query("SELECT * FROM staff ORDER BY staff_id ASC");
while ($row = $res->fetch_assoc()) {
    $row['children'] = [];
    $staff[$row['staff_id']] = $row;
}

$ceoRes = $conn->query("SELECT * FROM ceo WHERE staff_id='CEO' LIMIT 1");
$ceo = $ceoRes->fetch_assoc();
if (!$ceo) {
    $ceo = [
        'staff_id' => 'CEO',
        'first_name' => 'Alice',
        'last_name' => 'Garcia',
        'position_name' => 'CEO',
        'department_name' => 'Executive',
        'photo' => ''
    ];
    $stmt = $conn->prepare("INSERT INTO ceo (staff_id, first_name, last_name, position_name, department_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $ceo['staff_id'], $ceo['first_name'], $ceo['last_name'], $ceo['position_name'], $ceo['department_name']);
    $stmt->execute();
}
$ceo['children'] = [];

$employeeByName = [];
foreach ($staff as &$emp) {
    $fullName = trim($emp['first_name'].' '.$emp['last_name']);
    $employeeByName[strtolower($fullName)] = &$emp;
}

foreach ($staff as &$emp) {
    $managerName = trim(strtolower($emp['manager']));
    if ($managerName && isset($employeeByName[$managerName])) {
        $employeeByName[$managerName]['children'][] = &$emp;
    } else {
        $ceo['children'][] = &$emp;
    }
}
unset($emp);

function displayOrgChart($employees) {
    echo '<ul class="org">';
    foreach ($employees as $emp) {
        echo '<li>';
        echo '<div class="box">';

        $uploadPath = 'C:/xampp/htdocs/hotel/hr/uploads/';
        $webPath = '/hotel/hr/uploads/';
        $photoPath = '';

        if (!empty($emp['photo']) && file_exists($uploadPath . $emp['photo'])) {
            $photoPath = $webPath . $emp['photo'];
        }

        if ($photoPath) {
            echo '<img src="'.htmlspecialchars($photoPath).'" alt="'.htmlspecialchars($emp['first_name'].' '.$emp['last_name']).'">';
        } else {
            $initials = '';
            foreach (explode(' ', $emp['first_name'].' '.$emp['last_name']) as $word) {
                if (!empty($word)) $initials .= strtoupper($word[0]);
            }
            echo '<div class="initials">'.htmlspecialchars($initials).'</div>';
        }

        echo '<div class="position">'.htmlspecialchars($emp['position_name']).'</div>';
        echo '<div class="name">'.htmlspecialchars($emp['first_name'].' '.$emp['last_name']).'</div>';
        if (isset($emp['staff_id']) && $emp['staff_id'] === 'CEO') {
            echo '<a class="edit-ceo-btn" href="edit_ceo.php">Edit CEO</a>';
        }
        echo '</div>';

        if (!empty($emp['children'])) {
            displayOrgChart($emp['children']);
        }
        echo '</li>';
    }
    echo '</ul>';
}

$tree = [$ceo];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Hotel Organizational Chart</title>
<link rel="stylesheet" href="../css/department.css">
</head>
<body>
<a class="back-btn" href="hr_employee_management.php">← Back</a>
<h1>Hotel Organizational Chart</h1>
<div class="chart-wrapper">
<?php displayOrgChart($tree); ?>
</div>
</body>
</html>
