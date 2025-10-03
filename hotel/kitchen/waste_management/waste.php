<?php
require_once(__DIR__ . '/../utils/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item_id'])) {
    $item_id = (int)$_POST['update_item_id'];

    if (isset($_POST['update_waste_qty']) && isset($_POST['reason'])) {
        $new_waste_qty = (float)$_POST['update_waste_qty'];
        $reason = trim($_POST['reason']);

        $current = $pdo->prepare("SELECT wasted_qty, quantity_in_stock FROM inventory WHERE item_id = :id");
        $current->execute([':id'=>$item_id]);
        $row = $current->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $quantity_in_stock = (float)$row['quantity_in_stock'];
            $old_waste_qty = (float)$row['wasted_qty'];

            if ($new_waste_qty > $quantity_in_stock) $new_waste_qty = $quantity_in_stock;

            $new_stock = $quantity_in_stock - $new_waste_qty;
            $total_waste = $old_waste_qty + $new_waste_qty;

            $stmt = $pdo->prepare("UPDATE inventory SET wasted_qty = :waste_qty, quantity_in_stock = :new_stock, last_updated = NOW() WHERE item_id = :id");
            $stmt->execute([
                ':waste_qty' => $total_waste,
                ':new_stock' => $new_stock,
                ':id' => $item_id
            ]);

            $recentLog = $pdo->prepare("SELECT COUNT(*) FROM waste WHERE item_id = :item_id AND reason = :reason AND removed_at >= (NOW() - INTERVAL 1 MINUTE)");
            $recentLog->execute([':item_id'=>$item_id, ':reason'=>$reason]);
            $recentCount = (int)$recentLog->fetchColumn();

            if ($recentCount === 0) {
                $log = $pdo->prepare("
                    INSERT INTO waste 
                    (item_id, waste_qty, reason, removed_at, remark, footprint) 
                    VALUES (:item_id, :waste_qty, :reason, NOW(), :remark, NOW())
                ");
                $log->execute([
                    ':item_id' => $item_id,
                    ':waste_qty' => $new_waste_qty,
                    ':reason' => $reason,
                    ':remark' => null
                ]);
            }

            echo 'success';
        } else {
            echo 'failed';
        }
    } else {
        echo 'invalid';
    }
    exit;
}

$search_item = $_GET['item_name'] ?? '';
$search_category = $_GET['category'] ?? '';

$categoryStmt = $pdo->query("SELECT DISTINCT category FROM inventory WHERE category IS NOT NULL ORDER BY category ASC");
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

$conditions = [];
$params = [];
if ($search_item) { $conditions[] = "i.item_name LIKE :item_name"; $params[':item_name'] = "%$search_item%"; }
if ($search_category) { $conditions[] = "i.category = :category"; $params[':category'] = $search_category; }

$where = $conditions ? "WHERE ".implode(" AND ",$conditions) : "";

$query = "
SELECT 
    i.item_id,
    i.item_name,
    i.category AS category,
    i.quantity_in_stock,
    i.wasted_qty,
    w.reason,
    w.footprint
FROM inventory i
LEFT JOIN waste w ON w.item_id = i.item_id
$where
ORDER BY i.item_name ASC
";

$stmt = $pdo->prepare($query);
foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalQuery = "SELECT COALESCE(SUM(wasted_qty),0) AS total_wasted_qty FROM inventory";
$total = $pdo->query($totalQuery)->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Waste Management</title>
<link rel="stylesheet" href="waste.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
$(document).ready(function(){
    var modalEl = document.getElementById('wasteModal');
    var modal = new bootstrap.Modal(modalEl);

    $('.edit-btn').on('click', function(){
        let row = $(this).closest('tr');
        let itemId = row.data('id');
        let wasteQty = parseFloat(row.find('.waste-qty').text().trim());
        $('#wasteItemId').val(itemId);
        $('#wasteQty').val(wasteQty);
        $('#wasteReason').val('');
        modal.show();
    });

    $('#saveWaste').on('click', function(){
        let id = $('#wasteItemId').val();
        let qty = parseFloat($('#wasteQty').val());
        let reason = $('#wasteReason').val().trim();

        if(!id || isNaN(qty) || qty < 0 || !reason){
            alert('Please enter a valid quantity and select a reason.');
            return;
        }

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {update_item_id:id, update_waste_qty:qty, reason:reason},
            success: function(resp){
                if(resp.trim() === 'success'){
                    location.reload();
                } else {
                    alert('Failed to save waste.');
                }
            },
            error: function(xhr, status, error){
                alert('Error while saving waste.');
            }
        });
    });
});
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="overlay">
<div class="container">
<header>
<h1>Waste Management</h1>
<p>Total Wasted Quantity: <?= (int)$total['total_wasted_qty'] ?></p>
</header>
<div class="search-container">
    <a href="/hotel/kitchen/kitchen.php"><button type="button" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Kitchen</button></a>
    <form method="GET" class="search-form">
        <input type="text" name="item_name" placeholder="Search by item name" value="<?= htmlspecialchars($search_item) ?>">
        <select name="category">
            <option value="">All Categories</option>
            <?php foreach($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= ($search_category==$cat)?'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">🔍 Search</button>
    </form>
</div>

<table>
<thead>
<tr>
<th>Item Name</th>
<th>Category</th>
<th>Quantity in Stock</th>
<th>Total Wasted Quantity</th>
<th>Reason</th>
<th>Timestamp Footprint</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($items as $item):
$qty=(float)$item['quantity_in_stock'];
$wasted=(float)$item['wasted_qty'];
?>
<tr data-id="<?= $item['item_id'] ?>">
<td><?= htmlspecialchars($item['item_name']) ?></td>
<td><?= htmlspecialchars($item['category']??'Uncategorized') ?></td>
<td><?= $qty ?></td>
<td class="waste-qty"><?= $wasted ?></td>
<td><?= htmlspecialchars($item['reason'] ?? '') ?></td>
<td><?= htmlspecialchars($item['footprint'] ?? '') ?></td>
<td><button class="btn edit-btn" style="color:#fff;">Edit</button></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<div class="modal fade" id="wasteModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Update Waste</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<input type="hidden" id="wasteItemId">
<div class="mb-3">
<label for="wasteQty" class="form-label">Waste Quantity</label>
<input type="number" min="0" step="0.01" id="wasteQty" class="form-control">
</div>
<div class="mb-3">
<label for="wasteReason" class="form-label">Reason</label>
<select id="wasteReason" class="form-control">
<option value="">- Select Reason -</option>
<option value="Expired">Expired</option>
<option value="Damaged">Damaged</option>
<option value="Spoiled">Spoiled</option>
<option value="Other">Other</option>
</select>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
<button type="button" class="btn btn-primary" id="saveWaste">Save</button>
</div>
</div>
</div>
</div>
</body>
</html>
