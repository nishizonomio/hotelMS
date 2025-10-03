<?php
include '../db.php';

$success_msg = '';
if(isset($_GET['success'])){
    $success_msg = "Interview scheduled successfully!";
}

$sql = "SELECT * FROM recruitment WHERE status='Fit to the Job' AND (interview_datetime IS NULL OR interview_datetime='') ORDER BY applied_date DESC";
$result = $conn->query($sql);

$scheduled_sql = "SELECT * FROM recruitment WHERE status='Fit to the Job' AND interview_datetime IS NOT NULL AND interview_datetime<>'' ORDER BY interview_datetime ASC";
$scheduled_result = $conn->query($scheduled_sql);

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id'], $_POST['interview_datetime'])){
    $candidate_id = $conn->real_escape_string($_POST['candidate_id']);
    $interview_datetime = $conn->real_escape_string($_POST['interview_datetime']);
    $send_email = isset($_POST['send_email']);
    $send_sms = isset($_POST['send_sms']);

    $conn->query("UPDATE recruitment SET interview_datetime='$interview_datetime' WHERE candidate_id='$candidate_id'");

    if($send_email){
        $to = $_POST['email'] ?? '';
        if($to) mail($to, "Interview Schedule", "Your interview is scheduled on $interview_datetime.");
    }

    if($send_sms){
        $phone = $_POST['phone'] ?? '';
    }

    header("Location: recruitment.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Interview Scheduling</title>
<link rel="stylesheet" href="interview_scheduling.css">
<link rel="stylesheet" href="../css/interview_scheduling.css">

</head>
<body>
<header>
  <div class="header-container">
    <h1>Applicant Interview Schedules</h1>
  </div>
</header>

<div class="container">
<a href="recruitment.php" class="back">&larr; Back</a>

<?php if($success_msg): ?>
<div class="successMsg" id="successMsg"><?php echo $success_msg; ?></div>
<script>
setTimeout(()=>{document.getElementById('successMsg').style.display='none';},4000);
</script>
<?php endif; ?>

<table>
<tr>
<th>Candidate ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Interview Date</th><th>Action</th>
</tr>
<?php if($scheduled_result && $scheduled_result->num_rows>0): while($row=$scheduled_result->fetch_assoc()): ?>
<tr>
<td><?php echo htmlspecialchars($row['candidate_id']); ?></td>
<td><?php echo htmlspecialchars($row['first_name'].' '.$row['last_name']); ?></td>
<td><?php echo htmlspecialchars($row['email']); ?></td>
<td><?php echo htmlspecialchars($row['phone']); ?></td>
<td><?php echo htmlspecialchars($row['status']); ?></td>
<td><?php echo htmlspecialchars($row['interview_datetime']); ?></td>
<td>
<button class="rescheduleBtn" 
        data-id="<?php echo $row['candidate_id']; ?>" 
        data-datetime="<?php echo $row['interview_datetime']; ?>">Reschedule</button>
</td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="7" style="text-align:center;">No scheduled interviews.</td></tr>
<?php endif; ?>
</table>

<div id="rescheduleModal" class="modal">
<div class="modal-content">
<span class="close">&times;</span>
<h3>Reschedule Interview</h3>
<form method="post">
<input type="hidden" name="candidate_id" id="modal_candidate_id">
<input type="datetime-local" name="interview_datetime" id="modal_datetime" required><br>
<label class="checkboxLabel"><input type="checkbox" name="send_email"> Send Email</label>
<label class="checkboxLabel"><input type="checkbox" name="send_sms"> Send SMS</label><br>
<button type="submit" class="scheduleBtn">Update</button>
</form>
</div>
</div>

<script>
const modal=document.getElementById('rescheduleModal');
const closeBtn=document.querySelector('.close');
const rescheduleBtns=document.querySelectorAll('.rescheduleBtn');
const modalCandidateInput=document.getElementById('modal_candidate_id');
const modalDatetimeInput=document.getElementById('modal_datetime');

rescheduleBtns.forEach(btn=>{
    btn.addEventListener('click',()=>{
        modal.style.display='block';
        modalCandidateInput.value=btn.getAttribute('data-id');
        let dt=btn.getAttribute('data-datetime');
        if(dt) modalDatetimeInput.value=new Date(dt).toISOString().slice(0,16);
    });
});

closeBtn.addEventListener('click',()=>{ modal.style.display='none'; });
window.addEventListener('click',(e)=>{ if(e.target==modal) modal.style.display='none'; });
</script>

<?php $conn->close(); ?>
