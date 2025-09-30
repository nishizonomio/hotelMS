<?php
require '../db.php';
session_start();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get usage ID from URL
$usage_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$usage_id) {
    $_SESSION['error'] = "Invalid usage record ID.";
    header("Location: stock_usage.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_edit'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token.";
        header("Location: stock_usage.php");
        exit;
    }
    
    $new_used_qty = isset($_POST['used_qty']) ? (int)$_POST['used_qty'] : 0;
    $new_used_by = trim($_POST['used_by'] ?? '');
    $new_date_used = $_POST['date_used'] ?? '';
    
    // Get current usage record for processing
    $stmt = $pdo->prepare("
        SELECT 
            su.usage_id,
            su.item_id,
            su.used_qty,
            su.used_by,
            su.date_used,
            i.item_name,
            i.quantity_in_stock
        FROM stock_usage su
        JOIN inventory i ON su.item_id = i.item_id
        WHERE su.usage_id = :usage_id
    ");
    $stmt->execute([':usage_id' => $usage_id]);
    $current_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_record) {
        $_SESSION['error'] = "Usage record not found.";
        header("Location: stock_usage.php");
        exit;
    }
    
    // Validation
    if ($new_used_qty <= 0) {
        $_SESSION['error'] = "Quantity used must be greater than 0.";
    } elseif (empty($new_used_by)) {
        $_SESSION['error'] = "Please select the department that used the item.";
    } elseif (empty($new_date_used)) {
        $_SESSION['error'] = "Please enter the date used.";
    } elseif (strtotime($new_date_used) > time()) {
        $_SESSION['error'] = "Date used cannot be in the future.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $old_used_qty = (int)$current_record['used_qty'];
            $current_stock = (int)$current_record['quantity_in_stock'];
            
            // Calculate the difference in quantities
            $qty_difference = $new_used_qty - $old_used_qty;
            
            // Check if we have enough stock for the adjustment
            if ($qty_difference > $current_stock) {
                throw new Exception("Not enough stock available. Current stock: {$current_stock}, Additional needed: {$qty_difference}");
            }
            
            // Update inventory quantity (subtract the difference)
            $stmt = $pdo->prepare("
                UPDATE inventory 
                SET quantity_in_stock = quantity_in_stock - :qty_difference
                WHERE item_id = :item_id
            ");
            $result1 = $stmt->execute([
                ':qty_difference' => $qty_difference,
                ':item_id' => $current_record['item_id']
            ]);
            
            // Update usage record
            $stmt = $pdo->prepare("
                UPDATE stock_usage 
                SET used_qty = :used_qty, used_by = :used_by, date_used = :date_used
                WHERE usage_id = :usage_id
            ");
            $result2 = $stmt->execute([
                ':used_qty' => $new_used_qty,
                ':used_by' => $new_used_by,
                ':date_used' => $new_date_used,
                ':usage_id' => $usage_id
            ]);
            
            if ($result1 && $result2) {
                $pdo->commit();
                $_SESSION['success'] = "Usage record updated successfully!";
                header("Location: stock_usage.php");
                exit;
            } else {
                throw new Exception("Failed to update database records");
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error updating usage record: " . $e->getMessage();
        }
    }
}

// Get current usage record for display (after form processing)
try {
    $stmt = $pdo->prepare("
        SELECT 
            su.usage_id,
            su.item_id,
            su.used_qty,
            su.used_by,
            su.date_used,
            i.item_name,
            i.quantity_in_stock
        FROM stock_usage su
        JOIN inventory i ON su.item_id = i.item_id
        WHERE su.usage_id = :usage_id
    ");
    $stmt->execute([':usage_id' => $usage_id]);
    $usage_record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usage_record) {
        $_SESSION['error'] = "Usage record not found.";
        header("Location: stock_usage.php");
        exit;
    }

    // Define department categories
    $departments = [
        'POS' => 'Point of Sale',
        'Housekeeping' => 'Housekeeping',
        'Maintenance' => 'Maintenance'
    ];

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: stock_usage.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Stock Usage</title>
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
    max-width: 600px;
    margin: 0 auto;
}

.header {
    text-align: center;
    margin-bottom: 30px;
}

h1 {
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 10px;
    color: #FF9800;
}

.subtitle {
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

.edit-form {
    background: rgba(35, 39, 47, 0.95);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.item-info {
    background: rgba(255, 152, 0, 0.1);
    border: 1px solid #FF9800;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 25px;
    font-size: 14px;
    line-height: 1.6;
}

.item-info h3 {
    color: #FF9800;
    margin: 0 0 10px 0;
    font-size: 16px;
}

.form-group {
    margin-bottom: 20px;
}

.label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #FF9800;
    font-size: 14px;
}

.input {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255,255,255,0.1);
    border: 1px solid #444;
    border-radius: 8px;
    color: #fff;
    font-size: 15px;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.input:focus {
    outline: 2px solid #FF9800;
    background: rgba(255,255,255,0.15);
}

select.input {
    background: rgba(255,255,255,0.9);
    color: #333;
}

select.input option {
    background: #fff;
    color: #333;
}

.department-select {
    position: relative;
}

.department-select select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 5"><path fill="%23666" d="M2 0L0 2h4zm0 5L0 3h4z"/></svg>');
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 12px;
    padding-right: 40px;
}

.department-icons {
    display: flex;
    justify-content: space-around;
    margin-top: 10px;
    padding: 10px;
    background: rgba(0,0,0,0.2);
    border-radius: 6px;
}

.department-icon {
    display: flex;
    flex-direction: column;
    align-items: center;
    opacity: 0.5;
    transition: opacity 0.3s ease;
}

.department-icon.active {
    opacity: 1;
    color: #FF9800;
}

.department-icon i {
    font-size: 20px;
    margin-bottom: 5px;
}

.department-icon span {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stock-warning {
    background: rgba(52, 152, 219, 0.1);
    border: 1px solid #3498db;
    border-radius: 6px;
    padding: 10px;
    margin-top: 5px;
    font-size: 13px;
    color: #3498db;
}

.form-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 30px;
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
    text-decoration: none;
}

.btn.update {
    background: #2ecc71;
    color: #fff;
}

.btn.update:hover {
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
    transform: translateY(-1px);
}

.quantity-display {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    background: rgba(0, 0, 0, 0.2);
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.quantity-item {
    text-align: center;
}

.quantity-item .value {
    font-size: 18px;
    font-weight: 700;
    color: #FF9800;
}

.quantity-item .label-small {
    font-size: 12px;
    color: #ccc;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@media (max-width: 768px) {
    .overlay {
        padding: 20px 10px;
    }
    
    .edit-form {
        padding: 20px;
    }
    
    .form-buttons {
        grid-template-columns: 1fr;
    }
    
    h1 {
        font-size: 28px;
    }
    
    .quantity-display {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .department-icons {
        flex-wrap: wrap;
        gap: 10px;
    }
}
</style>
</head>
<body>
<div class="overlay">
  <div class="container">
    <header class="header">
      <h1><i class="fas fa-edit"></i> Edit Stock Usage</h1>
      <p class="subtitle">Modify usage record and adjust inventory accordingly</p>
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

    <form method="POST" class="edit-form">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="item-info">
            <h3><i class="fas fa-box"></i> Item Information</h3>
            <strong>Item:</strong> <?= htmlspecialchars($usage_record['item_name']) ?><br>
            <strong>Current Stock:</strong> <?= (int)$usage_record['quantity_in_stock'] ?> units
        </div>

        <div class="quantity-display">
            <div class="quantity-item">
                <div class="value"><?= (int)$usage_record['used_qty'] ?></div>
                <div class="label-small">Original Quantity</div>
            </div>
            <div class="quantity-item">
                <div class="value"><?= (int)$usage_record['quantity_in_stock'] ?></div>
                <div class="label-small">Available Stock</div>
            </div>
        </div>

        <div class="form-group">
            <label class="label"><i class="fas fa-calculator"></i> Quantity Used</label>
            <input type="number" name="used_qty" min="1" required class="input" 
                   value="<?= (int)$usage_record['used_qty'] ?>" id="used_qty">
            <div class="stock-warning" id="stock_warning" style="display: none;">
                <i class="fas fa-info-circle"></i> <span id="warning_text"></span>
            </div>
        </div>

        <div class="form-group">
            <label class="label"><i class="fas fa-building"></i> Department</label>
            <div class="department-select">
                <select name="used_by" required class="input" id="department_select">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $code => $name): ?>
                        <option value="<?= htmlspecialchars($code) ?>" 
                                <?= $code === $usage_record['used_by'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="department-icons">
                <div class="department-icon" data-department="POS">
                    <i class="fas fa-cash-register"></i>
                    <span>POS</span>
                </div>
                <div class="department-icon" data-department="Housekeeping">
                    <i class="fas fa-broom"></i>
                    <span>Housekeeping</span>
                </div>
                <div class="department-icon" data-department="Maintenance">
                    <i class="fas fa-tools"></i>
                    <span>Maintenance</span>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="label"><i class="fas fa-calendar"></i> Date Used</label>
            <input type="date" name="date_used" required class="input" 
                   value="<?= date('Y-m-d', strtotime($usage_record['date_used'])) ?>"
                   max="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-buttons">
            <button type="submit" name="submit_edit" class="btn update">
                <i class="fas fa-save"></i> Update Usage
            </button>
            <a href="stock_usage.php" class="btn cancel">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const usedQtyInput = document.getElementById('used_qty');
    const stockWarning = document.getElementById('stock_warning');
    const warningText = document.getElementById('warning_text');
    const departmentSelect = document.getElementById('department_select');
    const departmentIcons = document.querySelectorAll('.department-icon');
    
    const originalQty = <?= (int)$usage_record['used_qty'] ?>;
    const currentStock = <?= (int)$usage_record['quantity_in_stock'] ?>;
    
    // Update department icons based on selection
    function updateDepartmentIcons() {
        const selectedDept = departmentSelect.value;
        departmentIcons.forEach(icon => {
            if (icon.dataset.department === selectedDept) {
                icon.classList.add('active');
            } else {
                icon.classList.remove('active');
            }
        });
    }
    
    // Handle department icon clicks
    departmentIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const department = this.dataset.department;
            departmentSelect.value = department;
            updateDepartmentIcons();
        });
    });
    
    // Handle select change
    departmentSelect.addEventListener('change', updateDepartmentIcons);
    
    function updateWarning() {
        const newQty = parseInt(usedQtyInput.value) || 0;
        const difference = newQty - originalQty;
        
        if (difference > 0) {
            if (difference > currentStock) {
                warningText.textContent = `Not enough stock! You need ${difference} more units but only ${currentStock} available.`;
                warningText.style.color = '#e74c3c';
                stockWarning.style.borderColor = '#e74c3c';
                stockWarning.style.background = 'rgba(231, 76, 60, 0.1)';
            } else {
                warningText.textContent = `This will reduce current stock by ${difference} units.`;
                warningText.style.color = '#f39c12';
                stockWarning.style.borderColor = '#f39c12';
                stockWarning.style.background = 'rgba(243, 156, 18, 0.1)';
            }
            stockWarning.style.display = 'block';
        } else if (difference < 0) {
            warningText.textContent = `This will add ${Math.abs(difference)} units back to stock.`;
            warningText.style.color = '#2ecc71';
            stockWarning.style.borderColor = '#2ecc71';
            stockWarning.style.background = 'rgba(46, 204, 113, 0.1)';
            stockWarning.style.display = 'block';
        } else {
            stockWarning.style.display = 'none';
        }
    }
    
    usedQtyInput.addEventListener('input', updateWarning);
    
    // Initial setup
    updateWarning();
    updateDepartmentIcons();
    
    // Auto-hide messages after 5 seconds
    const messages = document.querySelectorAll('.success-message, .error-message');
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