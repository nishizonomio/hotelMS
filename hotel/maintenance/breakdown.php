<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "hotel";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$floor_filter = isset($_GET['floor']) ? $_GET['floor'] : '';

$query = "SELECT * FROM housekeeping_issues WHERE 1";
if ($priority_filter && $priority_filter != 'All') {
    $query .= " AND priority = '" . $conn->real_escape_string($priority_filter) . "'";
}
if ($floor_filter && $floor_filter != 'All') {
    if ($floor_filter == 'Floor 1-5') $query .= " AND room_number BETWEEN 100 AND 599";
    if ($floor_filter == 'Floor 6-10') $query .= " AND room_number BETWEEN 600 AND 1099";
    if ($floor_filter == 'Floor 11-15') $query .= " AND room_number BETWEEN 1100 AND 1599";
}
$query .= " ORDER BY reported_at DESC";
$result = $conn->query($query);

$issues = [];
if ($result) {
    while ($row = $result->fetch_assoc()) $issues[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Housekeeping Issues</title>
    <link href="https://fonts.googleapis.com/css?family=Outfit:400,700&display=swap" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            background: url('hotel_room.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        .overlay {
            min-height: 100vh;
            width: 100vw;
            background: rgba(0, 0, 0, 0.65);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 0;
        }
        .container {
            max-width: 900px;
            width: 100%;
            margin: 40px 0;
            padding: 32px 24px;
            background: rgba(255,255,255,0.12);
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h3 {
            text-align: center;
            margin-bottom: 28px;
            color: #fff;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .filter-bar {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }
        .filter-input, .btn {
            padding: 10px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            background: #fff;
            transition: border 0.2s;
        }
        .filter-input:focus {
            border-color: #3b82f6;
            outline: none;
        }
        .btn {
            background: linear-gradient(90deg, #3b82f6 60%, #6366f1 100%);
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(59,130,246,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            background: linear-gradient(90deg, #2563eb 60%, #6366f1 100%);
            box-shadow: 0 4px 16px rgba(59,130,246,0.12);
        }
        .issue-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255,255,255,0.95);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .issue-table th, .issue-table td {
            padding: 14px 10px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
        }
        .issue-table th {
            background: #f3f4f6;
            font-weight: 700;
            color: #374151;
            font-size: 1rem;
        }
        .issue-table td {
            font-size: 0.98rem;
            color: #374151;
            background: rgba(255,255,255,0.98);
        }
        .issue-table tr:last-child td {
            border-bottom: none;
        }
        .room-img {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            margin-right: 7px;
            vertical-align: middle;
            object-fit: cover;
            border: 1px solid #e5e7eb;
            background: #f3f4f6;
        }
        .priority-Urgent { color: #dc2626; font-weight: bold; }
        .priority-High { color: #ea580c; font-weight: bold; }
        .priority-Medium { color: #ca8a04; font-weight: bold; }
        .priority-Low { color: #16a34a; font-weight: bold; }
        .status {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.95em;
            font-weight: 600;
            display: inline-block;
        }
        .status-Resolved { background: #d1fae5; color: #065f46; }
        .status-Pending { background: #fef3c7; color: #92400e; }
        .status-Urgent { background: #fee2e2; color: #991b1b; }
        .status-InProgress { background: #bfdbfe; color: #1e40af; }
        .status-Completed { background: #e0f2fe; color: #0369a1; }
        /* 🔹 Back button styling */
        .back-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #6366f1;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.25);
            transition: background 0.3s;
            z-index: 1000;
        }
        .back-btn:hover {
            background: #4f46e5;
        }
          .footer {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: 5px;
      background: #111827;
      color: #f9fafb;
      font-size: 10px;
      border-top: 1px  #374151;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      z-index: 100;
    }
        @media (max-width: 900px) {
            .container { padding: 16px 4px; }
            .issue-table th, .issue-table td { padding: 8px 4px; font-size: 0.95em; }
        }
        @media (max-width: 600px) {
            .container { margin: 10px 0; }
            .issue-table th, .issue-table td { padding: 6px 2px; font-size: 0.92em; }
            h3 { font-size: 1.3rem; }
        }
    </style>
</head>
<body>

    <!-- 🔹 Back to Maintenance button (outside container, top-right) -->
    <a href="maintenance.php" class="back-btn">⬅ Back to Main</a>

    <div class="overlay">
        <div class="container">
            <h3>Breakdown History and Report Issues</h3>
            <form method="get" class="filter-bar">
                <select name="floor" class="filter-input">
                    <option <?= $floor_filter=='All'?'selected':'' ?>>All</option>
                    <option <?= $floor_filter=='Floor 1-5'?'selected':'' ?>>Floor 1-5</option>
                    <option <?= $floor_filter=='Floor 6-10'?'selected':'' ?>>Floor 6-10</option>
                    <option <?= $floor_filter=='Floor 11-15'?'selected':'' ?>>Floor 11-15</option>
                </select>
                <select name="priority" class="filter-input">
                    <option <?= $priority_filter=='All'?'selected':'' ?>>All</option>
                    <option <?= $priority_filter=='Urgent'?'selected':'' ?>>Urgent</option>
                    <option <?= $priority_filter=='High'?'selected':'' ?>>High</option>
                    <option <?= $priority_filter=='Medium'?'selected':'' ?>>Medium</option>
                </select>
                <input type="text" id="searchInput" class="filter-input" placeholder="🔍 Search by Room, Issue, or Staff" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button type="submit" class="btn">Filter</button>
            </form>
            <table class="issue-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Issue</th>
                        <th>Priority</th>
                        <th>Reported</th>
                        <th>Staff</th>
                        <th>Status</th>
                        <th>ETA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
                    $shown = 0;
                    foreach ($issues as $issue) {
                        if ($search) {
                            $haystack = strtolower($issue['room_number'] . ' ' . $issue['issue_description'] . ' ' . $issue['reported_by']);
                            if (strpos($haystack, $search) === false) continue;
                        }
                        $shown++;
                        ?>
                        <tr>
                            <td>
                                <?php if (!empty($issue['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($issue['image_url']) ?>" alt="Room" class="room-img">
                                <?php endif; ?>
                                <strong><?= htmlspecialchars($issue['room_number']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($issue['issue_description']) ?></td>
                            <td class="priority-<?= htmlspecialchars($issue['priority']) ?>">
                                <?= htmlspecialchars($issue['priority']) ?>
                            </td>
                            <td><?= htmlspecialchars($issue['reported_at']) ?></td>
                            <td><?= htmlspecialchars($issue['reported_by']) ?></td>
                            <td>
                                <?php $statusClass = 'status-' . str_replace(' ', '', htmlspecialchars($issue['status'])); ?>
                                <span class="status <?= $statusClass ?>">
                                    <?= htmlspecialchars($issue['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($issue['eta']) ?></td>
                        </tr>
                    <?php }
                    if ($shown == 0): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; color:#dc2626; font-weight:600;">No issues found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
  <footer class="footer">
  <p> © 2025 Hotel La Vista  | All Rights Reserved</p>
</footer>
</html>
