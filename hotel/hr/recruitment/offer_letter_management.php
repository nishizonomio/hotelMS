<?php
include '../db.php';
require '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;

$selected_candidate = $_POST['candidate_id'] ?? '';
$salary = $_POST['salary'] ?? '';
$benefits = $_POST['benefits'] ?? '';
$joining_date = $_POST['joining_date'] ?? '';
$finalize = $_POST['finalize'] ?? '';

$candidates = $conn->query("SELECT candidate_id, first_name, last_name, applied_position, email, phone FROM recruitment WHERE status='Consider'");

if ($selected_candidate) {
    $res = $conn->query("SELECT * FROM recruitment WHERE candidate_id='$selected_candidate'")->fetch_assoc();
    if (!$res) die("Candidate not found");

    $display_salary = $salary ?: 0.00;
    $display_benefits = $benefits ?: "Health Insurance, Paid Leave, 13th Month Pay";
    $display_joining = $joining_date ?: date('Y-m-d');

    $offerContent = "
    <h2>Offer Letter</h2>
    <p>Dear <strong>{$res['first_name']} {$res['last_name']}</strong>,</p>
    <p>We are pleased to offer you the position of <strong>{$res['applied_position']}</strong> at our company.</p>
    <p>Your joining date will be: <strong>" . date('F j, Y', strtotime($display_joining)) . "</strong></p>
    <p>Your salary will be: <strong>₱{$display_salary}</strong></p>
    <p>Benefits include:<br>" . nl2br(htmlspecialchars($display_benefits)) . "</p>
    <p>We look forward to having you on our team.</p>
    <p>Sincerely,<br>HR Department</p>
    ";
}

if ($finalize && $selected_candidate) {
    $res = $conn->query("SELECT * FROM recruitment WHERE candidate_id='$selected_candidate'")->fetch_assoc();
    if (!$res) die("Candidate not found");

    $staff_id = 'EMP'.rand(100000,999999);
    $default_password = 'temp123';
    $employment_status = 'Active';
    $gender = $res['gender'] ?? 'Male';
    $address = $res['address'] ?? 'Not Provided';
    $manager = 'Not Assigned';
    $employment_type = 'Full-time';
    $department_name = $res['applied_position'] ?? 'General';

    $dept_res = $conn->query("SELECT department_id FROM departments WHERE department_name='$department_name' LIMIT 1")->fetch_assoc();
    $department_id = $dept_res['department_id'] ?? NULL;

    $dompdf = new Dompdf();
    $dompdf->loadHtml($offerContent);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    if (!is_dir('../contracts')) mkdir('../contracts',0777,true);
    $contractFileName = "../contracts/contract_{$staff_id}.pdf";
    file_put_contents($contractFileName, $dompdf->output());

    $stmt = $conn->prepare("
        INSERT INTO staff 
        (staff_id, first_name, last_name, gender, email, phone, address, hire_date, employment_status, position_name, manager, employment_type, base_salary, contract_file, password, health_insurance, department_name, department_id) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "sssssssssssssssssi",
        $staff_id,
        $res['first_name'],
        $res['last_name'],
        $gender,
        $res['email'],
        $res['phone'],
        $address,
        $joining_date,
        $employment_status,
        $res['applied_position'],
        $manager,
        $employment_type,
        $salary,
        $contractFileName,
        $default_password,
        $benefits,
        $department_name,
        $department_id
    );

    if($stmt->execute()){
        $conn->query("DELETE FROM recruitment WHERE candidate_id='$selected_candidate'");
        $successMsg = "Candidate finalized as employee.<br>Employee ID: <strong>$staff_id</strong>, Password: <strong>$default_password</strong>";
    } else {
        $successMsg = "Error finalizing employee: ".$stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Generate Offer Letter</title>
<link rel="stylesheet" href="../css/offer_letter_management.css">
<script>
function openModal(){document.getElementById('editModal').style.display='flex';}
function closeModal(){document.getElementById('editModal').style.display='none';}
</script>
</head>
<body>
<div class="container">
<h1>Contract</h1>
<?php if(isset($successMsg)) echo "<div class='success-msg'>{$successMsg}</div>"; ?>
<a href="http://localhost/hotel/hr/recruitment/recruitment.php" class="back-btn">Back to HR Recruitment</a>
<form method="POST">
<ul class="candidate-list">
<?php while($c = $candidates->fetch_assoc()): ?>
<li>
<button type="submit" name="candidate_id" value="<?= $c['candidate_id']?>" class="candidate-button">
<?= htmlspecialchars($c['first_name'].' '.$c['last_name'])?>
</button>
</li>
<?php endwhile;?>
</ul>
</form>
<?php if($selected_candidate):?>
<button onclick="openModal()" class="edit-btn">Edit Offer</button>
<div class="offer-box"><?= $offerContent?></div>
<div class="modal" id="editModal">
<div class="modal-content">
<button class="close-btn" onclick="closeModal()">X</button>
<form method="POST">
<input type="hidden" name="candidate_id" value="<?= $selected_candidate?>">
<label>Salary:</label>
<input type="text" name="salary" value="<?= htmlspecialchars($salary ?: $display_salary)?>">
<label>Benefits:</label>
<textarea name="benefits" rows="4"><?= htmlspecialchars($benefits ?: $display_benefits)?></textarea>
<label>Joining Date:</label>
<input type="date" name="joining_date" value="<?= htmlspecialchars($joining_date ?: $display_joining)?>">
<button type="submit" name="edit_offer" value="1" class="ok-btn">OK</button>
</form>
</div>
</div>
<form method="POST">
<input type="hidden" name="candidate_id" value="<?= $selected_candidate?>">
<input type="hidden" name="salary" value="<?= htmlspecialchars($display_salary)?>">
<input type="hidden" name="benefits" value="<?= htmlspecialchars($display_benefits)?>">
<input type="hidden" name="joining_date" value="<?= htmlspecialchars($display_joining)?>">
<button type="submit" name="finalize" value="1" class="finalize-btn">Finalize and Hire</button>
</form>
<?php endif;?>
</div>
</body>
</html>
<?php $conn->close(); ?>
