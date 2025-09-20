<?php
require_once __DIR__ . '/db_connector/config.php';
require_once __DIR__ . '/db/InventoryAdapter.php';

$inventoryAdapter = new InventoryAdapter($pdo);

// Get housekeeping-related items that need reordering
try {
    $lowStockItems = $inventoryAdapter->getItemsNeedingReorder();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Housekeeping Supplies Management</title>
    <link rel="stylesheet" href="css/supplies.css">
    <link rel="stylesheet" href="css/shared_modal.css">
    <link rel="stylesheet" href="css/notifications.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Housekeeping Supplies Management</h1>
            <p>Monitor and manage housekeeping supplies inventory</p>
        </header>

        <div class="actions">
            <button id="createReorderRequest" class="btn primary">Create Reorder Request</button>
            <button id="viewInventory" class="btn secondary">View Full Inventory</button>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="supplies-grid">
            <div class="card">
                <h2>Low Stock Items</h2>
                <?php if (!empty($lowStockItems)): ?>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Reorder Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockItems as $item): ?>
                                <tr class="<?php echo $item['current_stock'] === 0 ? 'out-of-stock' : 'low-stock'; ?>">
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['current_stock']); ?></td>
                                    <td><?php echo htmlspecialchars($item['reorder_level']); ?></td>
                                    <td>
                                        <button class="btn small" onclick="createReorderRequest(<?php echo $item['item_id']; ?>)">
                                            Request Reorder
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No items currently need reordering.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Reorder Request Modal -->
    <div id="reorderModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Create Reorder Request</h2>
            <form id="reorderForm">
                <input type="hidden" id="itemId" name="itemId">
                <div class="form-group">
                    <label for="quantity">Quantity to Order:</label>
                    <input type="number" id="quantity" name="quantity" required min="1">
                </div>
                <div class="form-group">
                    <label for="priority">Priority:</label>
                    <select id="priority" name="priority" required>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes"></textarea>
                </div>
                <button type="submit" class="btn primary">Submit Request</button>
            </form>
        </div>
    </div>

    <script>
    const modal = document.getElementById('reorderModal');
    const closeBtn = document.getElementsByClassName('close')[0];
    const reorderForm = document.getElementById('reorderForm');

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

    function createReorderRequest(itemId) {
        document.getElementById('itemId').value = itemId;
        modal.style.display = 'block';
    }

    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }

    function setFormLoading(form, isLoading) {
        if (isLoading) {
            form.classList.add('form-loading');
            form.querySelector('button[type="submit"]').disabled = true;
        } else {
            form.classList.remove('form-loading');
            form.querySelector('button[type="submit"]').disabled = false;
        }
    }

    reorderForm.onsubmit = async function(e) {
        e.preventDefault();
        const formData = new FormData(reorderForm);
        
        try {
            setFormLoading(reorderForm, true);
            showNotification('<div class="spinner"></div> Submitting reorder request...', 'info');
            
            const response = await fetch('save_reorder_request.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showNotification('✓ Reorder request submitted successfully', 'success');
                modal.style.display = 'none';
                location.reload();
            } else {
                showNotification('✕ ' + (data.error || 'Failed to submit reorder request'), 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('✕ An error occurred while submitting the request', 'error');
        } finally {
            setFormLoading(reorderForm, false);
        }
    };

    // Show initial errors if any
    <?php if (isset($error)): ?>
        showNotification('✕ <?php echo htmlspecialchars($error); ?>', 'error');
    <?php endif; ?>

    // Periodically check for inventory updates
    setInterval(async () => {
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
    }, 30000); // Check every 30 seconds
    </script>
</body>
</html>