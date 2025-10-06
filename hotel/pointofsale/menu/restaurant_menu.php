<?php
require_once('../db.php');

$categories = ['Appetizer', 'Main Course', 'Dessert', 'Beverage'];
$menu_items = [];

foreach ($categories as $cat) {
    $stmt = $conn->prepare("SELECT * FROM recipes WHERE category = ? AND is_active = 1 ORDER BY display_order ASC");
    $stmt->execute([$cat]);
    $menu_items[$cat] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Restaurant Menu - Hotel La Vista</title>
    <link rel="stylesheet" href="restaurant_menu.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>
<div class="overlay">
    <div class="container">
        <header>
            <div class="header-text">
                <h1>Restaurant Menu</h1>
                <p>All Available Recipes</p>
            </div>
            <a href="http://localhost/hotel/pointofsale/menu/menu_dashboard.php" class="close-btn">&times;</a>
        </header>

        <?php foreach($menu_items as $cat => $items): ?>
            <section class="category-section">
                <h2><?= htmlspecialchars($cat) ?></h2>
                <?php if(empty($items)): ?>
                    <p class="empty-msg">No items found in this category.</p>
                <?php else: ?>
                    <div class="items-grid">
                        <?php foreach($items as $item): ?>
                            <div class="menu-item">
                                <div class="item-image">
                                    <?php
                                    $image_url = '/hotel/kitchen/uploads/recipes/' . ($item['image_path'] ?? '');
                                    ?>
                                    <img src="<?= htmlspecialchars($image_url) ?>" alt="<?= htmlspecialchars($item['recipe_name']) ?>" onerror="this.src='https://via.placeholder.com/150?text=No+Image'">
                                </div>
                                <h3><?= htmlspecialchars($item['recipe_name']) ?></h3>
                                <p>Price: ₱<?= number_format($item['price'],2) ?></p>
                                <p>Prep Time: <?= htmlspecialchars($item['preparation_time']) ?> mins</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
