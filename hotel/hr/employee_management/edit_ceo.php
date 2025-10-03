<?php
include '../db.php';

$res = $conn->query("SELECT * FROM ceo WHERE staff_id='CEO' LIMIT 1");
$ceo = $res->fetch_assoc();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $position_name = $_POST['position_name'];
    $department_name = $_POST['department_name'];
    $photo = $ceo['photo'];

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $filename = basename($_FILES['photo']['name']);
        $targetFile = $uploadDir . $filename;
        move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile);
        $photo = $filename;
    }

    $stmt = $conn->prepare("UPDATE ceo SET first_name=?, last_name=?, position_name=?, department_name=?, photo=? WHERE staff_id='CEO'");
    $stmt->bind_param("sssss", $first_name, $last_name, $position_name, $department_name, $photo);
    $stmt->execute();

    header("Location: departments.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit CEO</title>
<link rel="stylesheet" href="../css/edit_ceo.css">
</head>
<body>
<h1>Edit CEO</h1>
<form method="POST" enctype="multipart/form-data">
<input type="text" name="first_name" value="<?= htmlspecialchars($ceo['first_name']) ?>" required>
<input type="text" name="last_name" value="<?= htmlspecialchars($ceo['last_name']) ?>" required>
<input type="text" name="position_name" value="<?= htmlspecialchars($ceo['position_name']) ?>" required>
<input type="text" name="department_name" value="<?= htmlspecialchars($ceo['department_name']) ?>" required>
<input type="file" name="photo" accept="image/*">
<?php if(!empty($ceo['photo'])): ?>
<img src="uploads/<?= htmlspecialchars($ceo['photo']) ?>" alt="CEO Photo" style="width:100px;height:100px;margin-top:10px;">
<?php endif; ?>
<button type="submit">Save</button>
</form>
<a class="back-btn" href="departments.php">← Back to Org Chart</a>
</body>
</html>