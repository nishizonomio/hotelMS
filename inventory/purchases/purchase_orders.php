<?php
// Use consistent PDO connection
require '../db.php';

// Fetch orders with supplier names and condition status
$sql = "SELECT po.*, s.supplier_name 
        FROM purchase_orders po 
        LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id 
        ORDER BY po.order_date DESC";
$stmt = $pdo->query($sql);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Purchase Orders</title>
  <link rel="stylesheet" href="/hotel/inventory/inventory.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="overlay">
    <div class="container">
      <header>
        <h1>Purchase Orders</h1>
        <p>Manage and track purchase requests for your inventory items.</p>
      </header>

      <!-- Action Buttons Container -->
      <div class="action-buttons">
        <a href="add_purchase_orders.php" class="action-btn primary">
          <i class="fas fa-shopping-cart"></i>
          <span>Create Purchase Order</span>
        </a>
        <a href="/hotel/inventory/inventory.php" class="action-btn secondary">
          <i class="fas fa-arrow-left"></i>
          <span>Back to Inventory</span>
        </a>
      </div>

      <!-- Purchase Orders Table -->
      <div class="orders-table">
        <?php if (!empty($orders)): ?>
        <table>
          <thead>
            <tr>
              <th>PO #</th>
              <th>Supplier</th>
              <th>Item Name</th>
              <th>Category</th>
              <th>Quantity</th>
              <th>Total Amount</th>
              <th>Status</th>
              <th>Condition Status</th>
              <th>Order Date</th>
              <th>Received Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($orders as $row): ?>
              <tr>
                <td data-label="PO #"><?= htmlspecialchars($row['po_number']) ?></td>
                <td data-label="Supplier"><?= htmlspecialchars($row['supplier_name'] ?? 'Unknown') ?></td>
                <td data-label="Item Name"><?= htmlspecialchars($row['item_name']) ?></td>
                <td data-label="Category"><?= htmlspecialchars($row['category']) ?></td>
                <td data-label="Quantity"><?= htmlspecialchars($row['quantity']) ?></td>
                <td data-label="Total Amount">₱<?= number_format($row['total_amount'], 2) ?></td>
                <td data-label="Status">
                  <span class="status-badge <?= strtolower($row['status']) ?>">
                    <?= ucfirst(htmlspecialchars($row['status'])) ?>
                  </span>
                </td>
                <td data-label="Condition Status">
                  <span class="condition-badge <?= strtolower($row['condition_status'] ?? 'unknown') ?>">
                    <?= ucfirst(htmlspecialchars($row['condition_status'] ?? 'Unknown')) ?>
                  </span>
                </td>
                <td data-label="Order Date"><?= date('M j, Y', strtotime($row['order_date'])) ?></td>
                <td data-label="Received Date">
                  <?= $row['received_date'] ? date('M j, Y', strtotime($row['received_date'])) : '—' ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-file-invoice fa-3x"></i>
            <h3>No Purchase Orders Found</h3>
            <p>Start by creating your first purchase order to track inventory procurement.</p>
            <a href="add_purchase_orders.php" class="action-btn primary">
              <i class="fas fa-plus"></i>
              <span>Create First Order</span>
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <style>
    /* Action Buttons Styling */
    .action-buttons {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin: 30px 0;
      flex-wrap: wrap;
    }

    .action-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      min-width: 160px;
      justify-content: center;
    }

    .action-btn.primary {
      background: linear-gradient(135deg, #FF9800, #F57C00);
      color: #fff;
      box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
    }

    .action-btn.primary:hover {
      background: linear-gradient(135deg, #F57C00, #E65100);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
    }

    .action-btn.secondary {
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .action-btn.secondary:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-1px);
    }

    /* Table Styling */
    .orders-table { 
      margin: 30px auto; 
      overflow-x: auto; 
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    table { 
      width: 100%; 
      border-collapse: collapse; 
      background: rgba(255, 255, 255, 0.05); 
      border-radius: 12px; 
      overflow: hidden; 
      backdrop-filter: blur(10px);
    }

    table th, table td { 
      padding: 15px 12px; 
      text-align: center; 
      color: #fff; 
      border-bottom: 1px solid rgba(255, 255, 255, 0.1); 
    }

    table th { 
      background: rgba(0, 0, 0, 0.3); 
      font-weight: 600; 
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #FF9800;
    }

    table tbody tr:hover {
      background: rgba(255, 255, 255, 0.08);
      transition: background 0.3s ease;
    }

    /* Status Badge Styling */
    .status-badge, .condition-badge { 
      padding: 6px 12px; 
      border-radius: 20px; 
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Order Status Badges */
    .status-badge.pending { 
      background: #FF9800; 
      color: #fff; 
    }

    .status-badge.approved { 
      background: #4CAF50; 
      color: #fff; 
    }

    .status-badge.rejected { 
      background: #f44336; 
      color: #fff; 
    }

    .status-badge.received { 
      background: #2196F3; 
      color: #fff; 
    }

    /* Condition Status Badges */
    .condition-badge.good { 
      background: #4CAF50; 
      color: #fff; 
    }

    .condition-badge.fair { 
      background: #FF9800; 
      color: #fff; 
    }

    .condition-badge.poor { 
      background: #f44336; 
      color: #fff; 
    }

    .condition-badge.damaged { 
      background: #9C27B0; 
      color: #fff; 
    }

    .condition-badge.excellent { 
      background: #00BCD4; 
      color: #fff; 
    }

    .condition-badge.unknown { 
      background: #757575; 
      color: #fff; 
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #fff;
    }

    .empty-state i {
      opacity: 0.5;
      margin-bottom: 20px;
      color: #FF9800;
    }

    .empty-state h3 {
      font-size: 24px;
      margin-bottom: 10px;
      color: #fff;
    }

    .empty-state p {
      opacity: 0.8;
      margin-bottom: 30px;
      max-width: 400px;
      margin-left: auto;
      margin-right: auto;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .action-buttons {
        flex-direction: column;
        align-items: center;
      }

      .action-btn {
        width: 100%;
        max-width: 250px;
      }

      table, thead, tbody, th, td, tr { 
        display: block; 
      }

      thead tr { 
        position: absolute;
        top: -9999px;
        left: -9999px;
      }

      tr { 
        background: rgba(255, 255, 255, 0.05);
        margin-bottom: 15px; 
        border-radius: 12px; 
        padding: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      }

      td { 
        border: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        padding-left: 50% !important;
        text-align: right;
        padding-top: 10px;
        padding-bottom: 10px;
      }

      td:before { 
        position: absolute;
        top: 10px;
        left: 15px;
        width: 45%;
        padding-right: 10px;
        white-space: nowrap;
        content: attr(data-label) ":";
        color: #FF9800;
        font-weight: 600;
        text-align: left;
      }

      td:last-child {
        border-bottom: none;
      }
    }
  </style>
</body>
</html>