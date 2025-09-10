<?php
require_once __DIR__ . '/../db_connector/db_connect.php';
include __DIR__ . '/../repo/StaffPerformanceRepository.php';
include 'StaffPerformanceService.php';

$db = new Database();
$conn = $db->getConnection();

$repo = new StaffPerformanceRepository($conn);
$service = new StaffPerformanceService($repo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_performance'])){
    $service->add($_POST);
    header("Location: hp_staff_performance.php?success=added");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_performance'])){
    $service->update($_POST);
    header("Location: hp_staff_performance.php?success=edited");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_performance'])){
    $service->delete((int)$_POST['performance_id']);
    header("Location: hp_staff_performance.php?success=deleted");
    exit;
}

$staffPerformance = $service->list();
$stats = $service->stats();
$staffList = $service->staffList();
$tasksList = $service->tasksList();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Performance Tracking</title>
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="../css/room_status_new.css">
<link rel="stylesheet" href="../css/maintenance.css">
<link rel="stylesheet" href="../css/tasks_new.css">
<link rel="stylesheet" href="../css/forms.css">
<link rel="stylesheet" href="../css/modals.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../js_assets/charts.js" defer></script>
<script src="../js_assets/performance.js" defer></script>
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <div class="nav-buttons">
                    <a href="../housekeeping.php" class="nav-btn back-btn"><i class="fas fa-arrow-left"></i> Back</a>
                    <a href="../../index.php" class="nav-btn back-btn"><i class="fas fa-home"></i> Home</a>
                </div>
      <h1>Staff Performance Tracking</h1>
    </header>

    <!-- Stats + Chart -->
    <div class="stats-chart-container">
      <div class="stats-summary">
        <div class="stat-card total"><span class="stat-label">Total Records</span><span class="stat-value"><?= $stats['total'] ?></span></div>
        <div class="stat-card cleaning"><span class="stat-label">Avg Tasks Completed</span><span class="stat-value"><?= round($stats['avgTasks'],2) ?></span></div>
        <div class="stat-card linen"><span class="stat-label">Avg Quality Rating</span><span class="stat-value"><?= round($stats['avgRating'],2) ?></span></div>
      </div>
      <div class="chart-container dual-charts">
        <canvas id="tasksChart"></canvas>
      </div>
    </div>

    <script>
      const ctx = document.getElementById('tasksChart').getContext('2d');
      new Chart(ctx, {
        type:'bar',
        data:{
          labels:['Avg Tasks Completed','Avg Quality Rating'],
          datasets:[{ label:'Performance Metrics', data:[<?= $stats['avgTasks'] ?>,<?= $stats['avgRating'] ?>], backgroundColor:['#2ecc71','#3498db'], borderColor:['#27ae60','#2980b9'], borderWidth:1 }]
        },
        options:{
          responsive:true,
          maintainAspectRatio:true,
          aspectRatio: 1.8,
          plugins:{ legend:{ display:false }, tooltip:{ backgroundColor:'rgba(0,0,0,0.7)', bodyColor:'#fff', titleColor:'#fff' } },
          scales:{ x:{ ticks:{ color:'#fff' } }, y:{ beginAtZero:true, stepSize:1, ticks:{ color:'#fff' } } }
        }
      });
    </script>


      <div style="text-align:center; margin:20px 0;">
        <button id="openModal" class="back-btn glass-btn">
          <i class="fas fa-plus"></i> Add Performance Record
        </button>
      </div>

    <!-- Add Modal -->
<div id="addModal" class="modal">
  <div class="modal-content glass">
    <span class="close">&times;</span>
    <h2>Add Performance Record</h2>
    <form method="POST">
      <select name="staff_id" required>
        <option value="">Select Staff</option>
        <?php foreach($staffList as $s) echo "<option value='{$s['staff_id']}'>{$s['full_name']}</option>"; ?>
      </select>
      <select name="task_id">
        <option value="">Select Task (Optional)</option>
        <?php foreach($tasksList as $t) echo "<option value='{$t['task_id']}'>{$t['task_type']}</option>"; ?>
      </select>
      <input type="date" name="date" required>
      <input type="number" name="tasks_completed" min="0" placeholder="Tasks Completed" required>
      <input type="time" name="average_completion_time" required>
      <input type="number" name="quality_rating" min="1" max="5" placeholder="Quality Rating" required>
      <textarea name="feedback" placeholder="Feedback"></textarea>
      <select name="evaluator_id" required>
        <option value="">Select Evaluator</option>
        <?php foreach($staffList as $s) echo "<option value='{$s['staff_id']}'>{$s['full_name']}</option>"; ?>
      </select>
      <button type="submit" name="add_performance"><i class="fas fa-plus"></i> Add Record</button>
    </form>
  </div>
</div>


    <script>
      document.addEventListener('DOMContentLoaded', ()=>{
        const modal = document.getElementById('addModal');
        const btn = document.getElementById('openModal');
        const close = modal ? modal.querySelector('.close') : null;
        if(!modal) return;
        // Move modal to body to avoid parent stacking contexts
        if(modal.parentElement !== document.body) document.body.appendChild(modal);
        modal.style.zIndex = 20000;
        modal.style.pointerEvents = 'auto';
        const show = ()=> { modal.classList.add('show'); document.body.style.overflow = 'hidden'; };
        const hide = ()=> { modal.classList.remove('show'); document.body.style.overflow = ''; };
        if(btn) btn.addEventListener('click', show);
        if(close) close.addEventListener('click', hide);
        window.addEventListener('click', e => { if(e.target === modal) hide(); });
      });
    </script>

    <!-- Performance Table -->
    <div class="table-container">
      <table class="room-table">
        <thead>
          <tr>
            <th>ID</th><th>Staff</th><th>Task</th><th>Date</th><th>Tasks Completed</th><th>Avg Time</th><th>Quality Rating</th><th>Feedback</th><th>Evaluator</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($staffPerformance as $row): ?>
          <tr>
            <td><?= $row['performance_id'] ?></td>
            <form method="POST">
              <input type="hidden" name="performance_id" value="<?= $row['performance_id'] ?>">
              <td><select name="staff_id" required><?php foreach($staffList as $s) { $selected = $s['staff_id']==$row['staff_id']?'selected':''; echo "<option value='{$s['staff_id']}' $selected>{$s['full_name']}</option>"; } ?></select></td>
              <td><select name="task_id"><option value="">Select Task</option><?php foreach($tasksList as $t) { $selected = $t['task_id']==$row['task_id']?'selected':''; echo "<option value='{$t['task_id']}' $selected>{$t['task_type']}</option>"; } ?></select></td>
              <td><input type="date" name="date" value="<?= $row['date'] ?>" required></td>
              <td><input type="number" name="tasks_completed" value="<?= $row['tasks_completed'] ?>" min="0" required></td>
              <td><input type="time" name="average_completion_time" value="<?= $row['average_completion_time'] ?>" required></td>
              <td><input type="number" name="quality_rating" value="<?= $row['quality_rating'] ?>" min="1" max="5" required></td>
              <td><input type="text" name="feedback" value="<?= htmlspecialchars($row['feedback']) ?>"></td>
              <td><select name="evaluator_id" required><?php foreach($staffList as $s) { $selected = $s['staff_id']==$row['evaluator_id']?'selected':''; echo "<option value='{$s['staff_id']}' $selected>{$s['full_name']}</option>"; } ?></select></td>
              <td>
                <button type="submit" name="edit_performance" class="btn save-btn"><i class="fas fa-save"></i> Save</button>
                <button type="submit" name="delete_performance" class="btn delete-btn" onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i> Delete</button>
              </td>
            </form>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
       </div> <!-- end table-container -->

    <footer>
      <a href="../housekeeping.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Housekeeping</a>
    </footer>
  </div> <!-- end container -->
</div> <!-- end overlay -->

<!-- Optional JS for better table styling or interactivity -->
<script>
  // Example: Highlight row on hover
  const rows = document.querySelectorAll('.room-table tbody tr');
  rows.forEach(row => {
    row.addEventListener('mouseover', () => row.style.backgroundColor = 'rgba(255,255,255,0.1)');
    row.addEventListener('mouseout', () => row.style.backgroundColor = '');
  });
</script>
</body>
</html>

