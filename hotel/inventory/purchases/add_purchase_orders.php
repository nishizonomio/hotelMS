<?php
require '../db.php';

$categories = [
    'Hotel Supplies','Cleaning & Sanitation','Utility Products','Office Supplies','Kitchen Equipment','Furniture & Fixtures','Toiletries','Laundry & Linen','Beverage','Meat','Seafood','Vegetable','Fruit','Dairy','Seasoning','Grain','Beverage','Spice','Others'
];

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_id = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
    $po_number = trim($_POST['po_number'] ?? '');
    $item_name = trim($_POST['item_name'] ?? '');
    $order_date = $_POST['order_date'] ?? '';
    $category = $_POST['category'] ?? '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : null;
    $total_amount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : null;
    $status = 'pending';

    if (!$supplier_id) $errors[] = "Please select a supplier.";
    if ($po_number === '') $errors[] = "PO Number is required.";
    if ($item_name === '') $errors[] = "Item Name is required.";
    if ($order_date === '') $errors[] = "Order Date is required.";
    if (!$quantity || $quantity <= 0) $errors[] = "Quantity must be greater than zero.";
    if (!$total_amount || $total_amount <= 0) $errors[] = "Total Amount must be greater than zero.";
    if ($category === '') $errors[] = "Please select a category.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchase_orders WHERE po_number = ?");
        $stmt->execute([$po_number]);
        if ($stmt->fetchColumn() > 0) $errors[] = "PO Number already exists.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO purchase_orders (supplier_id, po_number, item_name, order_date, status, total_amount, category, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$supplier_id,$po_number,$item_name,$order_date,$status,$total_amount,$category,$quantity]);
        header("Location: purchase_orders.php?success=1");
        exit;
    }
}

$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers WHERE is_active=1 ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Purchase Order</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="add_purchase_orders.css">
</head>
<body>
<div class="overlay">
<div class="container">
<header>
<h1>Create Purchase Order</h1>
<p>Fill out the details below to create a new purchase order.</p>
</header>

<?php if (!empty($errors)): ?>
<div class="error-messages">
<?php foreach ($errors as $error): ?>
<p><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>
<form method="POST" class="add-form">
<select name="supplier_id" required>
<option value="">-- Select Supplier --</option>
<?php foreach ($suppliers as $supplier): ?>
<option value="<?= $supplier['supplier_id'] ?>" <?= (isset($_POST['supplier_id']) && $_POST['supplier_id'] == $supplier['supplier_id']) ? 'selected' : '' ?>>
<?= htmlspecialchars($supplier['supplier_name']) ?>
</option>
<?php endforeach; ?>
</select>

<input type="text" name="po_number" placeholder="PO Number (e.g., PO-2024-001)" value="<?= htmlspecialchars($_POST['po_number'] ?? '') ?>" pattern="[A-Za-z0-9\-_]+" title="Use only letters, numbers, hyphens and underscores" required>

<input type="text" name="item_name" placeholder="Item Name" value="<?= htmlspecialchars($_POST['item_name'] ?? '') ?>" required>

<input type="date" name="order_date" value="<?= $_POST['order_date'] ?? date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>

<select name="category" required>
<option value="">-- Select Category --</option>
<?php foreach ($categories as $cat): ?>
<option value="<?= htmlspecialchars($cat) ?>" <?= (isset($_POST['category']) && $_POST['category'] === $cat) ? 'selected' : '' ?>>
<?= htmlspecialchars($cat) ?>
</option>
<?php endforeach; ?>
</select>

<input type="number" name="quantity" placeholder="Quantity" value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>" min="1" required>

<input type="number" step="0.01" name="total_amount" placeholder="Total Amount (₱)" value="<?= htmlspecialchars($_POST['total_amount'] ?? '') ?>" min="0.01" required>

<div class="form-buttons">
<button type="submit"><i class="fas fa-save"></i> Create Purchase Order</button>
<a href="purchase_orders.php"><button type="button" class="cancel-btn"><i class="fas fa-times"></i> Cancel</button></a>
</div>
</form>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
const poNumberField = document.querySelector('input[name="po_number"]');
const currentDate = new Date();
const year = currentDate.getFullYear();
const month = String(currentDate.getMonth() + 1).padStart(2, '0');
const day = String(currentDate.getDate()).padStart(2, '0');
const randomNum = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
if (poNumberField && !poNumberField.value) {
poNumberField.value = `PO-${year}${month}${day}-${randomNum}`;
}
});
</script>
</body>
</html>
