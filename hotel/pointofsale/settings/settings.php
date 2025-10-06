<?php
require_once('../db.php');

$items = $conn->query("
    SELECT i.*, im.filename 
    FROM inventory i
    LEFT JOIN item_images im ON i.item_id = im.item_id
    WHERE i.category IN ('Mini Bar','Lounge Bar','Gift Store')
    ORDER BY i.category, i.item_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $item_id = $_POST['item_id'];
    $item_name = $_POST['item_name'];
    $unit_price = $_POST['unit_price'];
    $category = $_POST['category'];

    $stmt = $conn->prepare("UPDATE inventory SET item_name=:name, unit_price=:price, category=:category WHERE item_id=:id");
    $stmt->execute([':name'=>$item_name, ':price'=>$unit_price, ':category'=>$category, ':id'=>$item_id]);

    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir(__DIR__.'/../uploads')) mkdir(__DIR__.'/../uploads', 0777, true);
        $img_name = time() . '_' . basename($_FILES['item_image']['name']);
        $img_path = __DIR__.'/../uploads/' . $img_name;
        move_uploaded_file($_FILES['item_image']['tmp_name'], $img_path);

        $stmt = $conn->prepare("
            INSERT INTO item_images (item_id, filename) 
            VALUES (:item_id, :filename)
            ON DUPLICATE KEY UPDATE filename=:filename
        ");
        $stmt->execute([':item_id'=>$item_id, ':filename'=>$img_name]);
    }

    header("Location: settings.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Settings - Hotel La Vista POS</title>
<link rel="stylesheet" href="settings.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<header>
    <h1>Hotel La Vista - POS - <span>Restaurant</span></h1>
    <a href="http://localhost/hotel/pointofsale/pos.php"><button type="button">Back</button></a>
</header>

<div class="header-right">
    <h2 class="header-right-title">Inventory Settings</h2>
    <input type="text" id="item_search" placeholder="Search item..." onkeyup="filterItems()">
</div>

<div class="settings-container" id="settings_container">
    <?php foreach ($items as $item): ?>
    <form class="settings-item" method="post" enctype="multipart/form-data">
        <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
        <div class="item-image">
            <?php 
                $image_path = '../uploads/' . ($item['filename'] ?? '');
                if(!empty($item['filename']) && file_exists(__DIR__.'/../uploads/'.$item['filename'])): 
            ?>
            <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
            <?php else: ?>
            <img src="https://via.placeholder.com/150?text=No+Image" alt="No Image">
            <?php endif; ?>
        </div>
        <input type="text" name="item_name" value="<?= htmlspecialchars($item['item_name']) ?>" placeholder="Item Name" required>
        <input type="number" name="unit_price" value="<?= $item['unit_price'] ?>" step="0.01" placeholder="Price" required>
        <select name="category">
            <option value="Mini Bar" <?= $item['category']=='Mini Bar'?'selected':'' ?>>Mini Bar</option>
            <option value="Lounge Bar" <?= $item['category']=='Lounge Bar'?'selected':'' ?>>Lounge Bar</option>
            <option value="Gift Store" <?= $item['category']=='Gift Store'?'selected':'' ?>>Gift Store</option>
        </select>
        <input type="file" name="item_image">
        <button type="submit" name="update_item">Update</button>
    </form>
    <?php endforeach; ?>
</div>

<script>
function filterItems() {
    let input = document.getElementById('item_search').value.toLowerCase();
    let items = document.querySelectorAll('.settings-item');
    items.forEach(function(item) {
        let name = item.querySelector('input[name="item_name"]').value.toLowerCase();
        let category = item.querySelector('select[name="category"]').value;
        if(name.includes(input) && ['Mini Bar','Lounge Bar','Gift Store'].includes(category)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>
</body>
</html>
