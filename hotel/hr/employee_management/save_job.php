<?php
include '../db.php';
$staff_id = $_POST['staff_id'];
$company = $_POST['company_name'];
$position = $_POST['position'];
$start = $_POST['start_date'];
$end = $_POST['end_date'];

$stmt = $conn->prepare("INSERT INTO job_experience (staff_id, company_name, position, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $staff_id, $company, $position, $start, $end);
$stmt->execute();
echo "success";
?>
