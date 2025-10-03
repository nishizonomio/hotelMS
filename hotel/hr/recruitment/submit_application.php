<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($conn->real_escape_string($_POST['first_name']));
    $last_name = trim($conn->real_escape_string($_POST['last_name']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $phone = trim($conn->real_escape_string($_POST['phone']));
    $applied_position = trim($conn->real_escape_string($_POST['applied_position']));

    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        header("Location: hotellavista_jobs.php?success=0&error=empty_fields");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|ph|net|org|gov)$/', $email)) {
        header("Location: hotellavista_jobs.php?success=0&error=invalid_email");
        exit;
    }

    if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $phone)) {
        header("Location: hotellavista_jobs.php?success=0&error=invalid_phone");
        exit;
    }

    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        header("Location: hotellavista_jobs.php?success=0&error=no_resume");
        exit;
    }

    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $filename = time() . '_' . basename($_FILES['resume']['name']);
    $target_file = $upload_dir . $filename;
    if (!move_uploaded_file($_FILES['resume']['tmp_name'], $target_file)) {
        header("Location: hotellavista_jobs.php?success=0&error=resume_failed");
        exit;
    }

    $candidate_id = uniqid('CAND_');

    $sql = "INSERT INTO recruitment 
        (candidate_id, first_name, last_name, email, phone, applied_position, status, resume) 
        VALUES ('$candidate_id', '$first_name', '$last_name', '$email', '$phone', '$applied_position', 'Pending', '$filename')";

    if ($conn->query($sql)) {
        header("Location: hotellavista_jobs.php?success=1");
        exit;
    } else {
        header("Location: hotellavista_jobs.php?success=0&error=db_error");
        exit;
    }
} else {
    header('Location: hotellavista_jobs.php');
    exit;
}
?>
