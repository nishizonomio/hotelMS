<?php 
session_start();
require '../db.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle delete operation
if (isset($_GET['delete_id']) && isset($_GET['token'])) {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        $_SESSION['error'] = "Invalid security token";
        header("Location: stock_usage.php");
        exit;
    }
    
    $delete_id = (int)$_GET['delete_id'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT item_id, used_qty FROM stock_usage WHERE usage_id = :id");
        $stmt->execute([':id' => $delete_id]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($log) {
            // Restore inventory quantity
            $pdo->prepare("UPDATE inventory 
                           SET quantity_in_stock = quantity_in_stock + :used_qty 
                           WHERE item_id = :item_id")
                ->execute([':used_qty' => $log['used_qty'], ':item_id' => $log['item_id']]);
        }
        
        // Delete usage log
        $pdo->prepare("DELETE FROM stock_usage WHERE usage_id = :id")->execute([':id' => $delete_id]);
        
        $pdo->commit();
        $_SESSION['success'] = "Usage log deleted successfully and inventory restored.";
        
    } catch (Exception $e) {
        $pdo->rollback();
        $_SESSION['error'] = "Error deleting usage log: " . $e->getMessage();
    }
    
    header("Location: stock_usage.php");
    exit;
}

$search_item = $_POST['item_name'] ?? '';
$search_user = $_POST['used_by'] ?? '';

$where = [];
$params = [];
if ($search_item) { 
    $where[] = "i.item_name LIKE :item_name"; 
    $params[':item_name'] = "%$search_item%"; 
}
if ($search_user) { 
    $where[] = "su.used_by LIKE :used_by"; 
    $params[':used_by'] = "%$search_user%"; 
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$usage_logs = $pdo->prepare("
    SELECT 
        su.usage_id,
        i.item_name,
        su.used_qty,
        su.used_by,
        su.date_used
    FROM stock_usage su
    JOIN inventory i ON su.item_id = i.item_id
    $where_sql
    ORDER BY su.date_used DESC
");
$usage_logs->execute($params);
$usage_logs = $usage_logs->fetchAll(PDO::FETCH_ASSOC);

$recent_deductions = array_map(fn($log) => $log['item_name'].' (Used by: '.$log['used_by'].', Qty: '.$log['used_qty'].')', $usage_logs);
$recent_text = $recent_deductions ? implode(", ", array_slice($recent_deductions, 0, 5)) : "No items were recently deducted.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock Usage</title>
<link rel="stylesheet" href="stock_usage.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Additional styles for better button appearance */
.edit-btn, .delete-btn {
    display: inline-block;
    padding: 8px 12px;
    margin: 2px;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.edit-btn {
    background-color: #3498db;
    color: white;
}

.edit-btn:hover {
    background-color: #2980b9;
    transform: translateY(-1px);
}

.delete-btn {
    background-color: #e74c3c;
    color: white;
}

.delete-btn:hover {
    background-color: #c0392b;
    transform: translateY(-1px);
}

.message {
    padding: 12px 20px;
    margin: 15px 0;
    border-radius: 6px;
    font-weight: 500;
}

.success {
    background-color: rgba(39, 174, 96, 0.1);
    color: #27ae60;
    border: 1px solid #27ae60;
}

.error {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border: 1px solid #e74c3c;
}

.actions {
    white-space: nowrap;
}
</style>
</head>
<body>
<div class="overlay">
  <header>
    <h1>Stock Usage Management</h1>
    <p>Recently deducted items: <?= htmlspecialchars($recent_text) ?></p>
  </header>

  <?php
  // Display success message
  if (isset($_SESSION['success'])) {
      echo '<div class="message success">' . htmlspecialchars($_SESSION['success']) . '</div>';
      unset($_SESSION['success']);
  }
  
  // Display error message
  if (isset($_SESSION['error'])) {
      echo '<div class="message error">' . htmlspecialchars($_SESSION['error']) . '</div>';
      unset($_SESSION['error']);
  }
  ?>

  <div class="search-container">
    <form method="POST" class="search-form">
      <a href="../inventory.php">
        <button type="button"><i class="fas fa-arrow-left"></i> Back to Inventory</button>
      </a>
      <input type="text" name="item_name" placeholder="Search by Item" value="<?= htmlspecialchars($search_item) ?>">
      <input type="text" name="used_by" placeholder="Search by User" value="<?= htmlspecialchars($search_user) ?>">
      <button type="submit"><i class="fas fa-search"></i> Search</button>
      <a href="log_usage.php">
        <button type="button"><i class="fas fa-plus"></i> Log Usage</button>
      </a>
    </form>
  </div>

  <table>
    <thead>
      <tr>
        <th>Item Name</th>
        <th>Quantity Used</th>
        <th>Used By</th>
        <th>Date Used</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($usage_logs): ?>
        <?php foreach($usage_logs as $log): ?>
        <tr>
          <td><?= htmlspecialchars($log['item_name']) ?></td>
          <td><?= (int)$log['used_qty'] ?></td>
          <td><?= htmlspecialchars($log['used_by']) ?></td>
          <td><?= date('M j, Y g:i A', strtotime($log['date_used'])) ?></td>
          <td class="actions">
            <a href="edit_usage.php?id=<?= (int)$log['usage_id'] ?>" class="edit-btn">
              <i class="fas fa-edit"></i> Edit
            </a>
            <a href="stock_usage.php?delete_id=<?= (int)$log['usage_id'] ?>&token=<?= $_SESSION['csrf_token'] ?>" 
               class="delete-btn"
               onclick="return confirm('Are you sure you want to delete this usage log?\n\nThis will restore <?= (int)$log['used_qty'] ?> units of <?= htmlspecialchars($log['item_name']) ?> to inventory.');">
              <i class="fas fa-trash"></i> Delete
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="5" style="text-align: center; color: #666; font-style: italic;">
            <?php if ($search_item || $search_user): ?>
              No usage logs found matching your search criteria.
              <br><a href="stock_usage.php" style="color: #3498db;">Clear search to see all logs</a>
            <?php else: ?>
              No usage logs found. <a href="log_usage.php" style="color: #3498db;">Log your first usage</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
// Auto-hide messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.message');
    messages.forEach(function(message) {
        setTimeout(function() {
            message.style.opacity = '0';
            setTimeout(function() {
                message.remove();
            }, 300);
        }, 5000);
    });
});
</script>
</body>
</html>