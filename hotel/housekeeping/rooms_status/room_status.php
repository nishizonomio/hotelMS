<?php
// ================= BOOTSTRAP / DB =================
require_once __DIR__ . '/../db_connector/db_connect.php'; // gives us $conn (mysqli)
include 'roomstatus.php';


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Room Status Dashboard</title>
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/room_status_new.css">
  <link rel="stylesheet" href="../css/tasks_new.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
</head>
<body>
  <div class="overlay">
    <div class="container">
      <header>
        <div class="nav-buttons">
                    <a href="../housekeeping.php" class="nav-btn back-btn"><i class="fas fa-arrow-left"></i> Back</a>
                    <a href="../../homepage/index.php" class="nav-btn back-btn"><i class="fas fa-home"></i> Home</a>
                </div>
        <h1>Room Status Dashboard</h1>
      </header>

      <?php if ($flash): ?>
        <div style="margin:10px 0;padding:10px;border-radius:8px;
                    background:<?= $flash['type']==='success'?'rgba(46,204,113,0.2)':'rgba(231,76,60,0.25)' ?>;
                    color:#fff;">
          <?= htmlspecialchars($flash['msg']) ?>
        </div>
      <?php endif; ?>

      <!-- Stats + Chart -->
      <div class="stats-chart-container">
        <div class="stats-summary">
          <div class="stat-card available">
            <span class="stat-label">Available</span>
            <span class="stat-value"><?= $statusCounts['Available'] ?></span>
          </div>
          <div class="stat-card occupied">
            <span class="stat-label">Occupied</span>
            <span class="stat-value"><?= $statusCounts['Occupied'] ?></span>
          </div>
          <div class="stat-card cleaning">
            <span class="stat-label">Cleaning</span>
            <span class="stat-value"><?= $statusCounts['Cleaning'] ?></span>
          </div>
          <div class="stat-card maintenance">
            <span class="stat-label">Maintenance</span>
            <span class="stat-value"><?= $statusCounts['Maintenance'] ?></span>
          </div>
          <div class="stat-card total">
            <span class="stat-label">Total Rooms</span>
            <span class="stat-value"><?= array_sum($statusCounts) ?></span>
          </div>
        </div>

        <div class="chart-container">
          <canvas id="statusChart"></canvas>
        </div>
      </div>

      <!-- Table -->
      <div class="table-container">
        <table class="room-table">
          <thead>
            <tr>
              <th>Room Number</th>
              <th>Room Type</th>
              <th>Status</th>
              <th>Last Cleaned</th>
              <th>Remarks</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (count($rooms) > 0): ?>
            <?php foreach ($rooms as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r->room_number) ?></td>
                <td><?= htmlspecialchars($r->room_type) ?></td>
                <td><?= htmlspecialchars($r->status) ?></td>
                <td><?= htmlspecialchars($r->last_cleaned ?? '-') ?></td>
                <td><?= htmlspecialchars($r->remarks ?? '-') ?></td>
                <td>
                  <button class="btn edit-btn"
                          data-id="<?= $r->room_id ?>"
                          data-status="<?= htmlspecialchars($r->status) ?>"
                          data-remarks="<?= htmlspecialchars($r->remarks ?? '') ?>"
                          data-lastcleaned="<?= htmlspecialchars($r->last_cleaned ?? '') ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6">Waiting for room data from room management module…</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Update form -->
      <h2 class="form-title">Update Room</h2>
      <form id="updateForm" method="POST" action="" class="update-form">
        <input type="hidden" id="updateRoomId" name="room_id">
        <label for="status">Status:</label>
        <select id="updateStatus" name="status" required>
          <option value="Available">Available</option>
          <option value="Occupied">Occupied</option>
          <option value="Cleaning">Cleaning</option>
          <option value="Maintenance">Maintenance</option>
        </select>

        <label for="remarks">Remarks:</label>
        <textarea id="updateRemarks" name="remarks"></textarea>

        <label for="last_cleaned">Last Cleaned:</label>
        <input type="date" id="updateLastCleaned" name="last_cleaned">

        <button type="submit" name="update_room">Update Room</button>
      </form>

      <footer>
        <a href="../housekeeping.php" class="back-btn">
          <i class="fas fa-arrow-left"></i> Back to Housekeeping
        </a>
      </footer>
    </div>
  </div>

  <!-- Chart + Form wiring -->
  <script>
    // Chart
    (function(){
      const ctx = document.getElementById('statusChart').getContext('2d');
      const data = [<?= $statusCounts['Available'] ?>, <?= $statusCounts['Occupied'] ?>, <?= $statusCounts['Cleaning'] ?>, <?= $statusCounts['Maintenance'] ?>];

      new Chart(ctx, {
        type: 'pie',
        data: {
          labels: ['Available','Occupied','Cleaning','Maintenance'],
          datasets: [{
            data: data,
            backgroundColor: ['#28a745','#dc3545','#ffc107','#6c757d'],
            borderColor: '#ffffff',
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                color: '#ffffff',
                font: { size: 13, family: "'Outfit',sans-serif", weight: '600' }
              }
            },
            tooltip: {
              callbacks: {
                label: (ctx) => {
                  const total = ctx.dataset.data.reduce((a,b)=>a+b,0) || 1;
                  const val   = ctx.raw || 0;
                  const pct   = Math.round((val/total)*100);
                  return `${ctx.label}: ${val} (${pct}%)`;
                }
              }
            }
          }
        }
      });
    })();

    // Fill the update form when clicking "edit"
    (function(){
      const form   = document.getElementById('updateForm');
      const idEl   = document.getElementById('updateRoomId');
      const stEl   = document.getElementById('updateStatus');
      const remEl  = document.getElementById('updateRemarks');
      const dateEl = document.getElementById('updateLastCleaned');

      document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          idEl.value        = btn.dataset.id || '';
          stEl.value        = btn.dataset.status || 'Available';
          remEl.value       = btn.dataset.remarks || '';
          dateEl.value      = btn.dataset.lastcleaned || '';
          form.scrollIntoView({behavior:'smooth', block:'center'});
        });
      });
    })();
  </script>
</body>
</html>
