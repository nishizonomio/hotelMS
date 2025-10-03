<?php
session_start();
include '../db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: employee_login.php");
    exit;
}

$staff_id = $_SESSION['staff_id'];
$res = $conn->query("SELECT * FROM staff WHERE staff_id='$staff_id'")->fetch_assoc();
if (!$res) die("Employee not found");

$stmt_school = $conn->prepare("SELECT school_name, degree, graduation_year FROM school_attainment WHERE staff_id=? ORDER BY id DESC");
$stmt_school->bind_param("s", $res['staff_id']);
$stmt_school->execute();
$school = $stmt_school->get_result();

$stmt_job = $conn->prepare("SELECT company_name, position, start_date, end_date FROM job_experience WHERE staff_id=? ORDER BY id DESC");
$stmt_job->bind_param("s", $res['staff_id']);
$stmt_job->execute();
$job_exp = $stmt_job->get_result();


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile</title>
<link rel="stylesheet" href="../css/employee_profile.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<a href="homepage.php" class="back-button">&larr; Back</a>
<div class="container">
 <div class="profile-photo" id="profilePreview">
<?php 
$photoPath = '../uploads/'.$res['photo'];
if($res['photo'] && file_exists($photoPath)) {
    echo '<img src="'.$photoPath.'" alt="Profile Photo">';
} else {
    $initials = strtoupper(substr($res['first_name'],0,1).substr($res['last_name'],0,1));
    echo $initials;
}
?>
</div>

  <h1><?= htmlspecialchars($res['first_name'] . ' ' . $res['last_name']) ?></h1>

  <div class="tab">
    <button class="tablinks" onclick="openTab(event,'Personal')">Personal Info</button>
    <button class="tablinks" onclick="openTab(event,'Bank')">Bank Info</button>
    <button class="tablinks" onclick="openTab(event,'Job')">Job Info</button>
    <button class="tablinks" onclick="openTab(event,'Experience')">Job Experience</button>
    <button class="tablinks" onclick="openTab(event,'School')">School</button>
  </div>

  <div class="tab-container">
    <form method="POST" enctype="multipart/form-data" id="profileForm">
      <div id="Personal" class="tabcontent">
        <label>Employee ID</label><input type="text" value="<?= $res['staff_id'] ?>" readonly>
        <label>First Name</label><input type="text" name="first_name" value="<?= $res['first_name'] ?>" required>
        <label>Last Name</label><input type="text" name="last_name" value="<?= $res['last_name'] ?>" required>
        <label>Gender</label><input type="text" value="<?= $res['gender'] ?>" readonly>
        <label>Email</label><input type="email" name="email" value="<?= $res['email'] ?>" required>
        <label>Phone</label><input type="text" name="phone" value="<?= $res['phone'] ?>" required>
        <label>Address</label><input type="text" name="address" value="<?= $res['address'] ?>" required>
        <label>Emergency Contact</label><input type="text" name="emergency_contact" value="<?= $res['emergency_contact'] ?? '' ?>">
        <label>Profile Photo</label><input type="file" name="photo" onchange="previewPhoto(event)">
        <label>ID Proof</label><input type="file" name="id_proof">
        <?php if($res['id_proof'] && file_exists('../uploads/'.$res['id_proof'])): ?>
<p>Current: <a href="../uploads/<?= $res['id_proof'] ?>" target="_blank">View ID Proof</a></p>
<?php endif; ?>
</div>

      <div id="Bank" class="tabcontent">
        <label>Bank Name</label><input type="text" value="<?= $res['bank_name'] ?>" readonly>
        <label>Account Name</label><input type="text" value="<?= $res['account_name'] ?>" readonly>
        <label>Account Number</label><input type="text" value="<?= $res['account_number'] ?>" readonly>
      </div>

     <div id="Job" class="tabcontent">
    <table class="info-table" cellpadding="5" cellspacing="0">
        <tr>
            <th>Position</th>
            <td><?= htmlspecialchars($res['position_name']) ?></td>
        </tr>
        <tr>
            <th>Department</th>
            <td><?= htmlspecialchars($res['department_name']) ?></td>
        </tr>
        <tr>
            <th>Manager</th>
            <td><?= htmlspecialchars($res['manager']) ?></td>
        </tr>
        <tr>
            <th>Employment Type</th>
            <td><?= htmlspecialchars($res['employment_type']) ?></td>
        </tr>
        <tr>
            <th>Base Salary</th>
            <td><?= htmlspecialchars($res['base_salary']) ?></td>
        </tr>
        <tr>
       <th>Contract</th>
<td>
<?php 
$contractFile = $res['contract_file'] ?? '';
$contractPath = '';

if ($contractFile) {
    if (file_exists($contractFile)) {
        $contractPath = $contractFile;
    } elseif (file_exists('../contract/' . basename($contractFile))) {
        $contractPath = '../contract/' . basename($contractFile);
    } elseif (file_exists('../contracts/' . basename($contractFile))) {
        $contractPath = '../contracts/' . basename($contractFile);
    }
}

if ($contractPath): ?>
    <a href="<?= htmlspecialchars($contractPath) ?>" target="_blank">View Contract</a>
<?php else: ?>
    No file uploaded.
<?php endif; ?>
</td>

    </table>
</div>

<div id="Experience" class="tabcontent">
    <div class="center-buttons">
        <button type="button" onclick="openModal('jobModal')">Add Job Experience</button>
    </div>
    <table id="job_container" border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Company Name</th>
                <th>Position</th>
                <th>Start Date</th>
                <th>End Date</th>
            </tr>
        </thead>
        <tbody>
       <?php while($j = $job_exp->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($j['company_name']) ?></td>
    <td><?= htmlspecialchars($j['position']) ?></td>
    <td><?= htmlspecialchars($j['start_date']) ?></td>
    <td><?= htmlspecialchars($j['end_date']) ?></td>
</tr>
<?php endwhile; ?>

        </tbody>
    </table>
</div>

<div id="School" class="tabcontent">
    <div class="center-buttons">
        <button type="button" onclick="openModal('schoolModal')">Add School</button>
    </div>
    <table id="school_container" border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>School Name</th>
                <th>Degree</th>
                <th>Graduation Year</th>
            </tr>
        </thead>
        <tbody>
          <?php while($s = $school->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($s['school_name']) ?></td>
    <td><?= htmlspecialchars($s['degree']) ?></td>
    <td><?= htmlspecialchars($s['graduation_year']) ?></td>
</tr>
<?php endwhile; ?>

        </tbody>
    </table>
</div>

<div id="jobModal" class="modal">
    <div class="modal-content">
        <span onclick="closeModal('jobModal')" class="close">&times;</span>
        <h3>Add Job Experience</h3>
        <input type="text" id="job_company" placeholder="Company Name" required>
        <input type="text" id="job_position" placeholder="Position" required>
        <input type="date" id="job_start" required>
        <input type="date" id="job_end">
        <button type="button" onclick="saveJob()">Save</button>
    </div>
</div>

<div id="schoolModal" class="modal">
    <div class="modal-content">
        <span onclick="closeModal('schoolModal')" class="close">&times;</span>
        <h3>Add School</h3>
        <input type="text" id="school_name" placeholder="School Name" required>
        <input type="text" id="school_degree" placeholder="Degree" required>
        <input type="number" id="school_grad" placeholder="Graduation Year" required>
        <button type="button" onclick="saveSchool()">Save</button>
    </div>
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

function previewPhoto(event) {
    const preview=document.getElementById('profilePreview');
    const file=event.target.files[0];
    if(file){
        const reader=new FileReader();
        reader.onload=function(e){preview.innerHTML=`<img src="${e.target.result}" alt="Profile Photo">`;};
        reader.readAsDataURL(file);
    }
}

function openModal(id){ document.getElementById(id).style.display='block'; }
function closeModal(id){ document.getElementById(id).style.display='none'; }

function saveJob(){
    let company=$("#job_company").val();
    let position=$("#job_position").val();
    let start=$("#job_start").val();
    let end=$("#job_end").val();
    let staff_id = "<?= $res['staff_id'] ?>";
    if(company && position && start){
        $.post("save_job.php", {
            staff_id: staff_id,
            company_name: company,
            position: position,
            start_date: start,
            end_date: end
        }, function(data){
            $("#job_container tbody").prepend(`<tr>
                <td>${company}</td>
                <td>${position}</td>
                <td>${start}</td>
                <td>${end}</td>
            </tr>`);
            $("#job_company,#job_position,#job_start,#job_end").val('');
            closeModal('jobModal');
        });
    }
}

function saveSchool(){
    let name=$("#school_name").val();
    let degree=$("#school_degree").val();
    let grad=$("#school_grad").val();
    let staff_id = "<?= $res['staff_id'] ?>";
    if(name && degree && grad){
        $.post("save_school.php", {
            staff_id: staff_id,
            school_name: name,
            degree: degree,
            graduation_year: grad
        }, function(data){
            $("#school_container tbody").prepend(`<tr>
                <td>${name}</td>
                <td>${degree}</td>
                <td>${grad}</td>
            </tr>`);
            $("#school_name,#school_degree,#school_grad").val('');
            closeModal('schoolModal');
        });
    }
}

$('#profileForm input').on('change', function(){
    let formData = new FormData();
    $('#profileForm input').each(function(){
        if($(this).attr('type') !== 'file'){
            formData.append($(this).attr('name'), $(this).val());
        }
    });
    if($('input[name="photo"]')[0].files[0]) formData.append('photo', $('input[name="photo"]')[0].files[0]);
    if($('input[name="id_proof"]')[0].files[0]) formData.append('id_proof', $('input[name="id_proof"]')[0].files[0]);

    $.ajax({
        url:'update_profile.php',
        type:'POST',
        data: formData,
        processData:false,
        contentType:false
    });
});
</script>


</body>
</html>
<?php $conn->close(); ?>
