<?php
include '../db.php';

$position = isset($_GET['position']) ? $conn->real_escape_string($_GET['position']) : '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id'], $_POST['action'])){
    $candidate_id = $conn->real_escape_string($_POST['candidate_id']);
    if($_POST['action'] === 'reject'){
        $conn->query("UPDATE recruitment SET status='Rejected' WHERE candidate_id='$candidate_id'");
    } elseif($_POST['action'] === 'fit'){
        $interview_datetime = date('Y-m-d H:i:s', strtotime('+1 day 10:00'));
        $conn->query("UPDATE recruitment SET status='Fit to the Job', stage='Interview Scheduled', interview_datetime='$interview_datetime', assessment_result='Pending' WHERE candidate_id='$candidate_id'");
    }
    header("Location: view_applicants.php?position=".$position);
    exit;
}

$new_sql = "SELECT * FROM recruitment 
            WHERE applied_position='$position' 
            AND (status IS NULL OR status='') 
            ORDER BY applied_date DESC";
$new_result = $conn->query($new_sql);

$reviewed_sql = "SELECT * FROM recruitment 
                 WHERE applied_position='$position' 
                 AND status IN ('Fit to the Job','Rejected') 
                 ORDER BY applied_date DESC";
$reviewed_result = $conn->query($reviewed_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Applicants for <?php echo htmlspecialchars($position); ?></title>
<link rel="stylesheet" href="../css/view_applicants.css">
</head>
<body>
<header>
<h1>Applicants for <?php echo htmlspecialchars($position); ?></h1>
</header>
<div class="container">
<a href="../recruitment/recruitment.php" class="back">&larr; Back</a>

<div class="tab">
<button class="tablinks" onclick="openTab(event,'New')">New Applicants</button>
<button class="tablinks" onclick="openTab(event,'Reviewed')">Reviewed Applicants</button>
</div>

<div id="New" class="tabcontent">
<table>
<tr>
<th>Candidate ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Resume</th><th>Actions</th>
</tr>
<?php if($new_result && $new_result->num_rows>0): while($row=$new_result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['candidate_id']); ?></td>
<td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']); ?></td>
<td><?= htmlspecialchars($row['email']); ?></td>
<td><?= htmlspecialchars($row['phone']); ?></td>
<td><?= htmlspecialchars($row['status'] ?? 'New'); ?></td>
<td><?php if($row['resume']): ?><a class="resume" href="/hotel/hr/uploads/<?= htmlspecialchars($row['resume']); ?>" target="_blank">View</a><?php else: ?>N/A<?php endif; ?></td>
<td>
<form method="post" style="display:inline-block;">
<input type="hidden" name="candidate_id" value="<?= $row['candidate_id']; ?>">
<button type="submit" name="action" value="fit" class="actionBtn fit">Fit to the Job</button>
<button type="submit" name="action" value="reject" class="actionBtn reject">Reject</button>
</form>
</td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="7" style="text-align:center;">No new applicants.</td></tr>
<?php endif; ?>
</table>
</div>

<div id="Reviewed" class="tabcontent">
<table>
<tr>
<th>Candidate ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Resume</th><th>Stage</th><th>Interview Date</th><th>Assessment Result</th>
</tr>
<?php if($reviewed_result && $reviewed_result->num_rows>0): while($row=$reviewed_result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['candidate_id']); ?></td>
<td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']); ?></td>
<td><?= htmlspecialchars($row['email']); ?></td>
<td><?= htmlspecialchars($row['phone']); ?></td>
<td><?= htmlspecialchars($row['status']); ?></td>
<td><?php if($row['resume']): ?><a class="resume" href="/hotel/hr/uploads/<?= htmlspecialchars($row['resume']); ?>" target="_blank">View</a><?php else: ?>N/A<?php endif; ?></td>
<td><?= htmlspecialchars($row['stage'] ?? 'Resume Review'); ?></td>
<td><?= htmlspecialchars($row['interview_datetime'] ?? 'Not Scheduled'); ?></td>
<td><?= htmlspecialchars($row['assessment_result'] ?? 'Pending'); ?></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="9" style="text-align:center;">No reviewed applicants.</td></tr>
<?php endif; ?>
</table>
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
</script>

</body>
</html>
<?php $conn->close(); ?>
