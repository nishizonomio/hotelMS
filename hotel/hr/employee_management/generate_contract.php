<?php
include '../db.php';

$id = $_GET['id'] ?? '';
if (!$id) die("Employee ID required");

$res = $conn->query("SELECT * FROM staff WHERE id='$id'")->fetch_assoc();
if (!$res) die("Employee not found");

$contractContent = "
<h2>Employment Contract</h2>
<p>This contract is entered into between <strong>Hotel La Vista</strong> and <strong>{$res['first_name']} {$res['last_name']}</strong> for the position of <strong>{$res['position_name']}</strong> in the <strong>{$res['department_name']}</strong> department.</p>
<p>Employment Type: {$res['employment_type']}</p>
<p>Base Salary: PHP {$res['base_salary']}</p>
<p>Manager/Supervisor: {$res['manager']}</p>
<p>Start Date: {$res['hire_date']}</p>
<p>The employee agrees to abide by the company's rules and policies.</p>
<p>Signed on this day: " . date('F j, Y') . "</p>
<p>_________________________<br>Employer</p>
<p>_________________________<br>Employee</p>
";

if (isset($_POST['download_pdf'])) {
    require 'vendor/autoload.php'; // Make sure dompdf is installed
    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($contractContent);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("contract_{$res['staff_id']}.pdf");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Generate Contract</title>
<link rel="stylesheet" href="employee_profile.css">
<style>
.container{max-width:700px;margin:50px auto;text-align:center;font-family:Arial,sans-serif;}
.contract-box{background:#f9f9f9;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);text-align:left;}
button.download-btn{padding:10px 20px;background:#007BFF;color:#fff;border:none;border-radius:6px;cursor:pointer;margin-top:20px;}
.back-button{display:inline-block;margin-bottom:20px;text-decoration:none;color:#fff;background:#007BFF;padding:8px 12px;border-radius:5px;}
</style>
</head>
<body>
<div class="container">
<a class="back-button" href="employee_profile.php?id=<?= $res['id'] ?>">&larr; Back to Profile</a>
<h1>Contract Template for <?= htmlspecialchars($res['first_name'].' '.$res['last_name']) ?></h1>
<div class="contract-box"><?= $contractContent ?></div>
<form method="POST">
<button type="submit" name="download_pdf" class="download-btn">Download as PDF</button>
</form>
</div>
</body>
</html>
