<?php
include '../db.php';

if (!isset($_GET['id'])) {
    header("Location: employee_management.php");
    exit;
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Employee not found";
    exit;
}

$employee = $result->fetch_assoc();

$contract_file = "uploads/contract_{$employee['staff_id']}.pdf";
$resume_file = "uploads/resume_{$employee['staff_id']}.pdf";

if (!file_exists($contract_file)) {
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Company Name');
    $pdf->SetTitle('Employment Contract');
    $pdf->SetMargins(20, 20, 20);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'EMPLOYMENT CONTRACT', 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 12);

    $contract_content = "
This Employment Contract is made between {$employee['first_name']} {$employee['last_name']} and the Company.

Position: {$employee['position_name']}
Department: {$employee['department']}
Employment Type: {$employee['employment_type']}
Base Salary: {$employee['base_salary']}
Hire Date: {$employee['hire_date']}

Terms and conditions of employment are as per company policies.

Signed by Employee and Company.
";

    $pdf->MultiCell(0, 6, $contract_content, 0, 'L', 0, 1, '', '', true);
    $pdf->Output($contract_file, 'F');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Documents</title>
<link rel="stylesheet" href="hr.css">
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">
<style>
.bond-paper {
    width: 816px;
    height: 1056px;
    margin: 20px auto;
    padding: 50px 70px;
    background: white;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);
    border: 1px solid #ccc;
}
.bond-paper iframe {
    width: 100%;
    height: 100%;
    border: none;
}
</style>
</head>
<body>
<div class="container">
    <a href="view_employee.php?id=<?= $employee['id'] ?>" class="back-button"><i class="fas fa-arrow-left"></i> Back</a>
    <h1>Documents for <?= $employee['first_name'].' '.$employee['last_name'] ?></h1>

    <h2>Employment Contract</h2>
    <iframe src="<?= $contract_file ?>" style="width:100%; height:600px;" frameborder="0"></iframe>
    <div style="margin-top:15px;">
        <a href="<?= $contract_file ?>" download class="btn"><i class="fas fa-download"></i> Download Contract</a>
    </div>

    <h2 style="margin-top:40px;">Resume</h2>
    <?php if (file_exists($resume_file)): ?>
        <div class="bond-paper">
            <iframe src="<?= $resume_file ?>"></iframe>
        </div>
        <div style="margin-top:15px; text-align:center;">
            <a href="<?= $resume_file ?>" download class="btn"><i class="fas fa-download"></i> Download Resume</a>
        </div>
    <?php else: ?>
        <p>No resume uploaded for this employee.</p>
    <?php endif; ?>
</div>
</body>
</html>
