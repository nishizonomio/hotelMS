<?php
session_start();
include '../db.php';

if(!isset($_SESSION['staff_id'])) {
    header("Location: employee_login.php");
    exit;
}

$staff_id = $_SESSION['staff_id'];
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$emergency_contact = $_POST['emergency_contact'] ?? '';

$photo_db = null;
$id_db = null;
$upload_dir = 'C:/xampp/htdocs/hotel/hr/uploads/';

// ensure the upload directory exists
if(!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if(isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
    $photo_name = $staff_id . '_' . time() . '_' . basename($_FILES['photo']['name']);
    $photo_path = $upload_dir . $photo_name;
    if(move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
        $photo_db = $photo_name;
    }
}

if(isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] === 0) {
    $id_name = $staff_id . '_' . time() . '_' . basename($_FILES['id_proof']['name']);
    $id_path = $upload_dir . $id_name;
    if(move_uploaded_file($_FILES['id_proof']['tmp_name'], $id_path)) {
        $id_db = $id_name;
    }
}

$sql = "UPDATE staff SET first_name=?, last_name=?, email=?, phone=?, address=?, emergency_contact=?";
$params = [$first_name, $last_name, $email, $phone, $address, $emergency_contact];
$types = "ssssss";

if($photo_db) {
    $sql .= ", photo=?";
    $types .= "s";
    $params[] = $photo_db;
}

if($id_db) {
    $sql .= ", id_proof=?";
    $types .= "s";
    $params[] = $id_db;
}

$sql .= " WHERE staff_id=?";
$types .= "s";
$params[] = $staff_id;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->close();
$conn->close();
