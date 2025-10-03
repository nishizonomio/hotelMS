<?php
// room_status.php
include '../../db_connect.php'; // adjust path if needed

// Fetch all rooms with Dirty, Cleaning, and Under Maintenance statuses
$sql = "SELECT room_id, room_number, room_type, max_occupancy, price_rate, status
        FROM rooms 
        WHERE status IN ('dirty', 'cleaning', 'under maintenance')";
$result = $conn->query($sql);

// Count rooms by status for pie chart
$countQuery = "SELECT status, COUNT(*) as total
               FROM rooms
               WHERE status IN ('dirty', 'cleaning', 'under maintenance')
               GROUP BY status";
$countResult = $conn->query($countQuery);

$statuses = [];
$totals = [];
$colors = [];

$statusColors = [
    'dirty' => '#ffc107',            // yellow
    'cleaning' => '#ff69b4',         // pink
    'under maintenance' => '#007bff' // blue
];

if ($countResult && $countResult->num_rows > 0) {
    while ($row = $countResult->fetch_assoc()) {
        $statuses[] = ucfirst($row['status']);
        $totals[] = $row['total'];
        $colors[] = $statusColors[$row['status']];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Room Status Dashboard | Housekeeping</title>
  <link rel="stylesheet" href="room_status.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* Dark legend container under the chart */
    .legend-container {
      margin-top: 20px;
      background: rgba(30, 30, 30, 0.9);
      padding: 15px 20px;
      border-radius: 12px;
      display: flex;
      justify-content: center;
      gap: 20px;
      flex-wrap: wrap;
    }
    .legend-item {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #fff;
      font-weight: 600;
    }
    .legend-color {
      width: 20px;
      height: 20px;
      border-radius: 4px;
      display: inline-block;
    }
  </style>
</head>
<body>
  <div class="overlay">
    <div class="container">
      <header style="position: relative;">
        <h1><i class="fas fa-bed"></i> Room Status Dashboard</h1>
        <p>Showing rooms that are <strong>Dirty</strong>, <strong>Cleaning</strong>, or <strong>Under Maintenance</strong>.</p>
        <a href="../housekeeping.php" class="back-btn" style="position: absolute; top: 0; right: 0;"><i class="fas fa-arrow-left"></i> Back</a>
      </header>

      <div class="status-container">
        <!-- Chart -->
        <div class="chart-section">
          <canvas id="roomChart"></canvas>
          <p class="chart-label">Room Distribution by Status</p>

          <!-- Custom Legend -->
          <div class="legend-container">
            <?php foreach ($statuses as $index => $status): ?>
              <div class="legend-item">
                <span class="legend-color" style="background-color: <?php echo $colors[$index]; ?>;"></span>
                <span><?php echo $status; ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Table -->
        <div class="table-section">
          <?php if ($result && $result->num_rows > 0): ?>
            <table class="status-table">
              <thead>
                <tr>
                  <th>Room Number</th>
                  <th>Room Type</th>
                  <th>Max Occupancy</th>
                  <th>Price Rate</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['room_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['max_occupancy']); ?></td>
                    <td>₱<?php echo number_format($row['price_rate'], 2); ?></td>
                    <td>
                      <span class="status-badge" 
                            style="background-color: <?php echo $statusColors[$row['status']]; ?>;">
                        <?php echo ucfirst($row['status']); ?>
                      </span>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p class="no-data">✅ No rooms found for the selected statuses.</p>
          <?php endif; ?>
        </div>
      </div>

      <a href="../housekeeping.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back 
      </a>
    </div>
  </div>

  <script>
    const ctx = document.getElementById('roomChart').getContext('2d');
    const roomChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: <?php echo json_encode($statuses); ?>,
        datasets: [{
          data: <?php echo json_encode($totals); ?>,
          backgroundColor: <?php echo json_encode($colors); ?>,
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        plugins: {
          legend: {
            display: false // disable default legend
          }
        }
      }
    });
  </script>
</body>
</html>
<?php $conn->close(); ?>
