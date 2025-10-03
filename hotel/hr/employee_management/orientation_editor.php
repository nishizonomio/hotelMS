<?php
include '../db.php';

if (!isset($_GET['staff_id'])) {
    header("Location: employee_management.php");
    exit;
}

$staff_id = $_GET['staff_id'];

// fetch employee info
$stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Employee not found";
    exit;
}
$employee = $result->fetch_assoc();

// check if orientation already exists
$orientation_stmt = $conn->prepare("SELECT * FROM orientation WHERE staff_id = ?");
$orientation_stmt->bind_param("s", $staff_id);
$orientation_stmt->execute();
$orientation_result = $orientation_stmt->get_result();

if ($orientation_result->num_rows > 0) {
    $orientation = $orientation_result->fetch_assoc();
} else {
    $orientation = ['agenda' => '', 'notes' => ''];
}

// save orientation when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agenda = $_POST['agenda'];
    $notes  = $_POST['notes'];

    if ($orientation_result->num_rows > 0) {
        $update = $conn->prepare("UPDATE orientation SET agenda=?, notes=? WHERE staff_id=?");
        $update->bind_param("sss", $agenda, $notes, $staff_id);
        $update->execute();
    } else {
        $insert = $conn->prepare("INSERT INTO orientation (staff_id, agenda, notes) VALUES (?, ?, ?)");
        $insert->bind_param("sss", $staff_id, $agenda, $notes);
        $insert->execute();
    }

    header("Location: orientation.php?id=" . $employee['id']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Orientation - <?= $employee['first_name'].' '.$employee['last_name'] ?></title>
<link rel="stylesheet" href="hr.css">
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">
<style>
.form-container {
    max-width: 800px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 12px rgba(0,0,0,0.2);
}
label {
    font-weight: bold;
    display: block;
    margin-top: 15px;
}
textarea {
    width: 100%;
    height: 150px;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-family: Arial, sans-serif;
}
button {
    margin-top: 20px;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    background: #007BFF;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
}
button:hover {
    background: #0056b3;
}
</style>
</head>
<body>
<div class="form-container">
    <a href="view_employee.php?id=<?= $employee['id'] ?>" class="back-button">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    <h2>Edit Orientation for <?= $employee['first_name'].' '.$employee['last_name'] ?></h2>

    <form method="post">
        <label for="agenda">Orientation Agenda</label>
        <textarea name="agenda" id="agenda"><?= htmlspecialchars($orientation['agenda']) ?></textarea>

        <label for="notes">Additional Notes</label>
        <textarea name="notes" id="notes"><?= htmlspecialchars($orientation['notes']) ?></textarea>

        <button type="submit"><i class="fas fa-save"></i> Save Orientation</button>
    </form>
</div>
</body>
</html>
