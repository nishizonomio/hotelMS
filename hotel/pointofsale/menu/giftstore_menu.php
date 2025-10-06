<?php
require_once('../db.php');

$stmt = $conn->prepare("
    SELECT i.*, img.filename 
    FROM inventory i 
    LEFT JOIN item_images img ON i.item_id = img.item_id 
    WHERE i.category = 'Gift Store' 
    ORDER BY i.item_id ASC
");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gift Store Inventory</title>
<link rel="stylesheet" href="giftstore_menu.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="overlay">
    <div class="container">
        <header>
            <div class="header-text">
                <h1>Gift Store Inventory</h1>
                <p>All Available Items</p>
            </div>
            <a href="http://localhost/hotel/pointofsale/menu/menu_dashboard.php" class="close-btn">&times;</a>
        </header>

        <?php if(empty($items)): ?>
            <p class="empty-msg">No items found in the Gift Store.</p>
        <?php else: ?>
            <div class="items-grid">
                <?php foreach($items as $item): ?>
                    <div class="menu-item">
                        <div class="item-image">
                            <?php if($item['filename']): ?>
                                <img src="../uploads/<?php echo $item['filename']; ?>" alt="<?php echo $item['item_name']; ?>">
                            <?php else: ?>
                                <img src="../uploads/default.jpg" alt="No Image">
                            <?php endif; ?>
                        </div>
                        <div class="item-details">
                            <h3><?php echo $item['item_name']; ?></h3>
                            <p>Quantity: <?php echo $item['quantity_in_stock']; ?></p>
                            <p>Price: ₱<?php echo number_format($item['unit_price'],2); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
