<?php
// technician.php
include '../db_connect.php';

// Handle assignment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['staff_id'])) {
    $request_id = intval($_POST['request_id']);
    $staff_id = trim($_POST['staff_id']); // varchar

    // ✅ Check if staff_id exists in staff table
    $checkStaff = $conn->prepare("SELECT staff_id FROM staff WHERE staff_id = ?");
    $checkStaff->bind_param("s", $staff_id);
    $checkStaff->execute();
    $result = $checkStaff->get_result();

    if ($result->num_rows > 0) {
        // Insert into technician_assignments
        $stmt = $conn->prepare("
            INSERT INTO technician_assignments (request_id, staff_id, assigned_date, status, remarks)
            VALUES (?, ?, NOW(), 'Assigned', 'Technician assigned to request')
        ");
        $stmt->bind_param("is", $request_id, $staff_id);
        $stmt->execute();
        $stmt->close();

        // ✅ Update maintenance_requests so it no longer shows as Pending
        $update = $conn->prepare("UPDATE maintenance_requests SET status = 'In Progress' WHERE request_id = ?");
        $update->bind_param("i", $request_id);
        $update->execute();
        $update->close();

        echo "<script>alert('Request #$request_id assigned to Staff ID: $staff_id'); window.location.href='technician.php';</script>";
    } else {
        echo "<script>alert('Invalid Staff ID: $staff_id. Please enter a valid technician.');</script>";
    }
    $checkStaff->close();
}

// Fetch only Pending requests
$requests = $conn->query("
    SELECT request_id, room_id, issue_description, priority, status, remarks
    FROM maintenance_requests 
    WHERE status = 'Pending'
    ORDER BY reported_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Technician Assignment</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body, html { height: 100%; font-family: 'Outfit', sans-serif;
      background: url('hotel_room.jpg') no-repeat center center fixed;
      background-size: cover; }
    .overlay { background: rgba(0, 0, 0, 0.65); min-height: 100vh;
      display: flex; flex-direction: column; justify-content: flex-start; align-items: center; padding: 40px 20px; }
    .container { max-width: 1100px; width: 100%; text-align: center; color: #fff; }
    h1 { font-size: 2.2rem; font-weight: 700; margin-bottom: 20px; }
    .section { margin-bottom: 40px; background: rgba(255, 255, 255, 0.08); padding: 20px; border-radius: 12px;
      backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.12); color: #fff; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; background: rgba(255, 255, 255, 0.05); }
    table th, table td { padding: 12px; border: 1px solid rgba(255, 255, 255, 0.2); text-align: left; font-size: 14px; color: #fff; }
    table th { background: rgba(255, 215, 0, 0.25); color: #ffd700; }
    form { display: flex; gap: 10px; align-items: center; }
    input { padding: 6px; font-size: 14px; border-radius: 6px; border: none; }
    button { background: #2563eb; color: white; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; }
    button:hover { background: #1d4ed8; }
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
      <h1>Technician Assignment</h1>

      <!-- Pending Requests -->
      <div class="section">
        <h2>Pending Requests</h2>
        <table>
          <thead>
            <tr>
              <th>Request ID</th>
              <th>Room</th>
              <th>Issue</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Request Remarks</th>
              <th>Assign To</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($requests->num_rows > 0): ?>
              <?php while ($row = $requests->fetch_assoc()): ?>
                <tr>
                  <td><?php echo $row['request_id']; ?></td>
                  <td><?php echo $row['room_id']; ?></td>
                  <td><?php echo htmlspecialchars($row['issue_description']); ?></td>
                  <td><?php echo $row['priority']; ?></td>
                  <td><?php echo $row['status']; ?></td>
                  <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                  <td>
                    <form method="POST">
                      <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                      <input type="text" name="staff_id" placeholder="Enter Staff ID" required>
                      <button type="submit"><i class="fa-solid fa-user-check"></i> Assign</button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="7">No pending requests found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
