<?php
require_once(__DIR__ . '/../utils/db.php');

if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM complaints WHERE complaint_id = ?");
    $stmt->execute([$delete_id]);
    header("Location: complaints.php?success=1");
    exit;
}

$guestFilter = $_GET['guest_name'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$query = "SELECT 
            complaint_id, 
            guest_name, 
            date_filed AS complaint_date, 
            complaint_text, 
            status
          FROM complaints
          WHERE 1=1";
$params = [];

if ($guestFilter) {
    $query .= " AND guest_name LIKE ?";
    $params[] = "%$guestFilter%";
}

if ($statusFilter) {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY date_filed DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Complaints</title>
<link rel="stylesheet" href="recipes.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1>All Complaints</h1>
      <p>Recent Complaints Submitted</p>
    </header>

    <form method="GET" class="search-form" style="justify-content:center; flex-wrap:wrap; gap:8px; margin-bottom:20px;">
      <a href="order_reports.php" class="module-btn"><i class="fas fa-arrow-left"></i> Back to Order Reports</a>
      <input type="text" name="guest_name" placeholder="Search by Guest" value="<?= htmlspecialchars($guestFilter) ?>">
      <select name="status">
        <option value="">All Status</option>
        <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option value="Resolved" <?= $statusFilter === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
        <option value="Closed" <?= $statusFilter === 'Closed' ? 'selected' : '' ?>>Closed</option>
      </select>
      <button type="submit">Filter</button>
    </form>

    <table>
      <thead>
        <tr>
          <th>Guest Name</th>
          <th>Complaint Date</th>
          <th>Complaint</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($complaints)): ?>
          <tr><td colspan="5" style="text-align:center;">No complaints found.</td></tr>
        <?php else: ?>
          <?php foreach($complaints as $c): ?>
          <tr>
            <td data-label="Guest Name"><?= htmlspecialchars($c['guest_name']) ?></td>
            <td data-label="Complaint Date"><?= date("Y-m-d", strtotime($c['complaint_date'])) ?></td>
            <td data-label="Complaint"><?= nl2br(htmlspecialchars(mb_strimwidth($c['complaint_text'], 0, 50, '...'))) ?></td>
            <td data-label="Status"><?= htmlspecialchars($c['status']) ?></td>
            <td data-label="Action">
              <a href="file_a_complaint.php?complaint_id=<?= $c['complaint_id'] ?>&guest_name=<?= urlencode($c['guest_name']) ?>&complaint_text=<?= urlencode($c['complaint_text']) ?>&status=<?= urlencode($c['status']) ?>" style="padding:4px 6px; background:#4CAF50; color:#fff; border-radius:4px; text-decoration:none;"><i class="fas fa-edit"></i> Edit</a>
              <a href="complaints.php?delete_id=<?= $c['complaint_id'] ?>" onclick="return confirm('Are you sure you want to delete this complaint?');" style="padding:4px 6px; background:#f44336; color:#fff; border-radius:4px; text-decoration:none;"><i class="fas fa-trash"></i> Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
