<?php
// maintenance_requests.php
include '../db_connect.php'; // adjust path to your db_connect.php

$search = $_GET['search'] ?? '';

$sql = "SELECT request_id, room_id, reported_by, issue_description, priority, status, reported_date, completed_date, remarks 
        FROM maintenance_requests";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " WHERE request_id LIKE '%$search%' 
              OR room_id LIKE '%$search%' 
              OR reported_by LIKE '%$search%' 
              OR issue_description LIKE '%$search%' 
              OR status LIKE '%$search%'";
}

$sql .= " ORDER BY reported_date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Maintenance Requests</title>
  <!-- Google Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    /* Reset + Base */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body, html {
      height: 100%;
      font-family: 'Outfit', sans-serif;
      background: url('hotel_room.jpg') no-repeat center center fixed;
      background-size: cover;
    }

    /* Overlay */
    .overlay {
      background: rgba(0, 0, 0, 0.65);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center; 
      align-items: center;
      padding: 40px 20px;
    }

    /* Container */
    .container {
      max-width: 1100px;
      width: 100%;
      text-align: center;
      color: #fff;
    }

    /* Header */
    header {
      margin-bottom: 20px;
      position: relative;
    }
    header h1 {
      font-size: 3rem;
      font-weight: 700;
      letter-spacing: 1px;
      margin-bottom: 10px;
    }
    header p {
      font-size: 1.1rem;
      opacity: 0.85;
      margin-bottom: 20px;
    }

    /* Back button */
    .back-button {
      display: inline-flex;
      align-items: center;
      text-decoration: none;
      background: rgba(255, 255, 255, 0.15);
      padding: 8px 14px;
      border-radius: 8px;
      color: #fff;
      font-size: 14px;
      margin-bottom: 15px;
      transition: background 0.3s ease;
    }
    .back-button:hover {
      background: rgba(255, 255, 255, 0.3);
    }
    .back-button .material-icons {
      margin-right: 6px;
      font-size: 20px;
    }

    /* Search Bar */
    .search-form {
      margin-bottom: 20px;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
    }
    .search-form input {
      padding: 8px 12px;
      border-radius: 8px;
      border: none;
      outline: none;
      font-size: 14px;
      width: 250px;
    }
    .search-form button {
      display: inline-flex;
      align-items: center;
      background: #ffd700;
      border: none;
      border-radius: 8px;
      padding: 8px 14px;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.3s ease;
    }
    .search-form button:hover {
      background: #ffcc00;
    }
    .search-form .material-icons {
      margin-right: 6px;
    }

    /* Table */
    .table-container {
      background: rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      padding: 20px;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.12);
      box-shadow: 0 6px 25px rgba(0, 0, 0, 0.25);
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      color: #fff;
    }
    table thead {
      background: rgba(255, 215, 0, 0.9);
      color: black;
    }
    table th, table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid rgba(255,255,255,0.2);
      font-size: 14px;
    }
    table tr:hover {
      background: rgba(255, 255, 255, 0.12);
    }

    /* Badges */
    .priority-high, .priority-medium, .priority-low,
    .status-pending, .status-in-progress, .status-completed, .status-on-hold {
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: bold;
      display: inline-block;
      white-space: nowrap;
    }
    .priority-high { background: #dc3545; color: white; }
    .priority-medium { background: #ffc107; color: black; }
    .priority-low { background: #28a745; color: white; }
    .status-pending { background: #ffc107; color: black; }
    .status-in-progress { background: #17a2b8; color: white; }
    .status-completed { background: #28a745; color: white; }
    .status-on-hold { background: #6c757d; color: white; }
  </style>
</head>
<body>
  <div class="overlay">
    <div class="container">
      <header>
        <a href="maintenance.php" class="back-button">
          <span class="material-icons">arrow_back</span> Back
        </a>
        <h1>Maintenance Requests</h1>
        <p>Track and monitor all ongoing and completed requests</p>
      </header>

      <!-- Search Form -->
      <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search requests..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit"><span class="material-icons">search</span> Search</button>
      </form>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Request ID</th>
              <th>Room ID</th>
              <th>Reported By</th>
              <th>Issue Description</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Reported Date</th>
              <th>Completed Date</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?php echo $row['request_id']; ?></td>
                  <td><?php echo $row['room_id']; ?></td>
                  <td><?php echo htmlspecialchars($row['reported_by']); ?></td>
                  <td><?php echo htmlspecialchars($row['issue_description']); ?></td>
                  <td>
                    <span class="priority-<?php echo strtolower($row['priority']); ?>">
                      <?php echo $row['priority']; ?>
                    </span>
                  </td>
                  <td>
                    <span class="status-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                      <?php echo $row['status']; ?>
                    </span>
                  </td>
                  <td><?php echo $row['reported_date']; ?></td>
                  <td><?php echo $row['completed_date'] ?: '—'; ?></td>
                  <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="9">No maintenance requests found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
