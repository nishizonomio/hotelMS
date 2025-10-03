<?php
require_once(__DIR__ . '/../utils/db.php');

$staffStmt = $pdo->query("SELECT staff_id, first_name, last_name FROM staff");
$staffList = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

$complaint_id = $_GET['complaint_id'] ?? '';
$guest_name = $_GET['guest_name'] ?? '';
$complaint_text = $_GET['complaint_text'] ?? '';
$resolution = $_GET['resolution'] ?? '';
$status = $_GET['status'] ?? 'Open';
$message = $_GET['message'] ?? '';

$complaint_options = ['Wrong Order', 'Late Delivery', 'Damaged Product', 'Other'];
$resolution_options = ['Refund', 'Replacement', 'Apology', 'Other'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>File a Complaint</title>
<link rel="stylesheet" href="file_a_complaint.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1><?= $complaint_id ? 'Edit Complaint' : 'File a Complaint' ?></h1>
    </header>

    <?php if($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

  <form method="POST" action="/hotel/kitchen/complaints/submit_complaint.php" class="add-form">
      <input type="hidden" name="complaint_id" value="<?= htmlspecialchars($complaint_id) ?>">

      <label>Guest Name</label>
      <input type="text" name="guest_name" placeholder="Guest Name" value="<?= htmlspecialchars($guest_name) ?>" required>

      <label>Complaint</label>
      <select name="complaint_select" onchange="document.getElementById('complaint_text').value=this.value;">
        <option value="">--Select Complaint--</option>
        <?php foreach($complaint_options as $opt): ?>
        <option value="<?= htmlspecialchars($opt) ?>" <?= $complaint_text === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
        <?php endforeach; ?>
      </select>
      <textarea id="complaint_text" name="complaint_text" placeholder="Complaint" rows="5"><?= htmlspecialchars($complaint_text) ?></textarea>

      <label>Resolution</label>
      <select name="resolution_select" onchange="document.getElementById('resolution_text').value=this.value;">
        <option value="">--Select Resolution--</option>
        <?php foreach($resolution_options as $opt): ?>
        <option value="<?= htmlspecialchars($opt) ?>" <?= $resolution === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
        <?php endforeach; ?>
      </select>
      <textarea id="resolution_text" name="resolution" placeholder="Resolution (optional)" rows="3"><?= htmlspecialchars($resolution) ?></textarea>

      <label>Status</label>
      <select name="status" required>
        <option value="Open" <?= $status === 'Open' ? 'selected' : '' ?>>Open</option>
        <option value="In Progress" <?= $status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
        <option value="Resolved" <?= $status === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
      </select>

      <label>Assign Staff</label>
      <select name="assigned_staff" required>
        <option value="">Select Staff</option>
        <?php foreach($staffList as $s): ?>
          <option value="<?= $s['staff_id'] ?>"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></option>
        <?php endforeach; ?>
      </select>

      <button type="submit"><i class="fas fa-paper-plane"></i> <?= $complaint_id ? 'Update' : 'Submit' ?></button>
    </form>

    <a href="complaints.php" class="module back cancel-btn">
      <i class="fas fa-times"></i>
      <span>Cancel</span>
    </a>
  </div>
</div>
</body>
</html>
