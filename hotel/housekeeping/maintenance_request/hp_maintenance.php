<?php
require_once __DIR__ . '/../db_connector/db_connect.php';
include __DIR__ . '/../repo/MaintenanceRepository.php';
include __DIR__ . '/MaintenanceService.php';

$db = new Database();
$conn = $db->getConnection();

$repo = new MaintenanceRepository($conn);
$service = new MaintenanceService($repo);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Add new task
  if (isset($_POST['add_task'])) {
    $service->addTask($_POST);
  }

  // Delete has priority when present
  if (isset($_POST['delete_task'])) {
    $service->delete((int)$_POST['maintenance_id']);
  }

  // Update (edit button or status-only change)
  if (isset($_POST['maintenance_id']) && (isset($_POST['edit_task']) || isset($_POST['status']))) {
    $service->update($_POST);
  }

  header("Location: hp_maintenance.php?success=1");
  exit;
}

// Fetch data
$tasks = $service->list();
$stats = $service->counts();
$completed = $service->completedCounts();
$selectedMonth = (int)($_GET['month'] ?? date('m'));
$selectedMonthCount = $service->selectedMonthCount($selectedMonth);
$rooms = $service->getRooms();
// Optional debug: append ?dbg=1 to the URL to see the fetched tasks
if(isset($_GET['dbg']) && $_GET['dbg']=='1'){
  echo '<pre style="color:#fff; background:rgba(0,0,0,0.6); padding:10px;">';
  print_r($tasks);
  echo '</pre>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Housekeeping Maintenance</title>
<link rel="stylesheet" href="../../homepage/index.css">
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="../css/room_status_new.css">
<link rel="stylesheet" href="../css/maintenance.css">
<link rel="stylesheet" href="../css/tasks_new.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- modal/show styles moved to housekeeping/css/maintenance.css -->
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <div class="nav-buttons">
                    <a href="../housekeeping.php" class="nav-btn back-btn"><i class="fas fa-arrow-left"></i> Back</a>
                    <a href="../../index.php" class="nav-btn back-btn"><i class="fas fa-home"></i> Home</a>
                </div>
      <h1>Housekeeping Maintenance</h1>
    </header>

   <!-- Stats row stands alone -->
    <div class="stats-container-glass">
<div class="stats-summary">
  <div class="stat-card total"><span>Total Tasks</span><span><?= array_sum($stats) ?></span></div>
  <div class="stat-card cleaning"><span>Pending</span><span><?= $stats['Pending'] ?></span></div>
  <div class="stat-card linen"><span>In Progress</span><span><?= $stats['In Progress'] ?></span></div>
  <div class="stat-card toiletry"><span>Completed</span><span><?= $stats['Completed'] ?></span></div>
</div>
</div>

<!-- Rest of charts -->
<div class="stats-chart-container">
  <div class="chart-container dual-charts">
    <canvas id="maintenancePieChart"></canvas>
  </div>

  <div class="chart-container bar-chart">
    <div class="month-filter-container">
      <form method="GET" class="month-filter">
        <label for="month">Select Month:</label>
        <select name="month" id="month" onchange="this.form.submit()">
          <?php for($m=1;$m<=12;$m++):
            $monthName = date('F', mktime(0,0,0,$m,10));
            $selected = ($m==$selectedMonth)?'selected':''; ?>
            <option value="<?= $m ?>" <?= $selected ?>><?= $monthName ?></option>
          <?php endfor; ?>
        </select>
      </form>
      <canvas id="completedTasksChart"></canvas>
    </div>
  </div>
</div>




    <!-- Add Task Button -->
    <div style="text-align:center; margin:20px 0;">
      <button id="openModalBtn" class="back-btn">
        <i class="fas fa-plus"></i> Add Maintenance Task
      </button>
    </div>

    <!-- Modal -->
    <div id="taskModal" class="modal">
      <div class="modal-content glass">
        <span class="close" id="closeModal">&times;</span>
        <h2>Add New Maintenance Task</h2>
        <form method="POST">
          <select name="room_id" required>
            <option value="">Select Room</option>
            <?php foreach($rooms as $r): ?>
              <option value="<?= $r['room_id'] ?>">Room <?= $r['room_number'] ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" name="issue" placeholder="Issue Description" required>
          <input type="date" name="reported_date" required>
          <textarea name="remarks" placeholder="Remarks"></textarea>
          <button type="submit" name="add_task" class="btn add-btn">
            <i class="fas fa-plus"></i> Add Task
          </button>
        </form>
      </div>
    </div>

    <!-- Maintenance Table -->
    <div class="table-container">
      <table class="room-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Room</th>
            <th>Issue</th>
            <th>Status</th>
            <th>Reported Date</th>
            <th>Completed Date</th>
            <th>Remarks</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($tasks as $row): ?>
          <form method="POST">
          <tr>
            <td><?= $row['maintenance_id'] ?></td>
              <input type="hidden" name="maintenance_id" value="<?= $row['maintenance_id'] ?>">
              <td>
                <select name="room_id" required>
                  <?php foreach($rooms as $r): $sel = ($r['room_id']==$row['room_id'])?'selected':''; ?>
                  <option value="<?= $r['room_id'] ?>" <?= $sel ?>>Room <?= $r['room_number'] ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="text" name="issue" value="<?= htmlspecialchars($row['issue']) ?>"></td>
              <td>
                <select name="status" onchange="this.form.submit()">
                  <option value="Pending" <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
                  <option value="In Progress" <?= $row['status']=='In Progress'?'selected':'' ?>>In Progress</option>
                  <option value="Resolved" <?= $row['status']=='Resolved'?'selected':'' ?>>Completed</option>
                </select>
              </td>
              <td><?= $row['reported_date'] ?></td>
              <td><?= $row['completed_date'] ?? '-' ?></td>
              <td><input type="text" name="remarks" value="<?= htmlspecialchars($row['remarks'] ?? '') ?>"></td>
              <td>
                <button type="submit" name="edit_task" class="btn save-btn"><i class="fas fa-save"></i> Save</button>
                <button type="submit" name="delete_task" class="btn delete-btn" onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i> Delete</button>
              </td>
          </tr>
          </form>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <footer><a href="../housekeeping.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Housekeeping</a></footer>
  </div>
</div>
<script>
// Charts
const ctxPie = document.getElementById('maintenancePieChart').getContext('2d');
new Chart(ctxPie, {
    type: 'pie',
    data: {
        labels: ['Pending','In Progress','Completed'],
        datasets: [{
            data: [<?= $stats['Pending'] ?>, <?= $stats['In Progress'] ?>, <?= $stats['Completed'] ?>],
            backgroundColor: ['#f39c12','#3498db','#2ecc71'],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                labels: {
                    color: '#fff',   // 👈 legend text white
                    font: {
                        size: 14,
                        weight: 'bold'
                    }
                }
            },
            tooltip: {
                titleColor: '#fff',    // 👈 tooltip title white
                bodyColor: '#fff',     // 👈 tooltip body white
                backgroundColor: 'rgba(0,0,0,0.7)'
            }
        }
    }
});

const ctxBar = document.getElementById('completedTasksChart').getContext('2d');
new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: ['Yesterday','Last 3 Days','Last Month','Selected Month'],
        datasets: [{
            label: 'Completed Tasks',
            data: [<?= $completed['yesterday'] ?>, <?= $completed['last3Days'] ?>, <?= $completed['lastMonth'] ?>, <?= $selectedMonthCount ?>],
            backgroundColor: '#2ecc71'
        }]
    },
    options: {
    responsive: true,
    plugins: {
      legend: {
        labels: {
          color: '#fff'   // bar chart legend white
        }
      },
      tooltip: {
        titleColor: '#fff',
        bodyColor: '#fff',
        backgroundColor: 'rgba(0,0,0,0.7)'
      }
    },
    scales: {
      x: {
        ticks: { color: '#fff' },
        grid: { color: 'rgba(255,255,255,0.08)' }
      },
      y: {
        ticks: { color: '#fff' },
        grid: { color: 'rgba(255,255,255,0.08)' }
      }
    }
    }
});

// Modal toggle (show only when Add button clicked)
document.addEventListener('DOMContentLoaded', ()=>{
  const modal = document.getElementById('taskModal');
  const btn = document.getElementById('openModalBtn');
  const closeBtn = document.getElementById('closeModal');
  if(!modal) return;
  // ensure hidden by default
  modal.classList.remove('show');
  if(btn) btn.style.display = 'inline-block';
  const show = ()=> {
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    if(btn) btn.style.display = 'none';
    // focus first input for accessibility
    const first = modal.querySelector('input, select, textarea, button');
    if(first) first.focus();
  };
  const hide = ()=> {
    modal.classList.remove('show');
    document.body.style.overflow = '';
    if(btn) btn.style.display = 'inline-block';
  };
  if(btn) btn.addEventListener('click', show);
  if(closeBtn) closeBtn.addEventListener('click', hide);
  window.addEventListener('keydown', e=>{ if(e.key === 'Escape') hide(); });
  window.addEventListener('click', e=>{ if(e.target === modal) hide(); });
});
</script>

</body>
</html>
