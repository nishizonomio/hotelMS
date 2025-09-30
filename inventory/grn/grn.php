<?php
require '../db.php';
session_start();

// Get pending purchase orders
$pendingPOs = $pdo->query("
    SELECT po.po_id, po.po_number, po.item_name, po.category, po.total_amount, po.status, 
           s.supplier_name, po.order_date, po.quantity, po.unit_price
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    WHERE po.status = 'pending'
    ORDER BY po.order_date DESC, po.po_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get staff list for inspector dropdown
$staffList = $pdo->query("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM staff ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_COLUMN);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $po_id = $_POST['po_id'] ?? null;
    $condition = $_POST['condition'] ?? null;
    $quantity_received = isset($_POST['quantity_received']) ? (int) $_POST['quantity_received'] : 0;
    $inspected_by = $_POST['inspected_by'] ?? null;
    $notes = trim($_POST['notes'] ?? '');

    // Validate inputs
    if (!$po_id || !$condition || $quantity_received <= 0 || !$inspected_by) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: grn.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Get PO data
        $stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE po_id = :po_id AND status = 'pending' LIMIT 1");
        $stmt->execute([':po_id' => $po_id]);
        $poData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$poData) {
            throw new Exception("Purchase Order not found or already processed.");
        }

        // Validate quantity received doesn't exceed ordered quantity
        if ($quantity_received > $poData['quantity']) {
            throw new Exception("Quantity received cannot exceed ordered quantity ({$poData['quantity']}).");
        }

        // Calculate unit price (total amount / ordered quantity)
        $unitPrice = $poData['total_amount'] / $poData['quantity'];

        // Check if item already exists in inventory
        $existingItem = $pdo->prepare("
            SELECT item_id, quantity_in_stock FROM inventory 
            WHERE item_name = :item_name AND category = :category 
            LIMIT 1
        ");
        $existingItem->execute([
            ':item_name' => $poData['item_name'],
            ':category' => $poData['category']
        ]);
        $existing = $existingItem->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update existing inventory item
            $newQuantity = $existing['quantity_in_stock'] + $quantity_received;
            $pdo->prepare("
                UPDATE inventory 
                SET quantity_in_stock = :quantity, unit_price = :unit_price, inspected_by = :inspected_by
                WHERE item_id = :item_id
            ")->execute([
                ':quantity' => $newQuantity,
                ':unit_price' => $unitPrice,
                ':inspected_by' => $inspected_by,
                ':item_id' => $existing['item_id']
            ]);
        } else {
            // Insert new inventory item
            $pdo->prepare("
                INSERT INTO inventory (item_name, category, quantity_in_stock, unit_price, inspected_by, used_qty, wasted_qty) 
                VALUES (:item_name, :category, :quantity_in_stock, :unit_price, :inspected_by, 0, 0)
            ")->execute([
                ':item_name' => $poData['item_name'],
                ':category' => $poData['category'],
                ':quantity_in_stock' => $quantity_received,
                ':unit_price' => $unitPrice,
                ':inspected_by' => $inspected_by
            ]);
        }

        // Determine new PO status based on quantity received
        $newStatus = ($quantity_received >= $poData['quantity']) ? 'received' : 'partially_received';
        
        // Update purchase order status AND condition_status
        $pdo->prepare("
            UPDATE purchase_orders 
            SET status = :status, condition_status = :condition_status, received_date = NOW() 
            WHERE po_id = :po_id
        ")->execute([
            ':status' => $newStatus,
            ':condition_status' => $condition,
            ':po_id' => $po_id
        ]);

        // Insert GRN record
        $pdo->prepare("
            INSERT INTO grn (po_id, po_number, item_name, category, quantity_received, 
                           inspected_by, condition_status, date_received, notes)
            VALUES (:po_id, :po_number, :item_name, :category, :quantity_received, 
                   :inspected_by, :condition_status, NOW(), :notes)
        ")->execute([
            ':po_id' => $po_id,
            ':po_number' => $poData['po_number'],
            ':item_name' => $poData['item_name'],
            ':category' => $poData['category'],
            ':quantity_received' => $quantity_received,
            ':inspected_by' => $inspected_by,
            ':condition_status' => $condition,
            ':notes' => $notes
        ]);

        $pdo->commit();
        
        $statusMsg = ($newStatus === 'received') ? 'fully received' : 'partially received';
        $_SESSION['success'] = "GRN created successfully! Purchase Order #{$poData['po_number']} has been {$statusMsg} with condition: {$condition}.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error processing GRN: " . $e->getMessage();
    }

    header("Location: grn.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Goods Received Note (GRN)</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body, html {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: url('/hotel/homepage/hotel_room.jpg') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 0;
    color: #fff;
}

.overlay {
    background-color: rgba(0,0,0,0.88);
    min-height: 100vh;
    padding: 40px 20px;
    box-sizing: border-box;
}

.container {
    max-width: 900px;
    margin: 0 auto;
}

.grn-header {
    text-align: center;
    margin-bottom: 30px;
}

h1 {
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 10px;
}

p {
    font-size: 16px;
    color: #ccc;
    margin-bottom: 0;
}

.success-message, .error-message {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
}

.success-message {
    background: rgba(39, 174, 96, 0.2);
    color: #2ecc71;
    border-left: 4px solid #2ecc71;
}

.error-message {
    background: rgba(231, 76, 60, 0.2);
    color: #e74c3c;
    border-left: 4px solid #e74c3c;
}

.success-message.hide, .error-message.hide {
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.3s ease;
}

.grn-form {
    background: rgba(35, 39, 47, 0.95);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #FF9800;
    font-size: 14px;
}

.input, .po-dropdown {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255,255,255,0.1);
    border: 1px solid #444;
    border-radius: 8px;
    color: #fff;
    font-size: 15px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.input:focus, .po-dropdown:focus {
    outline: 2px solid #FF9800;
    background: rgba(255,255,255,0.15);
}

.po-dropdown {
    background: rgba(255,255,255,0.9);
    color: #333;
}

.po-dropdown option {
    background: #fff;
    color: #333;
    padding: 8px;
}

.po-details {
    background: rgba(255, 152, 0, 0.1);
    border: 1px solid #FF9800;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    font-size: 14px;
    line-height: 1.6;
}

.po-details[style*="display: none"] {
    display: none !important;
}

.po-details strong {
    color: #FF9800;
}

select.input {
    background: rgba(255,255,255,0.9);
    color: #333;
}

select.input option {
    background: #fff;
    color: #333;
}

textarea.input {
    min-height: 80px;
    resize: vertical;
}

.form-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 20px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn.submit {
    background: #2ecc71;
    color: #fff;
}

.btn.submit:hover {
    background: #27ae60;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
}

.btn.cancel {
    background: #6c757d;
    color: #fff;
}

.btn.cancel:hover {
    background: #5a6268;
}

.cancel-link {
    text-decoration: none;
}

.quantity-info {
    background: rgba(52, 152, 219, 0.1);
    border: 1px solid #3498db;
    border-radius: 6px;
    padding: 10px;
    margin-top: -15px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #3498db;
}

@media (max-width: 768px) {
    .overlay {
        padding: 20px 10px;
    }
    
    .grn-form {
        padding: 20px;
    }
    
    .form-buttons {
        grid-template-columns: 1fr;
    }
    
    h1 {
        font-size: 28px;
    }
}
</style>
</head>
<body>
<div class="overlay">
  <div class="container">
    <header class="grn-header">
      <h1><i class="fas fa-clipboard-check"></i> Goods Received Note</h1>
      <p>Inspect and receive items from pending purchase orders</p>
    </header>

    <?php if (!empty($_SESSION['success'])): ?>
      <div class="success-message">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($_SESSION['success']) ?>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
      <div class="error-message">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($_SESSION['error']) ?>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (empty($pendingPOs)): ?>
      <div class="error-message">
        <i class="fas fa-info-circle"></i>
        No pending purchase orders available for receiving.
        <a href="../purchase_orders/create_purchase_order.php" style="color: #FF9800; margin-left: 10px;">Create New PO</a>
      </div>
    <?php else: ?>

    <form method="POST" class="grn-form">
        <label class="label"><i class="fas fa-file-invoice"></i> Select Purchase Order</label>
        <select name="po_id" required class="po-dropdown" id="po-select">
            <option value="">-- Select a Purchase Order --</option>
            <?php foreach($pendingPOs as $po): ?>
                <option value="<?= (int)$po['po_id'] ?>"
                        data-po-number="<?= htmlspecialchars($po['po_number']) ?>"
                        data-supplier="<?= htmlspecialchars($po['supplier_name']) ?>"
                        data-item="<?= htmlspecialchars($po['item_name']) ?>"
                        data-category="<?= htmlspecialchars($po['category']) ?>"
                        data-quantity="<?= htmlspecialchars($po['quantity'] ?? 1) ?>"
                        data-amount="<?= htmlspecialchars(number_format($po['total_amount'], 2)) ?>"
                        data-order-date="<?= htmlspecialchars($po['order_date']) ?>">
                    PO-<?= htmlspecialchars($po['po_number']) ?> | <?= htmlspecialchars($po['supplier_name']) ?> | <?= htmlspecialchars($po['item_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="po-details" id="po-details" style="display: none;"></div>

        <label class="label"><i class="fas fa-clipboard-list"></i> Condition Status</label>
        <select name="condition" required class="input">
            <option value="">Select Condition</option>
            <option value="Good">✅ Good - Items in perfect condition</option>
            <option value="Damaged">⚠️ Damaged - Items have defects but usable</option>
            <option value="Expired">❌ Expired - Items past expiration date</option>
        </select>

        <label class="label"><i class="fas fa-boxes"></i> Quantity Received</label>
        <input type="number" name="quantity_received" min="1" required class="input" id="quantity_received" placeholder="Enter quantity received">
        <div class="quantity-info" id="quantity_info" style="display: none;">
          <i class="fas fa-info-circle"></i> <span id="quantity_text"></span>
        </div>

        <label class="label"><i class="fas fa-user-check"></i> Inspected By</label>
        <select name="inspected_by" required class="input" id="inspected_by">
            <option value="">Select Inspector</option>
            <?php foreach ($staffList as $staff): ?>
                <option value="<?= htmlspecialchars($staff) ?>"><?= htmlspecialchars($staff) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="label"><i class="fas fa-sticky-note"></i> Notes (Optional)</label>
        <textarea name="notes" class="input" placeholder="Add any additional notes about the received items..."></textarea>

        <div class="form-buttons">
            <button type="submit" class="btn submit">
                <i class="fas fa-check-circle"></i> Process GRN
            </button>
            <a href="../inventory.php" class="cancel-link">
                <button type="button" class="btn cancel">
                    <i class="fas fa-arrow-left"></i> Back to Inventory
                </button>
            </a>
        </div>
    </form>
    
    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // Auto-hide success/error messages
  const messages = document.querySelectorAll('.success-message, .error-message');
  messages.forEach(msg => {
    setTimeout(() => msg.classList.add('hide'), 4000);
    setTimeout(() => msg.remove(), 4500);
  });

  const select = document.getElementById('po-select');
  const details = document.getElementById('po-details');
  const quantityInput = document.getElementById('quantity_received');
  const quantityInfo = document.getElementById('quantity_info');
  const quantityText = document.getElementById('quantity_text');

  function updateDetails() {
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) { 
      details.style.display = 'none';
      quantityInfo.style.display = 'none';
      quantityInput.value = '';
      return; 
    }
    
    const orderedQty = parseInt(opt.dataset.quantity) || 0;
    
    details.innerHTML = `
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div>
          <strong>PO Number:</strong> ${esc(opt.dataset.poNumber)}<br>
          <strong>Supplier:</strong> ${esc(opt.dataset.supplier)}<br>
          <strong>Item:</strong> ${esc(opt.dataset.item)}
        </div>
        <div>
          <strong>Category:</strong> ${esc(opt.dataset.category)}<br>
          <strong>Ordered Qty:</strong> ${orderedQty}<br>
          <strong>Total Amount:</strong> ₱${esc(opt.dataset.amount)}
        </div>
      </div>
    `;
    
    details.style.display = 'block';
    quantityInput.value = orderedQty;
    quantityInput.max = orderedQty;
    
    quantityText.textContent = `Maximum receivable quantity: ${orderedQty} units`;
    quantityInfo.style.display = 'block';
  }

  function esc(s) { 
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); 
  }

  select.addEventListener('change', updateDetails);

  // Quantity validation
  quantityInput.addEventListener('input', function() {
    const opt = select.options[select.selectedIndex];
    if (opt && opt.value) {
      const maxQty = parseInt(opt.dataset.quantity) || 0;
      const currentVal = parseInt(this.value) || 0;
      
      if (currentVal > maxQty) {
        this.value = maxQty;
        quantityText.textContent = `Quantity adjusted to maximum: ${maxQty} units`;
        quantityText.style.color = '#e74c3c';
      } else {
        quantityText.textContent = `Maximum receivable quantity: ${maxQty} units`;
        quantityText.style.color = '#3498db';
      }
    }
  });
});
</script>
</body>
</html>