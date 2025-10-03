<?php
session_start();
require_once __DIR__ . '/../minibar/db_connect.php';

// Fetch gift shop items from inventory with available quantities like Lounge
$inventory = [];
try {
	$inventory = $pdo->query("
		SELECT i.item_id,
		       i.item_name,
		       i.category,
		       i.unit_price,
		       i.quantity_in_stock,
		       GREATEST(i.quantity_in_stock - COALESCE(p.pending_qty, 0), 0) AS available_quantity
		FROM inventory i
		LEFT JOIN (
		    SELECT goi.item_name, SUM(goi.quantity) AS pending_qty
		    FROM giftshop_orders go
		    JOIN giftshop_order_items goi ON goi.order_id = go.order_id
		    WHERE go.status = 'to_be_billed'
		    GROUP BY goi.item_name
		) p ON p.item_name = i.item_name
		WHERE i.category = 'Gift Shop'
		ORDER BY i.item_name
	")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
	$inventory = [];
}

// Fetch staff and guest data for info lookups
$staffData = $pdo->query("SELECT staff_id, first_name, last_name, position_name AS `position` FROM staff")->fetchAll(PDO::FETCH_ASSOC);
$staffList = [];
foreach($staffData as $s){
	$staffList[$s['staff_id']] = $s;
}

$guestData = $pdo->query("SELECT guest_id, first_name, last_name FROM guests")->fetchAll(PDO::FETCH_ASSOC);
$guestList = [];
foreach($guestData as $g){
	$guestList[$g['guest_id']] = $g;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Gift Shop POS</title>
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
	</style>
</head>
<body>
	<div class="overlay">
		<div class="container">
			<h2>Gift Shop Sales</h2>
			<?php if(isset($_SESSION['success'])){ echo '<p style="color:#8bc34a">'.$_SESSION['success'].'</p>'; unset($_SESSION['success']); } ?>
			<?php if(isset($_SESSION['error'])){ echo '<p style="color:#ff5252">'.$_SESSION['error'].'</p>'; unset($_SESSION['error']); } ?>
			<form method="POST" action="giftshop_process.php" id="orderForm">
				<div class="grid">
					<label>Order ID<input type="text" value="Auto-generated on submit" disabled></label>
					<label>Transaction ID<input type="text" value="Auto-generated on submit" disabled></label>
					<label>Guest ID<input type="text" name="guest_id" id="guest_id" required></label>
					<label>Staff ID<input type="text" name="staff_id" id="staff_id" required></label>
					<label>Order Date & Time<input type="text" id="orderDateTime" value="" disabled></label>
					<label>Status
						<select name="status">
							<option value="to_be_billed">To be billed</option>
							<option value="paid">Paid</option>
						</select>
					</label>
					<label>Payment Method
						<select name="payment_method">
							<option value="cash">Cash</option>
							<option value="card">Card</option>
							<option value="gcash">GCash</option>
							<option value="other">Other</option>
						</select>
					</label>
				</div>
                <div id="guestInfo" style="background:#1b1b1b;border:1px solid rgba(255,255,255,0.12);border-radius:8px;padding:10px;margin-top:6px"><strong>Guest Info:</strong> <span style="color:#FFD54F;font-weight:700">Not selected</span></div>
                <div id="staffInfoBox" style="background:#1b1b1b;border:1px solid rgba(255,255,255,0.12);border-radius:8px;padding:10px;margin-top:6px"><strong>Staff Info:</strong> <span style="color:#FFD54F;font-weight:700">Not selected</span></div>
				<label>Notes
					<textarea name="notes" rows="3" placeholder="Notes (optional)"></textarea>
				</label>

                <!-- Menu Items from Inventory (Gift Shop) -->
                <div class="menu-section">
                    <h3>🛍️ Gift Items</h3>
                    <div class="menu-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;margin-top:12px">
                        <?php foreach($inventory as $item): ?>
                            <div class="menu-item" style="background:#1b1b1b;padding:12px;border-radius:8px;border:1px solid rgba(255,255,255,0.12)">
                                <div class="item-info">
                                    <h4 style="margin:0 0 8px 0;color:#fff"><?= htmlspecialchars($item['item_name']) ?></h4>
                                    <p class="price" style="color:#4caf50;font-weight:bold;margin:4px 0">₱<?= number_format($item['unit_price'], 2) ?></p>
                                    <p class="stock" style="margin:4px 0">Stock: <span style="color:#8bc34a"><?= (int)($item['available_quantity'] ?? 0) ?></span></p>
                                </div>
                                <div class="item-controls" style="display:flex;gap:8px;align-items:center;margin-top:8px">
                                    <input type="number"
                                           name="items[<?= htmlspecialchars($item['item_name']) ?>][quantity]"
                                           min="0"
                                           max="<?= (int)($item['available_quantity'] ?? 0) ?>"
                                           value="0"
                                           class="quantity-input"
                                           data-price="<?= $item['unit_price'] ?>">
                                    <input type="hidden" name="items[<?= htmlspecialchars($item['item_name']) ?>][price]" value="<?= $item['unit_price'] ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="items order-summary" style="background:#1b1b1b;padding:16px;border-radius:8px;margin-top:16px">
                    <h3>💰 Order Summary</h3>
                    <div id="orderItems"></div>
                </div>

                <div class="grid">
					<label>Subtotal (₱)<input type="text" id="subtotalDisplay" value="0.00" disabled></label>
					<label>Tax (₱)<input type="text" id="taxDisplay" value="0.00" disabled></label>
					<label>Total (₱)<input type="text" id="totalDisplay" value="0.00" disabled></label>
				</div>
				<input type="hidden" name="subtotal_amount" id="subtotalAmount" value="0.00">
				<input type="hidden" name="tax_amount" id="taxAmount" value="0.00">
				<input type="hidden" name="total_amount" id="totalAmount" value="0.00">
				<input type="hidden" id="taxRate" value="0.12">
				<br>
				<button type="submit">Submit Sale</button>
				<a href="/hotel/pointofsale/pos.php"><button class="secondary" type="button">Back</button></a>
			</form>
		</div>
	</div>

	<!-- Recent Sales (Orders from giftshop_orders) -->
	<div class="container" style="max-width:1350px;margin:20px auto;background:#121212;color:#fff;padding:20px;border-radius:12px;border:1px solid rgba(255,255,255,0.12)">
		<h3>Recent Sales</h3>
		<div style="border:1px solid rgba(255,255,255,0.12);border-radius:8px;">
			<table style="width:100%;border-collapse:collapse;table-layout:auto;">
				<thead>
					<tr>
						<th>Order id</th>
						<th>Guest id</th>
						<th>Item</th>
						<th>Total</th>
						<th>Order date</th>
						<th>Status</th>
						<th>Staff id</th>
						<th>Notes</th>
						<th>Subtotal amount</th>
						<th>Tax amount</th>
						<th>Payment</th>
						<th>Transaction id</th>
					</tr>
				</thead>
				<tbody>
				<?php
				try {
					$stmt = $pdo->query("SELECT order_id, guest_id, item, total_amount, order_date, status, staff_id, notes, subtotal_amount, tax_amount, payment_method, transaction_id FROM giftshop_orders ORDER BY order_date DESC LIMIT 50");
					$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
					if (!$rows) {
						echo '<tr><td colspan="12" style="color:#bbb;">No recent sales.</td></tr>';
					} else {
						foreach ($rows as $r) {
							echo '<tr>'
								.'<td>#'.htmlspecialchars($r['order_id']).'</td>'
								.'<td>'.htmlspecialchars($r['guest_id']).'</td>'
								.'<td>'.htmlspecialchars($r['item'] ?? '').'</td>'
								.'<td>'.number_format((float)$r['total_amount'],2).'</td>'
								.'<td>'.htmlspecialchars($r['order_date']).'</td>'
								.'<td>'.htmlspecialchars($r['status']).'</td>'
								.'<td>'.htmlspecialchars($r['staff_id']).'</td>'
								.'<td>'.htmlspecialchars($r['notes'] ?? '').'</td>'
								.'<td>'.number_format((float)$r['subtotal_amount'],2).'</td>'
								.'<td>'.number_format((float)$r['tax_amount'],2).'</td>'
								.'<td>'.htmlspecialchars($r['payment_method']).'</td>'
								.'<td style="white-space:nowrap">'.htmlspecialchars($r['transaction_id'] ?? '').'</td>'
							.'</tr>';
						}
					}
				} catch (Exception $e) {
					echo '<tr><td colspan="12" style="color:#ff5252">Error loading sales</td></tr>';
				}
				?>
				</tbody>
			</table>
		</div>
	</div>

	<script>
	    (function(){
            // Lookup datasets for auto-filling Guest/Staff Info
            const staffList = <?php echo json_encode($staffList); ?>;
            const guestList = <?php echo json_encode($guestList); ?>;
        function computeTotals(){
			let subtotal = 0;
            const orderItems = document.getElementById('orderItems');
            let itemsHtml = '';
            document.querySelectorAll('.quantity-input').forEach(input => {
                const quantity = parseInt(input.value) || 0;
                if (quantity > 0) {
                    const price = parseFloat(input.dataset.price);
                    const itemTotal = quantity * price;
                    subtotal += itemTotal;
                    const itemName = input.name.match(/items\[([^\]]+)\]/)[1];
                    itemsHtml += `<div class="order-item" style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid rgba(255,255,255,0.1)"><span>${itemName} x${quantity}</span><span>₱${itemTotal.toFixed(2)}</span></div>`;
                }
            });
            if (orderItems) { orderItems.innerHTML = itemsHtml || '<p>No items selected</p>'; }
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

        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('input', computeTotals);
        });

        // Auto-display Guest/Staff information below inputs
        const guestInfoBox = document.getElementById('guestInfo') ? document.getElementById('guestInfo').querySelector('span') : null;
        const staffInfoBox = document.getElementById('staffInfoBox') ? document.getElementById('staffInfoBox').querySelector('span') : null;
        function updateInfo(){
            const guestEl = document.getElementById('guest_id');
            const staffEl = document.getElementById('staff_id');
            const guestId = guestEl ? guestEl.value.trim() : '';
            const staffId = staffEl ? staffEl.value.trim() : '';

            if (guestInfoBox){
                if (guestId && guestList[guestId]){
                    guestInfoBox.textContent = guestList[guestId].first_name + ' ' + guestList[guestId].last_name + ' (ID: ' + guestId + ')';
                } else {
                    guestInfoBox.textContent = guestId ? ('Guest ID ' + guestId + ' not found') : 'Not selected';
                }
            }

            if (staffInfoBox){
                if (staffId && staffList[staffId]){
                    const s = staffList[staffId];
                    staffInfoBox.textContent = s.first_name + ' ' + s.last_name + (s.position ? (' (' + s.position + ')') : '');
                } else {
                    staffInfoBox.textContent = staffId ? ('Staff ID ' + staffId + ' not found') : 'Not selected';
                }
            }
        }
        ['guest_id','staff_id'].forEach(id=>{ const el=document.getElementById(id); if(el){ el.addEventListener('input', updateInfo); }});

        function setNow(){
			const now = new Date();
			const fmt = now.getFullYear()+"-"+String(now.getMonth()+1).padStart(2,'0')+"-"+String(now.getDate()).padStart(2,'0')+" "+String(now.getHours()).padStart(2,'0')+":"+String(now.getMinutes()).padStart(2,'0')+":"+String(now.getSeconds()).padStart(2,'0');
			document.getElementById('orderDateTime').value = fmt;
		}
		setNow();
		setInterval(setNow, 1000);
        computeTotals();
        updateInfo();
	})();
	</script>
</body>
</html>


