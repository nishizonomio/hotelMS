<?php
// assignments.php
include '../db_connect.php';

// Fetch all current assignments
$assignments = $conn->query("
    SELECT ta.id, ta.request_id, ta.staff_id, ta.assigned_date, ta.status, ta.remarks,
           mr.room_id, mr.issue_description, mr.priority
    FROM technician_assignments ta
    JOIN maintenance_requests mr ON ta.request_id = mr.request_id
    ORDER BY ta.assigned_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Current Assignments</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body, html { height: 100%; font-family: 'Outfit', sans-serif;
      background: url('hotel_room.jpg') no-repeat center center fixed;
      background-size: cover; }
    .overlay { background: rgba(0, 0, 0, 0.65); min-height: 100vh;
      display: flex; flex-direction: column; justify-content: flex-start; align-items: center; padding: 40px 20px; }
    .container { max-width: 1200px; width: 100%; text-align: center; color: #fff; }
    h1 { font-size: 2.2rem; font-weight: 700; margin-bottom: 20px; }
    .section { margin-bottom: 40px; background: rgba(255, 255, 255, 0.08); padding: 20px; border-radius: 12px;
      backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.12); color: #fff; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; background: rgba(255, 255, 255, 0.05); }
    table th, table td { padding: 12px; border: 1px solid rgba(255, 255, 255, 0.2); text-align: left; font-size: 14px; color: #fff; }
    table th { background: rgba(59,130,246,0.3); color: #ffd700; }
    .btn-header { position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; }
    .btn-header a {
      background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.3);
      padding: 10px 16px; border-radius: 6px; font-size: 14px; text-decoration: none;
      display: flex; align-items: center; gap: 6px; backdrop-filter: blur(8px);
    }
    .btn-header a:hover { background: rgba(255, 255, 255, 0.2); color: #ffd700; }
  </style>
</head>
<body>
  <div class="overlay">
    <div class="container">
      <div class="btn-header">
        <a href="maintenance.php"><i class="fas fa-arrow-left"></i> Back</a>
      </div>
      <h1>Current Assignments</h1>

      <div class="section">
        <table>
          <thead>
            <tr>
              <th>Assignment ID</th>
              <th>Request ID</th>
              <th>Room</th>
              <th>Issue</th>
              <th>Priority</th>
              <th>Staff ID</th>
              <th>Assigned Date</th>
              <th>Status</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($assignments->num_rows > 0): ?>
              <?php while ($row = $assignments->fetch_assoc()): ?>
                <tr>
                  <td><?php echo $row['id']; ?></td>
                  <td><?php echo $row['request_id']; ?></td>
                  <td><?php echo $row['room_id']; ?></td>
                  <td><?php echo htmlspecialchars($row['issue_description']); ?></td>
                  <td><?php echo $row['priority']; ?></td>
                  <td><?php echo $row['staff_id']; ?></td>
                  <td><?php echo $row['assigned_date']; ?></td>
                  <td><?php echo $row['status']; ?></td>
                  <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="9">No current assignments found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
