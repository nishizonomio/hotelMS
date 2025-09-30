<?php
require '../db.php';

$categories = [
    'Hotel Supplies',
    'Foods & Beverages', 
    'Cleaning & Sanitation',
    'Utility Products',
    'Office Supplies',
    'Kitchen Equipment',
    'Furniture & Fixtures',
    'Laundry & Linen',
    'Others'
];

$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $supplier_id = filter_var($_POST['supplier_id'], FILTER_VALIDATE_INT);
    $po_number = trim($_POST['po_number']);
    $item_name = trim($_POST['item_name']);
    $order_date = $_POST['order_date'];
    $status = 'pending'; // Always set to pending
    $total_amount = filter_var($_POST['total_amount'], FILTER_VALIDATE_FLOAT);
    $category = $_POST['category'];
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);

    // Validation
    if (!$supplier_id) $errors[] = "Please select a valid supplier.";
    if (empty($po_number)) $errors[] = "PO Number is required.";
    if (empty($item_name)) $errors[] = "Item Name is required.";
    if (empty($order_date)) $errors[] = "Order Date is required.";
    if (!$total_amount || $total_amount <= 0) $errors[] = "Please enter a valid total amount.";
    if (!$quantity || $quantity <= 0) $errors[] = "Please enter a valid quantity.";
    if (empty($category)) $errors[] = "Please select a category.";

    // Check for duplicate PO number
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchase_orders WHERE po_number = ?");
        $stmt->execute([$po_number]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "PO Number already exists. Please use a unique number.";
        }
    }

    // If no errors, insert the record
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO purchase_orders 
                (supplier_id, po_number, item_name, order_date, status, total_amount, category, quantity) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $supplier_id, $po_number, $item_name, $order_date,
                $status, $total_amount, $category, $quantity
            ]);
            
            header("Location: purchase_orders.php?success=1");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database error: Unable to save purchase order.";
            error_log("PO Insert Error: " . $e->getMessage());
        }
    }
}

// Get suppliers for dropdown
try {
    $suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $suppliers = [];
    $errors[] = "Unable to load suppliers.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Purchase Order</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
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
        max-width: 800px;
        margin: 0 auto;
    }

    header {
        text-align: center;
        margin-bottom: 30px;
    }

    h1 {
        text-align: center;
        margin-bottom: 20px;
        font-size: 32px;
        font-weight: 600;
    }

    p {
        text-align: center;
        margin-bottom: 30px;
        font-size: 16px;
        color: #ccc;
    }

    .add-form {
        display: grid;
        gap: 15px;
        max-width: 600px;
        margin: 0 auto;
        padding: 30px;
        background: rgba(35, 39, 47, 0.95);
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }

    .add-form input,
    .add-form select,
    .add-form button {
        padding: 12px 16px;
        border-radius: 8px;
        border: 1px solid #444;
        font-size: 15px;
        background: rgba(255,255,255,0.1);
        color: #fff;
        transition: all 0.3s ease;
    }

    .add-form input::placeholder {
        color: #bbb;
    }

    .add-form input:focus,
    .add-form select:focus {
        outline: 2px solid #FF9800;
        box-shadow: 0 0 8px rgba(255, 152, 0, 0.3);
        background: rgba(255,255,255,0.15);
    }

    .add-form select {
        background: rgba(255,255,255,0.9);
        color: #333;
    }

    .add-form select option {
        background: #fff;
        color: #333;
    }

    .form-buttons {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-top: 20px;
    }

    .add-form button {
        background: #FF9800;
        color: #fff;
        cursor: pointer;
        border: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .add-form button:hover {
        background: #e67e22;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
    }

    .cancel-btn {
        background: #666 !important;
    }

    .cancel-btn:hover {
        background: #555 !important;
    }

    .form-buttons a {
        text-decoration: none;
    }

    .form-buttons button {
        width: 100%;
    }

    .error-messages {
        margin-bottom: 20px;
    }

    .error-messages p {
        color: #ff6b6b;
        background: rgba(255, 107, 107, 0.1);
        padding: 12px 16px;
        border-radius: 8px;
        margin: 8px 0;
        border-left: 4px solid #ff6b6b;
    }

    .add-form input[type="date"] {
        color-scheme: dark;
    }

    @media (max-width: 768px) {
        .overlay {
            padding: 20px 10px;
        }
        
        .add-form {
            padding: 20px;
        }
        
        .form-buttons {
            grid-template-columns: 1fr;
        }
        
        h1 {
            font-size: 28px;
        }
        
        .add-form input,
        .add-form select,
        .add-form button {
            font-size: 16px;
        }
    }
  </style>
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
            <p>
              <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="add-form">
        <select name="supplier_id" required>
          <option value="">-- Select Supplier --</option>
          <?php foreach ($suppliers as $supplier): ?>
            <option value="<?= $supplier['supplier_id'] ?>" 
                    <?= (isset($_POST['supplier_id']) && $_POST['supplier_id'] == $supplier['supplier_id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($supplier['supplier_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <input type="text" 
               name="po_number" 
               placeholder="PO Number (e.g., PO-2024-001)" 
               value="<?= htmlspecialchars($_POST['po_number'] ?? '') ?>"
               pattern="[A-Za-z0-9\-_]+"
               title="Use only letters, numbers, hyphens and underscores"
               required>

        <input type="text" 
               name="item_name" 
               placeholder="Item Name" 
               value="<?= htmlspecialchars($_POST['item_name'] ?? '') ?>"
               required>

        <input type="date" 
               name="order_date" 
               value="<?= $_POST['order_date'] ?? date('Y-m-d') ?>"
               max="<?= date('Y-m-d') ?>"
               required>

        <select name="category" required>
          <option value="">-- Select Category --</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" 
                    <?= (isset($_POST['category']) && $_POST['category'] === $cat) ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <input type="number" 
               name="quantity" 
               placeholder="Quantity" 
               value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>"
               min="1" 
               required>

        <input type="number" 
               step="0.01" 
               name="total_amount" 
               placeholder="Total Amount (₱)" 
               value="<?= htmlspecialchars($_POST['total_amount'] ?? '') ?>"
               min="0.01" 
               required>

        <div class="form-buttons">
          <button type="submit">
            <i class="fas fa-save"></i> Create Purchase Order
          </button>
          <a href="purchase_orders.php">
            <button type="button" class="cancel-btn">
              <i class="fas fa-times"></i> Cancel
            </button>
          </a>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Auto-generate PO number if empty
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