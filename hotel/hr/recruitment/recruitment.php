<?php
include '../db.php';

$vacant_sql = "
    SELECT 
        p.position_name,
        GROUP_CONCAT(DISTINCT p.department_name ORDER BY p.department_name SEPARATOR ', ') AS departments,
        SUM(p.required_count) AS total_required,
        (SUM(p.required_count) - IFNULL(
            (SELECT COUNT(*) FROM staff s 
             WHERE s.position_name = p.position_name 
             AND s.employment_status IN ('Active','Probation')),0)
        ) AS vacant_slots
    FROM positions p
    GROUP BY p.position_name
    HAVING vacant_slots > 0
";
$result = $conn->query($vacant_sql);

$positions = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $position_name = $conn->real_escape_string($row['position_name']);
        $applicant_result = $conn->query("
            SELECT COUNT(*) AS count 
            FROM recruitment 
            WHERE applied_position='$position_name' 
            AND (status IS NULL OR status='')
        ");
        $row['app_count'] = $applicant_result ? (int)$applicant_result->fetch_assoc()['count'] : 0;
        $positions[] = $row;
    }
    usort($positions, function($a, $b) {
        if($a['app_count'] == $b['app_count']) return 0;
        return ($a['app_count'] > 0 ? -1 : 1);
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HR Recruitment</title>
<link rel="stylesheet" href="../css/recruitment.css">
</head>
<body>
<div class="overlay">
<header>
<h1>HR Recruitment & Hiring</h1>
</header>
<nav>
<a href="../hr_dashboard.php">&larr; Back to HR Dashboard</a>
<a href="./hotellavista_jobs.php">Job Posting</a>
<a href="./interview_scheduling.php">Applicant Interview Schedules</a>
<a href="./shortlisted.php">Shortlisted</a>
<a href="./offer_letter_management.php">Offer Letter Management</a>
<a href="./candidate_database.php">Candidate Database</a>
</nav>

<h2>Available Vacant Positions</h2>
<div class="grid">
<?php if(!empty($positions)): ?>
    <?php foreach($positions as $row): ?>
        <div class="card">
            <div class="applicants">New Applicants: <a class="app-link" href="view_applicants.php?position=<?php echo urlencode($row['position_name']); ?>"><?php echo $row['app_count']; ?></a></div>
            <h3><?php echo htmlspecialchars($row['position_name']); ?></h3>
            <div class="departments"><strong>Departments:</strong> <?php echo htmlspecialchars($row['departments']); ?></div>
            <p><strong>Needed:</strong> <?php echo $row['total_required']; ?></p>
            <p><strong>Vacant Slots:</strong> <?php echo $row['vacant_slots']; ?></p>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align:center;">No vacant positions at the moment.</p>
<?php endif; ?>
</div>
</div>
</body>
</html>
<?php $conn->close(); ?>
