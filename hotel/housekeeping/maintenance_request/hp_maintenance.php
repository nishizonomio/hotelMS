<?php
require_once __DIR__ . '/../db_connector/db_connect.php';
include __DIR__ . '/../repo/MaintenanceRepository.php';
include __DIR__ . '/MaintenanceService.php';

$db = new Database();
$conn = $db->getConnection();

$repo = new MaintenanceRepository($conn);
$service = new MaintenanceService($repo);
// Error message to show on-screen when server-side validation or DB errors occur
$error = null;

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Add new task
    if (isset($_POST['add_task'])) {
      $service->addTask($_POST);
    }

    // Handle multiple delete
    if (isset($_POST['delete_selected']) && isset($_POST['selected_tasks'])) {
      foreach ($_POST['selected_tasks'] as $task_id) {
        $service->delete((int)$task_id);
      }
      header("Location: hp_maintenance.php?success=deleted");
      exit;
    }

    // Single delete
    if (isset($_POST['delete_task'])) {
      $service->delete((int)$_POST['maintenance_id']);
      header("Location: hp_maintenance.php?success=deleted");
      exit;
    }

    // Update (edit button or status-only change)
    if (isset($_POST['maintenance_id']) && (isset($_POST['edit_task']) || isset($_POST['status']))) {
      $service->update($_POST);
    }

    // On success, redirect back with success flag
    header("Location: hp_maintenance.php?success=1");
    exit;
  } catch (Exception $e) {
    // Capture the error to show on the page instead of a fatal error
    $error = $e->getMessage();
    // allow page to render and display the error banner
  }
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
<link rel="stylesheet" href="../css/modals.css">
<style>
  /* Ensure the select inside the modal aligns visually with inputs */
  #taskModal .modal-content .styled-select { background: rgba(255,255,255,0.03); }
</style>
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
    <?php if ($error): ?>
      <div class="server-error-banner">
        <strong>Error:</strong>
        <small><?= htmlspecialchars($error) ?></small>
      </div>
    <?php endif; ?>

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
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title">Add New Maintenance Task</h2>
          <button type="button" class="close" id="closeModal">&times;</button>
        </div>
        <form method="POST">
          <div class="form-group">
            <label for="room_id_add">Room</label>
            <select id="room_id_add" name="room_id" class="form-control" required>
              <option value="">Select Room</option>
              <?php foreach($rooms as $r): ?>
                <option value="<?= $r['room_id'] ?>">Room <?= $r['room_number'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="issue_add">Issue Description</label>
            <input id="issue_add" type="text" name="issue" class="form-control" placeholder="Describe the maintenance issue" required>
          </div>

          <div class="form-group">
            <label for="reported_date_add">Date Issued</label>
            <input id="reported_date_add" type="date" name="reported_date" class="form-control" required>
          </div>

          <div class="form-group">
            <label for="completed_date_add">Date Completed (optional)</label>
            <input id="completed_date_add" type="date" name="completed_date" class="form-control">
          </div>

          <div class="form-group">
            <label for="remarks_add">Remarks</label>
            <textarea id="remarks_add" name="remarks" class="form-control" placeholder="Additional notes or remarks"></textarea>
          </div>

          <div class="modal-footer">
            <button type="submit" name="add_task" class="btn btn-primary">
              <i class="fas fa-plus"></i> Add Task
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bulk Delete Form -->
    <form id="bulkDeleteForm" method="POST" style="text-align: right; margin-bottom: 10px;">
      <button type="submit" name="delete_selected" class="btn delete-btn bulk-delete-btn" style="display: none;">
        <i class="fas fa-trash"></i> Delete Selected
      </button>
    </form>

    <!-- Maintenance Table -->
    <div class="table-container">
      <table class="room-table">
        <thead>
          <tr>
            <th><input type="checkbox" id="selectAll"> Select</th>
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
            <td>
              <input type="checkbox" name="selected_tasks[]" form="bulkDeleteForm" value="<?= $row['maintenance_id'] ?>" class="row-checkbox">
            </td>
            <td>
              <?= $row['maintenance_id'] ?>
              <input type="hidden" name="maintenance_id" value="<?= $row['maintenance_id'] ?>">
            </td>
              <td>
                <select name="room_id" class="styled-select" required>
                  <?php foreach($rooms as $r): $sel = ($r['room_id']==$row['room_id'])?'selected':''; ?>
                  <option value="<?= $r['room_id'] ?>" <?= $sel ?>>Room <?= $r['room_number'] ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="text" name="issue" value="<?= htmlspecialchars($row['issue']) ?>"></td>
              <td>
                <select name="status" class="styled-select" onchange="this.closest('form').submit()">
                  <option value="Pending" <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
                  <option value="In Progress" <?= $row['status']=='In Progress'?'selected':'' ?>>In Progress</option>
                  <option value="Resolved" <?= $row['status']=='Resolved'?'selected':'' ?>>Completed</option>
                </select>
              </td>
              <td><?= $row['reported_date'] ?></td>
              <td>
                <input type="date" name="completed_date" value="<?= $row['completed_date'] ?? '' ?>">
              </td>
              <td><input type="text" name="remarks" value="<?= htmlspecialchars($row['remarks'] ?? '') ?>"></td>
              <td>
                <button type="submit" name="edit_task" class="btn save-btn"><i class="fas fa-save"></i> Save</button>
                <button type="submit" name="delete_task" class="btn delete-btn"><i class="fas fa-trash"></i> Delete</button>
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

<!-- Bulk delete handling -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle select all checkbox
    const selectAll = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkDeleteBtn = document.querySelector('.bulk-delete-btn');

    selectAll.addEventListener('change', function() {
        rowCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
        updateBulkDeleteButton();
    });

    // Handle individual checkboxes
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(rowCheckboxes).every(box => box.checked);
            const someChecked = Array.from(rowCheckboxes).some(box => box.checked);
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked && !allChecked;
            updateBulkDeleteButton();
        });
    });

    // Update bulk delete button visibility
    function updateBulkDeleteButton() {
        const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
        bulkDeleteBtn.style.display = checkedCount > 0 ? 'inline-block' : 'none';
    }

    // Handle bulk delete form submission
    document.getElementById('bulkDeleteForm').addEventListener('submit', function(e) {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked').length;
        if (!confirm(`Are you sure you want to delete ${checkedBoxes} selected task${checkedBoxes > 1 ? 's' : ''}? This action cannot be undone.`)) {
            e.preventDefault();
        }
    });
});
</script>

<!-- Delete confirmation modal -->
<div id="confirmModal" class="modal" aria-hidden="true">
  <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
    <div class="modal-header">
      <h2 id="confirmTitle" class="modal-title">Confirm Delete</h2>
      <button type="button" class="close" id="closeConfirm">&times;</button>
    </div>
    <p style="color: #fff; margin: 20px 0;">Are you sure you want to delete this maintenance task? This action cannot be undone.</p>
    <div class="modal-footer">
      <button id="cancelDelete" class="btn btn-secondary">Cancel</button>
      <button id="confirmDelete" class="btn btn-primary" style="background: #dc3545;">Delete</button>
    </div>
  </div>
</div>

<script>
// Custom delete confirmation flow
(() => {
  const modal = document.getElementById('confirmModal');
  const cancel = document.getElementById('cancelDelete');
  const confirm = document.getElementById('confirmDelete');
  let targetForm = null;

  document.addEventListener('click', e => {
    if (e.target.closest('.delete-btn')) {
      e.preventDefault();
      // find the form enclosing this button
      targetForm = e.target.closest('form');
      if (!targetForm) return;
      modal.classList.add('show');
      modal.setAttribute('aria-hidden', 'false');
    }
  });

  cancel.addEventListener('click', () => {
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    targetForm = null;
  });

  confirm.addEventListener('click', () => {
    if (!targetForm) return;
    // append a hidden input to indicate deletion and submit
    let marker = targetForm.querySelector('input[name="delete_task"]');
    if (!marker) {
      marker = document.createElement('input');
      marker.type = 'hidden';
      marker.name = 'delete_task';
      marker.value = '1';
      targetForm.appendChild(marker);
    }
    targetForm.submit();
  });

  // close on ESC or click outside
  window.addEventListener('keydown', e => { if (e.key === 'Escape') { modal.classList.remove('show'); modal.setAttribute('aria-hidden','true'); targetForm=null; } });
  modal.addEventListener('click', e => { if (e.target === modal) { modal.classList.remove('show'); modal.setAttribute('aria-hidden','true'); targetForm=null; } });
})();
</script>

</body>
</html>
