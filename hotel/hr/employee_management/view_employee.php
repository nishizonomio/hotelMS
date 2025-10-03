<?php
include '../db.php';

$staff_id = $_GET['staff_id'] ?? '';
if (!$staff_id) die("Employee ID required");

$res = $conn->query("SELECT * FROM `staff` WHERE `staff_id`='$staff_id'") or die($conn->error);
$res = $res->fetch_assoc();
if (!$res) die("Employee not found");

$job_exp = $conn->query("SELECT * FROM `job_experience` WHERE `staff_id`='".$res['staff_id']."' ORDER BY id DESC");
$school = $conn->query("SELECT * FROM `school_attainment` WHERE `staff_id`='".$res['staff_id']."' ORDER BY id DESC");

$docs = [];
$stmt = $conn->prepare("SELECT * FROM `employee_documents` WHERE `staff_id`=?");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$doc_res = $stmt->get_result();
$docs = $doc_res->fetch_assoc() ?: [];

if (isset($_POST['update_profile'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $emergency_contact = $_POST['emergency_contact'];
    $gender = $_POST['gender'];
    $position_name = $_POST['position_name'];
    $department_name = $_POST['department_name'];
    $manager = $_POST['manager'];
    $employment_type = $_POST['employment_type'];
    $base_salary = $_POST['base_salary'];
    $bank_name = $_POST['bank_name'];
    $account_name = $_POST['account_name'];
    $account_number = $_POST['account_number'];

    $stmt = $conn->prepare("UPDATE `staff` SET first_name=?, last_name=?, email=?, phone=?, address=?, emergency_contact=?, gender=?, position_name=?, department_name=?, manager=?, employment_type=?, base_salary=?, bank_name=?, account_name=?, account_number=? WHERE staff_id=?");
    $stmt->bind_param("ssssssssssssssss", $first_name, $last_name, $email, $phone, $address, $emergency_contact, $gender, $position_name, $department_name, $manager, $employment_type, $base_salary, $bank_name, $account_name, $account_number, $staff_id);
    $stmt->execute();

    $res = $conn->query("SELECT * FROM `staff` WHERE `staff_id`='$staff_id'")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Profile</title>
<link rel="stylesheet" href="../css/view_employee.css">
</head>
<body>
<a href="hr_employee_management.php" class="back-button">&larr; Back</a>

<div class="profile-header">
<?php 
$photoPath = '../uploads/'.$res['photo'];
if($res['photo'] && file_exists($photoPath)) {
    echo '<img class="profile-photo" src="'.$photoPath.'" alt="Profile Photo">';
} else {
    $initials = strtoupper(substr($res['first_name'],0,1).substr($res['last_name'],0,1));
    echo '<div class="profile-photo">'.$initials.'</div>';
}
?>
<h1><?= htmlspecialchars($res['first_name'].' '.$res['last_name']) ?></h1>
<p><?= htmlspecialchars($res['email']) ?></p>
<p><?= htmlspecialchars($res['phone']) ?></p>
<p><?= htmlspecialchars($res['position_name'].' | '.$res['department_name']) ?></p>
</div>

<div class="container">
<div class="tab">
<button class="tablinks" onclick="openTab(event,'Personal')">Personal Info</button>
<button class="tablinks" onclick="openTab(event,'Bank')">Bank Info</button>
<button class="tablinks" onclick="openTab(event,'Job')">Job Info</button>
<button class="tablinks" onclick="openTab(event,'Experience')">Job Experience</button>
<button class="tablinks" onclick="openTab(event,'School')">School</button>
<button class="tablinks" onclick="openTab(event,'Documents')">Documents</button>
</div>
<form method="POST" enctype="multipart/form-data" id="profileForm">
<div id="Personal" class="tabcontent">
  <div class="personal-field"><label>Employee ID</label><input type="text" name="staff_id" value="<?= $res['staff_id'] ?>" readonly></div>
  <div class="personal-field"><label>First Name</label><input type="text" name="first_name" value="<?= $res['first_name'] ?>"></div>
  <div class="personal-field"><label>Last Name</label><input type="text" name="last_name" value="<?= $res['last_name'] ?>"></div>
  <div class="personal-field"><label>Gender</label><input type="text" name="gender" value="<?= $res['gender'] ?>"></div>
  <div class="personal-field"><label>Email</label><input type="email" name="email" value="<?= $res['email'] ?>"></div>
  <div class="personal-field"><label>Phone</label><input type="text" name="phone" value="<?= $res['phone'] ?>"></div>
  <div class="personal-field full-width"><label>Address</label><input type="text" name="address" value="<?= $res['address'] ?>"></div>
  <div class="personal-field"><label>Emergency Contact</label><input type="text" name="emergency_contact" value="<?= $res['emergency_contact'] ?? '' ?>"></div>
 <div class="personal-field full-width"><label>Profile Photo</label>
<?php 
$photoPath = '../uploads/'.$res['photo'];
if(!empty($res['photo']) && file_exists($photoPath)): ?>
  <p><a href="<?= $photoPath ?>" target="_blank">
      <img class="document" src="<?= $photoPath ?>" alt="Profile Photo">
  </a></p>
<?php else: ?>
  <p>No profile photo uploaded.</p>
<?php endif; ?>
</div>
</div>
<div id="Bank" class="tabcontent">
<label>Bank Name</label>
<select name="bank_name" required>
<option value="BDO" <?= ($res['bank_name']=='BDO' || !$res['bank_name'])?'selected':'' ?>>BDO</option>
<option value="BPI" <?= ($res['bank_name']=='BPI')?'selected':'' ?>>BPI</option>
<option value="Metrobank" <?= ($res['bank_name']=='Metrobank')?'selected':'' ?>>Metrobank</option>
<option value="Other" <?= ($res['bank_name']=='Other')?'selected':'' ?>>Other</option>
</select>
<label>Account Name</label>
<input type="text" name="account_name" value="<?= $res['account_name'] ?? $res['first_name'].' '.$res['last_name'] ?>" required>
<label>Account Number</label>
<input type="text" name="account_number" value="<?= $res['account_number'] ?? '' ?>" required>
</div>

<div id="Job" class="tabcontent">
  <div class="job-field"><label>Position</label><input type="text" name="position_name" value="<?= $res['position_name'] ?>"></div>
  <div class="job-field"><label>Department</label><input type="text" name="department_name" value="<?= $res['department_name'] ?>"></div>
  <div class="job-field"><label>Manager</label><input type="text" name="manager" value="<?= $res['manager'] ?>"></div>
  <div class="job-field"><label>Employment Type</label><input type="text" name="employment_type" value="<?= $res['employment_type'] ?>"></div>
  <div class="job-field"><label>Base Salary</label><input type="text" name="base_salary" value="<?= $res['base_salary'] ?>"></div>
</div>

<div id="Experience" class="tabcontent">
<div id="job_container">
<?php while($j=$job_exp->fetch_assoc()): ?>
<div><?= $j['company_name'] ?> | <?= $j['position'] ?> | <?= $j['start_date'] ?> to <?= $j['end_date'] ?></div>
<?php endwhile; ?>
</div>
</div>

<div id="School" class="tabcontent">
<div id="school_container">
<?php while($s=$school->fetch_assoc()): ?>
<div><?= $s['school_name'] ?> | <?= $s['degree'] ?> | <?= $s['graduation_year'] ?></div>
<?php endwhile; ?>
</div>
</div>

<div id="Documents" class="tabcontent">
<h3>Government Numbers</h3>
<div class="form-grid">
  <div><label>SSS Number</label><input type="text" value="<?= htmlspecialchars($docs['sss_no'] ?? '') ?>" readonly></div>
  <div><label>PhilHealth Number</label><input type="text" value="<?= htmlspecialchars($docs['philhealth_no'] ?? '') ?>" readonly></div>
  <div><label>PAG-IBIG Number</label><input type="text" value="<?= htmlspecialchars($docs['pagibig_no'] ?? '') ?>" readonly></div>
  <div><label>TIN</label><input type="text" value="<?= htmlspecialchars($docs['tin_no'] ?? '') ?>" readonly></div>
</div>
<h3>Supporting Documents</h3>
<div class="form-grid">
<?php
$doc_fields = ['nbi_clearance'=>'NBI Clearance','birth_certificate'=>'Birth Certificate','diploma'=>'Diploma','tor'=>'Transcript of Records','barangay_clearance'=>'Barangay Clearance','police_clearance'=>'Police Clearance'];
foreach($doc_fields as $field => $label):
$filePath = $docs[$field] ?? '';
?>
<div>
<label><?= $label ?></label>
<?php if(!empty($filePath) && file_exists($filePath)):
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if(in_array($ext, ['jpg','jpeg','png','gif'])): ?>
<a href="<?= htmlspecialchars($filePath) ?>" target="_blank"><img src="<?= htmlspecialchars($filePath) ?>" style="max-width:200px;border-radius:6px;"></a>
<?php else: ?>
<embed src="<?= htmlspecialchars($filePath) ?>" width="400" height="300" type="application/pdf">
<?php endif; else: ?><span>Not uploaded</span><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</div>

<button type="submit" name="update_profile" class="update-btn">Update Profile</button>
</form>
</div>

<script>
function openTab(evt, tabName) {
    let tabcontent = document.getElementsByClassName("tabcontent");
    for(let i=0;i<tabcontent.length;i++) tabcontent[i].style.display="none";
    let tablinks = document.getElementsByClassName("tablinks");
    for(let i=0;i<tablinks.length;i++) tablinks[i].classList.remove("active");
    document.getElementById(tabName).style.display="block";
    evt.currentTarget.classList.add("active");
}
window.onload=function(){ document.getElementsByClassName('tablinks')[0].click(); }
</script>
</body>
</html>
