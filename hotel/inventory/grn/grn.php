<?php
require '../db.php';
session_start();

$pendingPOs = $pdo->query("
    SELECT po.po_id, po.po_number, po.item_name, po.category, po.total_amount, po.status, 
           s.supplier_name, po.order_date, po.quantity
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    WHERE po.status = 'pending'
    ORDER BY po.order_date DESC, po.po_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$managerPositions = [
    'Front Office Manager', 'Assistant Front Office Manager', 'F&B Manager', 
    'Restaurant Manager', 'Banquet Manager', 'Bar Manager', 'Head Waiter / Captain',
    'Executive Chef', 'Sous Chef', 'Chef de Partie', 'Laundry Supervisor', 
    'Security Manager', 'Sales Manager', 'Marketing Manager', 'HR Manager', 
    'Spa Manager', 'Events Manager', 'Chief Engineer'
];

$inClause = "'" . implode("','", $managerPositions) . "'";

$staffList = $pdo->query("
    SELECT CONCAT(first_name, ' ', last_name) AS full_name 
    FROM staff 
    WHERE position_name IN ($inClause)
    ORDER BY first_name, last_name
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $po_id = $_POST['po_id'] ?? '';
    $condition = $_POST['condition'] ?? '';
    $quantity_received = isset($_POST['quantity_received']) ? (int)$_POST['quantity_received'] : 0;
    $inspected_by = trim($_POST['inspected_by'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($po_id) || empty($condition) || $quantity_received <= 0 || $inspected_by === '') {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: grn.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE po_id = :po_id AND status = 'pending' LIMIT 1");
        $stmt->execute([':po_id' => $po_id]);
        $poData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$poData) throw new Exception("Purchase Order not found or already processed.");
        if ($quantity_received > $poData['quantity']) throw new Exception("Quantity received cannot exceed ordered quantity ({$poData['quantity']}).");

        $unitPrice = $poData['total_amount'] / $poData['quantity'];

        $existingItem = $pdo->prepare("SELECT item_id, quantity_in_stock FROM inventory WHERE item_name = :item_name AND category = :category LIMIT 1");
        $existingItem->execute([':item_name' => $poData['item_name'], ':category' => $poData['category']]);
        $existing = $existingItem->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $newQuantity = $existing['quantity_in_stock'] + $quantity_received;
            $updateStmt = $pdo->prepare("UPDATE inventory SET quantity_in_stock = :quantity, unit_price = :unit_price, inspected_by = :inspected_by WHERE item_id = :item_id");
            $updateStmt->execute([':quantity' => $newQuantity, ':unit_price' => $unitPrice, ':inspected_by' => $inspected_by, ':item_id' => $existing['item_id']]);
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO inventory (item_name, category, quantity_in_stock, unit_price, inspected_by, used_qty, wasted_qty) VALUES (:item_name, :category, :quantity_in_stock, :unit_price, :inspected_by, 0, 0)");
            $insertStmt->execute([':item_name' => $poData['item_name'], ':category' => $poData['category'], ':quantity_in_stock' => $quantity_received, ':unit_price' => $unitPrice, ':inspected_by' => $inspected_by]);
        }

        $newStatus = ($quantity_received >= $poData['quantity']) ? 'received' : 'partially_received';

        $updatePO = $pdo->prepare("UPDATE purchase_orders SET status = :status WHERE po_id = :po_id");
        $updatePO->execute([':status' => $newStatus, ':po_id' => $po_id]);

        $insertGRN = $pdo->prepare("INSERT INTO grn (po_id, po_number, item_name, category, quantity_received, inspected_by, condition_status, date_received, notes) VALUES (:po_id, :po_number, :item_name, :category, :quantity_received, :inspected_by, :condition_status, NOW(), :notes)");
        $insertGRN->execute([':po_id' => $po_id, ':po_number' => $poData['po_number'], ':item_name' => $poData['item_name'], ':category' => $poData['category'], ':quantity_received' => $quantity_received, ':inspected_by' => $inspected_by, ':condition_status' => $condition, ':notes' => $notes]);

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
<link rel="stylesheet" href="grn.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
      <h1><i class="fas fa-clipboard-check"></i> Goods Received Note</h1>
      <p>Inspect and receive items from pending purchase orders</p>
    </header>

    <?php if (!empty($_SESSION['success'])): ?>
      <div class="success-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
      <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (empty($pendingPOs)): ?>
      <div class="error-message">No pending purchase orders available. <a href="/hotel/inventory/purchases/add_purchase_orders.php">Create New PO</a></div>
    <?php else: ?>
    <form method="POST" class="grn-form">
        <label>Select Purchase Order</label>
        <select name="po_id" required id="po-select">
            <option value="">-- Select a Purchase Order --</option>
            <?php foreach($pendingPOs as $po): ?>
                <option value="<?= htmlspecialchars($po['po_id']) ?>" data-po-number="<?= htmlspecialchars($po['po_number']) ?>" data-supplier="<?= htmlspecialchars($po['supplier_name']) ?>" data-item="<?= htmlspecialchars($po['item_name']) ?>" data-category="<?= htmlspecialchars($po['category']) ?>" data-quantity="<?= htmlspecialchars($po['quantity']) ?>" data-amount="<?= htmlspecialchars(number_format($po['total_amount'], 2)) ?>" data-order-date="<?= htmlspecialchars($po['order_date']) ?>">
                    PO-<?= htmlspecialchars($po['po_number']) ?> | <?= htmlspecialchars($po['supplier_name']) ?> | <?= htmlspecialchars($po['item_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div id="po-details" style="display: none;"></div>

        <label>Condition Status</label>
        <select name="condition" required>
            <option value="">Select Condition</option>
            <option value="Good">Good</option>
            <option value="Damaged">Damaged</option>
            <option value="Expired">Expired</option>
        </select>

        <label>Quantity Received</label>
        <input type="number" name="quantity_received" min="1" required id="quantity_received">
        <div id="quantity_info" style="display: none;"><span id="quantity_text"></span></div>

        <label>Inspected By</label>
        <select name="inspected_by" required>
            <option value="">Select Inspector</option>
            <?php foreach ($staffList as $staff): ?>
                <option value="<?= htmlspecialchars($staff['full_name']) ?>"><?= htmlspecialchars($staff['full_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Notes (Optional)</label>
        <textarea name="notes" placeholder="Add any notes..."></textarea>

       <div class="form-buttons">
    <button type="submit">Process GRN</button>
    <a href="http://localhost/hotel/inventory/inventory.php"><button type="button">Back to Inventory</button></a>
</div>
    </form>
    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const select = document.getElementById('po-select');
  const details = document.getElementById('po-details');
  const quantityInput = document.getElementById('quantity_received');
  const quantityInfo = document.getElementById('quantity_info');
  const quantityText = document.getElementById('quantity_text');

  function esc(s) { return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  function updateDetails(){
    const opt = select.options[select.selectedIndex];
    if(!opt || !opt.value){ details.style.display='none'; quantityInfo.style.display='none'; quantityInput.value=''; return; }
    const orderedQty = parseInt(opt.dataset.quantity) || 0;
    details.innerHTML = `<div>PO Number: ${esc(opt.dataset.poNumber)}<br>Supplier: ${esc(opt.dataset.supplier)}<br>Item: ${esc(opt.dataset.item)}<br>Category: ${esc(opt.dataset.category)}<br>Ordered Qty: ${orderedQty}<br>Total Amount: ₱${esc(opt.dataset.amount)}</div>`;
    details.style.display='block';
    quantityInput.value = orderedQty;
    quantityInput.max = orderedQty;
    quantityText.textContent = `Maximum receivable quantity: ${orderedQty} units`;
    quantityInfo.style.display='block';
  }

  select.addEventListener('change', updateDetails);

  quantityInput.addEventListener('input', function(){
    const opt = select.options[select.selectedIndex];
    if(opt && opt.value){
      const maxQty = parseInt(opt.dataset.quantity) || 0;
      let val = parseInt(this.value) || 0;
      if(val > maxQty){ this.value = maxQty; quantityText.textContent=`Quantity adjusted to maximum: ${maxQty} units`; quantityText.style.color='#e74c3c'; } 
      else { quantityText.textContent=`Maximum receivable quantity: ${maxQty} units`; quantityText.style.color='#3498db'; }
    }
  });
});
</script>
</body>
</html>
