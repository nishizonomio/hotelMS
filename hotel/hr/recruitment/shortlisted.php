<?php
include '../db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id'])){
    $candidate_id = $conn->real_escape_string($_POST['candidate_id']);
    $interview_result = $conn->real_escape_string($_POST['interview_result'] ?? '');
    $assessment_result = $conn->real_escape_string($_POST['assessment_result'] ?? '');

    $row = $conn->query("SELECT assessment_retake FROM recruitment WHERE candidate_id='$candidate_id'")->fetch_assoc();
    $retake = $row['assessment_retake'];

    if($interview_result === 'Failed'){
        $candidate_status = 'Rejected';
        $retake = 0;
    } elseif($interview_result === 'Passed' && $assessment_result === 'Passed'){
        $candidate_status = 'Consider';
        $retake = 0;
    } elseif($interview_result === 'Passed' && $assessment_result === 'Failed'){
        if($retake == 0){
            $candidate_status = 'Consider';
            $retake = 1;
        } else {
            $candidate_status = 'Rejected';
            $retake = 0;
        }
    } else {
        $candidate_status = 'Consider';
    }

    $conn->query("UPDATE recruitment SET status='$candidate_status', interview_result='$interview_result', assessment_result='$assessment_result', assessment_retake='$retake' WHERE candidate_id='$candidate_id'");
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

$sql = "SELECT * FROM recruitment WHERE status='Fit to the Job' AND interview_datetime IS NOT NULL ORDER BY applied_date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<link rel="stylesheet" href="../css/shortlisted.css">
</head>
<body>
<header>
<h1>Interview And Assessment Results</h1>
</header>
<nav>
<a href="../hr_dashboard.php">&larr; Back to HR Dashboard</a>
<a href="./hotellavista_jobs.php">Job Posting</a>
<a href="./interview_scheduling.php">Applicant Interview Schedules</a>
<a href="./shortlisted.php">Shortlisted</a>
<a href="./offer_letter_management.php">Offer Letter Management</a>
</nav>

<div class="container">
<table>
<tr>
<th>Candidate ID</th>
<th>First Name</th>
<th>Last Name</th>
<th>Email</th>
<th>Phone</th>
<th>Applied Position</th>
<th>Applied Date</th>
<th>Status</th>
<th>Resume</th>
<th>Interview Result</th>
<th>Assessment Result</th>
<th>Save</th>
</tr>
<?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['candidate_id']) ?></td>
<td><?= htmlspecialchars($row['first_name']) ?></td>
<td><?= htmlspecialchars($row['last_name']) ?></td>
<td><?= htmlspecialchars($row['email']) ?></td>
<td><?= htmlspecialchars($row['phone']) ?></td>
<td><?= htmlspecialchars($row['applied_position']) ?></td>
<td><?= htmlspecialchars($row['applied_date']) ?></td>
<td><?= htmlspecialchars($row['status']) ?></td>
<td>
<?php 
$resumePath = !empty($row['resume']) ? 'http://localhost/hotel/hr/uploads/' . basename($row['resume']) : '';
if($resumePath): ?>
<a class="resume" href="<?= htmlspecialchars($resumePath) ?>" target="_blank">View Resume</a>
<?php else: ?>
N/A
<?php endif; ?>
</td>
<td>
<form method="post" class="inlineForm">
<input type="hidden" name="candidate_id" value="<?= $row['candidate_id'] ?>">
<select name="interview_result">
<option value="">Select Interview</option>
<option value="Passed" <?= $row['interview_result']==='Passed'?'selected':'' ?>>Passed</option>
<option value="Failed" <?= $row['interview_result']==='Failed'?'selected':'' ?>>Failed</option>
</select>
</td>
<td>
<select name="assessment_result">
<option value="">Select Assessment</option>
<option value="Passed" <?= $row['assessment_result']==='Passed'?'selected':'' ?>>Passed</option>
<option value="Failed" <?= $row['assessment_result']==='Failed'?'selected':'' ?>>Failed</option>
</select>
</td>
<td>
<button type="submit" class="saveBtn">Save</button>
</form>
</td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="12" style="text-align:center;">No scheduled applicants yet.</td></tr>
<?php endif; ?>
</table>
</div>
</body>
</html>
<?php $conn->close(); ?>
