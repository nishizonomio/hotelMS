<?php
require __DIR__ . '/../../inventory/db.php';
session_start();

// Load menu from kitchen recipes to keep in sync
$room_dining_menu = [];
try {
	$stmt = $pdo->query("SELECT id, recipe_name, category, price FROM recipes WHERE is_active = 1 ORDER BY display_order ASC, recipe_name ASC");
	$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($recipes as $r) {
		$category = $r['category'] ?: 'Main Course';
		if (!isset($room_dining_menu[$category])) {
			$room_dining_menu[$category] = [];
		}
		$room_dining_menu[$category][] = [
			'name' => $r['recipe_name'],
			'price' => (float)$r['price'],
			'category' => $category,
			'description' => ''
		];
	}
} catch (Exception $e) {
	$room_dining_menu = [];
}

// Fetch staff and guest data
$staffData = $pdo->query("SELECT * FROM staff")->fetchAll(PDO::FETCH_ASSOC);
$staffList = [];
foreach($staffData as $s){
    $staffList[$s['staff_id']] = $s;
}

$guestData = $pdo->query("SELECT guest_id, first_name, last_name FROM guests")->fetchAll(PDO::FETCH_ASSOC);
$guestList = [];
foreach($guestData as $g){
    $guestList[$g['guest_id']] = $g;
}

// Fetch room data
$roomData = $pdo->query("SELECT id AS room_id, room_number, type AS room_type FROM rooms WHERE status = 'occupied'")->fetchAll(PDO::FETCH_ASSOC);
$roomList = [];
foreach($roomData as $r){
    $roomList[$r['room_number']] = $r;
}

// Fetch recent orders
$recentOrders = $pdo->query("
    SELECT rdo.*, g.first_name, g.last_name, s.first_name as staff_fname, s.last_name as staff_lname
    FROM room_dining_orders rdo
    JOIN guests g ON rdo.guest_id = g.guest_id
    JOIN staff s ON rdo.staff_id = s.staff_id
    ORDER BY rdo.order_date DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Session messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In-Room Dining Management</title>
    <link rel="stylesheet" href="../index.css">
    <style>
        .container{max-width:1000px;margin:20px auto;background:#121212;color:#fff;padding:20px;border-radius:12px;border:1px solid rgba(255,255,255,0.12)}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .items{margin-top:12px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:8px;border-bottom:1px solid rgba(255,255,255,0.12)}
        input,select,textarea{width:100%;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,0.18);background:#1b1b1b;color:#fff}
        button{background:#1A237E;color:#fff;border:none;border-radius:8px;padding:10px 14px;cursor:pointer}
        button.secondary{background:#2e2e2e}
        .menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;margin-top:12px}
        .menu-item{background:#1b1b1b;padding:12px;border-radius:8px;border:1px solid rgba(255,255,255,0.12)}
        .menu-item h4{margin:0 0 8px 0;color:#fff}
        .menu-item .price{color:#4caf50;font-weight:bold;margin:4px 0}
        .menu-item .description{color:#ccc;font-size:0.9em;margin:4px 0}
        .item-controls{display:flex;gap:8px;align-items:center;margin-top:8px}
        .quantity-input{width:80px}
        .order-summary{background:#1b1b1b;padding:16px;border-radius:8px;margin-top:16px}
        .order-item{display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid rgba(255,255,255,0.1)}
        .total-section{text-align:right;margin-top:12px;font-size:1.2em;font-weight:bold}
        .form-actions{display:flex;gap:12px;margin-top:16px}
        .alert{padding:12px;border-radius:8px;margin:12px 0}
        .alert.error{background:#ff5252;color:#fff}
        .alert.success{background:#4caf50;color:#fff}
        .alert.warning{background:#ff9800;color:#fff}
        .status{padding:4px 8px;border-radius:4px;font-size:0.8em;font-weight:bold}
        .status-pending{background:#ff9800;color:#fff}
        .status-preparing{background:#2196f3;color:#fff}
        .status-out_for_delivery{background:#9c27b0;color:#fff}
        .status-delivered{background:#4caf50;color:#fff}
        .status-cancelled{background:#f44336;color:#fff}
        .btn-small{padding:4px 8px;font-size:0.8em}
    </style>
</head>
<body>
    <div class="overlay">
        <div class="container">
            <h2>🍽️ In-Room Dining Management</h2>

            <?php if($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Order Form -->
            <form id="roomDiningOrderForm" method="POST" action="room_dining_process.php">
                <div class="grid">
                    <label>Order ID<input type="text" value="Auto-generated on submit" disabled></label>
                    <label>Transaction ID<input type="text" value="Auto-generated on submit" disabled></label>
                    <label>Guest ID<input type="text" name="guest_id" id="guest_id" required></label>
                    <label>Room Number<input type="text" name="room_number" id="room_number" required list="roomList" placeholder="e.g., 101, 205">
                        <datalist id="roomList">
                            <?php foreach($roomList as $room): ?>
                                <option value="<?= $room['room_number'] ?>"><?= $room['room_type'] ?></option>
                            <?php endforeach; ?>
                        </datalist>
                    </label>
                    <label>Order Type
                        <select name="order_type" id="order_type">
                            <option value="dine_in">Dine-in</option>
                            <option value="takeaway">Takeaway</option>
                            <option value="room_service">Room Service</option>
                        </select>
                    </label>
                    <label>Order Date & Time<input type="text" id="orderDateTime" value="" disabled></label>
                    <label>Status
                        <select name="status">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In-Progress</option>
                            <option value="served">Served</option>
                            <option value="paid">Paid</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </label>
                    <label>Payment Method
                        <select name="payment_method">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="room_charge">Room Charge</option>
                            <option value="gcash">GCash</option>
                            <option value="other">Other</option>
                        </select>
                    </label>
                    <label>Staff ID<input type="text" name="staff_id" id="staff_id" required></label>
                    <label>Delivery Time<input type="datetime-local" name="delivery_time" id="delivery_time"></label>
                </div>
                <label>Special Instructions
                    <textarea name="special_instructions" id="special_instructions" rows="3" placeholder="Any special dietary requirements or cooking instructions..."></textarea>
                </label>

                <!-- Menu Items -->
                <div class="menu-section" id="menu">
                    <h2>🍽️ Room Service Menu</h2>
                    <?php foreach($room_dining_menu as $category => $items): ?>
                        <div class="menu-category">
                            <h3><?= htmlspecialchars($category) ?></h3>
                            <div class="menu-grid">
                                <?php foreach($items as $item): ?>
                                    <div class="menu-item">
                                        <div class="item-info">
                                            <h4><?= htmlspecialchars($item['name']) ?></h4>
                                            <p class="description"><?= htmlspecialchars($item['description']) ?></p>
                                            <p class="price">₱<?= number_format($item['price'], 2) ?></p>
                                        </div>
                                        <div class="item-controls">
                                            <input type="number" 
                                                   name="items[<?= htmlspecialchars($item['name']) ?>][quantity]" 
                                                   min="0" 
                                                   max="10" 
                                                   value="0" 
                                                   class="quantity-input"
                                                   data-price="<?= $item['price'] ?>"
                                                   data-category="<?= $item['category'] ?>">
                                            <input type="hidden" 
                                                   name="items[<?= htmlspecialchars($item['name']) ?>][price]" 
                                                   value="<?= $item['price'] ?>">
                                            <input type="hidden" 
                                                   name="items[<?= htmlspecialchars($item['name']) ?>][category]" 
                                                   value="<?= $item['category'] ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h3>💰 Order Summary</h3>
                    <div id="orderItems"></div>
                    <div class="grid">
                        <label>Subtotal (₱)<input type="text" id="subtotalDisplay" value="0.00" disabled></label>
                        <label>Tax (₱)<input type="text" id="taxDisplay" value="0.00" disabled></label>
                        <label>Total (₱)<input type="text" id="totalDisplay" value="0.00" disabled></label>
                    </div>
                </div>
                <input type="hidden" name="subtotal_amount" id="subtotalAmount" value="0.00">
                <input type="hidden" name="tax_amount" id="taxAmount" value="0.00">
                <input type="hidden" name="total_amount" id="totalAmount" value="0.00">
                <input type="hidden" id="taxRate" value="0.12">

                <div class="form-actions">
                    <button type="button" id="clearOrder">Clear Order</button>
                    <button type="submit" id="submitOrder">Place Order</button>
                    <a href="../pos.php"><button class="secondary" type="button">Back</button></a>
                </div>
            </form>

            <!-- Recent Orders -->
            <div class="recent-orders">
                <h2>📋 Recent Room Service Orders</h2>
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Type</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Delivery Time</th>
                                <th>Staff</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recentOrders): ?>
                                <?php foreach($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['order_id'] ?></td>
                                        <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                                        <td><?= htmlspecialchars($order['room_number']) ?></td>
                                        <td><?= ucfirst($order['order_type']) ?></td>
                                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="status status-<?= $order['status'] ?>">
                                                <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= $order['delivery_time'] ? date('M j, H:i', strtotime($order['delivery_time'])) : 'N/A' ?></td>
                                        <td><?= htmlspecialchars($order['staff_fname'] . ' ' . $order['staff_lname']) ?></td>
                                        <td><?= date('M j, Y H:i', strtotime($order['order_date'])) ?></td>
                                        <td>
                                            <button onclick="viewOrder(<?= $order['order_id'] ?>)" class="btn-small">View</button>
                                            <?php if($order['status'] == 'pending'): ?>
                                                <button onclick="updateStatus(<?= $order['order_id'] ?>, 'preparing')" class="btn-small">Preparing</button>
                                            <?php elseif($order['status'] == 'preparing'): ?>
                                                <button onclick="updateStatus(<?= $order['order_id'] ?>, 'out_for_delivery')" class="btn-small">Out for Delivery</button>
                                            <?php elseif($order['status'] == 'out_for_delivery'): ?>
                                                <button onclick="updateStatus(<?= $order['order_id'] ?>, 'delivered')" class="btn-small">Delivered</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="10">No orders found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const staffList = <?php echo json_encode($staffList); ?>;
        const guestList = <?php echo json_encode($guestList); ?>;
        const roomList = <?php echo json_encode($roomList); ?>;

        const staffInfoBox = document.getElementById("staffInfoBox").querySelector("span");
        const guestInfoBox = document.getElementById("guestInfo").querySelector("span");
        const roomInfoBox = document.getElementById("roomInfo").querySelector("span");

        function updateInfo() {
            const staffId = document.getElementById('staff_id').value;
            staffInfoBox.textContent = staffId && staffList[staffId]
                ? `${staffList[staffId].first_name} ${staffList[staffId].last_name} (${staffList[staffId].position})`
                : "Invalid staff or not selected";

            const guestId = document.getElementById('guest_id').value;
            guestInfoBox.textContent = guestId && guestList[guestId]
                ? `${guestList[guestId].first_name} ${guestList[guestId].last_name} (ID: ${guestId})`
                : guestId ? `Guest ID ${guestId} not found` : "Not selected";

            const roomNumber = document.getElementById('room_number').value;
            roomInfoBox.textContent = roomNumber && roomList[roomNumber]
                ? `Room ${roomNumber} - ${roomList[roomNumber].room_type}`
                : roomNumber ? `Room ${roomNumber} not found` : "Not selected";
        }

        function updateOrderSummary() {
            const orderItems = document.getElementById('orderItems');
            let subtotal = 0;
            let itemsHtml = '';

            document.querySelectorAll('.quantity-input').forEach(input => {
                const quantity = parseInt(input.value) || 0;
                if (quantity > 0) {
                    const price = parseFloat(input.dataset.price);
                    const itemTotal = quantity * price;
                    subtotal += itemTotal;
                    
                    const itemName = input.name.match(/items\[([^\]]+)\]/)[1];
                    itemsHtml += `
                        <div class="order-item">
                            <span>${itemName} x${quantity}</span>
                            <span>₱${itemTotal.toFixed(2)}</span>
                        </div>
                    `;
                }
            });

            orderItems.innerHTML = itemsHtml || '<p>No items selected</p>';
            
            // Calculate totals with tax
            subtotal = Math.max(0, subtotal);
            const taxRate = parseFloat(document.getElementById('taxRate').value || '0');
            const tax = +(subtotal * taxRate).toFixed(2);
            const total = +(subtotal + tax).toFixed(2);
            
            document.getElementById('subtotalDisplay').value = subtotal.toFixed(2);
            document.getElementById('taxDisplay').value = tax.toFixed(2);
            document.getElementById('totalDisplay').value = total.toFixed(2);
            document.getElementById('subtotalAmount').value = subtotal.toFixed(2);
            document.getElementById('taxAmount').value = tax.toFixed(2);
            document.getElementById('totalAmount').value = total.toFixed(2);
        }

        function clearOrder() {
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.value = 0;
            });
            updateOrderSummary();
        }

        function viewOrder(orderId) {
            alert(`Viewing order #${orderId}`);
        }

        function updateStatus(orderId, status) {
            if (confirm(`Update order #${orderId} status to ${status.replace('_', ' ')}?`)) {
                alert(`Order #${orderId} status updated to ${status.replace('_', ' ')}`);
                location.reload();
            }
        }

        // Set default delivery time to 30 minutes from now
        function setDefaultDeliveryTime() {
            const now = new Date();
            now.setMinutes(now.getMinutes() + 30);
            const timeString = now.toISOString().slice(0, 16);
            document.getElementById('delivery_time').value = timeString;
        }

        function setNow(){
            const now = new Date();
            const fmt = now.getFullYear()+"-"+String(now.getMonth()+1).padStart(2,'0')+"-"+String(now.getDate()).padStart(2,'0')+" "+String(now.getHours()).padStart(2,'0')+":"+String(now.getMinutes()).padStart(2,'0')+":"+String(now.getSeconds()).padStart(2,'0');
            document.getElementById('orderDateTime').value = fmt;
        }

        // Event listeners
        ["guest_id", "staff_id", "room_number"].forEach(id => 
            document.getElementById(id).addEventListener("input", updateInfo)
        );

        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('input', updateOrderSummary);
        });

        document.getElementById('clearOrder').addEventListener('click', clearOrder);

        // Initialize
        setDefaultDeliveryTime();
        setNow();
        setInterval(setNow, 1000);
        updateInfo();
        updateOrderSummary();

        // Fade alerts
        window.onload = function() {
            document.querySelectorAll('.alert').forEach(msg => {
                setTimeout(() => {
                    msg.style.transition = "opacity 1s ease";
                    msg.style.opacity = 0;
                    setTimeout(() => msg.remove(), 1000);
                }, 3000);
            });
        };
    </script>
</body>
</html>

