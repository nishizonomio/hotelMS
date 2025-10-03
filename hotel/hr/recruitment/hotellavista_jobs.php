<?php
include '../db.php';

$vacant_sql = "
SELECT 
    p.position_id AS id,
    p.position_name, 
    p.department_name, 
    p.required_count,
    IFNULL(COUNT(s.staff_id),0) AS filled_count,
    (p.required_count - IFNULL(COUNT(s.staff_id),0)) AS vacant_slots
FROM positions p
LEFT JOIN staff s 
    ON p.position_name = s.position_name 
    AND p.department_name = s.department_name 
    AND s.employment_status IN ('Active','Probation')
WHERE p.position_name NOT IN ('Executive','Director')
GROUP BY p.position_id, p.position_name, p.department_name, p.required_count
HAVING vacant_slots > 0
ORDER BY vacant_slots DESC
";

$vacant_result = $conn->query($vacant_sql);
$positions = [];

while($row = $vacant_result->fetch_assoc()) {
    $position_name = $conn->real_escape_string($row['position_name']);
    $applicant_result = $conn->query("SELECT COUNT(*) AS count FROM recruitment WHERE applied_position='$position_name' AND (status IS NULL OR status='Pending')");
    $app_data = $applicant_result->fetch_assoc();
    $row['app_count'] = $app_data['count'] ?? 0;
    $positions[] = $row;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name  = $conn->real_escape_string($_POST['last_name']);
    $birth_date = $conn->real_escape_string($_POST['birthdate']);
    $address    = $conn->real_escape_string($_POST['address']);
    $email      = $conn->real_escape_string($_POST['email']);
    $phone      = $conn->real_escape_string($_POST['phone']);
    $applied_position = $conn->real_escape_string($_POST['applied_position']);
    $applied_date = date('Y-m-d H:i:s');
    $resume     = '';

    $dob = new DateTime($birth_date);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
    if($age < 18){
        header("Location: hotellavista_jobs.php?error=age");
        exit;
    }

    if(isset($_FILES['resume']) && $_FILES['resume']['error'] === 0){
        $target_dir = "C:/xampp/htdocs/hotel/hr/uploads/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $filename = time() . "_" . basename($_FILES['resume']['name']);
        $target_file = $target_dir . $filename;
        move_uploaded_file($_FILES['resume']['tmp_name'], $target_file);
        $resume = $filename;
    }

    $last_candidate = $conn->query("SELECT candidate_id FROM recruitment ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $last_id = $last_candidate['candidate_id'] ?? "CAND-0000";
    $num = (int)substr($last_id, 5) + 1;
    $candidate_id = "CAND-" . str_pad($num, 4, "0", STR_PAD_LEFT);

    $stmt = $conn->prepare("
        INSERT INTO recruitment 
        (candidate_id, first_name, last_name, birth_date, address, email, phone, applied_position, applied_date, status, resume)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)
    ");
    $stmt->bind_param("ssssssssss", $candidate_id, $first_name, $last_name, $birth_date, $address, $email, $phone, $applied_position, $applied_date, $resume);
    $stmt->execute();

    header("Location: hotellavista_jobs.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Hotel La Vista Job Postings</title>
<link rel="stylesheet" href="../css/hotellavista_jobs.css">
</head>

<body>
<div class="overlay">
<header>
<h1>Hotel La Vista Hiring Positions</h1>
</header>
<div class="container">
<?php if(isset($_GET['success']) && $_GET['success']==1): ?>
<div class="success-message">
<p>Thank you for applying! Please wait for our call.</p>
<div class="hr-info">
<p>If you are not contacted within 2 weeks, consider yourself unshortlisted. We wish you luck!</p>
<p>HR Contact: <strong>+63 912 345 6789</strong> | Email: <strong>hr@hotellavista.com</strong></p>
</div>
</div>
<?php endif; ?>
<?php if(isset($_GET['error']) && $_GET['error']=='age'): ?>
<div class="error-msg">Applicants must be at least 18 years old.</div>
<?php endif; ?>
<div class="back-wrapper">
    <a href="/hotel/hr/recruitment/recruitment.php" class="back">&larr; Back</a>
</div>
<?php if(!empty($positions)): ?>
<div class="grid">
<?php foreach($positions as $v): ?>
<div class="card">
<h3><?php echo htmlspecialchars($v['position_name']); ?></h3>
<p>Department: <?php echo htmlspecialchars($v['department_name']); ?></p>
<p>Vacant Slots: <?php echo max(0,$v['vacant_slots']); ?></p>
<p>Applicants: <?php echo $v['app_count']; ?></p>
<button class="applyBtn" onclick="openModal('<?php echo htmlspecialchars($v['position_name']); ?>')">Apply</button>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<p style="text-align:center;">No vacant positions available at the moment.</p>
<?php endif; ?>
</div>
</div>

<div id="applyModal" class="modal">
<div class="modal-content">
<span class="close" onclick="closeModal()">&times;</span>
<h2>Apply for <span id="modalPosition"></span></h2>
<form method="post" action="" enctype="multipart/form-data" onsubmit="return validateAge()">
<input type="hidden" name="applied_position" id="applied_position">
<label>First Name</label>
<input type="text" name="first_name" required>
<label>Last Name</label>
<input type="text" name="last_name" required>
<label>Birthdate</label>
<input type="date" name="birthdate" id="birthdate" required>
<div id="ageError" class="error-msg"></div>
<label>Address</label>
<textarea name="address" required></textarea>
<label>Email</label>
<input type="email" name="email" required pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|ph|net|org|gov)$">
<label>Phone</label>
<input type="text" name="phone" required pattern="^(09\d{9}|\+639\d{9})$">
<label>Resume</label>
<input type="file" name="resume" accept=".pdf,.doc,.docx" required>
<button type="submit" class="submitBtn">Submit Application</button>
</form>
</div>
</div>

<script>
function openModal(position){
document.getElementById('applyModal').style.display='block';
document.getElementById('modalPosition').innerText=position;
document.getElementById('applied_position').value=position;
document.getElementById('ageError').innerText='';
}
function closeModal(){
document.getElementById('applyModal').style.display='none';
}
window.onclick=function(event){
var modal=document.getElementById('applyModal');
if(event.target==modal) modal.style.display='none';
}
function validateAge() {
const birthdate = document.getElementById('birthdate').value;
const ageError = document.getElementById('ageError');
const dob = new Date(birthdate);
const today = new Date();
let age = today.getFullYear() - dob.getFullYear();
const m = today.getMonth() - dob.getMonth();
if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) { age--; }
if (age < 18) {
ageError.innerText = "Applicants must be at least 18 years old.";
return false;
}
ageError.innerText = "";
return true;
}
</script>
</body>
</html>
<?php $conn->close(); ?>
