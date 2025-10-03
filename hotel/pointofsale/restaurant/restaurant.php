<?php
session_start();
require_once __DIR__ . '/../../inventory/db.php';
// Fetch active recipes for menu
$recipes = [];
try {
	$stmt = $pdo->query("SELECT id, recipe_name, category, price FROM recipes WHERE is_active = 1 ORDER BY display_order ASC, recipe_name ASC");
	$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
	$recipes = [];
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
	<title>Restaurant POS</title>
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
			<h2>Restaurant/Buffet Billing</h2>
			<?php if(isset($_SESSION['success'])){ echo '<p style="color:#8bc34a">'.$_SESSION['success'].'</p>'; unset($_SESSION['success']); } ?>
			<?php if(isset($_SESSION['error'])){ echo '<p style="color:#ff5252">'.$_SESSION['error'].'</p>'; unset($_SESSION['error']); } ?>
			<form method="POST" action="restaurant_process.php" id="orderForm">
				<div class="grid">
					<label>Order ID<input type="text" value="Auto-generated on submit" disabled></label>
					<label>Transaction ID<input type="text" value="Auto-generated on submit" disabled></label>
					<label>Guest ID<input type="text" name="guest_id" id="guest_id" required></label>
					<label>Staff ID<input type="text" name="staff_id" id="staff_id" required></label>
					<label>Table #<input type="text" name="table_number" placeholder="e.g., T1"></label>
					<label>Order Type
						<select name="order_type">
							<option value="dine_in">Dine-in</option>
							<option value="takeaway">Takeaway</option>
							<option value="buffet">Buffet</option>
						</select>
					</label>
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
				<!-- Info boxes -->
				<div class="grid">
					<div id="guestInfo" style="background:#1b1b1b;border:1px solid rgba(255,255,255,0.12);border-radius:8px;padding:10px">
						<strong>Guest Info:</strong> <span>Not selected</span>
					</div>
					<div id="staffInfoBox" style="background:#1b1b1b;border:1px solid rgba(255,255,255,0.12);border-radius:8px;padding:10px">
						<strong>Staff Info:</strong> <span>Not selected</span>
					</div>
				</div>
				<label>Notes
					<textarea name="notes" rows="3" placeholder="Special instructions"></textarea>
				</label>
				<div class="items">
					<h3>Menu</h3>
					<div class="grid">
						<label>Category Filter
							<select id="menuCategoryFilter">
								<option value="">All</option>
								<option value="Appetizer">Appetizer</option>
								<option value="Main Course">Main Course</option>
								<option value="Dessert">Dessert</option>
								<option value="Beverage">Beverage</option>
								<option value="Breakfast">Breakfast</option>
								<option value="Lunch">Lunch</option>
								<option value="Dinner">Dinner</option>
							</select>
						</label>
						<label>Search
							<input type="text" id="menuSearch" placeholder="Search menu...">
						</label>
					</div>
					<table id="menuTable">
						<thead>
							<tr><th style="width:45%">Item</th><th style="width:20%">Category</th><th style="width:15%">Price (₱)</th><th style="width:20%"></th></tr>
						</thead>
						<tbody>
						<?php foreach($recipes as $r): ?>
							<tr data-name="<?php echo htmlspecialchars($r['recipe_name']); ?>" data-category="<?php echo htmlspecialchars($r['category']); ?>" data-price="<?php echo number_format((float)$r['price'], 2, '.', ''); ?>">
								<td><?php echo htmlspecialchars($r['recipe_name']); ?></td>
								<td><?php echo htmlspecialchars($r['category']); ?></td>
								<td>₱<?php echo number_format((float)$r['price'], 2); ?></td>
								<td><button type="button" class="addFromMenu">Add</button></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div class="items">
					<h3>Items</h3>
					<table id="itemsTable">
						<thead>
							<tr><th>Name</th><th>Category</th><th>Qty</th><th>Price</th><th>Note</th><th></th></tr>
						</thead>
						<tbody></tbody>
					</table>
					<button type="button" id="addItem">+ Add Item</button>
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
				<button type="submit">Submit Order</button>
				<a href="/hotel/pointofsale/pos.php"><button class="secondary" type="button">Back</button></a>
			</form>
		</div>
	</div>

	<!-- Recent Sales -->
	<div class="container">
		<h3>Recent Sales</h3>
		<div style="overflow:auto;border:1px solid rgba(255,255,255,0.12);border-radius:8px;">
			<table>
				<thead>
					<tr>
						<th>Order ID</th>
						<th>Item</th>
						<th>Category</th>
						<th>Quantity</th>
						<th>Unit Price</th>
						<th>Total Price</th>
						<th>Special Instructions</th>
					</tr>
				</thead>
				<tbody>
				<?php
				try {
					$stmt = $pdo->query("SELECT order_id, item_name, category, quantity, unit_price, total_price, special_instructions FROM restaurant_order_items ORDER BY id DESC LIMIT 50");
					$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
					if (!$rows) {
						echo '<tr><td colspan="7" style="color:#bbb;">No recent sales.</td></tr>';
					} else {
						foreach ($rows as $r) {
							echo '<tr>'
								.'<td>#'.htmlspecialchars($r['order_id']).'</td>'
								.'<td>'.htmlspecialchars($r['item_name']).'</td>'
								.'<td>'.htmlspecialchars($r['category']).'</td>'
								.'<td>'.htmlspecialchars($r['quantity']).'</td>'
								.'<td>₱'.number_format((float)$r['unit_price'],2).'</td>'
								.'<td>₱'.number_format((float)$r['total_price'],2).'</td>'
								.'<td>'.htmlspecialchars($r['special_instructions'] ?? '').'</td>'
							.'</tr>';
						}
					}
				} catch (Exception $e) {
					echo '<tr><td colspan="7" style="color:#ff5252">Error loading sales</td></tr>';
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
	<script>
	(function(){
		// Lookup datasets
		const staffList = <?php echo json_encode($staffList); ?>;
		const guestList = <?php echo json_encode($guestList); ?>;

		const tbody = document.querySelector('#itemsTable tbody');
		const menuTbody = document.querySelector('#menuTable tbody');
		const menuFilter = document.getElementById('menuCategoryFilter');
		const menuSearch = document.getElementById('menuSearch');
		const guestInput = document.getElementById('guest_id');
		const staffInput = document.getElementById('staff_id');
		const guestInfoBox = document.getElementById('guestInfo').querySelector('span');
		const staffInfoBox = document.getElementById('staffInfoBox').querySelector('span');
		document.getElementById('addItem').addEventListener('click', addRow);
		function addRow(itemName = '', category = 'Main Course', price = 0){
			const tr = document.createElement('tr');
			tr.innerHTML = `
				<td><input name="items[${itemName || 'Item ' + (tbody.children.length+1)}][name]" placeholder="Item name" value="${itemName}"></td>
				<td>
					<select name="items[${itemName || 'Item ' + (tbody.children.length+1)}][category]">
						<option value="Main Course" ${category === 'Main Course' ? 'selected' : ''}>Main Course</option>
						<option value="Appetizers" ${category === 'Appetizers' ? 'selected' : ''}>Appetizers</option>
						<option value="Desserts" ${category === 'Desserts' ? 'selected' : ''}>Desserts</option>
						<option value="Beverages" ${category === 'Beverages' ? 'selected' : ''}>Beverages</option>

					</select>
				</td>
				<td><input type="number" name="items[${itemName || 'Item ' + (tbody.children.length+1)}][quantity]" value="1" min="1"></td>
				<td><input type="number" step="0.01" name="items[${itemName || 'Item ' + (tbody.children.length+1)}][price]" value="${price}"></td>
				<td><input name="items[${itemName || 'Item ' + (tbody.children.length+1)}][special_instructions]" placeholder="e.g., no spicy"></td>
				<td><button type="button" class="secondary">Remove</button></td>`;
			tr.querySelector('button').addEventListener('click', ()=>{ tr.remove(); computeTotals(); });
			tr.querySelectorAll('input, select').forEach(el=>{
				el.addEventListener('input', computeTotals);
				el.addEventListener('change', computeTotals);
			});
			tbody.appendChild(tr);
			computeTotals();
		}

		function mapCategoryForItems(category){
			switch(category){
				case 'Appetizer': return 'Appetizers';
				case 'Dessert': return 'Desserts';
				case 'Beverage': return 'Beverages';
				default: return category;
			}
		}

		function addFromMenu(name, category, price){
			addRow(name, mapCategoryForItems(category), parseFloat(price).toFixed(2));
		}



		if(menuTbody){
			menuTbody.addEventListener('click', function(e){
				if(e.target && e.target.classList.contains('addFromMenu')){
					const tr = e.target.closest('tr');
					addFromMenu(tr.dataset.name, tr.dataset.category, tr.dataset.price);
				}
			});
		}



		function applyMenuFilters(){
			const q = (menuSearch.value || '').toLowerCase();
			const cat = menuFilter.value || '';
			[...menuTbody.querySelectorAll('tr')].forEach(tr=>{
				const name = tr.dataset.name.toLowerCase();
				const category = tr.dataset.category;
				const matchesName = !q || name.includes(q);
				const matchesCat = !cat || category === cat;
				tr.style.display = (matchesName && matchesCat) ? '' : 'none';
			});
		}
		if(menuFilter) menuFilter.addEventListener('change', applyMenuFilters);
		if(menuSearch) menuSearch.addEventListener('input', applyMenuFilters);

		function updateInfo(){
			const guestId = (guestInput && guestInput.value) || '';
			if(guestId && guestList[guestId]){
				guestInfoBox.textContent = guestList[guestId].first_name + ' ' + guestList[guestId].last_name + ' (ID: ' + guestId + ')';
			}else{
				guestInfoBox.textContent = guestId ? ('Guest ID ' + guestId + ' not found') : 'Not selected';
			}

			const staffId = (staffInput && staffInput.value) || '';
			if(staffId && staffList[staffId]){
				const s = staffList[staffId];
				staffInfoBox.textContent = s.first_name + ' ' + s.last_name + (s.position ? (' (' + s.position + ')') : '');
			}else{
				staffInfoBox.textContent = staffId ? ('Staff ID ' + staffId + ' not found') : 'Not selected';
			}
		}

		updateInfo();
		['guest_id','staff_id'].forEach(id=>{
			const el = document.getElementById(id);
			if(el){ el.addEventListener('input', updateInfo); }
		});

		function computeTotals(){
			let subtotal = 0;
			[...tbody.querySelectorAll('tr')].forEach(tr=>{
				const qty = parseFloat(tr.querySelector('input[name$="[quantity]"]').value || '0');
				const price = parseFloat(tr.querySelector('input[name$="[price]"]').value || '0');
				if(qty > 0 && price >= 0){ subtotal += qty * price; }
			});
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

		function setNow(){
			const now = new Date();
			const fmt = now.getFullYear()+"-"+String(now.getMonth()+1).padStart(2,'0')+"-"+String(now.getDate()).padStart(2,'0')+" "+String(now.getHours()).padStart(2,'0')+":"+String(now.getMinutes()).padStart(2,'0')+":"+String(now.getSeconds()).padStart(2,'0');
			document.getElementById('orderDateTime').value = fmt;
		}
		setNow();
		setInterval(setNow, 1000);
	})();
	</script>
</body>
</html>
