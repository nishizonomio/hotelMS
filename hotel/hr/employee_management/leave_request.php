<?php
include '../db.php';

$leave_reasons = ['Vacation','Sick','Maternity/Paternity','Emergency','Personal','Training','Other'];

$search = '';
$employees = [];
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $emp_result = $conn->query("SELECT * FROM staff WHERE CONCAT(first_name,' ',last_name) LIKE '%$search%' OR staff_id LIKE '%$search%' LIMIT 5");
    if ($emp_result && $emp_result->num_rows > 0) {
        while ($row = $emp_result->fetch_assoc()) {
            $employees[] = $row;
        }
    }
}

$errors = [];
if (isset($_POST['file_leave'])) {
    $staff_id = $_POST['staff_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    if ($end_date < $start_date) {
        $errors[] = "End date cannot be earlier than start date.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO leave_requests (staff_id, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("ssss", $staff_id, $start_date, $end_date, $reason);
        $stmt->execute();
        $stmt->close();
        header("Location: leave_request.php");
        exit;
    }
}

if (isset($_POST['action']) && isset($_POST['leave_id'])) {
    $action = $_POST['action'];
    $leave_id = $_POST['leave_id'];
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE leave_requests SET status='Approved' WHERE id=?");
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE leave_requests SET status='Rejected' WHERE id=?");
    }
    if (isset($stmt)) {
        $stmt->bind_param("i", $leave_id);
        $stmt->execute();
        $stmt->close();
    }
}

$leave_result = $conn->query("SELECT l.id, s.first_name, s.last_name, s.staff_id, s.position_name, l.start_date, l.end_date, l.status, l.reason 
                              FROM leave_requests l 
                              JOIN staff s ON l.staff_id = s.staff_id
                              ORDER BY l.start_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Leave Requests</title>
<link rel="stylesheet" href="../css/leave_request.css">
<link rel="stylesheet" href="fontawesome-free-7.0.1-web/css/all.min.css">

</head>
<body>
<div class="container">
<a href="hr_employee_management.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
<div class="container-section">
<h1>Leave Request</h1>
<form method="GET" style="margin-bottom:20px;">
<input type="text" name="search" placeholder="Search by employee name or ID..." value="<?= htmlspecialchars($search) ?>">
<button type="submit">Search</button>
</form>
<?php if (!empty($employees)): ?>
<div class="search-results-container">
<?php foreach ($employees as $employee): ?>
<div class="search-result">
<h3><?= htmlspecialchars($employee['first_name'].' '.$employee['last_name']) ?></h3>
<p>Employee ID: <?= htmlspecialchars($employee['staff_id']) ?></p>
<p>Position: <?= htmlspecialchars($employee['position_name']) ?></p>
<p>Department: <?= htmlspecialchars($employee['department_name']) ?></p>
<button class="openModalBtn" data-empid="<?= $employee['staff_id'] ?>">File Leave Request</button>
</div>
<?php endforeach; ?>
</div>
<div id="leaveModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeModal">&times;</span>
    <h3>Leave Request Form</h3>
    <?php if (!empty($errors)): foreach($errors as $err): ?>
    <div class="error"><?= htmlspecialchars($err) ?></div>
    <?php endforeach; endif; ?>
    <form method="POST">
      <input type="hidden" name="staff_id" id="modal_staff_id">
      <label>Start Date</label>
      <input type="date" name="start_date" required>
      <label>End Date</label>
      <input type="date" name="end_date" required>
      <label>Reason</label>
      <select name="reason" required>
        <option value="">Select Reason</option>
        <?php foreach($leave_reasons as $reason_option): ?>
          <option value="<?= $reason_option ?>"><?= $reason_option ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" name="file_leave">Submit Leave Request</button>
    </form>
  </div>
</div>
<script>
var modal = document.getElementById("leaveModal");
var span = document.getElementById("closeModal");
span.onclick = function() { modal.style.display = "none"; }
window.onclick = function(event) { if (event.target == modal) modal.style.display = "none"; }
document.querySelectorAll('.openModalBtn').forEach(function(btn){
    btn.onclick = function(){
        document.getElementById('modal_staff_id').value = this.dataset.empid;
        modal.style.display = "block";
    }
});
</script>
<?php elseif($search): ?>
<p>No employee found matching "<?= htmlspecialchars($search) ?>"</p>
<?php endif; ?>
</div>
<div class="container-section">
<h1>All Leave Requests</h1>
<?php if ($leave_result && $leave_result->num_rows > 0): ?>
<table>
<thead>
<tr>
<th>Employee</th>
<th>Employee ID</th>
<th>Position</th>
<th>Start Date</th>
<th>End Date</th>
<th>Status</th>
<th>Reason</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php while($row = $leave_result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></td>
<td><?= htmlspecialchars($row['staff_id']) ?></td>
<td><?= htmlspecialchars($row['position_name']) ?></td>
<td><?= htmlspecialchars($row['start_date']) ?></td>
<td><?= htmlspecialchars($row['end_date']) ?></td>
<td><span class="status <?= $row['status'] ?>"><?= $row['status'] ?></span></td>
<td><?= htmlspecialchars($row['reason']) ?></td>
<td>
<form method="POST" style="display:inline;">
<input type="hidden" name="leave_id" value="<?= $row['id'] ?>">
<button type="submit" name="action" value="approve" class="action-btn approve-btn">Approve</button>
<button type="submit" name="action" value="reject" class="action-btn reject-btn">Reject</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php else: ?>
<p>No leave requests found.</p>
<?php endif; ?>
</div>
</div>
</body>
</html>
