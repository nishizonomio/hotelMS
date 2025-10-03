<?php
include '../db.php';
$staff_id = $_POST['staff_id'];
$school = $_POST['school_name'];
$degree = $_POST['degree'];
$grad = $_POST['graduation_year'];

$stmt = $conn->prepare("INSERT INTO school_attainment (staff_id, school_name, degree, graduation_year) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssi", $staff_id, $school, $degree, $grad);
$stmt->execute();
echo "success";
?>
