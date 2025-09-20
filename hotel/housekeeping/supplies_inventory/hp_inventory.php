<?php
require_once __DIR__ . '/../db_connector/db_connect.php';
require_once __DIR__ . '/adapters/InventoryAdapter.php';
require_once __DIR__ . '/db/InventoryLogger.php';
include 'Supplies.php';
include __DIR__ . '/../repo/SupplyRepository.php';
include 'SupplyService.php';

use Housekeeping\Supplies\Adapters\InventoryAdapter;

// Create database connections
$db = new Database(); // Database class from db_connect.php
$conn = $db->getConnection();

// Create PDO connection
try {
    $dsn = "mysql:host=127.0.0.1;dbname=hotel;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}
catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$inventoryAdapter = new InventoryAdapter($pdo);
$repo = new SupplyRepository($conn);
$service = new SupplyService($repo, $inventoryAdapter);
// Early GET endpoint for stats so AJAX fetches receive JSON (avoid full page HTML)
if (isset($_GET['get_stats'])) {
  if (ob_get_level()) ob_clean();
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($service->counts());
  exit;
}

// ================= HANDLE FORM SUBMISSIONS =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Add new item
        if (isset($_POST['add_item'])) {
            $service->save([
                'item_name' => $_POST['item_name'],
                'category' => $_POST['category'],
                'quantity' => (int)$_POST['quantity'],
                'unit' => $_POST['unit'],
                'reorder_level' => (int)$_POST['reorder_level']
            ]);

            // If this is an AJAX request, return JSON including rendered row HTML
            if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
                // Re-fetch the last inserted item to render a table row
                $supplies = $service->list();
                $newItem = end($supplies);

                // Render a table row HTML string for the new item
                ob_start();
                ?>
                <tr data-item-id="<?= $newItem->item_id ?>">
                  <td>
                    <input type="checkbox" name="selected_items[]" form="bulkDeleteForm" value="<?= $newItem->item_id ?>" class="item-checkbox">
                  </td>
                  <td><?= $newItem->item_id ?></td>
                  <td><?= htmlspecialchars($newItem->item_name, ENT_QUOTES) ?></td>
                  <td><?= htmlspecialchars($newItem->category, ENT_QUOTES) ?></td>
                  <td><input type="number" name="quantity" form="form-<?= $newItem->item_id ?>" value="<?= $newItem->quantity ?>" required></td>
                  <td><?= htmlspecialchars($newItem->unit, ENT_QUOTES) ?></td>
                  <td><input type="number" name="reorder_level" form="form-<?= $newItem->item_id ?>" value="<?= $newItem->reorder_level ?>" required></td>
                  <td>
                    <?php $status = (is_numeric($newItem->quantity) && is_numeric($newItem->reorder_level) && ((int)$newItem->quantity <= (int)$newItem->reorder_level)) ? 'Low' : 'OK'; ?>
                    <span class="badge <?= $status === 'Low' ? 'low' : 'ok' ?>"><?= $status === 'Low' ? 'Low stock' : 'OK' ?></span>
                  </td>
                  <td>
                    <form id="form-<?= $newItem->item_id ?>" method="POST" action="hp_inventory.php" style="display:inline;">
                      <input type="hidden" name="item_id" value="<?= $newItem->item_id ?>">
                      <button type="submit" name="update_supply" class="btn edit-btn"><i class="fas fa-save"></i> Update</button>
                      <button type="button" onclick="confirmDelete(<?= $newItem->item_id ?>, '<?= htmlspecialchars($newItem->item_name, ENT_QUOTES) ?>')" class="btn delete-btn">
                        <i class="fas fa-trash"></i> Delete
                      </button>
                    </form>
                  </td>
                </tr>
                <?php
                $rowHtml = ob_get_clean();

        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
          'success' => true,
          'counts' => $service->counts(),
          'row_html' => $rowHtml
        ]);
                exit;
            }

            header("Location: hp_inventory.php?success=added");
            exit;
        }

        // Update existing item
    if (isset($_POST['update_supply'])) {
      $service->save([
        'item_id' => (int)$_POST['item_id'],
        'quantity' => (int)$_POST['quantity'],
        'reorder_level' => (int)$_POST['reorder_level']
      ]);
      if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'counts' => $service->counts()]);
        exit;
      }
      header("Location: hp_inventory.php?success=updated");
      exit;
    }

    // Delete single item (legacy non-AJAX) - prefer delete_supply.php for AJAX deletes
    if (isset($_POST['delete_item'])) {
      $service->delete((int)$_POST['item_id']);
      if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'counts' => $service->counts()]);
        exit;
      }
      header("Location: hp_inventory.php?success=deleted");
      exit;
    }

  // (stats endpoint handled earlier)

    // Handle bulk delete
    if (isset($_POST['delete_selected']) && isset($_POST['selected_items'])) {
      foreach ($_POST['selected_items'] as $item_id) {
        $service->delete((int)$item_id);
      }
      if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'counts' => $service->counts()]);
        exit;
      }
      header("Location: hp_inventory.php?success=deleted");
      exit;
    }

  } catch (Throwable $e) {
    $flash = ['type'=>'error','msg'=>$e->getMessage()];
  }

}

// ================= FETCH DATA =================
$supplies = $service->list();
$counts = $service->counts();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Housekeeping Inventory</title>
  <link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="../css/room_status_new.css">
<link rel="stylesheet" href="../css/maintenance.css">
<link rel="stylesheet" href="../css/tasks_new.css">
<link rel="stylesheet" href="../css/forms.css">
<link rel="stylesheet" href="../css/modals.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/notifications.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    // Function to check inventory status
    async function checkInventoryStatus() {
        try {
            const response = await fetch('get_inventory_status.php');
            const data = await response.json();
            
            if (data.hasUpdates) {
                showNotification('ℹ Inventory status has changed. Refreshing...', 'info');
                location.reload();
            }
        } catch (error) {
            console.error('Error checking inventory status:', error);
        }
    }

    // Check inventory status every 30 seconds
    setInterval(checkInventoryStatus, 30000);

    // Handle success messages from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        const action = urlParams.get('success');
        switch (action) {
            case 'added':
                showNotification('✅ Item added successfully', 'success');
                break;
            case 'updated':
                showNotification('✅ Item updated successfully', 'success');
                break;
            case 'deleted':
                showNotification('✅ Item(s) deleted successfully', 'success');
                break;
        }
        // Clean up URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
  </script>
  <style>
  /* Page layout and theme to match screenshot */
  :root{--card-bg:rgba(0,0,0,0.45); --card-border:rgba(255,255,255,0.06);}
  body { font-family: 'Poppins', Arial, Helvetica, sans-serif; background: linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.65)), url('../../homepage/hotel_room.jpg') center/cover no-repeat fixed; color:#fff; margin:0; padding:28px; }
  .container { max-width:1200px; margin:0 auto; }
  header h1 { text-align:center; margin:0 0 18px 0; font-size:28px; font-weight:700; letter-spacing:0.6px; }
  .stats { display:flex; gap:18px; margin-bottom:28px; justify-content:center; }
  .stat-card { background:var(--card-bg); padding:22px 28px; border-radius:12px; min-width:210px; box-shadow: 0 8px 30px rgba(0,0,0,0.6); border-left:6px solid rgba(0,0,0,0); }
  .stat-card.cleaning{ border-left-color:#4CAF50 }
  .stat-card.linen{ border-left-color:#f44336 }
  .stat-card.toiletry{ border-left-color:#ffc107 }
  .stat-card.total{ border-left-color:#1976d2 }
  .stat-card h4 { margin:0 0 12px 0; font-size:14px; color:#ddd; text-align:center }
  .stat-card span { display:block; text-align:center; font-size:28px; font-weight:800 }

  .chart-wrapper { background:var(--card-bg); padding:36px; border-radius:12px; box-shadow:0 8px 40px rgba(0,0,0,0.6); margin-bottom:22px; display:flex; align-items:center; justify-content:center; flex-direction:column }
  .chart-canvas { width:320px; height:380px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; }
  canvas#supplyChart { width:260px !important; height:340px !important; display:block; }
  .chart-legend { margin-top:16px; display:flex; gap:20px; align-items:center; justify-content:center }
  .chart-legend span { display:inline-flex; gap:8px; align-items:center; font-weight:700 }
  .legend-box { width:22px; height:12px; display:inline-block; border-radius:3px }

  .inventory-panel { margin-top:18px }
  table { width:100%; border-collapse:collapse; margin-top:12px; background:transparent }
  table thead th { text-align:left; padding:14px; border-bottom:1px solid var(--card-border); color:#ddd }
  table tbody td { padding:12px; border-bottom:1px solid rgba(255,255,255,0.03); }

  /* Add form styling */
  .add-card { background:var(--card-bg); padding:28px; border-radius:12px; width:480px; margin:18px auto 40px; box-shadow:0 12px 40px rgba(0,0,0,0.6); }
  .add-card h2 { text-align:center; margin-top:0 }
  .form-row { margin-bottom:12px }
    .form-row label { display:block; color:#ddd; margin-bottom:6px; font-size:13px }
    /* Inputs styled to match table/theme: subtle inset, translucent, white text */
    .form-row input, .form-row select {
      width:100%; padding:10px 12px; border-radius:8px; border:1px solid rgba(255,255,255,0.06);
      background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); color:#fff; box-shadow: inset 0 2px 6px rgba(0,0,0,0.45);
    }
    .form-row input::placeholder { color: rgba(255,255,255,0.35) }
  .btn-primary { background:#f7b500; color:#111; padding:10px 22px; border-radius:8px; border:none; font-weight:700; cursor:pointer; display:block; margin:12px auto 0 }
    @media (max-width:900px){ .stats{flex-wrap:wrap} .add-card{width:95%} }

    /* Inventory table card and theme to match task assignment panel */
    .table-card { background: rgba(0,0,0,0.5); padding:18px; border-radius:12px; box-shadow:0 12px 30px rgba(0,0,0,0.6); margin-top:18px; }
    #inventoryTable_wrapper, .table-card { width:100%; }
    /* Make header stand out */
    #inventoryTable thead { background: linear-gradient(90deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)); }
    #inventoryTable thead th { color:#f3f3f3; font-weight:700; padding:14px 12px; border-bottom:1px solid rgba(255,255,255,0.06); }
    #inventoryTable tbody tr { background: rgba(255,255,255,0.02); transition: background .15s ease, transform .12s ease; }
    #inventoryTable tbody tr:nth-child(even) { background: rgba(255,255,255,0.015); }
    #inventoryTable tbody tr:hover { background: rgba(255,255,255,0.04); transform: translateY(-2px); }
    #inventoryTable td { padding:12px; color:#e9e9e9; vertical-align:middle }
    .item-checkbox { width:16px; height:16px; transform:translateY(1px) }
    .badge { display:inline-block; padding:6px 10px; border-radius:8px; font-weight:700; font-size:12px }
    .badge.ok { background: rgba(76,175,80,0.12); color:#b8f0b8; }
    .badge.low { background: rgba(231,76,60,0.12); color:#ffd6d4; }

    /* Table cell inputs (quantity / reorder_level) styled to match theme */
    #inventoryTable tbody input[type="number"],
    #inventoryTable tbody input[type="text"] {
      width:100%; max-width:120px; padding:8px 10px; border-radius:8px; border:1px solid rgba(255,255,255,0.06);
      background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); color:#fff; box-shadow: inset 0 2px 6px rgba(0,0,0,0.45);
      transition: box-shadow .12s ease, border-color .12s ease, transform .08s ease;
    }
    #inventoryTable tbody input[type="number"]:focus,
    #inventoryTable tbody input[type="text"]:focus {
      outline: none; border-color: rgba(247,181,0,0.9); box-shadow: 0 6px 18px rgba(247,181,0,0.12) inset;
      transform: translateY(-1px);
    }

    /* Action buttons - clearer colors and hover states */
    .btn { padding:8px 12px; border-radius:8px; border:none; cursor:pointer; font-weight:700; transition: transform .08s ease, box-shadow .12s ease; }
    .btn.edit-btn {
      background: linear-gradient(90deg,#1976d2,#4e73df); color:#fff; box-shadow: 0 6px 18px rgba(30,100,200,0.18);
    }
    .btn.edit-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(30,100,200,0.22); }
    .btn.delete-btn {
      background: linear-gradient(90deg,#e74c3c,#ff6b6b); color:#fff; box-shadow: 0 6px 18px rgba(231,76,60,0.16);
    }
    .btn.delete-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(231,76,60,0.22); }

    /* Back button consistent style */
    .back-btn { display:inline-block; background:#f7b500; color:#111; padding:12px 20px; border-radius:10px; text-decoration:none; font-weight:800; margin:22px auto 0 }

    /* Make modals and forms contrast more with background */
    .confirm-box, .add-card { background: linear-gradient(180deg, rgba(0,0,0,0.55), rgba(0,0,0,0.45)); border:1px solid rgba(255,255,255,0.04); }

    /* Responsive tweaks */
    @media (max-width:700px) {
      #inventoryTable thead { display:none }
      #inventoryTable tbody td { display:block; width:100%; }
      #inventoryTable tbody tr { display:block; margin-bottom:12px; }
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1>Housekeeping - Supplies Inventory</h1>
    </header>

    <!-- Stat cards used by JS -->
    <section class="stats">
      <div class="stat-card cleaning"><h4>Cleaning</h4><span><?= (int)($counts['cleaning'] ?? 0) ?></span></div>
      <div class="stat-card linen"><h4>Linen</h4><span><?= (int)($counts['linen'] ?? 0) ?></span></div>
      <div class="stat-card toiletry"><h4>Toiletry</h4><span><?= (int)($counts['toiletry'] ?? 0) ?></span></div>
      <div class="stat-card total"><h4>Total</h4><span><?= (int)($counts['total'] ?? 0) ?></span></div>
    </section>

    <!-- Inventory table and forms -->
    <div class="inventory-panel">

      <!-- Chart area -->
      <div class="chart-wrapper">
        <div class="chart-canvas">
          <canvas id="supplyChart"></canvas>
        </div>
        <div class="chart-legend">
          <span><i class="legend-box" style="background:#4CAF50"></i> Cleaning</span>
          <span><i class="legend-box" style="background:#f44336"></i> Linen</span>
          <span><i class="legend-box" style="background:#ffc107"></i> Toiletry</span>
        </div>
      </div>

      <!-- Add item form -->
      <div class="add-card">
        <h2>Add New Item</h2>
        <form id="addItemForm" method="POST" action="hp_inventory.php">
          <div class="form-row">
            <label for="item_name">Item Name</label>
            <input type="text" id="item_name" name="item_name" placeholder="Item Name" required>
          </div>
          <div class="form-row">
            <label for="category">Category</label>
            <select id="category" name="category" required>
              <option value="" disabled selected style="color:rgba(9, 9, 9, 0.6);">Select category</option>
              <option value="cleaning" style="color:rgba(9, 9, 9, 0.6);">Cleaning</option>
              <option value="linen" style="color:rgba(9, 9, 9, 0.6);">Linen</option>
              <option value="toiletry" style="color:rgba(9, 9, 9, 0.6);">Toiletry</option>
            </select>
          </div>
          <div class="form-row">
            <label for="quantity">Quantity <small style="color:rgba(255,255,255,0.6); font-weight:400; font-size:12px">(leave blank to set later)</small></label>
            <input type="number" id="quantity" name="quantity" placeholder="e.g. 10" min="0">
          </div>
          <div class="form-row">
            <label for="unit">Unit <small style="color:rgba(255,255,255,0.6); font-weight:400; font-size:12px">(e.g. pcs, kg, L, liters)</small></label>
            <input type="text" id="unit" name="unit" placeholder="e.g. pcs, kg, L">
          </div>
          <div class="form-row">
            <label for="reorder_level">Reorder Level <small style="color:rgba(255,255,255,0.6); font-weight:400; font-size:12px">(threshold to mark Low)</small></label>
            <input type="number" id="reorder_level" name="reorder_level" placeholder="e.g. 5" min="0">
          </div>
          <button type="submit" class="btn-primary">Add Item</button>
        </form>
      </div>

      <!-- Hidden bulk delete form used by JS -->
      <form id="bulkDeleteForm" method="POST" style="display:none">
        <input type="hidden" name="delete_selected" value="1">
      </form>

  <div class="table-card">
  <table id="inventoryTable">
          <thead>
            <tr>
              <th><input type="checkbox" id="selectAll"> Select</th>
              <th>ID</th>
              <th>Supply Name</th>
              <th>Category</th>
              <th>Quantity</th>
              <th>Unit</th>
              <th>Reorder Level</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($supplies as $row): ?>
              <tr data-item-id="<?= $row->item_id ?>">
                <td>
                  <input type="checkbox" name="selected_items[]" form="bulkDeleteForm" value="<?= $row->item_id ?>" class="item-checkbox">
                </td>
                <td><?= $row->item_id ?></td>
                <td><?= $row->item_name ?></td>
                <td><?= $row->category ?></td>
                <td><input type="number" name="quantity" form="form-<?= $row->item_id ?>" value="<?= $row->quantity ?>" required></td>
                <td><?= $row->unit ?></td>
                <td><input type="number" name="reorder_level" form="form-<?= $row->item_id ?>" value="<?= $row->reorder_level ?>" required></td>
                <td>
                  <?php
                    $status = 'OK';
                    if (is_numeric($row->quantity) && is_numeric($row->reorder_level)) {
                        if ((int)$row->quantity <= (int)$row->reorder_level) $status = 'Low';
                    }
                  ?>
                  <span class="badge <?= $status === 'Low' ? 'low' : 'ok' ?>"><?= $status === 'Low' ? 'Low stock' : 'OK' ?></span>
                </td>
                <td>
                  <form id="form-<?= $row->item_id ?>" method="POST" action="hp_inventory.php" style="display:inline;">
                    <input type="hidden" name="item_id" value="<?= $row->item_id ?>">
                    <button type="submit" name="update_supply" class="btn edit-btn"><i class="fas fa-save"></i> Update</button>
                    <button type="button" onclick="confirmDelete(<?= $row->item_id ?>, '<?= htmlspecialchars($row->item_name) ?>')" class="btn delete-btn">
                      <i class="fas fa-trash"></i> Delete
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <footer>
        <a href="../housekeeping.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Housekeeping</a>
      </footer>
    </div>
  </div>

  <!-- Bulk delete handling -->
  <script>
  document.addEventListener('DOMContentLoaded', function() {
      // Handle select all checkbox
  const selectAll = document.getElementById('selectAll');
  let rowCheckboxes = [];
  const bulkDeleteBtn = document.querySelector('.bulk-delete-btn');

      // Re-attach handlers for checkboxes and update/delete buttons after dynamic changes
      function attachRowHandlers() {
        rowCheckboxes = Array.from(document.querySelectorAll('.item-checkbox'));

        // selectAll behavior
        if (selectAll) {
          selectAll.removeEventListener('change', selectAllChangeHandler);
          selectAll.addEventListener('change', selectAllChangeHandler);
        }

        // Individual checkbox handlers
        rowCheckboxes.forEach(checkbox => {
          checkbox.removeEventListener('change', checkboxChangeHandler);
          checkbox.addEventListener('change', checkboxChangeHandler);
        });

        // Update bulk delete button right away
        updateBulkDeleteButton();
      }

      function selectAllChangeHandler() {
        rowCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
        updateBulkDeleteButton();
      }

      function checkboxChangeHandler() {
        const allChecked = rowCheckboxes.length > 0 && rowCheckboxes.every(box => box.checked);
        const someChecked = rowCheckboxes.some(box => box.checked);
        if (selectAll) {
          selectAll.checked = allChecked;
          selectAll.indeterminate = someChecked && !allChecked;
        }
        updateBulkDeleteButton();
      }

      // Update bulk delete button visibility
      function updateBulkDeleteButton() {
          const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
          if (bulkDeleteBtn) bulkDeleteBtn.style.display = checkedCount > 0 ? 'inline-block' : 'none';
      }

      // Initial attach
      attachRowHandlers();

      // Handle bulk delete form submission
  const bulkFormEl = document.getElementById('bulkDeleteForm');
  if (bulkFormEl) bulkFormEl.addEventListener('submit', function(e) {
          const checkedBoxes = document.querySelectorAll('.item-checkbox:checked').length;
          if (!confirm(`Are you sure you want to delete ${checkedBoxes} selected item${checkedBoxes > 1 ? 's' : ''}? This action cannot be undone.`)) {
              e.preventDefault();
          }
      });
  });
  </script>
      </footer>
    </div>
  </div>

  <script>
  const supplyCanvas = document.getElementById('supplyChart');
  if (supplyCanvas) {
    const chartCtx = supplyCanvas.getContext('2d');
    // If an earlier chart instance exists, destroy it to avoid duplicate drawings
    try {
      if (window.supplyChart && typeof window.supplyChart.destroy === 'function') {
        window.supplyChart.destroy();
      }
    } catch (e) {
      console.warn('Error destroying existing supplyChart:', e);
    }
    window.supplyChart = new Chart(chartCtx, {
      type: 'pie',
      data: {
        labels: ['Cleaning', 'Linen', 'Toiletry'],
        datasets: [{
          data: [<?= $counts['cleaning'] ?>, <?= $counts['linen'] ?>, <?= $counts['toiletry'] ?>],
          backgroundColor: ['#4CAF50', '#f44336', '#ffc107'],
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
          padding: {
            top: 5,
            bottom: 20
          }
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: '#fff',
              font: {
                size: 12,
                weight: 'bold'
              },
              padding: 10,
              boxWidth: 12,
              usePointStyle: true,
              pointStyle: 'rectRounded'
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const value = context.raw;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = ((value / total) * 100).toFixed(1);
                return `${context.label}: ${value} (${percentage}%)`;
              }
            },
            titleColor: '#fff',
            bodyColor: '#fff',
            backgroundColor: 'rgba(0,0,0,0.8)',
            padding: 12,
            bodyFont: {
              size: 14
            },
            cornerRadius: 8
          }
        }
      }
      });
    }
    </script>

  <script>
    // Utility: safely parse JSON responses that may have BOM or stray whitespace
    function safeParseJSON(text) {
      if (!text) return {};
      // Trim invisible BOM and whitespace
      text = text.replace(/^\uFEFF/, '').trim();
      return JSON.parse(text);
    }
    // ================= Add Item Form Handler =================
    document.addEventListener('DOMContentLoaded', function() {
      const addForm = document.getElementById('addItemForm');
      if (addForm) {
        addForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const addBtn = addForm.querySelector('button[type="submit"]');
          if (addBtn) {
            addBtn.disabled = true;
            const spinner = document.createElement('span');
            spinner.className = 'spinner';
            spinner.style.marginLeft = '8px';
            spinner.innerHTML = '⏳';
            addBtn.appendChild(spinner);
          }

          const formData = new FormData(this);
          formData.append('add_item', '1');
          formData.append('ajax', '1');
          
          fetch('hp_inventory.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            // If server returned row_html, insert the new row into the table
      if (data.row_html) {
                const tbody = document.querySelector('#inventoryTable tbody');
                if (tbody) {
                    // insert as last row
                    const temp = document.createElement('tbody');
                    temp.innerHTML = data.row_html;
                    const newRow = temp.firstElementChild;
                    if (newRow) tbody.appendChild(newRow);
        // reattach handlers for newly added elements
        attachRowHandlers();
                }
            }

            // Update stat cards (counts may be nested under 'counts' or direct)
            const counts = data.counts || data;
            document.querySelector('.stat-card.cleaning span:last-child').textContent = counts.cleaning;
            document.querySelector('.stat-card.linen span:last-child').textContent = counts.linen;
            document.querySelector('.stat-card.toiletry span:last-child').textContent = counts.toiletry;
            document.querySelector('.stat-card.total span:last-child').textContent = counts.total;

            // Update pie chart
            if (window.supplyChart) {
              window.supplyChart.data.datasets[0].data = [counts.cleaning, counts.linen, counts.toiletry];
              window.supplyChart.update();
            }

            // Reset form and keep UI updated in-place (no reload)
            addForm.reset();
            addBtn.disabled = false;
            addBtn.querySelector('.spinner')?.remove();
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('❌ Error adding item. Please try again.', 'error');
          });
        });
      }

      // Handle delete operations update
      function updateStatsAndChart() {
        fetch('hp_inventory.php?get_stats=1')
          .then(response => response.text())
          .then(text => {
            try {
              const data = safeParseJSON(text);
              // Update stat cards
              document.querySelector('.stat-card.cleaning span:last-child').textContent = data.cleaning;
              document.querySelector('.stat-card.linen span:last-child').textContent = data.linen;
              document.querySelector('.stat-card.toiletry span:last-child').textContent = data.toiletry;
              document.querySelector('.stat-card.total span:last-child').textContent = data.total;

              // Update pie chart
              if (window.supplyChart) {
                window.supplyChart.data.datasets[0].data = [data.cleaning, data.linen, data.toiletry];
                window.supplyChart.update();
              }
            } catch (err) {
              console.error('Failed to parse get_stats response as JSON. Raw response:\n', text, '\nError:', err);
            }
          })
          .catch(err => console.error('Network error fetching stats:', err));
      }

      // Apply counts object to stat cards and chart immediately
      function applyCounts(counts) {
        if (!counts) return;
        document.querySelector('.stat-card.cleaning span:last-child').textContent = counts.cleaning ?? counts.cleaning_count ?? 0;
        document.querySelector('.stat-card.linen span:last-child').textContent = counts.linen ?? counts.linen_count ?? 0;
        document.querySelector('.stat-card.toiletry span:last-child').textContent = counts.toiletry ?? counts.toiletry_count ?? 0;
        document.querySelector('.stat-card.total span:last-child').textContent = counts.total ?? ( (counts.cleaning||0) + (counts.linen||0) + (counts.toiletry||0) );

        if (window.supplyChart && Array.isArray(window.supplyChart.data.datasets) && window.supplyChart.data.datasets[0]) {
          const c = [counts.cleaning ?? counts.cleaning_count ?? 0, counts.linen ?? counts.linen_count ?? 0, counts.toiletry ?? counts.toiletry_count ?? 0];
          window.supplyChart.data.datasets[0].data = c;
          window.supplyChart.update();
        }
      }

      // Update stats after delete operations
      const deleteForm = document.getElementById('deleteForm');
      if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const formData = new FormData(this);
          const itemId = formData.get('item_id');

          const deleteBtn = deleteForm.querySelector('button[type="submit"], .delete-confirm');
          if (deleteBtn) { deleteBtn.disabled = true; deleteBtn.dataset.origText = deleteBtn.textContent; deleteBtn.textContent = 'Deleting...'; }

          fetch('hp_inventory.php', {
            method: 'POST',
            body: formData
          })
          .then(async r => {
            const text = await r.text();
            try {
              const data = safeParseJSON(text);
              if (data && data.success) {
                // Remove deleted row
                const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
                if (row) row.remove();
                else {
                  // fallback: try to find by cell text
                  const rows = Array.from(document.querySelectorAll('#inventoryTable tbody tr'));
                  const found = rows.find(tr => tr.cells[1] && tr.cells[1].textContent.trim() === String(itemId));
                  if (found) found.remove();
                }

                // reattach handlers and update UI
                attachRowHandlers();
                applyCounts(data.counts || data);
                closeDeleteModal();
              } else {
                alert('Error deleting item: ' + (data && data.message ? data.message : 'Unknown error'));
              }
            } catch (err) {
              console.error('Delete response not JSON, raw response:\n', text, '\nError:', err);
              alert('Server returned unexpected response while deleting. Check console.');
            }

            if (deleteBtn) { deleteBtn.disabled = false; deleteBtn.textContent = deleteBtn.dataset.origText || 'Delete'; }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('Error deleting item. Please try again.');
            if (deleteBtn) { deleteBtn.disabled = false; deleteBtn.textContent = deleteBtn.dataset.origText || 'Delete'; }
          });
        });
      }

      // Update stats after bulk delete
      const bulkFormEl2 = document.getElementById('bulkDeleteForm');
      if (bulkFormEl2) bulkFormEl2.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('hp_inventory.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Remove rows for deleted items
            const deleted = formData.getAll('selected_items[]');
            deleted.forEach(id => {
              const row = document.querySelector(`tr[data-item-id="${id}"]`);
              if (row) row.remove();
            });

            // Apply returned counts
            applyCounts(data.counts || data);
            closeBulkDeleteModal();
          } else {
            alert('Error deleting items: ' + (data.message || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error deleting items. Please try again.');
        });
      });
    });
  </script>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="confirm-modal">
    <div class="confirm-box">
      <h3>Delete Item</h3>
      <p>Are you sure you want to delete "<span id="deleteItemName"></span>"?</p>
      <form id="deleteForm" method="POST">
        <input type="hidden" name="item_id" id="deleteItemId">
        <div class="confirm-actions">
          <button type="button" class="btn cancel-btn" onclick="closeDeleteModal()">Cancel</button>
          <button type="button" name="delete_item" class="btn delete-confirm" onclick="performDelete(this)">Delete</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Bulk Delete Confirmation Modal -->
  <div id="bulkDeleteModal" class="confirm-modal">
    <div class="confirm-box">
      <h3>Delete Multiple Items</h3>
      <p id="bulkDeleteMessage">Select items to delete</p>
      <div class="selected-items" id="selectedItemsList"></div>
      <div class="confirm-actions">
          <button type="button" class="btn cancel-btn" onclick="closeBulkDeleteModal()">Cancel</button>
          <button type="button" class="btn delete-confirm" onclick="confirmBulkDelete()">Delete Selected</button>
      </div>
    </div>
  </div>

  <script>
    function confirmDelete(itemId, itemName) {
      document.getElementById('deleteItemName').textContent = itemName;
      document.getElementById('deleteItemId').value = itemId;
      document.getElementById('deleteModal').classList.add('show');
    }

    // Perform AJAX delete directly (works for newly added rows too)
    function performDelete(button) {
      const idEl = document.getElementById('deleteItemId');
      const itemId = idEl ? idEl.value : null;
      if (!itemId) return alert('Invalid item id');

      const deleteBtn = button || document.querySelector('.delete-confirm');
      if (deleteBtn) { deleteBtn.disabled = true; deleteBtn.dataset.origText = deleteBtn.textContent; deleteBtn.textContent = 'Deleting...'; }

  const fd = new FormData();
      fd.append('item_id', itemId);
      fd.append('ajax', '1');
      fd.append('delete_item', '1');

      fetch('hp_inventory.php', { method: 'POST', body: fd })
        .then(async r => {
          const text = await r.text();
          try {
            const data = safeParseJSON(text);
            if (data && data.success) {
              const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
              if (row) row.remove();
              applyCounts(data.counts || data);
              closeDeleteModal();
            } else {
              alert('Error deleting item: ' + (data && data.message ? data.message : 'Unknown'));
            }
          } catch (err) {
            console.error('Delete response not JSON, raw response:\n', text, '\nError:', err);
            alert('Server returned unexpected response while deleting. Check console.');
          }
        })
        .catch(err => {
          console.error('Delete error:', err);
          alert('Error deleting item');
        })
        .finally(() => {
          if (deleteBtn) { deleteBtn.disabled = false; deleteBtn.textContent = deleteBtn.dataset.origText || 'Delete'; }
        });
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.remove('show');
    }

    function showBulkDeleteModal() {
      const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
      const selectedItems = Array.from(checkedBoxes).map(checkbox => {
        const row = checkbox.closest('tr');
        const itemName = row.cells[2].textContent; // Item name is in the second column
        return itemName;
      });

      const modal = document.getElementById('bulkDeleteModal');
      const message = document.getElementById('bulkDeleteMessage');
      const itemsList = document.getElementById('selectedItemsList');
      
      message.textContent = `Are you sure you want to delete these ${checkedBoxes.length} item${checkedBoxes.length > 1 ? 's' : ''}?`;
      
      if (selectedItems.length > 0) {
        itemsList.innerHTML = selectedItems.map(name => 
          `<div class="selected-item"><i class="fas fa-box"></i> ${name}</div>`
        ).join('');
      }
      
      modal.classList.add('show');
    }

    function closeBulkDeleteModal() {
      document.getElementById('bulkDeleteModal').classList.remove('show');
    }

    function confirmBulkDelete() {
      document.getElementById('bulkDeleteForm').submit();
    }

    // Close modals if clicking outside
    document.querySelectorAll('.confirm-modal').forEach(modal => {
      modal.addEventListener('click', function(e) {
        if (e.target === this) {
          this.classList.remove('show');
        }
      });
    });

    // Update bulk delete form submit handler
    const bulkFormEl3 = document.getElementById('bulkDeleteForm');
    if (bulkFormEl3) {
      bulkFormEl3.addEventListener('submit', function(e) {
        e.preventDefault();
        showBulkDeleteModal();
      });
    }
  </script>

  <style>
    .selected-items {
      margin: 15px 0;
      max-height: 150px;
      overflow-y: auto;
      background: rgba(0,0,0,0.2);
      border-radius: 8px;
      padding: 8px;
    }
    
    .selected-item {
      padding: 8px 12px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      color: #fff;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .selected-item:last-child {
      border-bottom: none;
    }
    
    .selected-item i {
      color: #3498db;
      font-size: 12px;
    }

    .confirm-modal {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.7);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }

    .confirm-modal.show {
      display: flex;
    }

    .confirm-box {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      padding: 25px;
      border-radius: 12px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.3);
      border: 1px solid rgba(255,255,255,0.1);
    }

    .confirm-box h3 {
      margin: 0 0 15px 0;
      color: #fff;
      font-size: 1.2em;
    }

    .confirm-box p {
      margin: 0 0 20px 0;
      color: rgba(255,255,255,0.9);
    }

    .confirm-actions {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }

    .confirm-actions button {
      padding: 8px 16px;
      border-radius: 6px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .cancel-btn {
      background: rgba(255,255,255,0.1);
      color: #fff;
    }

    .cancel-btn:hover {
      background: rgba(255,255,255,0.2);
    }

    .delete-confirm {
      background: #e74c3c;
      color: #fff;
    }

    .delete-confirm:hover {
      background: #c0392b;
    }
  </style>
</body>
</html>
