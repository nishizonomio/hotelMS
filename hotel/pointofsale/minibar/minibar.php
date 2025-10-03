<?php
require __DIR__ . '/db_connect.php'; 
session_start();

// Define which inventory items belong to minibar
$minibar_inventory_ids = [
    'Beverages' => [
        'Bottled Water', 'Soft Drinks / Soda', 'Juice', 'Beer (Bottled)',
        'Wine (Red / White)', 'Whiskey / Vodka Shots', 'Sparkling Water'
    ],
    'Snacks' => [
        'Chips / Crisps', 'Nuts / Mixed Nuts', 'Chocolate Bar', 
        'Cookies / Biscuits', 'Candy / Mints'
    ]
];

// Fetch inventory items that match minibar list and merge duplicates
$items = [];
foreach($minibar_inventory_ids as $category => $names){
    $placeholders = implode(',', array_fill(0, count($names), '?'));
    $stmt = $pdo->prepare("
        SELECT 
            item_name, 
            SUM(quantity_in_stock) AS stock, 
            AVG(unit_price) AS price
        FROM inventory
        WHERE item_name IN ($placeholders)
        GROUP BY item_name
    ");
    $stmt->execute($names);
    $items[$category] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch staff list
$staffData = $pdo->query("SELECT * FROM staff")->fetchAll(PDO::FETCH_ASSOC);
$staffList = [];
foreach($staffData as $s){
    $staffList[$s['staff_id']] = $s;
}

// Fetch guest list
$guestData = $pdo->query("SELECT guest_id, first_name, last_name FROM guests")->fetchAll(PDO::FETCH_ASSOC);
$guestList = [];
foreach($guestData as $g){
    $guestList[$g['guest_id']] = $g;
}

// Fetch minibar consumption records
$consumptionRecords = $pdo->query("
    SELECT mc.checked_at, g.first_name, g.last_name, i.item_name, mc.quantity, mc.price, s.first_name AS staff_fname, s.last_name AS staff_lname
    FROM minibar_consumption mc
    JOIN inventory i ON mc.item_id = i.item_id
    JOIN guests g ON mc.guest_id = g.guest_id
    JOIN staff s ON mc.staff_id = s.staff_id
    ORDER BY mc.checked_at DESC
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
  <title>Mini-bar Tracking</title>
  <link rel="stylesheet" href="minibar.css">
</head>
<body>
<div class="overlay">
  <div class="container">

    <header>
      <h1>🥂 Mini-bar Tracking</h1>
      <p>Record consumed items & auto-bill guests</p>
      <!-- Back Button on top -->
      <a href="../pos.php"><button type="button" style="margin-top:10px; margin-bottom:20px;">Back</button></a>
    </header>

    <?php if($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if($success): ?>
      <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form id="minibarForm" method="POST" action="minibar_process.php">
        <div class="input-container flex-row">
            <div class="input-group">
                <label>Guest ID:</label>
                <input type="text" name="guest_id" id="guest_id" required>
                <div id="guestInfo" class="review-box">
                    Guest Info: <span style="color:#ffd700;">Not selected</span>
                </div>
            </div>

            <div class="input-group">
                <label>Room Number:</label>
                <input type="text" name="room_number" id="room_number" required>
            </div>

            <div class="input-group">
                <label>Staff ID (Checker):</label>
                <input type="text" name="staff_id" id="staff_id" required>
                <div id="staffInfoBox" class="review-box">
                    Staff Info: <span style="color:#ffd700;">Not selected</span>
                </div>
            </div>
        </div>

        <!-- Minibar Items by Category -->
        <?php foreach($items as $category => $catItems): ?>
            <h2><?= htmlspecialchars($category) ?></h2>
            <div class="grid">
                <?php foreach($catItems as $item): ?>
                    <div class="module">
                        <i>🍾</i>
                        <span><?= htmlspecialchars($item['item_name']) ?></span>
                        <p>₱<?= number_format($item['price'],2) ?></p>
                        <p>Stock: <?= $item['stock'] ?></p>
                        <input type="number" name="items[<?= htmlspecialchars($item['item_name']) ?>]" min="0" max="<?= $item['stock'] ?>" value="0">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="form-actions" style="margin-top: 20px;">
            <button type="submit">Save Consumption</button>
        </div>
    </form>

    <!-- Display minibar consumption table -->
    <h2 style="margin-top:40px;">📝 Mini-bar Consumption Records</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Guest</th>
                <th>Item</th>
                <th>Quantity</th>
                <th>Price per Unit</th>
                <th>Total</th>
                <th>Checked by</th>
            </tr>
        </thead>
        <tbody>
            <?php if($consumptionRecords): ?>
                <?php foreach($consumptionRecords as $rec): ?>
                    <tr>
                        <td><?= htmlspecialchars($rec['checked_at']) ?></td>
                        <td><?= htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']) ?></td>
                        <td><?= htmlspecialchars($rec['item_name']) ?></td>
                        <td><?= htmlspecialchars($rec['quantity']) ?></td>
                        <td>₱<?= number_format($rec['price'],2) ?></td>
                        <td>₱<?= number_format($rec['quantity'] * $rec['price'],2) ?></td>
                        <td><?= htmlspecialchars($rec['staff_fname'] . ' ' . $rec['staff_lname']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">No records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

  </div>
</div>

<script>
const staffList = <?php echo json_encode($staffList); ?>;
const guestList = <?php echo json_encode($guestList); ?>;

const staffInfoBox = document.getElementById("staffInfoBox").querySelector("span");
const guestInfoBox = document.getElementById("guestInfo").querySelector("span");

function updateInfo() {
    const staffId = document.getElementById('staff_id').value;
    staffInfoBox.textContent = staffId && staffList[staffId]
        ? `${staffList[staffId].first_name} ${staffList[staffId].last_name} (${staffList[staffId].position} - ${staffList[staffId].department})`
        : "Invalid staff or not selected";

    const guestId = document.getElementById('guest_id').value;
    guestInfoBox.textContent = guestId && guestList[guestId]
        ? `${guestList[guestId].first_name} ${guestList[guestId].last_name} (ID: ${guestId})`
        : guestId ? `Guest ID ${guestId} not found` : "Not selected";
}

["guest_id","staff_id"].forEach(id => document.getElementById(id).addEventListener("input", updateInfo));
updateInfo();

// Fade alerts
window.onload = function() {
    document.querySelectorAll('.alert').forEach(msg=>{
        setTimeout(()=>{
            msg.style.transition="opacity 1s ease";
            msg.style.opacity=0;
            setTimeout(()=>msg.remove(),1000);
        },3000);
    });
};
</script>
</body>
</html>
