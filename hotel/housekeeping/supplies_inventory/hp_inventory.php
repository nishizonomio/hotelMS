<?php
require_once __DIR__ . '/../db_connector/db_connect.php';
include 'Supplies.php';
include __DIR__ . '/../repo/SupplyRepository.php';
include 'SupplyService.php';

$db = new Database(); // Database class from db_connect.php
$conn = $db->getConnection();

$repo = new SupplyRepository($conn);
$service = new SupplyService($repo);

// ================= HANDLE FORM SUBMISSIONS =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_item'])) {
            $service->save([
                'item_name' => $_POST['item_name'],
                'category' => $_POST['category'],
                'quantity' => (int)$_POST['quantity'],
                'unit' => $_POST['unit'],
                'reorder_level' => (int)$_POST['reorder_level']
            ]);

            if (isset($_POST['ajax'])) {
                echo json_encode($service->counts());
                exit;
            } else {
                header("Location: hp_inventory.php?success=added");
                exit;
            }
        }

        if (isset($_POST['update_supply'])) {
            $service->save([
                'item_id' => (int)$_POST['item_id'],
                'quantity' => (int)$_POST['quantity'],
                'reorder_level' => (int)$_POST['reorder_level']
            ]);
            header("Location: hp_inventory.php?success=updated");
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

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Supplies Inventory</title>
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/room_status_new.css">
  <link rel="stylesheet" href="../css/inventory.css">
  <link rel="stylesheet" href="../css/tasks_new.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="overlay supplies-inventory">
    <div class="container">
      <header>
        <div class="nav-buttons">
                    <a href="../housekeeping.php" class="nav-btn back-btn"><i class="fas fa-arrow-left"></i> Back</a>
                    <a href="../../index.php" class="nav-btn back-btn"><i class="fas fa-home"></i> Home</a>
                </div>
        <h1>Supplies Inventory</h1>
      </header>

      <!-- Stats + Chart -->
      <div class="stats-chart-container">
        <div class="stats-summary">
          <div class="stat-card total"><span class="stat-label">Total Items</span><span class="stat-value"><?= $counts['total'] ?></span></div>
          <div class="stat-card cleaning"><span class="stat-label">Cleaning</span><span class="stat-value"><?= $counts['cleaning'] ?></span></div>
          <div class="stat-card linen"><span class="stat-label">Linen</span><span class="stat-value"><?= $counts['linen'] ?></span></div>
          <div class="stat-card toiletry"><span class="stat-label">Toiletry</span><span class="stat-value"><?= $counts['toiletry'] ?></span></div>
        </div>
        <div class="chart-container"><canvas id="supplyChart"></canvas></div>
      </div>

  <!-- Add New Item -->
  <div class="add-item-form">
        <h2>Add New Item</h2>
        <form id="addItemForm">
          <input type="text" name="item_name" placeholder="Item Name" required>
          <select name="category" required>
            <option value="">Select Category</option>
            <option value="Cleaning Supply">Cleaning Supply</option>
            <option value="Linen">Linen</option>
            <option value="Toiletry">Toiletry</option>
          </select>
          <input type="number" name="quantity" placeholder="Quantity" required>
          <input type="text" name="unit" placeholder="Unit" required>
          <input type="number" name="reorder_level" placeholder="Reorder Level" required>
          <button type="submit" class="btn add-btn"><i class="fas fa-plus"></i> Add Item</button>
        </form>
      </div>

      <!-- Supplies Table -->
      <div class="table-container">
        <table class="room-table inventory-table" id="inventoryTable">
          <thead>
            <tr>
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
              <tr>
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

  <script>
    const chartCtx = document.getElementById('supplyChart').getContext('2d');
    const supplyChart = new Chart(chartCtx, {
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
    options: { responsive: true, plugins: { legend: { position:'bottom', labels: { color: '#fff' } } } }
    });

    // ================= Add Item AJAX =================
    const addForm = document.getElementById('addItemForm');
    addForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(addForm);
      formData.append('add_item', true);
      formData.append('ajax', true);

      fetch('hp_inventory.php', { method:'POST', body:formData })
        .then(res => res.json())
        .then(data => {
          document.querySelector('.stat-card.total .stat-value').textContent = data.total;
          document.querySelector('.stat-card.cleaning .stat-value').textContent = data.cleaning;
          document.querySelector('.stat-card.linen .stat-value').textContent = data.linen;
          document.querySelector('.stat-card.toiletry .stat-value').textContent = data.toiletry;
          supplyChart.data.datasets[0].data = [data.cleaning,data.linen,data.toiletry];
          supplyChart.update();
          addForm.reset();
          location.reload();
        })
        .catch(err => console.error(err));
    });
  </script>
</body>
</html>
