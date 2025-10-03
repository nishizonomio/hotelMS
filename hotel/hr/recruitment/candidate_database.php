<?php
include '../db.php';

$candidate_sql = "SELECT * FROM recruitment ORDER BY applied_date DESC";
$result = $conn->query($candidate_sql);
$candidates = [];
if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $candidates[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Candidate Database</title>
<link rel="stylesheet" href="../css/candidate_database.css">
<style>
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th, td {
    border: 1px solid #ccc;
    padding: 10px;
    text-align: left;
}
th {
    background-color: #f4f4f4;
}
.status-pending { color: orange; font-weight: bold; }
.status-passed { color: green; font-weight: bold; }
.status-failed { color: red; font-weight: bold; }
</style>
</head>
<body>
<div class="overlay">
<header>
<h1>Candidate Database</h1>
</header>
<nav>
<a href="../hr_dashboard.php">&larr; Back to HR Dashboard</a>
<a href="./hotellavista_jobs.php">Job Posting</a>
<a href="./interview_scheduling.php">Applicant Interview Schedules</a>
<a href="./shortlisted.php">Shortlisted</a>
<a href="./offer_letter_management.php">Offer Letter Management</a>
</nav>


<?php if(!empty($candidates)): ?>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Birth Date</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Applied Position</th>
            <th>Applied Date</th>
            <th>Status</th>
            <th>Interview</th>
            <th>Resume</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($candidates as $cand): ?>
        <tr>
            <td><?php echo htmlspecialchars($cand['candidate_id']); ?></td>
            <td><?php echo htmlspecialchars($cand['first_name'] . ' ' . $cand['last_name']); ?></td>
            <td><?php echo htmlspecialchars($cand['birth_date']); ?></td>
            <td><?php echo htmlspecialchars($cand['email']); ?></td>
            <td><?php echo htmlspecialchars($cand['phone']); ?></td>
            <td><?php echo htmlspecialchars($cand['applied_position']); ?></td>
            <td><?php echo htmlspecialchars($cand['applied_date']); ?></td>
            <td class="status-<?php echo strtolower($cand['status'] ?: 'pending'); ?>">
                <?php echo htmlspecialchars($cand['status'] ?: 'Pending'); ?>
            </td>
            <td><?php echo htmlspecialchars($cand['interview_datetime'] ?: '-'); ?></td>
            <td>
                <?php if(!empty($cand['resume'])): ?>
                    <a href="/hotel/hr/uploads/<?php echo htmlspecialchars($cand['resume']); ?>" target="_blank">View</a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="text-align:center;">No candidates found.</p>
<?php endif; ?>

</div>
</body>
</html>
<?php $conn->close(); ?>
