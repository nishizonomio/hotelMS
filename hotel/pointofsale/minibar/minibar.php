<?php
require __DIR__ . '/db_connect.php'; 
session_start();

// Fetch minibar inventory items (only Mini Bar category)
$minibar_items = [];
try {
    $stmt = $pdo->query("
        SELECT item_id, item_name, quantity_in_stock, unit_price, unit 
        FROM inventory
        WHERE category = 'Mini Bar' 
        ORDER BY item_name ASC
    ");
    $minibar_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to fetch minibar items: " . $e->getMessage();
}

// Fetch staff list for accountability
$staff_list = [];
try {
    $stmt = $pdo->query("
        SELECT s.staff_id, s.first_name, s.last_name, p.position_name, d.department_name 
        FROM staff s 
        LEFT JOIN positions p ON s.position_id = p.position_id 
        LEFT JOIN departments d ON p.department_id = d.department_id 
        ORDER BY s.first_name ASC
    ");
    $staff_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($staff_data as $staff) {
        $staff_list[$staff['staff_id']] = [
            'first_name' => $staff['first_name'],
            'last_name' => $staff['last_name'],
            'position' => $staff['position_name'] ?: 'Unknown Position',
            'department' => $staff['department_name'] ?: 'Unknown Department'
        ];
    }
} catch (PDOException $e) {
    $error = "Failed to fetch staff list: " . $e->getMessage();
}

// Fetch guest list
$guest_list = [];
try {
    $stmt = $pdo->query("SELECT guest_id, first_name, last_name FROM guests ORDER BY first_name ASC");
    $guest_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($guest_data as $guest) {
        $guest_list[$guest['guest_id']] = $guest;
    }
} catch (PDOException $e) {
    $error = "Failed to fetch guest list: " . $e->getMessage();
}

// Fetch recent consumption records
$recent_consumption = [];
try {
    $stmt = $pdo->query("
        SELECT 
            mc.consumption_id,
            mc.room_number,
            mc.quantity,
            mc.total_cost,
            mc.consumed_at,
            mc.notes,
            mc.staff_id,
            g.first_name as guest_fname,
            g.last_name as guest_lname,
            i.item_name,
            i.unit_price,
            s.first_name as staff_fname,
            s.last_name as staff_lname,
            p.position_name,
            d.department_name
    FROM minibar_consumption mc
        JOIN guests g ON mc.guest_id = g.guest_id
    JOIN inventory i ON mc.item_id = i.item_id
    JOIN staff s ON mc.staff_id = s.staff_id
        LEFT JOIN positions p ON s.position_id = p.position_id
        LEFT JOIN departments d ON p.department_id = d.department_id
        ORDER BY mc.consumed_at DESC
        LIMIT 20
    ");
    $recent_consumption = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to fetch consumption records: " . $e->getMessage();
}

// Fetch refill logs
$refill_logs = [];
try {
    $stmt = $pdo->query("
        SELECT 
            mr.refill_id,
            mr.room_number,
            mr.quantity_added,
            mr.refilled_at,
            mr.expiry_date,
            mr.notes,
            mr.staff_id,
            i.item_name,
            s.first_name as staff_fname,
            s.last_name as staff_lname,
            p.position_name,
            d.department_name
        FROM minibar_refill_logs mr
        JOIN inventory i ON mr.item_id = i.item_id
        JOIN staff s ON mr.staff_id = s.staff_id
        LEFT JOIN positions p ON s.position_id = p.position_id
        LEFT JOIN departments d ON p.department_id = d.department_id
        ORDER BY mr.refilled_at DESC
        LIMIT 20
    ");
    $refill_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to fetch refill logs: " . $e->getMessage();
}

// Fetch active alerts
$active_alerts = [];
try {
    $stmt = $pdo->query("
        SELECT 
            ma.alert_id,
            ma.alert_type,
            ma.room_number,
            ma.current_quantity,
            ma.expiry_date,
            ma.alert_message,
            ma.created_at,
            i.item_name
        FROM minibar_inventory_alerts ma
        JOIN inventory i ON ma.item_id = i.item_id
        WHERE ma.is_resolved = 0
        ORDER BY ma.created_at DESC
    ");
    $active_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to fetch alerts: " . $e->getMessage();
}

// Session messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? $error ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini-bar Management - Hotel Turista</title>
  <link rel="stylesheet" href="minibar.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="overlay">
  <div class="container">
    <header>
            <h1><i class="fas fa-wine-bottle"></i> Mini-bar Management</h1>
            <p>Track consumption, manage inventory, and monitor refills</p>
            <a href="../pos.php"><button type="button">← Back to POS</button></a>
    </header>

    <?php if($error): ?>
            <div class="alert error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
        
    <?php if($success): ?>
            <div class="alert success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Alert Summary -->
        <?php if(!empty($active_alerts)): ?>
        <div class="alert warning">
            <i class="fas fa-bell"></i> <strong><?= count($active_alerts) ?> Active Alert(s)</strong>
            <ul style="margin-top: 10px; list-style-type: none;">
                <?php foreach(array_slice($active_alerts, 0, 3) as $alert): ?>
                    <li>• <?= htmlspecialchars($alert['alert_message']) ?></li>
                <?php endforeach; ?>
                <?php if(count($active_alerts) > 3): ?>
                    <li>• ... and <?= count($active_alerts) - 3 ?> more alerts</li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="showTab('consumption')">
                <i class="fas fa-shopping-cart"></i> Record Consumption
            </div>
            <div class="tab" onclick="showTab('refill')">
                <i class="fas fa-plus-circle"></i> Refill Items
            </div>
            <div class="tab" onclick="showTab('reports')">
                <i class="fas fa-chart-line"></i> Reports & Logs
            </div>
            <div class="tab" onclick="showTab('alerts')">
                <i class="fas fa-bell"></i> Alerts (<?= count($active_alerts) ?>)
                </div>
            </div>

        <!-- Consumption Tab -->
        <div id="consumption" class="tab-content active">
            <form id="consumptionForm" method="POST" action="minibar_process.php">
                <input type="hidden" name="action" value="record_consumption">
                
                <div class="form-container">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="guest_id"><i class="fas fa-user"></i> Guest ID:</label>
                            <input type="number" name="guest_id" id="guest_id" required placeholder="Enter Guest ID">
                            <div id="guestInfo" class="info-box">
                                <i class="fas fa-info-circle"></i> Guest Info: <span>Not selected</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="room_number"><i class="fas fa-door-open"></i> Room Number:</label>
                <input type="text" name="room_number" id="room_number" required>
            </div>

                        <div class="form-group">
                            <label for="staff_id"><i class="fas fa-id-badge"></i> Staff ID (Checker):</label>
                            <input type="text" name="staff_id" id="staff_id" required placeholder="Enter Staff ID (e.g., EMP123456)" pattern="[A-Za-z0-9]+" title="Enter a valid staff ID">
                            <div id="staffInfo" class="info-box">
                                <i class="fas fa-info-circle"></i> Staff Info: <span>Not selected</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes"><i class="fas fa-sticky-note"></i> Notes (Optional):</label>
                        <input type="text" name="notes" id="notes" placeholder="Additional notes about the consumption...">
                    </div>
                </div>

                <div class="items-section">
                    <h2><i class="fas fa-wine-glass"></i> Select Items Consumed</h2>
                    <div class="items-grid">
                        <?php foreach($minibar_items as $item): ?>
                            <div class="item-card">
                                <div class="icon">
                                    <?php
                                    // Icon mapping based on item type
                                    $icon = "fas fa-cube";
                                    $name_lower = strtolower($item['item_name']);
                                    if(strpos($name_lower, 'water') !== false) $icon = "fas fa-tint";
                                    elseif(strpos($name_lower, 'beer') !== false || strpos($name_lower, 'wine') !== false) $icon = "fas fa-wine-bottle";
                                    elseif(strpos($name_lower, 'coke') !== false || strpos($name_lower, 'sprite') !== false) $icon = "fas fa-glass";
                                    elseif(strpos($name_lower, 'chocolate') !== false || strpos($name_lower, 'cookies') !== false) $icon = "fas fa-cookie-bite";
                                    elseif(strpos($name_lower, 'nuts') !== false || strpos($name_lower, 'peanuts') !== false) $icon = "fas fa-seedling";
                                    echo "<i class='$icon'></i>";
                                    ?>
                                </div>
                                <div class="name"><?= htmlspecialchars($item['item_name']) ?></div>
                                <div class="price">₱<?= number_format($item['unit_price'], 2) ?></div>
                                <div class="stock <?= $item['quantity_in_stock'] <= 5 ? ($item['quantity_in_stock'] == 0 ? 'out' : 'low') : '' ?>">
                                    Stock: <?= $item['quantity_in_stock'] ?> <?= htmlspecialchars($item['unit']) ?>
                                </div>
                                <input type="number" 
                                       name="items[<?= $item['item_id'] ?>]" 
                                       min="0" 
                                       max="<?= $item['quantity_in_stock'] ?>" 
                                       value="0"
                                       <?= $item['quantity_in_stock'] == 0 ? 'disabled' : '' ?>>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit">
                        <i class="fas fa-save"></i> Record Consumption
                    </button>
            </div>
            </form>
        </div>

        <!-- Refill Tab -->
        <div id="refill" class="tab-content">
            <form id="refillForm" method="POST" action="minibar_process.php">
                <input type="hidden" name="action" value="refill_items">
                
                <div class="form-container">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="refill_room"><i class="fas fa-door-open"></i> Room Number:</label>
                            <input type="text" name="room_number" id="refill_room" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="refill_staff"><i class="fas fa-id-badge"></i> Staff ID (Housekeeper):</label>
                            <input type="text" name="staff_id" id="refill_staff" required placeholder="Enter Staff ID (e.g., EMP123456)" pattern="[A-Za-z0-9]+" title="Enter a valid staff ID">
                            <div id="refillStaffInfo" class="info-box">
                                <i class="fas fa-info-circle"></i> Staff Info: <span>Not selected</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expiry_date"><i class="fas fa-calendar-alt"></i> Expiry Date (Optional):</label>
                            <input type="date" name="expiry_date" id="expiry_date">
                        </div>
                        
                        <div class="form-group">
                            <label for="refill_notes"><i class="fas fa-sticky-note"></i> Notes (Optional):</label>
                            <input type="text" name="notes" id="refill_notes" placeholder="Refill notes...">
                        </div>
                    </div>
                </div>

                <div class="items-section">
                    <h2><i class="fas fa-plus-circle"></i> Items to Refill</h2>
                    <div class="items-grid">
                        <?php foreach($minibar_items as $item): ?>
                            <div class="item-card">
                                <div class="icon">
                                    <?php
                                    $icon = "fas fa-cube";
                                    $name_lower = strtolower($item['item_name']);
                                    if(strpos($name_lower, 'water') !== false) $icon = "fas fa-tint";
                                    elseif(strpos($name_lower, 'beer') !== false || strpos($name_lower, 'wine') !== false) $icon = "fas fa-wine-bottle";
                                    elseif(strpos($name_lower, 'coke') !== false || strpos($name_lower, 'sprite') !== false) $icon = "fas fa-glass";
                                    elseif(strpos($name_lower, 'chocolate') !== false || strpos($name_lower, 'cookies') !== false) $icon = "fas fa-cookie-bite";
                                    elseif(strpos($name_lower, 'nuts') !== false || strpos($name_lower, 'peanuts') !== false) $icon = "fas fa-seedling";
                                    echo "<i class='$icon'></i>";
                                    ?>
                                </div>
                                <div class="name"><?= htmlspecialchars($item['item_name']) ?></div>
                                <div class="price">₱<?= number_format($item['unit_price'], 2) ?></div>
                                <div class="stock <?= $item['quantity_in_stock'] <= 5 ? 'low' : '' ?>">
                                    Current Stock: <?= $item['quantity_in_stock'] ?> <?= htmlspecialchars($item['unit']) ?>
                                </div>
                                <input type="number" 
                                       name="refill_items[<?= $item['item_id'] ?>]" 
                                       min="0" 
                                       value="0"
                                       placeholder="Qty to add">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit">
                        <i class="fas fa-plus"></i> Record Refill
                    </button>
                </div>
            </form>
        </div>

        <!-- Reports Tab -->
        <div id="reports" class="tab-content">
            <!-- Recent Consumption -->
            <div class="table-container">
                <h2><i class="fas fa-shopping-cart"></i> Recent Consumption Records</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Checked By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($recent_consumption)): ?>
                            <?php foreach($recent_consumption as $record): ?>
                                <tr>
                                    <td><?= date('M j, Y g:i A', strtotime($record['consumed_at'])) ?></td>
                                    <td><?= htmlspecialchars($record['guest_fname'] . ' ' . $record['guest_lname']) ?></td>
                                    <td><?= htmlspecialchars($record['room_number']) ?></td>
                                    <td><?= htmlspecialchars($record['item_name']) ?></td>
                                    <td><?= $record['quantity'] ?></td>
                                    <td>₱<?= number_format($record['unit_price'], 2) ?></td>
                                    <td>₱<?= number_format($record['total_cost'], 2) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($record['staff_fname'] . ' ' . $record['staff_lname']) ?></strong><br>
                                        <small style="color: #bbb;">
                                            <?= htmlspecialchars(($record['position_name'] ?: 'Unknown Position') . ' - ' . ($record['department_name'] ?: 'Unknown Dept')) ?><br>
                                            ID: <?= htmlspecialchars($record['staff_id']) ?>
                                        </small>
                                    </td>
                                    <td><?= htmlspecialchars($record['notes'] ?: '-') ?></td>
                                </tr>
                <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align: center; color: #888;">No consumption records found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Refill Logs -->
            <div class="table-container">
                <h2><i class="fas fa-plus-circle"></i> Recent Refill Logs</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Room</th>
                            <th>Item</th>
                            <th>Qty Added</th>
                            <th>Refilled By</th>
                            <th>Expiry Date</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($refill_logs)): ?>
                            <?php foreach($refill_logs as $log): ?>
                                <tr>
                                    <td><?= date('M j, Y g:i A', strtotime($log['refilled_at'])) ?></td>
                                    <td><?= htmlspecialchars($log['room_number']) ?></td>
                                    <td><?= htmlspecialchars($log['item_name']) ?></td>
                                    <td><?= $log['quantity_added'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($log['staff_fname'] . ' ' . $log['staff_lname']) ?></strong><br>
                                        <small style="color: #bbb;">
                                            <?= htmlspecialchars(($log['position_name'] ?: 'Unknown Position') . ' - ' . ($log['department_name'] ?: 'Unknown Dept')) ?><br>
                                            ID: <?= htmlspecialchars($log['staff_id']) ?>
                                        </small>
                                    </td>
                                    <td><?= $log['expiry_date'] ? date('M j, Y', strtotime($log['expiry_date'])) : '-' ?></td>
                                    <td><?= htmlspecialchars($log['notes'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; color: #888;">No refill logs found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alerts Tab -->
        <div id="alerts" class="tab-content">
            <div class="table-container">
                <h2><i class="fas fa-bell"></i> Active Alerts</h2>
    <table>
        <thead>
            <tr>
                            <th>Alert Type</th>
                <th>Item</th>
                            <th>Room</th>
                            <th>Current Qty</th>
                            <th>Expiry Date</th>
                            <th>Message</th>
                            <th>Created</th>
                            <th>Action</th>
            </tr>
        </thead>
        <tbody>
                        <?php if(!empty($active_alerts)): ?>
                            <?php foreach($active_alerts as $alert): ?>
                                <tr>
                                    <td>
                                        <span class="status-badge <?= $alert['alert_type'] == 'low_stock' ? 'low' : ($alert['alert_type'] == 'out_of_stock' ? 'out' : 'expired') ?>">
                                            <?= ucwords(str_replace('_', ' ', $alert['alert_type'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($alert['item_name']) ?></td>
                                    <td><?= htmlspecialchars($alert['room_number'] ?: 'All') ?></td>
                                    <td><?= $alert['current_quantity'] ?? '-' ?></td>
                                    <td><?= $alert['expiry_date'] ? date('M j, Y', strtotime($alert['expiry_date'])) : '-' ?></td>
                                    <td><?= htmlspecialchars($alert['alert_message']) ?></td>
                                    <td><?= date('M j, Y', strtotime($alert['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" action="minibar_process.php" style="display: inline;">
                                            <input type="hidden" name="action" value="resolve_alert">
                                            <input type="hidden" name="alert_id" value="<?= $alert['alert_id'] ?>">
                                            <button type="submit" class="status-badge available" style="border: none; cursor: pointer;">
                                                <i class="fas fa-check"></i> Resolve
                                            </button>
                                        </form>
                                    </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                            <tr><td colspan="8" style="text-align: center; color: #888;">No active alerts</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
            </div>
        </div>

  </div>
</div>

<script>
// Data for JavaScript
const staffList = <?= json_encode($staff_list) ?>;
const guestList = <?= json_encode($guest_list) ?>;

// Debounce function to prevent excessive updates while typing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Tab functionality
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab content and activate tab
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}

// Update info boxes
function updateStaffInfo(staffId, targetElement) {
    const staff = staffList[staffId];
    const infoSpan = document.querySelector(targetElement + ' span');
    const infoBox = document.querySelector(targetElement);
    
    if (staff) {
        infoSpan.innerHTML = `<strong>${staff.first_name} ${staff.last_name}</strong><br>
                             <small>${staff.position} - ${staff.department}</small><br>
                             <small>Staff ID: ${staffId}</small>`;
        infoSpan.style.color = '#4caf50';
        infoBox.style.borderLeft = '3px solid #4caf50';
    } else if (staffId) {
        infoSpan.textContent = `Staff ID ${staffId} not found`;
        infoSpan.style.color = '#f44336';
        infoBox.style.borderLeft = '3px solid #f44336';
    } else {
        infoSpan.textContent = 'Not selected';
        infoSpan.style.color = '#ffd700';
        infoBox.style.borderLeft = '3px solid #ffd700';
    }
}

function updateGuestInfo(guestId) {
    const guest = guestList[guestId];
    const infoSpan = document.querySelector('#guestInfo span');
    const infoBox = document.querySelector('#guestInfo');
    
    if (guest) {
        infoSpan.innerHTML = `<strong>${guest.first_name} ${guest.last_name}</strong><br>
                             <small>Guest ID: ${guestId}</small>`;
        infoSpan.style.color = '#4caf50';
        infoBox.style.borderLeft = '3px solid #4caf50';
    } else if (guestId) {
        infoSpan.textContent = `Guest ID ${guestId} not found`;
        infoSpan.style.color = '#f44336';
        infoBox.style.borderLeft = '3px solid #f44336';
    } else {
        infoSpan.textContent = 'Not selected';
        infoSpan.style.color = '#ffd700';
        infoBox.style.borderLeft = '3px solid #ffd700';
    }
}

// Create debounced versions of update functions
const debouncedStaffUpdate = debounce(updateStaffInfo, 300);
const debouncedGuestUpdate = debounce(updateGuestInfo, 300);

// Event listeners for manual ID inputs
document.getElementById('staff_id').addEventListener('input', function() {
    debouncedStaffUpdate(this.value, '#staffInfo');
});

document.getElementById('refill_staff').addEventListener('input', function() {
    debouncedStaffUpdate(this.value, '#refillStaffInfo');
});

document.getElementById('guest_id').addEventListener('input', function() {
    debouncedGuestUpdate(this.value);
});

// Initialize info boxes
updateStaffInfo('', '#staffInfo');
updateStaffInfo('', '#refillStaffInfo');
updateGuestInfo('');

// Auto-fade alerts
window.addEventListener('load', function() {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 1s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 1000);
        }, 5000);
    });
});

// Form validation
document.getElementById('consumptionForm').addEventListener('submit', function(e) {
    const items = document.querySelectorAll('input[name^="items["]');
    let hasItems = false;
    
    items.forEach(input => {
        if (parseInt(input.value) > 0) {
            hasItems = true;
        }
    });
    
    if (!hasItems) {
        e.preventDefault();
        alert('Please select at least one item to record consumption.');
    }
});

document.getElementById('refillForm').addEventListener('submit', function(e) {
    const items = document.querySelectorAll('input[name^="refill_items["]');
    let hasItems = false;
    
    items.forEach(input => {
        if (parseInt(input.value) > 0) {
            hasItems = true;
        }
    });
    
    if (!hasItems) {
        e.preventDefault();
        alert('Please specify at least one item to refill.');
    }
});
</script>
</body>
</html>
