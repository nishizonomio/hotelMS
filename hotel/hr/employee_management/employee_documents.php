<?php
include '../db.php';
$staff_id = $_GET['id'] ?? '';
if (!$staff_id) die("Employee not found.");

$stmt = $conn->prepare("SELECT s.*, e.* 
                        FROM staff s
                        LEFT JOIN employee_documents e ON s.staff_id = e.staff_id
                        WHERE s.staff_id = ? LIMIT 1");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
if (!$employee) die("Employee not found.");

$doc_columns = [];
$res = $conn->query("SHOW COLUMNS FROM employee_documents");
while ($col = $res->fetch_assoc()) {
    if (!in_array($col['Field'], ['id','staff_id'])) $doc_columns[] = $col['Field'];
}

$documents = [];
$documents['Contract'] = !empty($employee['contract_file']) && file_exists($employee['contract_file']) ? $employee['contract_file'] : '';
$documents['Government ID'] = !empty($employee['id_proof']) && file_exists($employee['id_proof']) ? $employee['id_proof'] : '';
$documents['Photo'] = !empty($employee['photo']) && file_exists($employee['photo']) ? $employee['photo'] : '';
foreach ($doc_columns as $col) {
    $name = ucwords(str_replace('_',' ', $col));
    $documents[$name] = !empty($employee[$col]) && file_exists($employee[$col]) ? $employee[$col] : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Documents</title>
<link rel="stylesheet" href="../css/employee_documents.css">
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">
</head>
<?php
include '../db.php';
$staff_id = $_GET['id'] ?? '';
if (!$staff_id) die("Employee not found.");

$stmt = $conn->prepare("SELECT s.*, e.* 
                        FROM staff s
                        LEFT JOIN employee_documents e ON s.staff_id = e.staff_id
                        WHERE s.staff_id = ? LIMIT 1");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
if (!$employee) die("Employee not found.");

$doc_columns = [];
$res = $conn->query("SHOW COLUMNS FROM employee_documents");
while ($col = $res->fetch_assoc()) {
    if (!in_array($col['Field'], ['id','staff_id'])) $doc_columns[] = $col['Field'];
}

$documents = [];
$documents['Contract'] = !empty($employee['contract_file']) && file_exists($employee['contract_file']) ? $employee['contract_file'] : '';
$documents['Government ID'] = !empty($employee['id_proof']) && file_exists($employee['id_proof']) ? $employee['id_proof'] : '';
$documents['Photo'] = !empty($employee['photo']) && file_exists($employee['photo']) ? $employee['photo'] : '';
foreach ($doc_columns as $col) {
    $name = ucwords(str_replace('_',' ', $col));
    $documents[$name] = !empty($employee[$col]) && file_exists($employee[$col]) ? $employee[$col] : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Documents</title>
<link rel="stylesheet" href="../css/employee_documents.css">
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">

</head>
<body>
<div class="container">
<a href="document_submission.php" class="back-button"><i class="fas fa-arrow-left"></i> Back</a>
<h1><?= htmlspecialchars($employee['first_name'].' '.$employee['last_name']) ?></h1>
<p>Position: <?= htmlspecialchars($employee['position_name']) ?></p>
<p>Email: <?= htmlspecialchars($employee['email']) ?></p>
<p>Phone: <?= htmlspecialchars($employee['phone']) ?></p>
<table>
    <thead>
        <tr>
            <th>Document</th>
            <th>File</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($documents as $docName => $filePath): ?>
            <tr>
                <td><?= htmlspecialchars($docName) ?></td>
                <td>
                    <?php if ($filePath): ?>
                        <a href="<?= htmlspecialchars($filePath) ?>" target="_blank" class="view-btn">View</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
</body>
</html>
